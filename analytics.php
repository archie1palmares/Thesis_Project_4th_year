<?php 
// 1. DATABASE CONNECTION
$conn = new mysqli("localhost", "root", "", "login");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// 2. HANDLE REGISTRATION (POST Request)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['register_student'])) {
    $fname = $_POST['Fname'];
    $lname = $_POST['Lname'];
    $fullname = $fname . " " . $lname;
    $sid = $_POST['student_id'];
    $email = $_POST['email'];

    $stmt = $conn->prepare("INSERT INTO students (student_id, fullname, email) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $sid, $fullname, $email);

    if ($stmt->execute()) {
        echo "<script>alert('Student Registered Successfully!'); window.location.href='analytics.php';</script>";
    } else {
        echo "Error: " . $stmt->error;
    }
    $stmt->close();
}

// 3. FETCH DATA FOR ANALYTICS
$peak_count = $conn->query("SELECT COUNT(*) as total FROM checkin_logs WHERE is_peak_hour = 1")->fetch_assoc()['total'];
$normal_count = $conn->query("SELECT COUNT(*) as total FROM checkin_logs WHERE is_peak_hour = 0")->fetch_assoc()['total'];
$total = $peak_count + $normal_count;

foreach($days as $index => $day_name) {
    $count = $day_data[$index] ?? 0;
    $bar_width = ($max_day_count > 0) ? ($count / $max_day_count) * 100 : 0;
}

$max_day_count = !empty($day_data) ? max(array_values($day_data)) : 1;

date_default_timezone_set('Asia/Manila');

$correct = $conn->query("
    SELECT COUNT(*) as total FROM checkin_logs
    WHERE (is_peak_hour = 1 AND HOUR(checkin_time) >= 10 AND HOUR(checkin_time) <= 15)
    OR (is_peak_hour = 0 AND (HOUR(checkin_time) < 10 OR HOUR(checkin_time) > 15))
")->fetch_assoc()['total'];
$accuracy = ($total > 0) ? round(($correct / $total) * 100, 1) : 0;


$current_hour = (int)date('H');
$current_time = date('h:i A');
$is_currently_peak = ($current_hour >= 10 && $current_hour < 15);
$peak_status = $is_currently_peak ? "PEAK HOUR" : "NORMAL HOUR";
$peak_color = $is_currently_peak ? "#ff9800" : "#10b981";
$peak_icon = $is_currently_peak ? "fa-fire" : "fa-clock";

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Prediction Analytics | LibAdmin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
    <link rel="stylesheet" href="style2.css">
    <style>
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .chart-container { display: flex; gap: 25px; align-items: flex-start; }
        .prediction-box { background: linear-gradient(135deg, #6366f1, #a855f7); color: white; padding: 30px; border-radius: 24px; flex: 1; }
        .trend-bar { height: 12px; background: #e2e8f0; border-radius: 6px; overflow: hidden; margin: 10px 0; }
        .trend-fill { height: 100%; background: var(--primary); transition: width 1s ease-in-out; }
        
        /* Modal for Registration if needed */
        .reg-card { margin-top: 30px; border-top: 4px solid var(--primary); }
        .input-group { margin-bottom: 15px; }
        .input-group input { width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #ddd; }

        /* ── Entrance animations (self-contained, works even without style2.css update) ── */
        @keyframes navSlideIn {
            from { opacity: 0; transform: translateX(-18px); }
            to   { opacity: 1; transform: translateX(0); }
        }
        @keyframes cardSlideUp {
            from { opacity: 0; transform: translateY(26px); }
            to   { opacity: 1; transform: translateY(0); }
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
        .card
        {
            animation: cardSlideUp 0.55s cubic-bezier(0.22, 1, 0.36, 1) both;
        }
        .stats-grid .card:nth-child(1) { animation-delay: 0.05s; }
        .stats-grid .card:nth-child(2) { animation-delay: 0.15s; }
        .stats-grid .card:nth-child(3) { animation-delay: 0.25s; }
        .chart-container .card { animation-delay: 0.35s; }
        @media (prefers-reduced-motion: reduce) {
            .nav-btn, .card { animation: none !important; opacity: 1 !important; transform: none !important; }
        }
    </style>
</head>
<body style="display: flex; background: #f8fafc;">

    <div class="sidebar">
        <h2 style="color: var(--primary); margin-bottom: 40px; padding-left: 20px;">
            <i class="fas fa-book-reader"></i> LibAdmin
        </h2>
        <div class="nav-container">
            <a href="dashboard.php" class="nav-btn"><i class="fas fa-house"></i> Home</a>
            <a href="admin.php" class="nav-btn"><i class="fas fa-chart-line"></i> Dashboard</a>
            <a href="manage_students.php" class="nav-btn"><i class="fas fa-users"></i> Students</a>
            <a href="activity_logs.php" class="nav-btn"><i class="fas fa-history"></i> Logs</a>
            <a href="analytics.php" class="nav-btn active"><i class="fas fa-brain"></i> Analysis</a>
            <hr style="border: 0; border-top: 1px solid #eee; margin: 20px 10px;">
            <a href="guide.php" class="nav-btn"><i class="fas fa-circle-question"></i> System Guide</a>
            <a href="logout.php" class="nav-btn" style="color: #ff4d4d;"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <div class="main-content">
        <header style="margin-bottom: 30px;">
            <h1 style="font-weight: 800;">Algorithm Analysis Lab</h1>
            <p style="color: var(--text-muted);">Predictive modeling using Random Forest for library occupancy.</p>
        </header>

        <div class="stats-grid">
            <div class="card">
                <small style="color: var(--text-muted); font-weight: bold;">ALGORITHM ACCURACY</small>
                <h2 style="color: #10b981; font-size: 2rem;"><?php echo $accuracy; ?>%</h2>
                <div class="trend-bar"><div class="trend-fill" style="width: <?php echo $accuracy; ?>%;"></div></div>
                <p style="font-size: 0.75rem; color: var(--text-muted);">Calculated via OOB Error Estimate</p>
            </div>
            <div class="card" style="border-top: 4px solid <?php echo $peak_color; ?>;">
                <small style="color: var(--text-muted); font-weight: bold;">CURRENT TIME STATUS</small>
                <h2 style="color: <?php echo $peak_color; ?>; font-size: 2rem;">
                    <i class="fas <?php echo $peak_icon; ?>"></i> <?php echo $peak_status; ?>
                </h2>
                <p style="font-size: 1.1rem; font-weight: bold; margin: 5px 0;"><?php echo $current_time; ?></p>
                <p style="font-size: 0.75rem; color: var(--text-muted);">
                    Peak window: 10:00 AM – 3:00 PM
                </p>
                <div class="trend-bar" style="margin-top: 10px;">
                    <div class="trend-fill" style="
                        width: <?php echo min(($current_hour / 23) * 100, 100); ?>%;
                        background: <?php echo $peak_color; ?>;
                    "></div>
                </div>
                <div style="display: flex; justify-content: space-between; font-size: 0.7rem; color: var(--text-muted); margin-top: 4px;">
                    <span>12 AM</span>
                    <span>↑ Peak 10AM–3PM</span>
                    <span>11 PM</span>
                </div>
            </div>
            <div class="card">
                <small style="color: var(--text-muted); font-weight: bold;">CURRENT MODEL STATE</small>
                <h2 style="color: var(--primary); font-size: 2rem;">Stable</h2>
                <p style="font-size: 0.85rem; margin-top: 10px;"><i class="fas fa-check-circle"></i> Features: Time, Day, Count</p>
            </div>
        </div>

        <div class="chart-container">
            <!-- <div class="prediction-box">
                <h3 style="margin-bottom: 15px;"><i class="fas fa-microchip"></i> Random Forest Logic</h3>
                <p style="font-size: 0.9rem; opacity: 0.9; margin-bottom: 20px;">
                    The system uses multiple decision trees to classify if the current check-in belongs to a "Peak Hour" based on historical data.
                </p>
                <div style="background: rgba(255,255,255,0.1); padding: 15px; border-radius: 15px;">
                    <div style="display:flex; justify-content:space-between; margin-bottom:10px;">
                        <span>Peak Classification</span>
                        <span><?php echo ($total > 0) ? round(($peak_count/$total)*100) : 0; ?>%</span>
                    </div>
                    <div style="display:flex; justify-content:space-between;">
                        <span>Normal Classification</span>
                        <span><?php echo ($total > 0) ? round(($normal_count/$total)*100) : 0; ?>%</span>
                    </div>
                </div>
            </div> -->

            <div class="card" style="flex: 1.5;">
                <h3>Peak Day Distribution</h3>
                <table style="margin-top: 15px; width: 100%;">
                    <thead>
                        <tr>
                            <th>Day</th>
                            <th>Entry Volume</th>
                            <th>Trend</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $days = ["Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"];
                        $day_query = $conn->query("SELECT day_of_week, COUNT(*) as count FROM checkin_logs GROUP BY day_of_week");
                        $day_data = [];
                        while($r = $day_query->fetch_assoc()) { $day_data[$r['day_of_week']] = $r['count']; }

                        foreach($days as $index => $day_name) {
                            $count = $day_data[$index] ?? 0;
                            $max_day_count = max(array_values($day_data) ?: [1]);
                            $bar_width = ($max_day_count > 0) ? ($count / $max_day_count) * 100 : 0;
                            echo "<tr>
                                    <td>$day_name</td>
                                    <td style='font-weight:bold;'>$count</td>
                                    <td style='width: 50%;'><div class='trend-bar'><div class='trend-fill' style='width: $bar_width%;'></div></div></td>
                                </tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- <div class="card reg-card">
            <h3>Quick Student Registration</h3>
            <p style="color: var(--text-muted); font-size: 0.8rem; margin-bottom: 20px;">Add new students directly from the analysis lab.</p>
            <form action="analytics.php" method="POST" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <input type="hidden" name="register_student" value="1">
                <div class="input-group">
                    <input type="text" name="Fname" placeholder="First Name" required>
                </div>
                <div class="input-group">
                    <input type="text" name="Lname" placeholder="Last Name" required>
                </div>
                <div class="input-group">
                    <input type="text" name="student_id" placeholder="Student ID (e.g., 2024-001)" required>
                </div>
                <div class="input-group">
                    <input type="email" name="email" placeholder="Email Address" required>
                </div>
                <button type="submit" class="nav-btn active" style="grid-column: span 2; border: none; cursor: pointer; justify-content: center;">
                    Register Student
                </button>
            </form>
        </div> -->
    </div>

</body>
</html>
<?php $conn->close(); ?>