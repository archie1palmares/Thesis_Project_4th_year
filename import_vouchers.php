<?php
// import_vouchers.php
// Supports: .csv and .txt ONLY — no Composer, no PhpSpreadsheet needed
// Table: voucher_codes (ID, voucher_codes, Status, CreatedDate, UsedDate, created_at, updated_at)

header('Content-Type: application/json');

// ── DB Connection ──
$conn = new mysqli("localhost", "root", "", "login");
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Connection failed: ' . $conn->connect_error]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit();
}

if (!isset($_FILES['voucher_file']) || $_FILES['voucher_file']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'No file uploaded or upload error.']);
    exit();
}

$file    = $_FILES['voucher_file'];
$ext     = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$tmpPath = $file['tmp_name'];

if (!in_array($ext, ['csv', 'txt'])) {
    echo json_encode(['success' => false, 'message' => 'Unsupported file. Only .csv and .txt are allowed.']);
    exit();
}

// ── Header values to skip ──
$skipHeaders = ['voucher_codes', 'voucher', 'code', 'id', 'voucher code', 'vouchercode'];

// ══════════════════════════════════════════
//  PARSE
// ══════════════════════════════════════════
$vouchers = [];

if ($ext === 'txt') {
    $lines = file($tmpPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (!$lines) {
        echo json_encode(['success' => false, 'message' => 'File is empty or unreadable.']);
        exit();
    }
    foreach ($lines as $line) {
        $line = trim($line);
        if (in_array(strtolower($line), $skipHeaders)) continue;
        foreach (explode(',', $line) as $part) {
            $code = trim(preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $part));
            if ($code !== '') $vouchers[] = $code;
        }
    }

} elseif ($ext === 'csv') {
    $handle = fopen($tmpPath, 'r');
    if (!$handle) {
        echo json_encode(['success' => false, 'message' => 'Could not open CSV file.']);
        exit();
    }
    $firstRow = true;
    while (($row = fgetcsv($handle, 1000, ',')) !== false) {
        if ($firstRow) {
            $firstRow  = false;
            $firstCell = strtolower(trim($row[0] ?? ''));
            if (in_array($firstCell, $skipHeaders)) continue; // skip header row
        }
        foreach ($row as $cell) {
            $code = trim(preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $cell));
            if ($code !== '') $vouchers[] = $code;
        }
    }
    fclose($handle);
}

// ── Remove duplicates within the file ──
$vouchers = array_values(array_unique($vouchers));

if (count($vouchers) === 0) {
    echo json_encode(['success' => false, 'message' => 'No voucher codes found in the file.']);
    exit();
}

// ══════════════════════════════════════════
//  INSERT INTO MySQL
// ══════════════════════════════════════════
$stmt = $conn->prepare("
    INSERT IGNORE INTO voucher_codes 
        (voucher_codes, Status, CreatedDate, UsedDate, created_at, updated_at)
    VALUES 
        (?, 'active', CURDATE(), NULL, NOW(), NOW())
");

if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'DB prepare error: ' . $conn->error]);
    exit();
}

$inserted = 0;
$skipped  = 0;
$invalid  = [];

foreach ($vouchers as $code) {
    // Only reject if empty or over 100 chars — accept all other characters
    if (strlen($code) < 1 || strlen($code) > 100) {
        $invalid[] = $code;
        $skipped++;
        continue;
    }

    $stmt->bind_param("s", $code);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        $inserted++;
    } else {
        $skipped++; // Already exists in DB (INSERT IGNORE)
    }
}

$stmt->close();
$conn->close();

echo json_encode([
    'success'  => true,
    'inserted' => $inserted,
    'skipped'  => $skipped,
    'total'    => count($vouchers),
    'invalid'  => array_slice($invalid, 0, 10),
    'message'  => "$inserted voucher(s) imported successfully. $skipped skipped (duplicates or invalid)."
]);
?>