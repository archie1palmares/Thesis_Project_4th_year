<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Guide | LibFlow AI</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="style2.css">
    <style>
    @import url('https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600&display=swap');

    @keyframes swipeUp {
        from { opacity: 0; transform: translateY(30px); }
        to   { opacity: 1; transform: translateY(0); }
    }

    .guide-intro {
        background: linear-gradient(150deg, #101B33 0%, #1F2E5C 100%);
        border-radius: 24px;
        padding: 32px 40px;
        color: #F4F1E8;
        margin-bottom: 30px;
        animation: swipeUp 0.6s cubic-bezier(0.22, 1, 0.36, 1) both;
    }
    .guide-intro h1 {
        font-family: 'Fraunces', serif;
        font-weight: 500;
        font-size: 1.7rem;
        margin-bottom: 8px;
    }
    .guide-intro p {
        color: #C7CADA;
        font-size: 0.92rem;
        max-width: 640px;
        line-height: 1.7;
    }

    .guide-section {
        background: #fff;
        border-radius: 20px;
        border: 1px solid #EFEAD8;
        padding: 28px 32px;
        margin-bottom: 20px;
        opacity: 0;
        animation: swipeUp 0.55s cubic-bezier(0.22, 1, 0.36, 1) forwards;
    }
    .guide-section:nth-of-type(1) { animation-delay: 0.12s; }
    .guide-section:nth-of-type(2) { animation-delay: 0.20s; }
    .guide-section:nth-of-type(3) { animation-delay: 0.28s; }
    .guide-section:nth-of-type(4) { animation-delay: 0.36s; }
    .guide-section:nth-of-type(5) { animation-delay: 0.44s; }

    /* ── Nav button entrance, self-contained ── */
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
    .nav-container .nav-btn:nth-of-type(7) { animation-delay: 0.41s; }

    @media (prefers-reduced-motion: reduce) {
        .guide-intro, .guide-section, .nav-btn { animation: none !important; opacity: 1 !important; transform: none !important; }
    }
    .guide-head {
        display: flex;
        align-items: center;
        gap: 16px;
        margin-bottom: 16px;
    }
    .guide-icon {
        width: 46px; height: 46px;
        border-radius: 13px;
        display: flex; align-items: center; justify-content: center;
        font-size: 19px;
        flex-shrink: 0;
    }
    .guide-head h2 {
        font-size: 1.15rem;
        color: #17213C;
        margin-bottom: 2px;
    }
    .guide-head span {
        font-size: 0.78rem;
        color: #8B6F14;
        font-family: monospace;
        background: #FBF4E3;
        padding: 2px 8px;
        border-radius: 6px;
    }
    .guide-row {
        display: grid;
        grid-template-columns: 130px 1fr;
        gap: 10px;
        padding: 10px 0;
        border-top: 1px solid #F4F1E8;
        font-size: 0.88rem;
    }
    .guide-row:first-of-type { border-top: none; }
    .guide-row b {
        color: #5B6478;
        font-weight: 600;
        font-size: 0.78rem;
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }
    .guide-row div { color: #333; line-height: 1.6; }
    </style>
</head>
<body>

    <div class="sidebar">
        <h2 style="color: var(--primary); margin-bottom: 40px; padding-left: 20px;">
            <i class="fas fa-book-reader"></i> LibAdmin
        </h2>
        <div class="nav-container">
            <a href="dashboard.php" class="nav-btn"><i class="fas fa-house"></i> Home</a>
            <a href="admin.php"           class="nav-btn"><i class="fas fa-chart-line"></i> Dashboard</a>
            <a href="manage_students.php" class="nav-btn"><i class="fas fa-users"></i> Students</a>
            <a href="activity_logs.php"   class="nav-btn"><i class="fas fa-history"></i> Logs</a>
            <a href="analytics.php"       class="nav-btn"><i class="fas fa-brain"></i> Analysis</a>
            
            <hr style="border:0;border-top:1px solid #eee;margin:20px 10px;">
            <a href="guide.php"           class="nav-btn active"><i class="fas fa-circle-question"></i> System Guide</a>
            <a href="logout.php" class="nav-btn" style="color:#ff4d4d;"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <div class="main-content">

        <div class="guide-intro">
            <h1><i class="fas fa-compass"></i> How this system works</h1>
            <p>This page explains each part of LibFlow AI in plain words — what it is, where to find it, and what you can do there. No technical terms, just a simple walkthrough.</p>
        </div>

        <!-- HOME -->
        <div class="guide-section">
            <div class="guide-head">
                <div class="guide-icon" style="background:rgba(16,27,51,0.08);color:#101B33;"><i class="fas fa-house"></i></div>
                <div>
                    <h2>Home</h2>
                    <span>dashboard.php</span>
                </div>
            </div>
            <div class="guide-row"><b>What it is</b><div>The page you land on right after you log in. It's a starting point with four cards — one for each part of the system.</div></div>
            <div class="guide-row"><b>Where it is</b><div>Click "Home" at the top of the left-hand menu, on any page.</div></div>
            <div class="guide-row"><b>What to do</b><div>Click a card to go to that part of the system: Dashboard, Students, Logs, or Analysis.</div></div>
        </div>

        <!-- DASHBOARD -->
        <div class="guide-section">
            <div class="guide-head">
                <div class="guide-icon" style="background:rgba(99,102,241,0.1);color:#6366f1;"><i class="fas fa-chart-line"></i></div>
                <div>
                    <h2>Dashboard</h2>
                    <span>admin.php</span>
                </div>
            </div>
            <div class="guide-row"><b>What it is</b><div>Where you add a brand-new student and where their QR pass gets created automatically.</div></div>
            <div class="guide-row"><b>Where it is</b><div>Click "Dashboard" in the left menu, or the first card on the Home page.</div></div>
            <div class="guide-row"><b>What to do</b><div>Fill in the student's name, course, year, section, student ID, and email, then press "Register & Generate Pass". You'll also see the most recent check-ins on the right side.</div></div>
        </div>

        <!-- STUDENTS -->
        <div class="guide-section">
            <div class="guide-head">
                <div class="guide-icon" style="background:rgba(14,165,233,0.1);color:#0ea5e9;"><i class="fas fa-users"></i></div>
                <div>
                    <h2>Students</h2>
                    <span>manage_students.php</span>
                </div>
            </div>
            <div class="guide-row"><b>What it is</b><div>The full list of every student who is allowed to use the library. Think of it as the master list.</div></div>
            <div class="guide-row"><b>Where it is</b><div>Click "Students" in the left menu, or the second card on the Home page.</div></div>
            <div class="guide-row"><b>What to do</b><div>Use the search box to find someone by name, ID, or email. Click the QR icon to view or print their pass, the pencil icon to edit them, or the trash icon to remove them. You can also import a whole list of students or voucher codes from a file using the buttons at the top.</div></div>
        </div>

        <!-- LOGS -->
        <div class="guide-section">
            <div class="guide-head">
                <div class="guide-icon" style="background:rgba(245,158,11,0.1);color:#f59e0b;"><i class="fas fa-history"></i></div>
                <div>
                    <h2>Logs</h2>
                    <span>activity_logs.php</span>
                </div>
            </div>
            <div class="guide-row"><b>What it is</b><div>A record of every single check-in — who came in, at what time, and which voucher code they used.</div></div>
            <div class="guide-row"><b>Where it is</b><div>Click "Logs" in the left menu, or the third card on the Home page.</div></div>
            <div class="guide-row"><b>What to do</b><div>Scroll to look up past visits, or click "Export PDF" at the top right to save or print the whole record.</div></div>
        </div>

        <!-- ANALYSIS -->
        <div class="guide-section">
            <div class="guide-head">
                <div class="guide-icon" style="background:rgba(168,85,247,0.1);color:#a855f7;"><i class="fas fa-brain"></i></div>
                <div>
                    <h2>Analysis</h2>
                    <span>analytics.php</span>
                </div>
            </div>
            <div class="guide-row"><b>What it is</b><div>A simple report on how busy the library gets — which hours count as "peak", and how accurate that prediction has been.</div></div>
            <div class="guide-row"><b>Where it is</b><div>Click "Analysis" in the left menu, or the fourth card on the Home page.</div></div>
            <div class="guide-row"><b>What to do</b><div>Look at the accuracy percentage, the current hour's status, and which day of the week tends to be busiest.</div></div>
        </div>

    </div>

</body>
</html>