<?php
$conn = new mysqli("localhost", "root", "", "login");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $sid = $_POST['student_id'];
    $today = date('Y-m-d');

// 1. check if student exists
    $student_stmt = $conn->prepare("SELECT fullname FROM students WHERE student_id = ?");
    $student_stmt->bind_param("s", $sid);
    $student_stmt->execute();
    $student_result = $student_stmt->get_result();

    if ($student_result->num_rows == 0) {
        // Student does not exist
        header("Location: scanner.php?status2=not_found");
        exit();
    }

    // 1. Check if student already checked in today

    $check_stmt = $conn->prepare("SELECT voucher_code FROM checkin_logs WHERE student_id = ? AND DATE(checkin_time) = ?");
    $check_stmt->bind_param("ss", $sid, $today);
    $check_stmt->execute();
    $result = $check_stmt->get_result();

    if ($result->num_rows > 0) {
        // Already checked in today - redirect with existing voucher
        $row = $result->fetch_assoc();
        header("Location: scanner.php?status=already_in");
        exit();
        
    
    } else {
        // 2. New Check-in: Generate RF Analysis Data

        //Get Voucher from the Voucher Codes table
        $getVoucher = $conn->prepare("SELECT voucher_codes FROM Voucher_codes WHERE status = 'active' LIMIT 1");
        $getVoucher->execute();
        $voucher_result = $getVoucher->get_result();
        $voucher = $voucher_result->fetch_assoc()['voucher_codes'];

        //If no active voucher is available, you can handle it as needed (e.g., show an error or generate a new one)
        if (!$voucher) {
            header("Location: scanner.php?status2=no_voucher");
            exit();
        }

        $hour = (int)date('H');
        $is_peak = ($hour >= 10 && $hour < 15) ?  1 : 0; // 1 for peak hours, 0 for non-peak hours
        $day = (int)date('w');

        // Insert check-in log with RF analysis data
        $insert_stmt = $conn->prepare("INSERT INTO checkin_logs (student_id, voucher_code, is_peak_hour, day_of_week) VALUES (?, ?, ?, ?)");
        $insert_stmt->bind_param("ssii", $sid, $voucher, $is_peak, $day);

        //Update Voucher status to 'used'
        $updateVoucher = $conn->prepare("UPDATE Voucher_codes SET status = 'used', UsedDate = NOW() WHERE voucher_codes = ?");
        $updateVoucher->bind_param("s", $voucher);
        $updateVoucher->execute();

        if ($insert_stmt->execute()) {
            header("Location: after_scan.php?voucher=" . $voucher);
            exit();
        }
    }
    
}
?>