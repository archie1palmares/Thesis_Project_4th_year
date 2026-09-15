<?php

include 'connect.php';

if (isset($_POST['signUp'])) {
    $firstName = $_POST['Fname'];
    $lastName = $_POST['Lname'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $password = md5($password); // Hash the password for security

    $checkEmail="SELECT * FROM users WHERE email='$email'";
    $result=$conn->query($checkEmail);
    if($result->num_rows > 0) {
        echo "Email already exists !";
    } else {
        $insertQuery = "INSERT INTO users(firstName, lastName, email, password) VALUES ('$firstName', '$lastName', '$email', '$password')";
        if ($conn->query($insertQuery) === TRUE) {
            header("Location: index.php");
        }
        else {
            echo "Error: ".conn->error;
    }
    }
}

if(isset($_POST['login'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];
    $password = md5($password); // Hash the password for security

    $sql="SELECT * FROM users WHERE email='$email' AND password='$password'";
    $result=$conn->query($sql);
    if($result->num_rows > 0) {
        session_start();
        $row=$result->fetch_assoc();
        $_SESSION['email']=$row['email'];
        header("Location: dashboard.php");
        exit();
    } else {
        echo "Invalid email or password !";     
}
}

// logic_processor.php
if(isset($_POST['qr_scanned_data'])) {
    $student_id = $_POST['student_id'];
    
    // 1. Validation logic
    // 2. Generate Random Token (Voucher)
    $voucher = strtoupper(substr(md5(time()), 0, 8)); // Simple unique 8-char code
    
    // 3. Save to Database
    $stmt = $conn->prepare("INSERT INTO attendance_logs (student_name, student_id, voucher_code) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $name, $student_id, $voucher);
    $stmt->execute();
    
    // 4. Trigger SMS (Placeholder for your SMS API)
    // sendSMS($phone, "Your Library Wifi Voucher is: " . $voucher);
}

?>