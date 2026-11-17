<?php
// import_vouchers.php — target table: login.Voucher_codes
//
//  ID               int AUTO_INCREMENT (PK)
//  voucher_codes    varchar(20)  NOT NULL
//  Status           enum('active','inactive','used','')  NOT NULL
//  CreatedDate      datetime NOT NULL (no default)
//  UsedDate         datetime NULL
//  expires_at       datetime NULL
//  duration_minutes int NOT NULL DEFAULT 120
//  notes            varchar(255) NULL
//  created_at / updated_at  timestamp NULL   (always set to NOW())
//
// FILE FORMAT
//   TXT : one voucher code per line.
//   XLSX: Excel workbook, first sheet, same layout as the CSV below.
//   CSV : header row + columns in ANY order. Only voucher_codes is required:
//           voucher_codes, status, created_date, used_date,
//           expires_at, duration_minutes, notes
//   Anything left empty falls back to a default:
//           status -> active | created_date -> now | used_date -> NULL
//           expires_at -> NULL | duration_minutes -> 120 | notes -> NULL

header('Content-Type: application/json');
mysqli_report(MYSQLI_REPORT_OFF);
require_once __DIR__ . '/spreadsheet_reader.php';

function out($ok, $msg, $extra = []) {
    echo json_encode(array_merge(['success' => $ok, 'message' => $msg], $extra));
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') out(false, 'Invalid request method.');

$conn = new mysqli('localhost', 'root', '', 'login');
if ($conn->connect_error) out(false, 'Connection failed: ' . $conn->connect_error);
$conn->set_charset('utf8mb4');

/* ---------- File ---------- */
if (!isset($_FILES['voucher_file']) || $_FILES['voucher_file']['error'] !== UPLOAD_ERR_OK) {
    out(false, 'No file uploaded or upload error.');
}
$ext = strtolower(pathinfo($_FILES['voucher_file']['name'], PATHINFO_EXTENSION));
if (!in_array($ext, ['csv', 'txt', 'xlsx', 'xls'], true)) out(false, 'Only .csv, .txt and .xlsx files are allowed.');

try {
    $fileRows = sr_load_rows($_FILES['voucher_file']['tmp_name'], $ext);
} catch (RuntimeException $e) {
    out(false, $e->getMessage());
}

/* ---------- Header names we understand ---------- */
$alias = [
    'voucher_codes' => 'code',     'voucher_code' => 'code', 'vouchercode' => 'code',
    'voucher'       => 'code',     'code'         => 'code',
    'status'        => 'status',
    'created_date'  => 'created',  'createddate'  => 'created', 'created' => 'created',
    'used_date'     => 'used',     'useddate'     => 'used',
    'expires_at'    => 'expires',  'expiration_date' => 'expires', 'expiry' => 'expires',
    'expiry_date'   => 'expires',  'expiration'   => 'expires',
    'duration_minutes' => 'duration', 'duration'  => 'duration', 'minutes' => 'duration',
    'notes'         => 'notes',    'note'         => 'notes',
];
$ignoredHeaders = ['id', 'created_at', 'updated_at'];   // recognised but not imported
$allowedStatus  = ['active', 'inactive', 'used'];

$clean = function ($v) {
    $v = preg_replace('/^\xEF\xBB\xBF/', '', (string)$v);        // strip BOM
    return trim(preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $v));
};

// "" -> [null, true] | valid date -> ['Y-m-d H:i:s', true] | bad date -> [null, false]
$parseDate = function ($v) {
    if ($v === '') return [null, true];
    $t = strtotime($v);
    if ($t === false || $t < 0) return [null, false];
    return [date('Y-m-d H:i:s', $t), true];
};

/* ---------- Parse ---------- */
$col     = ['code' => 0];
$rowNum  = 0;
$rows    = [];      // code => [...fields]
$invalid = [];

foreach ($fileRows as $row) {
    $row = array_map($clean, $row);
    if (count(array_filter($row, fn($c) => $c !== '')) === 0) continue;   // blank line

    $rowNum++;

    // First non-empty row: is it a header?
    if ($rowNum === 1) {
        $map = [];
        $isHeader = false;
        foreach ($row as $i => $name) {
            $key = str_replace([' ', '-'], '_', strtolower($name));
            if (isset($alias[$key]))                     { $map[$alias[$key]] = $i; $isHeader = true; }
            elseif (in_array($key, $ignoredHeaders, true)) { $isHeader = true; }
        }
        if ($isHeader) {
            if (!isset($map['code'])) out(false, 'The header row must contain a "voucher_codes" column.');
            $col = $map;
            continue;
        }
        // no header -> first column is the code, everything else default
    }

    $get = fn($f) => (isset($col[$f]) && isset($row[$col[$f]])) ? $row[$col[$f]] : '';

    $code = $get('code');
    if ($code === '') continue;

    if (strlen($code) > 20 || !preg_match('/^[A-Za-z0-9._\-]+$/', $code)) {
        $invalid[] = "$code (bad code)";
        continue;
    }

    // status
    $status = strtolower($get('status'));
    if ($status === '') $status = 'active';
    if (!in_array($status, $allowedStatus, true)) {
        $invalid[] = "$code (status must be active, inactive or used)";
        continue;
    }

    // dates
    [$created, $okC] = $parseDate($get('created'));
    [$used,    $okU] = $parseDate($get('used'));
    [$expires, $okE] = $parseDate($get('expires'));
    if (!$okC) { $invalid[] = "$code (bad created_date)"; continue; }
    if (!$okU) { $invalid[] = "$code (bad used_date)";    continue; }
    if (!$okE) { $invalid[] = "$code (bad expires_at)";   continue; }

    // duration (blank -> database default of 120)
    $durRaw   = $get('duration');
    $duration = null;
    if ($durRaw !== '') {
        if (!ctype_digit($durRaw) || (int)$durRaw < 1 || (int)$durRaw > 100000) {
            $invalid[] = "$code (bad duration_minutes)";
            continue;
        }
        $duration = (int)$durRaw;
    }

    // notes
    $notes = $get('notes');
    $notes = ($notes === '') ? null : mb_substr($notes, 0, 255);

    $rows[$code] = [
        'status'   => $status,
        'created'  => $created,
        'used'     => $used,
        'expires'  => $expires,
        'duration' => $duration,
        'notes'    => $notes,
    ];   // same code twice in the file -> the last one wins
}
if (!$rows && !$invalid) out(false, 'No voucher codes found in the file.');
if (!$rows) out(false, 'No valid voucher codes found.', ['invalid' => array_slice($invalid, 0, 15)]);

/* ---------- Insert ---------- */
$check = $conn->prepare('SELECT 1 FROM Voucher_codes WHERE voucher_codes = ? LIMIT 1');

// COALESCE(?, NOW()) -> CreatedDate falls back to "now" when the file leaves it empty
$insDefault = $conn->prepare(
    "INSERT INTO Voucher_codes
        (voucher_codes, Status, CreatedDate, UsedDate, expires_at, notes, created_at, updated_at)
     VALUES (?, ?, COALESCE(?, NOW()), ?, ?, ?, NOW(), NOW())"
);
$insCustom = $conn->prepare(
    "INSERT INTO Voucher_codes
        (voucher_codes, Status, CreatedDate, UsedDate, expires_at, notes, duration_minutes, created_at, updated_at)
     VALUES (?, ?, COALESCE(?, NOW()), ?, ?, ?, ?, NOW(), NOW())"
);
if (!$check || !$insDefault || !$insCustom) out(false, 'DB prepare error: ' . $conn->error);

$inserted   = 0;
$duplicates = [];

foreach ($rows as $code => $r) {
    $code = (string)$code;

    $check->bind_param('s', $code);
    $check->execute();
    $check->store_result();
    if ($check->num_rows > 0) { $duplicates[] = $code; continue; }

    $status = $r['status'];  $created = $r['created'];  $used  = $r['used'];
    $expires = $r['expires']; $notes  = $r['notes'];    $dur   = $r['duration'];

    if ($dur === null) {
        $insDefault->bind_param('ssssss', $code, $status, $created, $used, $expires, $notes);
        $ok = $insDefault->execute();
        $err = $insDefault->error;
    } else {
        $insCustom->bind_param('ssssssi', $code, $status, $created, $used, $expires, $notes, $dur);
        $ok = $insCustom->execute();
        $err = $insCustom->error;
    }

    if ($ok) $inserted++;
    else     $invalid[] = "$code (database error: $err)";
}

$check->close();
$insDefault->close();
$insCustom->close();
$conn->close();

$skipped = count($duplicates) + count($invalid);
out(true, "$inserted voucher(s) imported. $skipped skipped.", [
    'inserted'   => $inserted,
    'skipped'    => $skipped,
    'duplicates' => array_slice($duplicates, 0, 15),
    'invalid'    => array_slice($invalid, 0, 15),
]);
