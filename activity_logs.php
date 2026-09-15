<?php 
$conn = new mysqli("localhost", "root", "", "login");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$total_entries = $conn->query("SELECT COUNT(*) as total FROM checkin_logs")->fetch_assoc()['total'];
$peak_entries = $conn->query("SELECT COUNT(*) as total FROM checkin_logs WHERE is_peak_hour = 1")->fetch_assoc()['total'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Activity Logs | LibAdmin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="style2.css">
    <style>
    /* ── Print Styles ────────────────────────────────────────────────── */
    @media print {
        /* Hide everything except the print area */
        body * { visibility: hidden; }
        #printArea, #printArea * { visibility: visible; }
        #printArea {
            position: fixed;
            inset: 0;
            width: 100%;
            padding: 20px;
            background: #fff;
        }
        .no-print { display: none !important; }
    }

    /* ── Print Area (hidden on screen, shown on print) ───────────────── */
    #printArea { display: none; }

    /* ── Print document styling ──────────────────────────────────────── */
    .print-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        border-bottom: 3px solid #6366f1;
        padding-bottom: 16px;
        margin-bottom: 20px;
    }
    .print-header .school-info h2 {
        font-size: 18px;
        font-weight: 800;
        color: #1e293b;
        margin: 0 0 2px;
    }
    .print-header .school-info p {
        font-size: 11px;
        color: #64748b;
        margin: 0;
    }
    .print-header .report-meta {
        text-align: right;
        font-size: 11px;
        color: #64748b;
    }
    .print-header .report-meta strong {
        display: block;
        font-size: 15px;
        color: #6366f1;
        font-weight: 800;
        margin-bottom: 4px;
    }
    .print-summary {
        display: flex;
        gap: 16px;
        margin-bottom: 18px;
    }
    .print-summary .stat-box {
        flex: 1;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 10px 14px;
        background: #f8fafc;
    }
    .print-summary .stat-box small {
        font-size: 10px;
        text-transform: uppercase;
        font-weight: 700;
        color: #94a3b8;
        letter-spacing: 0.5px;
    }
    .print-summary .stat-box h3 {
        margin: 4px 0 0;
        font-size: 22px;
        font-weight: 800;
        color: #1e293b;
    }
    .print-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 11px;
    }
    .print-table thead tr {
        background: #6366f1;
        color: #fff;
    }
    .print-table thead th {
        padding: 8px 10px;
        text-align: left;
        font-weight: 700;
        font-size: 10px;
        text-transform: uppercase;
        letter-spacing: 0.4px;
    }
    .print-table tbody tr:nth-child(even) {
        background: #f8fafc;
    }
    .print-table tbody tr:nth-child(odd) {
        background: #ffffff;
    }
    .print-table tbody td {
        padding: 7px 10px;
        border-bottom: 1px solid #e2e8f0;
        color: #334155;
    }
    .print-table tbody td.mono {
        font-family: monospace;
        font-weight: 700;
        color: #6366f1;
    }
    .print-table .badge-peak {
        background: #fef3c7;
        color: #92400e;
        padding: 2px 8px;
        border-radius: 99px;
        font-size: 9px;
        font-weight: 700;
    }
    .print-table .badge-normal {
        background: #d1fae5;
        color: #065f46;
        padding: 2px 8px;
        border-radius: 99px;
        font-size: 9px;
        font-weight: 700;
    }
    .print-footer {
        margin-top: 24px;
        border-top: 1px solid #e2e8f0;
        padding-top: 10px;
        display: flex;
        justify-content: space-between;
        font-size: 10px;
        color: #94a3b8;
    }

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
    .main-content > div:nth-of-type(1).card,
    div[style*="grid-template-columns:1fr 1fr"] .card:nth-child(1) { animation-delay: 0.05s; }
    div[style*="grid-template-columns:1fr 1fr"] .card:nth-child(2) { animation-delay: 0.15s; }
    @media (prefers-reduced-motion: reduce) {
        .nav-btn, .card { animation: none !important; opacity: 1 !important; transform: none !important; }
    }
    </style>
</head>
<body style="display:flex; background:#f8fafc;">

    <!-- SIDEBAR -->
    <div class="sidebar no-print">
        <h2 style="color:var(--primary);margin-bottom:40px;padding-left:20px;">
            <i class="fas fa-book-reader"></i> LibAdmin
        </h2>
        <div class="nav-container">
            <a href="dashboard.php" class="nav-btn"><i class="fas fa-house"></i> Home</a>
            <a href="admin.php"           class="nav-btn"><i class="fas fa-chart-line"></i> Dashboard</a>
            <a href="manage_students.php" class="nav-btn"><i class="fas fa-users"></i> Students</a>
            <a href="activity_logs.php"   class="nav-btn active"><i class="fas fa-history"></i> Logs</a>
            <a href="analytics.php"       class="nav-btn"><i class="fas fa-brain"></i> Analysis</a>
            <hr style="border:0;border-top:1px solid #eee;margin:20px 10px;">
            <a href="guide.php" class="nav-btn"><i class="fas fa-circle-question"></i> System Guide</a>
            <a href="logout.php" class="nav-btn" style="color:#ff4d4d;"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main-content no-print">
        <header style="display:flex;justify-content:space-between;align-items:center;margin-bottom:30px;">
            <div>
                <h1 style="font-weight:800;">Entry Records</h1>
                <p style="color:var(--text-muted);">Monitoring <?php echo $total_entries; ?> total student sessions.</p>
            </div>
            <button onclick="triggerPrint()" class="btn-modern" style="background:#64748b;border:none;cursor:pointer;">
                <i class="fas fa-file-pdf"></i> Export PDF
            </button>
        </header>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:25px;">
            <div class="card" style="padding:20px;border-left:5px solid var(--primary);">
                <small style="color:var(--text-muted);text-transform:uppercase;font-weight:bold;">Total Visits</small>
                <h2 style="margin-top:5px;"><?php echo $total_entries; ?></h2>
            </div>
            <div class="card" style="padding:20px;border-left:5px solid #f59e0b;">
                <small style="color:var(--text-muted);text-transform:uppercase;font-weight:bold;">Peak Hour Traffic</small>
                <h2 style="margin-top:5px;"><?php echo $peak_entries; ?></h2>
            </div>
        </div>

        <div class="card">
            <table style="width:100%;">
                <thead>
                    <tr>
                        <th>Check-in Time</th>
                        <th>Student Name</th>
                        <th>Student ID</th>
                        <th>Course</th>
                        <th>Year</th>
                        <th>Section</th>
                        <th>Voucher Code</th>
                        <th>System Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $sql = "SELECT checkin_logs.*, students.fullname, students.course, students.year, students.section
                            FROM checkin_logs 
                            LEFT JOIN students ON checkin_logs.student_id = students.student_id 
                            ORDER BY checkin_logs.id DESC";
                    $result = $conn->query($sql);
                    if ($result && $result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            $hour = (int)date('H', strtotime($row['checkin_time']));
                            $isPeak = ($hour >= 10 && $hour <15);
                            $statusText = $isPeak ? "Peak Period" : "Normal";
                            $badgeClass = $isPeak ? "badge-warning" : "badge-success";
                            $formattedTime = date('M d, h:i A', strtotime($row['checkin_time']));
                            echo "<tr>
                                <td style='color:var(--text-muted);font-size:0.85rem;'>$formattedTime</td>
                                <td style='font-weight:600;'>".($row['fullname'] ?? 'Unknown Student')."</td>
                                <td style='font-family:monospace;font-weight:bold;color:var(--primary);'>{$row['student_id']}</td>
                                <td style='color:var(--text-muted);'>{$row['course']}</td>
                                <td style='color:var(--text-muted);'>{$row['year']}</td>
                                <td style='color:var(--text-muted);'>{$row['section']}</td>
                                <td><code style='background:#f1f5f9;padding:5px 10px;border-radius:8px;color:#4338ca;font-weight:bold;'>{$row['voucher_code']}</code></td>
                                <td><span class='badge $badgeClass'>$statusText</span></td>
                            </tr>";
                        }
                    } else {
                        echo "<tr><td colspan='8' style='text-align:center;padding:50px;color:var(--text-muted);'>No activity logs found.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ══════════════════════════════════════════════════
        PRINT AREA — rendered only on print/export
    ══════════════════════════════════════════════════ -->
    <div id="printArea">

        <!-- Document Header -->
        <div class="print-header">
            <div class="school-info">
                <h2><i class="fas fa-book-reader"></i> LibFlow AI — Library Management System</h2>
                <p>Official Student Check-in Activity Report</p>
            </div>
            <div class="report-meta">
                <strong>Entry Records</strong>
                <span>Printed: <span id="printDate"></span></span><br>
                <span>Total Records: <strong><?php echo $total_entries; ?></strong></span>
            </div>
        </div>

        <!-- Summary Stats -->
        <div class="print-summary">
            <div class="stat-box" style="border-left:4px solid #6366f1;">
                <small>Total Visits</small>
                <h3><?php echo $total_entries; ?></h3>
            </div>
            <div class="stat-box" style="border-left:4px solid #f59e0b;">
                <small>Peak Hour Traffic</small>
                <h3><?php echo $peak_entries; ?></h3>
            </div>
            <div class="stat-box" style="border-left:4px solid #10b981;">
                <small>Normal Traffic</small>
                <h3><?php echo $total_entries - $peak_entries; ?></h3>
            </div>
        </div>

        <!-- Check-in Table -->
        <table class="print-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Check-in Time</th>
                    <th>Student Name</th>
                    <th>Student ID</th>
                    <th>Course</th>
                    <th>Year</th>
                    <th>Section</th>
                    <th>Voucher Code</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Re-run query for print area
                $result2 = $conn->query(
                    "SELECT checkin_logs.*, students.fullname, students.course, students.year, students.section
                    FROM checkin_logs 
                    LEFT JOIN students ON checkin_logs.student_id = students.student_id 
                    ORDER BY checkin_logs.id DESC"
                );
                $rowNum = 1;
                if ($result2 && $result2->num_rows > 0) {
                    while ($row = $result2->fetch_assoc()) {
                        $hour = (int)date('H', strtotime($row['checkin_time']));
                        $isPeak = ($hour >= 10 && $hour < 15);
                        $statusText = $isPeak ? "Peak Period" : "Normal";
                        $badgeClass = $isPeak ? "badge-peak" : "badge-normal";
                        $formattedTime = date('M d, Y  h:i A', strtotime($row['checkin_time']));
                        $fullname      = htmlspecialchars($row['fullname'] ?? 'Unknown Student');
                        $sid           = htmlspecialchars($row['student_id']);
                        $course        = htmlspecialchars($row['course'] ?? '—');
                        $year          = htmlspecialchars($row['year']   ?? '—');
                        $section       = htmlspecialchars($row['section'] ?? '—');
                        $voucher       = htmlspecialchars($row['voucher_code'] ?? '—');
                        echo "<tr>
                            <td style='color:#94a3b8;font-size:10px;'>$rowNum</td>
                            <td>$formattedTime</td>
                            <td style='font-weight:600;'>$fullname</td>
                            <td class='mono'>$sid</td>
                            <td>$course</td>
                            <td>$year</td>
                            <td>$section</td>
                            <td class='mono' style='color:#4338ca;'>$voucher</td>
                            <td><span class='$badgeClass'>$statusText</span></td>
                        </tr>";
                        $rowNum++;
                    }
                } else {
                    echo "<tr><td colspan='9' style='text-align:center;padding:30px;color:#94a3b8;'>No records found.</td></tr>";
                }
                ?>
            </tbody>
        </table>

        <!-- Document Footer -->
        <div class="print-footer">
            <span>LibFlow AI — Library Management System</span>
            <span>Total of <?php echo $total_entries; ?> check-in record(s) exported.</span>
            <span id="printFooterDate"></span>
        </div>

    </div>
    
    <!-- end #printArea -->

    <script>
    function triggerPrint() {
        // Stamp the current date/time into the print header & footer
        const now = new Date().toLocaleString('en-PH', {
            year: 'numeric', month: 'long', day: 'numeric',
            hour: '2-digit', minute: '2-digit'
        });
        document.getElementById('printDate').textContent      = now;
        document.getElementById('printFooterDate').textContent = now;

        // Show the print area, trigger print, then hide again
        const area = document.getElementById('printArea');
        area.style.display = 'block';
        window.print();
        area.style.display = 'none';
    }
    </script>

</body>
</html>
<?php $conn->close(); ?>