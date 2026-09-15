<?php
header('Content-Type: application/json');

$conn = new mysqli("localhost", "root", "", "login");
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'DB connection failed.']);
    exit();
}

if (!isset($_FILES['student_file']) || $_FILES['student_file']['error'] !== 0) {
    echo json_encode(['success' => false, 'message' => 'No file uploaded.']);
    exit();
}

$ext = strtolower(pathinfo($_FILES['student_file']['name'], PATHINFO_EXTENSION));
if (!in_array($ext, ['csv', 'txt'])) {
    echo json_encode(['success' => false, 'message' => 'Only .csv or .txt files are accepted.']);
    exit();
}

// ── QR Code setup ──────────────────────────────────────────────────────────
// Make sure the qrcodes folder exists and is writable
$qrDir = __DIR__ . '/qrcodes/';
if (!is_dir($qrDir)) {
    mkdir($qrDir, 0755, true);
}

// Load phpqrcode library (place qrlib.php in the same folder or adjust path)
$qrLibPath = __DIR__ . '/phpqrcode/qrlib.php';
$hasQrLib  = file_exists($qrLibPath);
if ($hasQrLib) {
    require_once $qrLibPath;
}

/**
 * Generate a QR code PNG for the given student ID.
 * Falls back to a GD-drawn placeholder if phpqrcode is not available.
 */
function generateQR(string $sid, string $qrDir, bool $hasQrLib): void {
    $outFile = $qrDir . $sid . '.png';
    if (file_exists($outFile)) return; // already exists, skip

    if ($hasQrLib) {
        // phpqrcode: QRcode::png($text, $outfile, $level, $size, $margin)
        QRcode::png($sid, $outFile, QR_ECLEVEL_M, 8, 2);
    } else {
        // ── Fallback: plain GD placeholder ───────────────────────────────
        // Creates a white 200×200 PNG with the student ID text centred.
        // Replace with a real QR library for production.
        $size  = 200;
        $img   = imagecreatetruecolor($size, $size);
        $white = imagecolorallocate($img, 255, 255, 255);
        $black = imagecolorallocate($img, 0,   0,   0);
        $red   = imagecolorallocate($img, 200, 50,  50);
        imagefill($img, 0, 0, $white);
        imagerectangle($img, 0, 0, $size - 1, $size - 1, $black);

        $font   = 3;
        $label1 = 'NO QR LIB';
        $label2 = $sid;
        $x1 = (int)(($size - imagefontwidth($font) * strlen($label1)) / 2);
        $x2 = (int)(($size - imagefontwidth($font) * strlen($label2)) / 2);
        imagestring($img, $font, $x1, 85,  $label1, $red);
        imagestring($img, $font, $x2, 105, $label2, $black);

        imagepng($img, $outFile);
        imagedestroy($img);
    }
}
// ───────────────────────────────────────────────────────────────────────────

$lines    = file($_FILES['student_file']['tmp_name'], FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$imported = 0;
$skipped  = [];
$header   = null;

foreach ($lines as $line) {
    $cols = str_getcsv($line);

    // Detect & skip header row
    if ($header === null) {
        $lower = array_map('strtolower', array_map('trim', $cols));
        if (in_array('student_id', $lower) || in_array('fullname', $lower)) {
            $header = $lower;
            continue;
        }
        // No header — assume fixed order
        $header = ['student_id', 'fullname', 'email', 'course', 'year', 'section'];
    }

    $map     = array_combine($header, array_pad($cols, count($header), ''));
    $sid     = trim($map['student_id'] ?? '');
    $name    = trim($map['fullname']   ?? '');
    $email   = trim($map['email']      ?? '');
    $course  = trim($map['course']     ?? '');
    $year    = trim($map['year']       ?? '');
    $section = trim($map['section']    ?? '');

    if (empty($sid) || empty($name)) continue;

    // Skip duplicates
    $check = $conn->prepare("SELECT id FROM students WHERE student_id = ?");
    $check->bind_param("s", $sid);
    $check->execute();
    $check->store_result();
    if ($check->num_rows > 0) {
        $skipped[] = $sid;
        $check->close();
        continue;
    }
    $check->close();

    // Insert student
    $stmt = $conn->prepare(
        "INSERT INTO students (student_id, fullname, email, course, year, section, isActive)
        VALUES (?, ?, ?, ?, ?, ?, 1)"
    );
    $stmt->bind_param("ssssss", $sid, $name, $email, $course, $year, $section);
    if ($stmt->execute()) {
        $imported++;
        // Generate QR code right after successful insert
        generateQR($sid, $qrDir, $hasQrLib);
    }
    $stmt->close();
}

$conn->close();

echo json_encode([
    'success' => true,
    'message' => "$imported student(s) imported successfully.",
    'skipped' => $skipped,
    'qr_note' => $hasQrLib
        ? 'QR codes generated in /qrcodes/'
        : 'phpqrcode not found — placeholder images created. Install phpqrcode for real QR codes.',
]);