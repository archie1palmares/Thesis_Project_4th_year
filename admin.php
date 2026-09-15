<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Librarian Admin | Modern Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="style.css"> <style>
        :root {
            --primary: #6c63ff;
            --bg: #f4f7fe;
            --sidebar: #ffffff;
        }

        body { display: flex; background: var(--bg); min-height: 100vh; align-items: flex-start; }

        /* Sidebar */
        .sidebar {
            width: 260px;
            height: 100vh;
            background: var(--sidebar);
            padding: 30px 20px;
            position: fixed;
            box-shadow: 4px 0 10px rgba(0,0,0,0.05);
        }

        .main-content { margin-left: 280px; padding: 40px; width: calc(100% - 280px); }

        /* Modern Grid Layout */
        .dashboard-grid {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 30px;
        }

        /* Modern Form Design */
        .card {
            background: white;
            padding: 25px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.03);
        }

        .admin-form input {
            width: 100%;
            padding: 12px;
            margin-bottom: 15px;
            border: 1.5px solid #eee;
            border-radius: 10px;
            outline: none;
            transition: 0.3s;
        }

        .admin-form input:focus { border-color: var(--primary); }

        /* Table Styling */
        table { width: 100%; border-collapse: separate; border-spacing: 0 10px; }
        th { color: #888; font-weight: 500; text-align: left; padding: 10px; }
        tr.row-data { background: #fff; box-shadow: 0 5px 10px rgba(0,0,0,0.02); transition: 0.2s; }
        tr.row-data:hover { transform: scale(1.01); }
        td { padding: 15px; border-radius: 0; }
        td:first-child { border-radius: 10px 0 0 10px; }
        td:last-child { border-radius: 0 10px 10px 0; }

        .badge {
            padding: 5px 12px;
            border-radius: 8px;
            font-size: 0.75rem;
            font-weight: bold;
            background: rgba(108, 99, 255, 0.1);
            color: var(--primary);
        }

        /* ── Card entrance: swipe up into place ── */
        @keyframes cardSlideUp {
            from { opacity: 0; transform: translateY(26px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .card {
            animation: cardSlideUp 0.55s cubic-bezier(0.22, 1, 0.36, 1) both;
        }
        .dashboard-grid .card:nth-child(1) { animation-delay: 0.05s; }
        .dashboard-grid .card:nth-child(2) { animation-delay: 0.18s; }
    </style>


</head>
<body>

<style>
    /* Modern Sidebar Navigation Styles */
    .sidebar {
        width: 260px;
        height: 100vh;
        background: #ffffff;
        padding: 30px 15px; /* Reduced side padding for better button fit */
        position: fixed;
        left: 0;
        top: 0;
        box-shadow: 4px 0 10px rgba(0,0,0,0.05);
        display: flex;
        flex-direction: column;
    }

    .nav-container {
        display: flex;
        flex-direction: column;
        
    }

    .nav-btn {
        display: flex;
        align-items: center;
        text-decoration: none;
        color: #888;
        padding: 12px 20px;
        border-radius: 12px;
        
        transition: all 0.4s ease;
    }

    /* The "Hover" effect */
    .nav-btn:hover {
        background-color: rgba(108, 99, 255, 0.05);
        color: #6c63ff;
        transform: translateX(5px); /* Subtle slide right effect */
    }

    /* The "Active" state button */
    .nav-btn.active {
        background-color: #6c63ff;
        color: white;
        box-shadow: 0 4px 15px rgba(108, 99, 255, 0.3);
    }

    .nav-btn i {
        margin-right: 15px;
        font-size: 1.1rem;
        width: 25px; /* Keeps icons aligned */
        text-align: center;
    }

    /* ── Nav button entrance: slide in from the left, one by one ── */
    @keyframes navSlideIn {
        from { opacity: 0; transform: translateX(-18px); }
        to   { opacity: 1; transform: translateX(0); }
    }
    .nav-btn {
        opacity: 0;
        animation: navSlideIn 0.45s cubic-bezier(0.22, 1, 0.36, 1) forwards;
    }
    .nav-container .nav-btn:nth-of-type(1) { animation-delay: 0.05s; }
    .nav-container .nav-btn:nth-of-type(2) { animation-delay: 0.11s; }
    .nav-container .nav-btn:nth-of-type(3) { animation-delay: 0.17s; }
    .nav-container .nav-btn:nth-of-type(4) { animation-delay: 0.23s; }
    .nav-container .nav-btn:nth-of-type(5) { animation-delay: 0.29s; }
    .nav-container .nav-btn:nth-of-type(6) { animation-delay: 0.35s; }

    @media (prefers-reduced-motion: reduce) {
        .nav-btn, .card { animation: none !important; opacity: 1 !important; transform: none !important; }
    }
</style>

<div class="sidebar">
    <h2 style="color: #6c63ff; margin-bottom: 40px; padding-left: 20px;">
        <i class="fas fa-book-reader"></i> LibAdmin
    </h2>
    
    <div class="nav-container">

        <a href="dashboard.php" class="nav-btn"><i class="fas fa-house"></i> Home</a>


        <a href="admin.php" class="nav-btn active">
            <i class="fas fa-chart-line"></i> Dashboard</a>
        
        <a href="manage_students.php" class="nav-btn">
            <i class="fas fa-users"></i> Students</a>
        
        <a href="activity_logs.php" class="nav-btn">
            <i class="fas fa-history"></i> Logs</a>

        <a href="analytics.php" class="nav-btn">
            <i class="fas fa-brain"></i> Analysis</a>

        
        <hr style="border: 0; border-top: 1px solid #eee; margin: 20px 10px;">

        <a href="guide.php" class="nav-btn"><i class="fas fa-circle-question"></i> System Guide</a>

        <a href="logout.php" class="nav-btn" style="color: #ff4d4d;">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
</div>

<div class="main-content">
    <h1 style="margin-bottom: 30px; color: #333;">Library Insights</h1>

    <div class="dashboard-grid">
        <div class="card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 style="margin: 0;">Register New Student</h3>
            <span class="badge" style="background: rgba(108, 99, 255, 0.1); color: #6c63ff;">
                <i class="fas fa-magic"></i> Auto-Generate QR
            </span>
        </div>
    
    <form action="add_student.php" method="POST" class="admin-form">
        <input type="text" name="Fname" placeholder="First Name" required>
        <input type="text" name="Lname" placeholder="Last Name" required>
        <input type="text" name="course" placeholder="Course" required>
        <input type="text" name="year" placeholder="Year" required>
        <input type="text" name="section" placeholder="Section" required>
        <input type="text" name="student_id" placeholder="Student ID (e.g. 2024-0001)" required>
        <input type="email" name="email" placeholder="Institutional Email" required>
        
        <button type="submit" class="btn" style="width: 100%; background: #6c63ff; color: white; border: none; padding: 12px; border-radius: 10px; font-weight: 600; cursor: pointer;">
            Register & Generate Pass
        </button>
    </form>
</div>
        <div class="card">
    <h3 style="margin-bottom: 20px;">Recent Activity & RF Predictions</h3>
    <table>
        <thead>
            <tr>
                <th>Student</th>
                <th>Course</th>
                <th>Year</th>
                <th>Section</th>
                <th>Check-in</th>
                <th>Voucher</th>
                <th>RF Prediction</th>
            </tr>
        </thead>
        <tbody>
            <?php
            // Connect to database
            $conn = new mysqli("localhost", "root", "", "login");

            // Query to join logs with student names
            $query = "SELECT checkin_logs.*, students.fullname, students.course, students.year, students.section
                      FROM checkin_logs 
                      JOIN students ON checkin_logs.student_id = students.student_id 
                      ORDER BY checkin_logs.id DESC LIMIT 5";
            
            $result = $conn->query($query);

            if ($result && $result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    // Random Forest Prediction Logic based on your column
                    $logged_hour = (int)date('G', strtotime($row['checkin_time']));
                    $actual_peak = ($logged_hour >= 10 && $logged_hour < 15) ? 1 : 0;
                    $logged_hour = (int)date('G', strtotime($row['checkin_time']));
                    $isPeak = ($logged_hour >= 10 && $logged_hour < 15);
                    $prediction = $isPeak ? "Peak Traffic" : "Normal Flow";
                    $badgeStyle = $isPeak
                        ? "background: rgba(255,152,0,0.1); color: #ff9800;"
                        : "background: rgba(16,185,129,0.1); color: #10b981;";
                    
                    echo "<tr class='row-data'>";
                    echo "<td>" . htmlspecialchars($row['fullname']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['course']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['year']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['section']) . "</td>";
                    echo "<td>" . date('h:i A', strtotime($row['checkin_time'])) . "</td>";
                    echo "<td><code>" . $row['voucher_code'] . "</code></td>";
                    echo "<td><span class='badge' style='$badgeStyle'>$prediction</span></td>";
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='7' style='text-align:center; padding: 20px; color: #888;'>No recent activity found.</td></tr>";
            }
            ?>
        </tbody>
    </table>
</div>



<script src="https://unpkg.com/html5-qrcode"></script>
<script>
function onScanSuccess(decodedText, decodedResult) {
    // 1. Set the input field value
    document.getElementById('student_id').value = decodedText;
    
    // 2. Stop the scanner
    html5QrcodeScanner.clear();
    
    // 3. Auto-submit the form
    document.getElementById('qr-form').submit();
}

function onScanFailure(error) {
    // We ignore errors while searching for a code
}

// MacBook & Android optimized config
let html5QrcodeScanner = new Html5QrcodeScanner(
    "reader", 
    { 
        fps: 20, 
        qrbox: {width: 250, height: 250},
        aspectRatio: 1.0 
    }
);

html5QrcodeScanner.render(onScanSuccess, onScanFailure);

</script>


        
    </div>
</div>

</body>
</html>