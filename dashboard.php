<?php
session_start();
include 'connect.php';

// Small counts for the welcome strip — purely informational, no logic changed elsewhere
$student_count = 0;
$today_checkins = 0;
$res1 = $conn->query("SELECT COUNT(*) as total FROM students WHERE isActive=1");
if ($res1) { $student_count = $res1->fetch_assoc()['total']; }
$res2 = $conn->query("SELECT COUNT(*) as total FROM checkin_logs WHERE DATE(checkin_time) = CURDATE()");
if ($res2) { $today_checkins = $res2->fetch_assoc()['total']; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home | LibFlow AI</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="style2.css">
    <style>
    @import url('https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600&display=swap');

    @keyframes swipeUp {
        from { opacity: 0; transform: translateY(30px); }
        to   { opacity: 1; transform: translateY(0); }
    }

    .welcome-band {
        background: linear-gradient(150deg, #101B33 0%, #1F2E5C 100%);
        border-radius: 24px;
        padding: 36px 40px;
        color: #F4F1E8;
        margin-bottom: 30px;
        position: relative;
        overflow: hidden;
        animation: swipeUp 0.6s cubic-bezier(0.22, 1, 0.36, 1) both;
    }
    .welcome-band::before {
        content: '';
        position: absolute;
        width: 260px; height: 260px;
        border-radius: 50%;
        border: 1px dashed rgba(201,162,39,0.25);
        top: -90px; right: -60px;
    }
    .welcome-band h1 {
        font-family: 'Fraunces', serif;
        font-weight: 500;
        font-size: 1.9rem;
        margin-bottom: 6px;
        position: relative;
        z-index: 1;
    }
    .welcome-band p {
        color: #C7CADA;
        font-size: 0.92rem;
        position: relative;
        z-index: 1;
    }
    .welcome-stats {
        display: flex;
        gap: 28px;
        margin-top: 20px;
        position: relative;
        z-index: 1;
    }
    .welcome-stat small {
        display: block;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        font-size: 0.68rem;
        color: #C9A227;
        margin-bottom: 4px;
    }
    .welcome-stat strong {
        font-size: 1.4rem;
        font-weight: 600;
    }

    .hub-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 22px;
    }
    .hub-card {
        background: #fff;
        border-radius: 20px;
        padding: 28px;
        border: 1px solid #EFEAD8;
        text-decoration: none;
        color: inherit;
        display: block;
        transition: transform 0.25s ease, border-color 0.25s ease, box-shadow 0.25s ease;
        box-shadow: 0 10px 30px rgba(16,27,51,0.03);
        opacity: 0;
        animation: swipeUp 0.6s cubic-bezier(0.22, 1, 0.36, 1) forwards;
    }
    .hub-grid .hub-card:nth-child(1) { animation-delay: 0.15s; }
    .hub-grid .hub-card:nth-child(2) { animation-delay: 0.25s; }
    .hub-grid .hub-card:nth-child(3) { animation-delay: 0.35s; }
    .hub-grid .hub-card:nth-child(4) { animation-delay: 0.45s; }
    .hub-card:hover {
        transform: translateY(-4px);
        border-color: #C9A227;
        box-shadow: 0 16px 34px rgba(16,27,51,0.08);
    }
    @media (prefers-reduced-motion: reduce) {
        .welcome-band, .hub-card { animation: none !important; opacity: 1 !important; transform: none !important; }
    }
    .hub-icon {
        width: 50px; height: 50px;
        border-radius: 14px;
        display: flex; align-items: center; justify-content: center;
        font-size: 20px;
        margin-bottom: 18px;
    }
    .hub-card h3 {
        font-size: 1.05rem;
        margin-bottom: 6px;
        color: #17213C;
    }
    .hub-card p {
        font-size: 0.85rem;
        color: #8B8D97;
        line-height: 1.6;
        margin-bottom: 14px;
    }
    .hub-go {
        font-size: 0.78rem;
        font-weight: 600;
        color: #8B6F14;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    @media (max-width: 800px) {
        .hub-grid { grid-template-columns: 1fr; }
        .welcome-stats { flex-wrap: wrap; }
    }
    </style>
</head>
<body>

    <!-- SIDEBAR (same structure/classes used across the system) -->
    <div class="sidebar">
        <h2 style="color: var(--primary); margin-bottom: 40px; padding-left: 20px;">
            <i class="fas fa-book-reader"></i> LibAdmin
        </h2>
        <div class="nav-container">
            <a href="dashboard.php" class="nav-btn active"><i class="fas fa-house"></i> Home</a>
            <a href="admin.php"           class="nav-btn"><i class="fas fa-chart-line"></i> Dashboard</a>
            <a href="manage_students.php" class="nav-btn"><i class="fas fa-users"></i> Students</a>
            <a href="activity_logs.php"   class="nav-btn"><i class="fas fa-history"></i> Logs</a>
            <a href="analytics.php"       class="nav-btn"><i class="fas fa-brain"></i> Analysis</a>
            
            <hr style="border:0;border-top:1px solid #eee;margin:20px 10px;">
            <a href="guide.php"           class="nav-btn"><i class="fas fa-circle-question"></i> System Guide</a>
            <a href="logout.php" class="nav-btn" style="color:#ff4d4d;"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <!-- MAIN -->
    <div class="main-content">

        <div class="welcome-band">
            <h1><i class="fas fa-book-reader"></i> Welcome back</h1>
            <p>Pick where you want to go. Everything you need for the library is right here.</p>
            <div class="welcome-stats">
                <div class="welcome-stat">
                    <small>Active students</small>
                    <strong><?php echo $student_count; ?></strong>
                </div>
                <div class="welcome-stat">
                    <small>Check-ins today</small>
                    <strong><?php echo $today_checkins; ?></strong>
                </div>
            </div>
        </div>

        <div class="hub-grid">

            <a href="admin.php" class="hub-card">
                <div class="hub-icon" style="background:rgba(99,102,241,0.1);color:#6366f1;">
                    <i class="fas fa-chart-line"></i>
                </div>
                <h3>Dashboard</h3>
                <p>Register a new student, generate their QR pass, and see the latest check-ins as they happen.</p>
                <span class="hub-go">Open Dashboard <i class="fas fa-arrow-right"></i></span>
            </a>

            <a href="manage_students.php" class="hub-card">
                <div class="hub-icon" style="background:rgba(14,165,233,0.1);color:#0ea5e9;">
                    <i class="fas fa-users"></i>
                </div>
                <h3>Students</h3>
                <p>See every registered student, search the list, view their QR pass, or import a whole batch from a file.</p>
                <span class="hub-go">Open Students <i class="fas fa-arrow-right"></i></span>
            </a>

            <a href="activity_logs.php" class="hub-card">
                <div class="hub-icon" style="background:rgba(245,158,11,0.1);color:#f59e0b;">
                    <i class="fas fa-history"></i>
                </div>
                <h3>Logs</h3>
                <p>The full record of every check-in — time, student, voucher used — and a button to export it as a PDF.</p>
                <span class="hub-go">Open Logs <i class="fas fa-arrow-right"></i></span>
            </a>

            <a href="analytics.php" class="hub-card">
                <div class="hub-icon" style="background:rgba(168,85,247,0.1);color:#a855f7;">
                    <i class="fas fa-brain"></i>
                </div>
                <h3>Analysis</h3>
                <p>How busy the library gets, which hours and days are the peak, and how accurate the prediction is.</p>
                <span class="hub-go">Open Analysis <i class="fas fa-arrow-right"></i></span>
            </a>

        </div>
    </div>

</body>
</html>
<?php $conn->close(); ?>