<?php if (session_status() === PHP_SESSION_NONE) { session_start(); } ?>
<?php 
$conn = new mysqli("localhost", "root", "", "login");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (isset($_GET['delete_id'])) {
    $id = mysqli_real_escape_string($conn, $_GET['delete_id']);
    $conn->query("UPDATE students SET isActive=0 WHERE id = '$id'");
    header("Location: manage_students.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Students | LibFlow AI</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="style2.css">
    <style>
    @keyframes viSlideUp {
        from { opacity:0; transform:translateY(20px); }
        to   { opacity:1; transform:translateY(0); }
    }
    @keyframes viSpin { to { transform:rotate(360deg); } }
    @keyframes rowIn {
        from { opacity: 0; transform: translateY(4px); }
        to   { opacity: 1; transform: translateY(0); }
    }

    .highlight {
        background: rgba(99, 102, 241, 0.15);
        border-radius: 3px;
        padding: 0 2px;
        font-weight: 700;
        color: var(--primary);
    }
    #no-results { display: none; }
    #no-results td {
        text-align: center;
        padding: 40px;
        color: var(--text-muted);
        font-size: 14px;
    }
    .search-wrapper { position: relative; flex: 1; }
    .search-wrapper i {
        position: absolute; left: 15px; top: 50%;
        transform: translateY(-50%);
        color: var(--text-muted); pointer-events: none;
    }
    .search-wrapper input {
        width: 100%;
        padding: 12px 40px 12px 45px;
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        outline: none; font-size: 14px;
        transition: border-color 0.2s, box-shadow 0.2s;
    }
    .search-wrapper input:focus {
        border-color: var(--primary, #6366f1);
        box-shadow: 0 0 0 3px rgba(99,102,241,0.1);
    }
    #clear-search {
        position: absolute; right: 12px; top: 50%;
        transform: translateY(-50%);
        background: none; border: none; cursor: pointer;
        color: var(--text-muted); font-size: 13px;
        display: none; padding: 4px 6px;
        border-radius: 4px; transition: color 0.15s;
    }
    #clear-search:hover { color: #ef4444; }
    #search-count {
        font-family: monospace; font-size: 12px;
        color: var(--text-muted); white-space: nowrap;
        padding: 6px 12px; background: #f8fafc;
        border: 1px solid #e2e8f0; border-radius: 8px; display: none;
    }
    .filter-chips { display: flex; gap: 8px; flex-wrap: wrap; padding: 0 0 4px; }
    .chip {
        padding: 5px 14px; border-radius: 99px;
        font-size: 12px; font-weight: 600; cursor: pointer;
        border: 1px solid #e2e8f0; background: #f8fafc;
        color: var(--text-muted); transition: all 0.15s; user-select: none;
    }
    .chip:hover, .chip.active {
        background: var(--primary, #6366f1);
        border-color: var(--primary, #6366f1); color: #fff;
    }
    tbody tr.hidden-row { display: none; }
    tbody tr:not(.hidden-row) { animation: rowIn 0.18s ease both; }
    @media print {
        body * { visibility: hidden; }
        #printableArea, #printableArea * { visibility: visible; }
        #printableArea { position: fixed; left: 0; top: 0; width: 100%; height: auto; border: none; box-shadow: none; }
        .no-print { display: none !important; }
    }

    /* ── Page header: wraps so the buttons never get pushed off-screen ── */
    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 16px;
        margin-bottom: 30px;
    }
    .header-actions {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }
    .header-actions .btn-modern {
        white-space: nowrap;
        display: inline-flex;
        align-items: center;
        gap: 8px;
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
    .main-content > .card:nth-of-type(1) { animation-delay: 0.05s; }
    .main-content > .card:nth-of-type(2) { animation-delay: 0.15s; }
    @media (prefers-reduced-motion: reduce) {
        .nav-btn, .card { animation: none !important; opacity: 1 !important; transform: none !important; }
    }
    
/* Shared user account shortcut */
.sidebar-user { position:absolute; left:12px; right:12px; bottom:14px; display:flex; align-items:center; gap:10px; padding:12px; border:1px solid #E8EAF0; border-radius:14px; text-decoration:none; color:#17213C; background:#fff; transition:background .2s,border-color .2s,transform .2s; z-index:5; }
.sidebar-user:hover { background:#F6F5FF; border-color:#C9C7FF; transform:translateY(-2px); }
.sidebar-user-avatar { width:38px; height:38px; flex:0 0 38px; border-radius:50%; display:flex; align-items:center; justify-content:center; background:rgba(99,102,241,.12); color:#6366F1; font-weight:700; font-size:14px; }
.sidebar-user-details { min-width:0; }
.sidebar-user-name { display:block; font-size:13px; font-weight:600; overflow:hidden; white-space:nowrap; text-overflow:ellipsis; }
.sidebar-user-caption { display:block; font-size:11px; color:#8B8D97; margin-top:3px; }
.sidebar-user-arrow { margin-left:auto; color:#8B8D97; font-size:12px; }
@media(max-width:800px) { .sidebar-user { position:static; margin-top:20px; } }


/* ── Example download buttons (Excel / CSV / TXT) ── */
.dl-btn { display:inline-flex; align-items:center; gap:7px; padding:7px 14px; border-radius:8px; font-size:12px; font-weight:700; color:#fff !important; text-decoration:none; line-height:1.3; transition:filter .15s, transform .15s; }
.dl-btn:hover { filter:brightness(1.08); transform:translateY(-1px); }
.dl-excel { background:#16a34a; }
.dl-csv   { background:#2563eb; }
.dl-txt   { background:#7c3aed; }

/* ── New Student modal form ── */
.ns-grid { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
.ns-grid .full { grid-column:1 / -1; }
.ns-field label { display:block; font-size:11px; font-weight:600; color:#64748b; margin-bottom:5px; }
.ns-field input, .ns-field select {
    width:100%; padding:11px 13px; border:1.5px solid #e2e8f0; border-radius:10px;
    font-size:13px; font-family:'Poppins',sans-serif; color:#1e293b; background:#fbfcfe; outline:none;
    transition:border-color .2s, box-shadow .2s;
}
.ns-field input:focus, .ns-field select:focus { border-color:var(--primary); box-shadow:0 0 0 3px rgba(99,102,241,.12); background:#fff; }
@media(max-width:520px) { .ns-grid { grid-template-columns:1fr; } }
</style>
</head>
<body>

    <!-- SIDEBAR -->
    <div class="sidebar">
        <h2 style="color: var(--primary); margin-bottom: 40px; padding-left: 20px;">
            <i class="fas fa-book-reader"></i> LibAdmin
        </h2>
        <div class="nav-container">
            <a href="dashboard.php" class="nav-btn"><i class="fas fa-house"></i> Home</a>
            <!-- <a href="admin.php"           class="nav-btn"><i class="fas fa-chart-line"></i> Dashboard</a> -->
            <a href="manage_students.php" class="nav-btn active"><i class="fas fa-users"></i> Students</a>
            <a href="voucher_management.php" class="nav-btn"><i class="fas fa-ticket"></i> Vouchers</a>
            <a href="activity_logs.php"   class="nav-btn"><i class="fas fa-history"></i> Logs</a>
            <a href="analytics.php"       class="nav-btn"><i class="fas fa-brain"></i> Analysis</a>
            <hr style="border:0;border-top:1px solid #eee;margin:20px 10px;">
            <a href="guide.php" class="nav-btn"><i class="fas fa-circle-question"></i> System Guide</a>
            <a href="logout.php" class="nav-btn" style="color:#ff4d4d;"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    
    <?php
    $sidebarName = 'My Account';
    $sidebarInitials = 'U';
    if (isset($_SESSION['email'])) {
        $sidebarDb = new mysqli('localhost', 'root', '', 'login');
        if (!$sidebarDb->connect_error) {
            $sidebarStmt = $sidebarDb->prepare('SELECT firstName, lastName FROM users WHERE email = ? LIMIT 1');
            if ($sidebarStmt) {
                $sidebarStmt->bind_param('s', $_SESSION['email']);
                $sidebarStmt->execute();
                $sidebarUser = $sidebarStmt->get_result()->fetch_assoc();
                if ($sidebarUser) {
                    $sidebarName = trim($sidebarUser['firstName'] . ' ' . $sidebarUser['lastName']);
                    $sidebarInitials = strtoupper(substr($sidebarUser['firstName'], 0, 1) . substr($sidebarUser['lastName'], 0, 1));
                }
                $sidebarStmt->close();
            }
            $sidebarDb->close();
        }
    }
    ?>
    <a href="profile.php" class="sidebar-user" title="Manage your account">
        <span class="sidebar-user-avatar"><?= htmlspecialchars($sidebarInitials) ?></span>
        <span class="sidebar-user-details">
            <span class="sidebar-user-name"><?= htmlspecialchars($sidebarName) ?></span>
            <span class="sidebar-user-caption">Manage account</span>
        </span>
        <i class="fas fa-chevron-right sidebar-user-arrow"></i>
    </a>
</div>

    <!-- MAIN -->
    <div class="main-content">

        <!-- Header -->
        <header class="page-header">
            <div>
                <h1 style="font-weight:800;">Student Registry</h1>
                <p style="color:var(--text-muted);">View and manage authorized library users.</p>
            </div>

            <!-- ★ THE BUTTONS ARE HERE (top-right of the page) ★ -->
            <div class="header-actions">

                <!-- Import Students -->
                <button class="btn-modern" style="background:#0ea5e9;border:none;cursor:pointer;" onclick="openStudentImportModal()">
                    <i class="fas fa-users"></i> Import Students
                </button>
                <input type="file" id="studentImportFileInput"
                    accept=".csv,.txt,.xlsx,.xls"
                    style="display:none"
                    onchange="handleStudentImportFile(this)">

                <!-- Import Vouchers -->
                <button class="btn-modern" style="background:#8b5cf6;border:none;cursor:pointer;" onclick="openVoucherModal()">
                    <i class="fas fa-ticket"></i> Import Vouchers
                </button>
                <input type="file" id="voucherFileInput"
                    accept=".csv,.txt,.xlsx,.xls"
                    style="display:none"
                    onchange="handleVoucherFile(this)">

                <!-- New Student -->
                <button type="button" class="btn-modern" style="border:none;cursor:pointer;" onclick="openNewStudentModal()">
                    <i class="fas fa-plus"></i> New Student
                </button>
            </div>
        </header>

        <!-- Search bar card -->
        <div class="card" style="padding:20px;display:flex;flex-direction:column;gap:12px;">
            <div style="display:flex;gap:12px;align-items:center;">
                <div class="search-wrapper">
                    <i class="fas fa-search"></i>
                    <input type="text" id="studentSearch"
                        placeholder="Search by name, ID, or email…"
                        onkeyup="filterStudents()"
                        autocomplete="off" spellcheck="false">
                    <button id="clear-search" onclick="clearSearch()" title="Clear search">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <span id="search-count"></span>
            </div>
            <div class="filter-chips" id="filter-chips">
                <span class="chip active" onclick="setChip(this, 'all')">All</span>
                <span class="chip" onclick="setChip(this, 'name')">Name</span>
                <span class="chip" onclick="setChip(this, 'id')">Student ID</span>
                <span class="chip" onclick="setChip(this, 'email')">Email</span>
            </div>
        </div>

        <!-- Table card -->
        <div class="card">
            <table id="studentTable">
                <thead>
                    <tr>
                        <th>Student ID</th>
                        <th>Full Name</th>
                        <th>Course</th>
                        <th>Year</th>
                        <th>Section</th>
                        <th>Email Address</th>
                        <th style="text-align:right;">Actions</th>
                    </tr>
                </thead>
                <tbody id="studentTableBody">
                    <?php
                    $sql    = "SELECT * FROM students WHERE isActive=1 ORDER BY id DESC";
                    $result = $conn->query($sql);
                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            $sid     = htmlspecialchars($row['student_id']);
                            $name    = htmlspecialchars($row['fullname']);
                            $email   = htmlspecialchars($row['email']);
                            $course  = htmlspecialchars($row['course']);
                            $year    = htmlspecialchars($row['year']);
                            $section = htmlspecialchars($row['section']);
                            $id      = $row['id'];
                            echo "
                            <tr data-id='$sid' data-name='$name' data-email='$email'>
                                <td class='cell-id' style='font-family:monospace;font-weight:700;color:var(--primary);'>$sid</td>
                                <td class='cell-name' style='font-weight:600;'>$name</td>
                                <td class='cell-course' style='color:var(--text-muted);'>$course</td>
                                <td class='cell-year' style='color:var(--text-muted);'>$year</td>
                                <td class='cell-section' style='color:var(--text-muted);'>$section</td>
                                <td class='cell-email' style='color:var(--text-muted);'>$email</td>
                                <td style='text-align:right;'>
                                    <div style='display:flex;gap:8px;justify-content:flex-end;'>
                                        <button onclick=\"showQR('$sid', '$name')\"
                                            style='border:none;background:rgba(108,99,255,0.1);color:var(--primary);padding:8px 12px;border-radius:8px;cursor:pointer;'
                                            title='View QR Pass'>
                                            <i class='fas fa-qrcode'></i>
                                        </button>
                                        <button class='btn-edit'
                                            style='border:none;background:#eef2ff;color:var(--primary);padding:8px 12px;border-radius:8px;cursor:pointer;'>
                                            <i class='fas fa-edit'></i>
                                        </button>
                                        <a href='manage_students.php?delete_id=$id'
                                            onclick=\"return confirm('Delete this student?')\"
                                            style='background:#fef2f2;color:#ef4444;padding:8px 12px;border-radius:8px;text-decoration:none;display:inline-flex;align-items:center;'>
                                            <i class='fas fa-trash'></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>";
                        }
                    }
                    ?>
                    <tr id="no-results">
                        <td colspan="7">
                            <i class="fas fa-search" style="font-size:28px;margin-bottom:10px;display:block;opacity:0.3;"></i>
                            No students match <strong id="no-results-query"></strong>
                        </td>
                    </tr>
                </tbody>
            </table>

            <!-- QR Modal -->
            <div id="qrModal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.6);z-index:9999;justify-content:center;align-items:center;">
                <div id="printableArea" style="background:white;padding:30px;border-radius:20px;width:320px;text-align:center;position:relative;">
                    <h2 style="color:var(--primary);margin-bottom:5px;">Student Pass</h2>
                    <p style="font-size:12px;color:#666;margin-bottom:15px;">Official Library QR Code</p>
                    <div style="background:#f9f9f9;padding:15px;border-radius:15px;border:2px dashed #ddd;margin-bottom:15px;">
                        <img id="qrTarget" src="" style="width:200px;height:200px;display:block;margin:0 auto;">
                    </div>
                    <h3 id="targetName" style="margin:5px 0;"></h3>
                    <p id="targetID" style="font-family:monospace;font-weight:700;color:var(--primary);"></p>
                    <div style="margin-top:20px;display:flex;gap:10px;" class="no-print">
                        <button onclick="window.print()" style="flex:1;background:var(--primary);color:white;border:none;padding:10px;border-radius:8px;cursor:pointer;">
                            <i class="fas fa-print"></i> Print
                        </button>
                        <button onclick="document.getElementById('qrModal').style.display='none'" style="flex:1;background:#eee;border:none;padding:10px;border-radius:8px;cursor:pointer;">
                            Close
                        </button>
                    </div>
                </div>
            </div>

            <!-- Total count footer -->
            <div style="padding:12px 0 0;border-top:1px solid #e2e8f0;margin-top:12px;font-size:13px;color:var(--text-muted);">
                Showing <strong id="visible-count">0</strong> of
                <strong id="total-count">0</strong> students
            </div>
        </div>

    </div>

    <!-- ══════════════════════════════════════════
         New Student Modal  (form was moved here from admin.php)
    ══════════════════════════════════════════ -->
    <div id="newStudentModal" style="
        display:none;position:fixed;inset:0;
        background:rgba(15,17,28,0.55);backdrop-filter:blur(5px);
        z-index:9999;justify-content:center;align-items:center;padding:16px;">
        <div style="
            background:#fff;border-radius:20px;width:500px;max-width:100%;max-height:92vh;overflow-y:auto;
            padding:32px;box-shadow:0 24px 60px rgba(0,0,0,0.18);
            animation:viSlideUp 0.22s ease;font-family:'Poppins',sans-serif;">

            <!-- Modal Header -->
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:22px;">
                <div style="display:flex;align-items:center;gap:10px;">
                    <div style="width:38px;height:38px;border-radius:10px;background:rgba(99,102,241,0.1);display:flex;align-items:center;justify-content:center;color:#6366f1;font-size:16px;">
                        <i class="fas fa-user-plus"></i>
                    </div>
                    <div>
                        <div style="font-weight:700;font-size:16px;color:#1e293b;">Register New Student</div>
                        <div style="font-size:11px;color:#64748b;">A QR pass is created automatically</div>
                    </div>
                </div>
                <button type="button" onclick="closeNewStudentModal()" style="background:none;border:none;cursor:pointer;color:#94a3b8;font-size:18px;padding:4px 6px;border-radius:6px;transition:all 0.15s;"
                    onmouseenter="this.style.color='#ef4444'" onmouseleave="this.style.color='#94a3b8'" aria-label="Close">
                    <i class="fas fa-xmark"></i>
                </button>
            </div>

            <form action="add_student.php" method="POST" id="newStudentForm">
                <div class="ns-grid">
                    <div class="ns-field">
                        <label for="nsFname">First Name</label>
                        <input type="text" name="Fname" id="nsFname" placeholder="First Name" required>
                    </div>
                    <div class="ns-field">
                        <label for="nsLname">Last Name</label>
                        <input type="text" name="Lname" id="nsLname" placeholder="Last Name" required>
                    </div>

                    <div class="ns-field">
                        <label for="nsCourse">Course</label>
                        <select name="course" id="nsCourse" required>
                            <option value="" disabled selected>Select Course</option>
                            <option value="BSCS">BSCS</option>
                            <option value="BSOA">BSOA</option>
                            <option value="BEEd">BEEd</option>
                            <option value="BSED">BSED</option>
                            <option value="BPED">BPED</option>
                            <option value="BSAIS">BSAIS</option>
                            <option value="BSA">BSA</option>
                            <option value="BSBA">BSBA</option>
                        </select>
                    </div>
                    <div class="ns-field">
                        <label for="nsYear">Year</label>
                        <select name="year" id="nsYear" required>
                            <option value="" disabled selected>Select Year</option>
                            <option value="1st Year">1st Year</option>
                            <option value="2nd Year">2nd Year</option>
                            <option value="3rd Year">3rd Year</option>
                            <option value="4th Year">4th Year</option>
                        </select>
                    </div>

                    <div class="ns-field">
                        <label for="nsSection">Section</label>
                        <input type="text" name="section" id="nsSection" placeholder="Section" required>
                    </div>
                    <div class="ns-field">
                        <label for="nsSid">Student ID</label>
                        <input type="text" name="student_id" id="nsSid" placeholder="e.g. AB-C123456" required>
                    </div>

                    <div class="ns-field full">
                        <label for="nsEmail">Institutional Email</label>
                        <input type="email" name="email" id="nsEmail" placeholder="Institutional Email" required>
                    </div>
                </div>

                <div style="display:flex;gap:10px;margin-top:22px;justify-content:flex-end;">
                    <button type="button" onclick="closeNewStudentModal()" style="background:#f1f5f9;color:#64748b;border:none;padding:10px 20px;border-radius:10px;font-family:'Poppins',sans-serif;font-size:13px;font-weight:600;cursor:pointer;">
                        Cancel
                    </button>
                    <button type="submit" style="background:#6366f1;color:#fff;border:none;padding:10px 24px;border-radius:10px;font-family:'Poppins',sans-serif;font-size:13px;font-weight:600;cursor:pointer;display:inline-flex;align-items:center;gap:8px;box-shadow:0 4px 14px rgba(99,102,241,0.25);">
                        <i class="fas fa-user-plus"></i> Register Now
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ══════════════════════════════════════════
         Import Students Modal
    ══════════════════════════════════════════ -->
    <div id="studentImportModal" style="
        display:none;position:fixed;inset:0;
        background:rgba(15,17,28,0.55);backdrop-filter:blur(5px);
        z-index:9999;justify-content:center;align-items:center;">
        <div style="
            background:#fff;border-radius:20px;width:460px;max-width:95vw;
            padding:32px;box-shadow:0 24px 60px rgba(0,0,0,0.18);
            animation:viSlideUp 0.22s ease;font-family:'Poppins',sans-serif;">

            <!-- Modal Header -->
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:22px;">
                <div style="display:flex;align-items:center;gap:10px;">
                    <div style="width:38px;height:38px;border-radius:10px;background:rgba(14,165,233,0.1);display:flex;align-items:center;justify-content:center;color:#0ea5e9;font-size:16px;">
                        <i class="fas fa-users"></i>
                    </div>
                    <div>
                        <div style="font-weight:700;font-size:16px;color:#1e293b;">Import Student List</div>
                        <div style="font-size:11px;color:#64748b;">CSV, TXT or Excel · student_id, fullname, email, course, year, section</div>
                    </div>
                </div>
                <button onclick="closeStudentImportModal()" style="background:none;border:none;cursor:pointer;color:#94a3b8;font-size:18px;padding:4px 6px;border-radius:6px;transition:all 0.15s;"
                    onmouseenter="this.style.color='#ef4444'" onmouseleave="this.style.color='#94a3b8'">
                    <i class="fas fa-xmark"></i>
                </button>
            </div>

            <!-- Drop Zone -->
            <div id="siDropZone"
                onclick="document.getElementById('studentImportFileInput').click()"
                ondragover="siDragOver(event)"
                ondragleave="siDragLeave(event)"
                ondrop="siDrop(event)"
                style="border:2px dashed #e2e8f0;border-radius:14px;padding:32px 20px;text-align:center;cursor:pointer;transition:all 0.2s;background:#fafbff;">
                <i class="fas fa-cloud-arrow-up" style="font-size:34px;color:#0ea5e9;margin-bottom:10px;display:block;opacity:0.7;"></i>
                <div style="font-weight:600;font-size:14px;color:#334155;margin-bottom:4px;">Drop file here or click to browse</div>
                <div style="font-size:12px;color:#94a3b8;">Accepts .csv · .txt · .xlsx (Excel)</div>
                <div id="siFileTag" style="display:none;margin-top:12px;background:rgba(14,165,233,0.08);border:1px solid rgba(14,165,233,0.25);color:#0ea5e9;font-size:12px;padding:5px 12px;border-radius:6px;font-family:monospace;word-break:break-all;"></div>
            </div>

            <!-- Format hint -->
            <div style="margin-top:14px;background:#eff6ff;border:1px solid #bfdbfe;border-radius:10px;padding:10px 14px;font-size:12px;color:#1e40af;line-height:1.8;max-height:280px;overflow-y:auto;">
                <strong style="display:block;margin-bottom:4px;"><i class="fas fa-circle-info"></i> Required Columns (header row)</strong>
                <code style="background:rgba(0,0,0,0.06);padding:2px 6px;border-radius:4px;">student_id, fullname, email, course, year, section</code><br><br>
                Header row is required and must match exactly (case-insensitive).<br>
                <strong>Excel:</strong> type these column titles in row 1 of the first sheet, then save as <code style="background:rgba(0,0,0,0.06);padding:1px 5px;border-radius:4px;">.xlsx</code>.<br>
                Duplicate student IDs will be skipped automatically.
                <div style="background:#fff;border:1px solid #bfdbfe;border-radius:10px;padding:10px 12px;margin-top:10px;">
                    <strong style="display:block;color:#15803d;font-size:13px;margin-bottom:4px;"><i class="fas fa-file-excel"></i> Example 1 &mdash; Excel file (6 students)</strong>
                    <div style="overflow-x:auto;margin:8px 0;"><table style="border-collapse:collapse;font-size:11px;color:#1e293b;background:#fff;"><tr><td style="border:1px solid #bfdbfe;padding:3px 7px;font-weight:700;background:#dbeafe;white-space:nowrap;">student_id</td><td style="border:1px solid #bfdbfe;padding:3px 7px;font-weight:700;background:#dbeafe;white-space:nowrap;">fullname</td><td style="border:1px solid #bfdbfe;padding:3px 7px;font-weight:700;background:#dbeafe;white-space:nowrap;">email</td><td style="border:1px solid #bfdbfe;padding:3px 7px;font-weight:700;background:#dbeafe;white-space:nowrap;">course</td><td style="border:1px solid #bfdbfe;padding:3px 7px;font-weight:700;background:#dbeafe;white-space:nowrap;">year</td><td style="border:1px solid #bfdbfe;padding:3px 7px;font-weight:700;background:#dbeafe;white-space:nowrap;">section</td></tr><tr><td style="border:1px solid #bfdbfe;padding:3px 7px;white-space:nowrap;">AB-C100001</td><td style="border:1px solid #bfdbfe;padding:3px 7px;white-space:nowrap;">Juan Dela Cruz</td><td style="border:1px solid #bfdbfe;padding:3px 7px;white-space:nowrap;">juan.delacruz@school.edu.ph</td><td style="border:1px solid #bfdbfe;padding:3px 7px;white-space:nowrap;">BSCS</td><td style="border:1px solid #bfdbfe;padding:3px 7px;white-space:nowrap;">1st Year</td><td style="border:1px solid #bfdbfe;padding:3px 7px;white-space:nowrap;">A</td></tr><tr><td style="border:1px solid #bfdbfe;padding:3px 7px;white-space:nowrap;">AB-C100002</td><td style="border:1px solid #bfdbfe;padding:3px 7px;white-space:nowrap;">Maria Santos</td><td style="border:1px solid #bfdbfe;padding:3px 7px;white-space:nowrap;">maria.santos@school.edu.ph</td><td style="border:1px solid #bfdbfe;padding:3px 7px;white-space:nowrap;">BSED</td><td style="border:1px solid #bfdbfe;padding:3px 7px;white-space:nowrap;">2nd Year</td><td style="border:1px solid #bfdbfe;padding:3px 7px;white-space:nowrap;">B</td></tr><tr><td style="border:1px solid #bfdbfe;padding:3px 7px;white-space:nowrap;">AB-C100003</td><td style="border:1px solid #bfdbfe;padding:3px 7px;white-space:nowrap;">Jose Reyes</td><td style="border:1px solid #bfdbfe;padding:3px 7px;white-space:nowrap;">jose.reyes@school.edu.ph</td><td style="border:1px solid #bfdbfe;padding:3px 7px;white-space:nowrap;">BSBA</td><td style="border:1px solid #bfdbfe;padding:3px 7px;white-space:nowrap;">3rd Year</td><td style="border:1px solid #bfdbfe;padding:3px 7px;white-space:nowrap;">C</td></tr><tr><td style="border:1px solid #bfdbfe;padding:3px 7px;white-space:nowrap;">AB-C100004</td><td style="border:1px solid #bfdbfe;padding:3px 7px;white-space:nowrap;">Ana Garcia</td><td style="border:1px solid #bfdbfe;padding:3px 7px;white-space:nowrap;">ana.garcia@school.edu.ph</td><td style="border:1px solid #bfdbfe;padding:3px 7px;white-space:nowrap;">BSA</td><td style="border:1px solid #bfdbfe;padding:3px 7px;white-space:nowrap;">4th Year</td><td style="border:1px solid #bfdbfe;padding:3px 7px;white-space:nowrap;">A</td></tr><tr><td style="border:1px solid #bfdbfe;padding:3px 7px;white-space:nowrap;">AB-C100005</td><td style="border:1px solid #bfdbfe;padding:3px 7px;white-space:nowrap;">Mark Villanueva</td><td style="border:1px solid #bfdbfe;padding:3px 7px;white-space:nowrap;">mark.villanueva@school.edu.ph</td><td style="border:1px solid #bfdbfe;padding:3px 7px;white-space:nowrap;">BEEd</td><td style="border:1px solid #bfdbfe;padding:3px 7px;white-space:nowrap;">1st Year</td><td style="border:1px solid #bfdbfe;padding:3px 7px;white-space:nowrap;">D</td></tr><tr><td style="border:1px solid #bfdbfe;padding:3px 7px;white-space:nowrap;">AB-C100006</td><td style="border:1px solid #bfdbfe;padding:3px 7px;white-space:nowrap;">Liza Mendoza</td><td style="border:1px solid #bfdbfe;padding:3px 7px;white-space:nowrap;">liza.mendoza@school.edu.ph</td><td style="border:1px solid #bfdbfe;padding:3px 7px;white-space:nowrap;">BSOA</td><td style="border:1px solid #bfdbfe;padding:3px 7px;white-space:nowrap;">2nd Year</td><td style="border:1px solid #bfdbfe;padding:3px 7px;white-space:nowrap;">B</td></tr></table></div>
                    <a href="data:application/vnd.openxmlformats-officedocument.spreadsheetml.sheet;base64,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" download="students_example.xlsx" class="dl-btn dl-excel"><i class="fas fa-download"></i> Download example Excel</a>
                </div>

                <div style="background:#fff;border:1px solid #bfdbfe;border-radius:10px;padding:10px 12px;margin-top:10px;">
                    <strong style="display:block;color:#2563eb;font-size:13px;margin-bottom:4px;"><i class="fas fa-file-csv"></i> Example 2 &mdash; CSV file (with header row)</strong>
                    <div style="color:#475569;">Open <b>Notepad</b>, type the titles on the first line, then one student per line. Separate with <b>commas</b>. Save as <code>students.csv</code> (Save as type: <b>All files</b>).</div>
                    <div style="font-family:monospace;font-size:11px;background:#f8fafc;border:1px dashed #cbd5e1;border-radius:6px;padding:8px 10px;color:#1e293b;overflow-x:auto;white-space:nowrap;margin:8px 0;">student_id,fullname,email,course,year,section<br>AB-C100001,Juan Dela Cruz,juan.delacruz@school.edu.ph,BSCS,1st Year,A<br>AB-C100002,Maria Santos,maria.santos@school.edu.ph,BSED,2nd Year,B<br>AB-C100003,Jose Reyes,jose.reyes@school.edu.ph,BSBA,3rd Year,C<br>AB-C100004,Ana Garcia,ana.garcia@school.edu.ph,BSA,4th Year,A<br>AB-C100005,Mark Villanueva,mark.villanueva@school.edu.ph,BEEd,1st Year,D<br>AB-C100006,Liza Mendoza,liza.mendoza@school.edu.ph,BSOA,2nd Year,B</div>
                    <a href="data:text/csv;charset=utf-8,student_id%2Cfullname%2Cemail%2Ccourse%2Cyear%2Csection%0D%0AAB-C100001%2CJuan%20Dela%20Cruz%2Cjuan.delacruz%40school.edu.ph%2CBSCS%2C1st%20Year%2CA%0D%0AAB-C100002%2CMaria%20Santos%2Cmaria.santos%40school.edu.ph%2CBSED%2C2nd%20Year%2CB%0D%0AAB-C100003%2CJose%20Reyes%2Cjose.reyes%40school.edu.ph%2CBSBA%2C3rd%20Year%2CC%0D%0AAB-C100004%2CAna%20Garcia%2Cana.garcia%40school.edu.ph%2CBSA%2C4th%20Year%2CA%0D%0AAB-C100005%2CMark%20Villanueva%2Cmark.villanueva%40school.edu.ph%2CBEEd%2C1st%20Year%2CD%0D%0AAB-C100006%2CLiza%20Mendoza%2Cliza.mendoza%40school.edu.ph%2CBSOA%2C2nd%20Year%2CB%0D%0A" download="students_example.csv" class="dl-btn dl-csv"><i class="fas fa-download"></i> Download example CSV</a>
                </div>

                <div style="background:#fff;border:1px solid #bfdbfe;border-radius:10px;padding:10px 12px;margin-top:10px;">
                    <strong style="display:block;color:#7c3aed;font-size:13px;margin-bottom:4px;"><i class="fas fa-file-lines"></i> Example 3 &mdash; TXT file (no header row)</strong>
                    <div style="color:#475569;">One student per line, separated by <b>commas</b>, always in this order: <code>student_id, fullname, email, course, year, section</code>. Save as <code>students.txt</code>.</div>
                    <div style="font-family:monospace;font-size:11px;background:#f8fafc;border:1px dashed #cbd5e1;border-radius:6px;padding:8px 10px;color:#1e293b;overflow-x:auto;white-space:nowrap;margin:8px 0;">AB-C100001,Juan Dela Cruz,juan.delacruz@school.edu.ph,BSCS,1st Year,A<br>AB-C100002,Maria Santos,maria.santos@school.edu.ph,BSED,2nd Year,B<br>AB-C100003,Jose Reyes,jose.reyes@school.edu.ph,BSBA,3rd Year,C<br>AB-C100004,Ana Garcia,ana.garcia@school.edu.ph,BSA,4th Year,A<br>AB-C100005,Mark Villanueva,mark.villanueva@school.edu.ph,BEEd,1st Year,D<br>AB-C100006,Liza Mendoza,liza.mendoza@school.edu.ph,BSOA,2nd Year,B</div>
                    <a href="data:text/plain;charset=utf-8,AB-C100001%2CJuan%20Dela%20Cruz%2Cjuan.delacruz%40school.edu.ph%2CBSCS%2C1st%20Year%2CA%0D%0AAB-C100002%2CMaria%20Santos%2Cmaria.santos%40school.edu.ph%2CBSED%2C2nd%20Year%2CB%0D%0AAB-C100003%2CJose%20Reyes%2Cjose.reyes%40school.edu.ph%2CBSBA%2C3rd%20Year%2CC%0D%0AAB-C100004%2CAna%20Garcia%2Cana.garcia%40school.edu.ph%2CBSA%2C4th%20Year%2CA%0D%0AAB-C100005%2CMark%20Villanueva%2Cmark.villanueva%40school.edu.ph%2CBEEd%2C1st%20Year%2CD%0D%0AAB-C100006%2CLiza%20Mendoza%2Cliza.mendoza%40school.edu.ph%2CBSOA%2C2nd%20Year%2CB%0D%0A" download="students_example.txt" class="dl-btn dl-txt"><i class="fas fa-download"></i> Download example TXT</a>
                </div>

                <div style="margin-top:10px;color:#475569;">Replace the example students with your own before uploading.</div>
            </div>

            <!-- Progress bar -->
            <div id="siProgressWrap" style="display:none;margin-top:14px;background:#f1f5f9;border-radius:99px;height:5px;overflow:hidden;">
                <div id="siProgressBar" style="height:100%;width:0%;background:linear-gradient(90deg,#0ea5e9,#38bdf8);border-radius:99px;transition:width 0.35s ease;"></div>
            </div>

            <!-- Result banner -->
            <div id="siResult" style="display:none;margin-top:14px;border-radius:10px;padding:12px 14px;font-size:12px;font-family:monospace;line-height:1.7;white-space:pre-line;"></div>

            <!-- Actions -->
            <div style="display:flex;gap:10px;margin-top:20px;justify-content:flex-end;">
                <button onclick="closeStudentImportModal()" style="background:#f1f5f9;color:#64748b;border:none;padding:10px 20px;border-radius:10px;font-family:'Poppins',sans-serif;font-size:13px;font-weight:600;cursor:pointer;">
                    Cancel
                </button>
                <button id="siUploadBtn" onclick="siUpload()" disabled style="background:#0ea5e9;color:#fff;border:none;padding:10px 24px;border-radius:10px;font-family:'Poppins',sans-serif;font-size:13px;font-weight:600;cursor:pointer;display:inline-flex;align-items:center;gap:8px;opacity:0.45;box-shadow:0 4px 14px rgba(14,165,233,0.25);">
                    <div id="siSpinner" style="display:none;width:13px;height:13px;border:2px solid rgba(255,255,255,0.3);border-top-color:#fff;border-radius:50%;animation:viSpin 0.7s linear infinite;"></div>
                    <i class="fas fa-upload" id="siIcon"></i>
                    <span id="siLabel">Upload &amp; Import</span>
                </button>
            </div>
        </div>
    </div>

    <!-- ══════════════════════════════════════════
         Import Vouchers Modal  (drop file only)
    ══════════════════════════════════════════ -->
    <div id="voucherImportModal" style="
        display:none;position:fixed;inset:0;
        background:rgba(15,17,28,0.55);backdrop-filter:blur(5px);
        z-index:9999;justify-content:center;align-items:center;">
        <div style="
            background:#fff;border-radius:20px;width:460px;max-width:95vw;
            padding:32px;box-shadow:0 24px 60px rgba(0,0,0,0.18);
            animation:viSlideUp 0.22s ease;font-family:'Poppins',sans-serif;">

            <!-- Modal Header -->
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:22px;">
                <div style="display:flex;align-items:center;gap:10px;">
                    <div style="width:38px;height:38px;border-radius:10px;background:rgba(139,92,246,0.1);display:flex;align-items:center;justify-content:center;color:#8b5cf6;font-size:16px;">
                        <i class="fas fa-ticket"></i>
                    </div>
                    <div>
                        <div style="font-weight:700;font-size:16px;color:#1e293b;">Import Voucher Codes</div>
                        <div style="font-size:11px;color:#64748b;">CSV, TXT or Excel · one code per line</div>
                    </div>
                </div>
                <button onclick="closeVoucherModal()" style="background:none;border:none;cursor:pointer;color:#94a3b8;font-size:18px;padding:4px 6px;border-radius:6px;transition:all 0.15s;"
                    onmouseenter="this.style.color='#ef4444'" onmouseleave="this.style.color='#94a3b8'">
                    <i class="fas fa-xmark"></i>
                </button>
            </div>

            <!-- Drop Zone -->
            <div id="viDropZone"
                onclick="document.getElementById('voucherFileInput').click()"
                ondragover="viDragOver(event)"
                ondragleave="viDragLeave(event)"
                ondrop="viDrop(event)"
                style="border:2px dashed #e2e8f0;border-radius:14px;padding:32px 20px;text-align:center;cursor:pointer;transition:all 0.2s;background:#fafbff;">
                <i class="fas fa-cloud-arrow-up" style="font-size:34px;color:#8b5cf6;margin-bottom:10px;display:block;opacity:0.7;"></i>
                <div style="font-weight:600;font-size:14px;color:#334155;margin-bottom:4px;">Drop file here or click to browse</div>
                <div style="font-size:12px;color:#94a3b8;">Accepts .csv · .txt · .xlsx (Excel)</div>
                <div id="viFileTag" style="display:none;margin-top:12px;background:rgba(139,92,246,0.08);border:1px solid rgba(139,92,246,0.25);color:#7c3aed;font-size:12px;padding:5px 12px;border-radius:6px;font-family:monospace;word-break:break-all;"></div>
            </div>

<!-- Format hint -->
            <div style="margin-top:14px;background:#fffbeb;border:1px solid #fde68a;border-radius:10px;padding:12px 14px;font-size:12px;color:#92400e;line-height:1.7;max-height:280px;overflow-y:auto;">

                <strong style="display:block;margin-bottom:6px;font-size:13px;"><i class="fas fa-circle-info"></i> How to import vouchers</strong>
                <div style="margin-bottom:10px;">
                    Make a <b>TXT</b>, a <b>CSV</b> or an <b>Excel (.xlsx)</b> file with your codes, drag it into the box above, then click <b>Upload &amp; Save</b>.
                    TXT and CSV files are made in <b>Notepad</b>; Excel files are made in <b>Excel</b>.
                </div>

                <!-- EXAMPLE 1 : TXT -->
                <div style="background:#fff;border:1px solid #fcd34d;border-radius:10px;padding:10px 12px;margin-bottom:10px;">
                    <strong style="display:block;color:#7c3aed;font-size:13px;margin-bottom:6px;">
                        <i class="fas fa-file-lines"></i> Example 1 &mdash; TXT file (codes only)
                    </strong>
                    <ol style="margin:0 0 8px 18px;padding:0;">
                        <li>Open <b>Notepad</b>.</li>
                        <li>Type <b>one code on each line</b>. Press <b>Enter</b> after each code.</li>
                        <li>Click <b>File</b> &rarr; <b>Save As</b>, name it <code>vouchers.txt</code> and click <b>Save</b>.</li>
                        <li>Drag the file into the box above and click <b>Upload &amp; Save</b>.</li>
                    </ol>
                    <div style="font-family:monospace;background:#f8fafc;border:1px dashed #cbd5e1;border-radius:6px;padding:8px 12px;color:#1e293b;">
                        VCH-2026-101<br>
                        VCH-2026-102<br>
                        VCH-2026-103
                    </div>
                    <div style="color:#64748b;margin-top:6px;">Every voucher is saved as <b>active</b> with a time limit of <b>120 minutes</b> and no expiry date.</div>
                    <a href="data:text/plain;charset=utf-8,VCH-2026-101%0AVCH-2026-102%0AVCH-2026-103%0AVCH-2026-104%0AVCH-2026-105%0A" download="vouchers.txt" class="dl-btn dl-txt" style="margin-top:6px;"><i class="fas fa-download"></i> Download example TXT</a>
                </div>

                <!-- EXAMPLE 2 : CSV -->
                <div style="background:#fff;border:1px solid #fcd34d;border-radius:10px;padding:10px 12px;margin-bottom:10px;">
                    <strong style="display:block;color:#2563eb;font-size:13px;margin-bottom:6px;">
                        <i class="fas fa-file-csv"></i> Example 2 &mdash; CSV file (with time limit, expiry date and more)
                    </strong>
                    <ol style="margin:0 0 8px 18px;padding:0;">
                        <li>Open <b>Notepad</b>.</li>
                        <li>On the <b>first line</b>, type the column titles separated by commas.</li>
                        <li>On each next line, type one voucher. Separate the values with <b>commas</b> and <b>no spaces</b> around them.</li>
                        <li>To skip a value, type two commas in a row.</li>
                        <li>Click <b>File</b> &rarr; <b>Save As</b>. Choose <b>All files</b> in &ldquo;Save as type&rdquo;, name it <code>vouchers.csv</code> and click <b>Save</b>.</li>
                        <li>Drag the file into the box above and click <b>Upload &amp; Save</b>.</li>
                    </ol>
                    <div style="font-family:monospace;font-size:11px;background:#f8fafc;border:1px dashed #cbd5e1;border-radius:6px;padding:8px 10px;color:#1e293b;overflow-x:auto;white-space:nowrap;margin-bottom:8px;">
                        voucher_codes,status,created_date,used_date,expires_at,duration_minutes,notes<br>
                        VCH-2026-301,active,2026-11-10 09:00:00,,2026-12-31 23:59:00,60,1 hour pass<br>
                        VCH-2026-302,active,2026-11-10 09:00:00,,2026-12-31 23:59:00,120,2 hour pass<br>
                        VCH-2026-303,used,2026-11-01 08:00:00,2026-11-05 14:30:00,2027-01-15 23:59:00,180,Already used
                    </div>

                    <strong style="display:block;margin-bottom:4px;">What each column means</strong>
                    <table style="border-collapse:collapse;font-size:11px;color:#1e293b;width:100%;margin-bottom:6px;">
                        <tr style="background:#fef3c7;font-weight:700;">
                            <td style="border:1px solid #fcd34d;padding:3px 8px;">Column title</td>
                            <td style="border:1px solid #fcd34d;padding:3px 8px;">Meaning</td>
                            <td style="border:1px solid #fcd34d;padding:3px 8px;">If empty</td>
                        </tr>
                        <tr>
                            <td style="border:1px solid #fcd34d;padding:3px 8px;font-family:monospace;font-weight:700;">voucher_codes</td>
                            <td style="border:1px solid #fcd34d;padding:3px 8px;">The voucher code <b>(required)</b></td>
                            <td style="border:1px solid #fcd34d;padding:3px 8px;">&mdash;</td>
                        </tr>
                        <tr>
                            <td style="border:1px solid #fcd34d;padding:3px 8px;font-family:monospace;font-weight:700;">status</td>
                            <td style="border:1px solid #fcd34d;padding:3px 8px;">active, inactive or used</td>
                            <td style="border:1px solid #fcd34d;padding:3px 8px;">active</td>
                        </tr>
                        <tr>
                            <td style="border:1px solid #fcd34d;padding:3px 8px;font-family:monospace;font-weight:700;">created_date</td>
                            <td style="border:1px solid #fcd34d;padding:3px 8px;">When it was created</td>
                            <td style="border:1px solid #fcd34d;padding:3px 8px;">right now</td>
                        </tr>
                        <tr>
                            <td style="border:1px solid #fcd34d;padding:3px 8px;font-family:monospace;font-weight:700;">used_date</td>
                            <td style="border:1px solid #fcd34d;padding:3px 8px;">When it was used</td>
                            <td style="border:1px solid #fcd34d;padding:3px 8px;">empty</td>
                        </tr>
                        <tr>
                            <td style="border:1px solid #fcd34d;padding:3px 8px;font-family:monospace;font-weight:700;">expires_at</td>
                            <td style="border:1px solid #fcd34d;padding:3px 8px;">Last date and time it can be used</td>
                            <td style="border:1px solid #fcd34d;padding:3px 8px;">empty</td>
                        </tr>
                        <tr>
                            <td style="border:1px solid #fcd34d;padding:3px 8px;font-family:monospace;font-weight:700;">duration_minutes</td>
                            <td style="border:1px solid #fcd34d;padding:3px 8px;">Time limit in minutes</td>
                            <td style="border:1px solid #fcd34d;padding:3px 8px;">120</td>
                        </tr>
                        <tr>
                            <td style="border:1px solid #fcd34d;padding:3px 8px;font-family:monospace;font-weight:700;">notes</td>
                            <td style="border:1px solid #fcd34d;padding:3px 8px;">Any short note</td>
                            <td style="border:1px solid #fcd34d;padding:3px 8px;">empty</td>
                        </tr>
                    </table>
                    <div style="color:#64748b;">
                        Only <b>voucher_codes</b> is required. Write dates like <code>2026-12-31 23:59:00</code>.
                    </div>
                    <a href="data:text/csv;charset=utf-8,voucher_codes%2Cstatus%2Ccreated_date%2Cused_date%2Cexpires_at%2Cduration_minutes%2Cnotes%0AVCH-2026-301%2Cactive%2C2026-11-10%2009%3A00%3A00%2C%2C2026-12-31%2023%3A59%3A00%2C60%2C1%20hour%20pass%0AVCH-2026-302%2Cactive%2C2026-11-10%2009%3A00%3A00%2C%2C2026-12-31%2023%3A59%3A00%2C120%2C2%20hour%20pass%0AVCH-2026-303%2Cactive%2C2026-11-10%2009%3A00%3A00%2C%2C2027-01-15%2023%3A59%3A00%2C180%2C3%20hour%20pass%0AVCH-2026-304%2Cused%2C2026-11-01%2008%3A00%3A00%2C2026-11-05%2014%3A30%3A00%2C2026-12-31%2023%3A59%3A00%2C120%2CAlready%20used%0AVCH-2026-305%2Cinactive%2C2026-11-10%2009%3A00%3A00%2C%2C2027-02-28%2023%3A59%3A00%2C240%2CDisabled%20for%20now%0A" download="vouchers.csv" class="dl-btn dl-csv" style="margin-top:6px;"><i class="fas fa-download"></i> Download example CSV</a>
                </div>

                <!-- EXAMPLE 3 : EXCEL -->
                <div style="background:#fff;border:1px solid #fcd34d;border-radius:10px;padding:10px 12px;margin-bottom:10px;">
                    <strong style="display:block;color:#15803d;font-size:13px;margin-bottom:6px;">
                        <i class="fas fa-file-excel"></i> Example 3 &mdash; Excel file (.xlsx)
                    </strong>
                    <ol style="margin:0 0 8px 18px;padding:0;">
                        <li>Open <b>Excel</b>.</li>
                        <li>In <b>row 1</b>, type the same column titles as the CSV example (only <code>voucher_codes</code> is required).</li>
                        <li>Type one voucher per row underneath. Dates can be real Excel dates.</li>
                        <li>Click <b>File</b> &rarr; <b>Save As</b> and choose <b>Excel Workbook (.xlsx)</b>.</li>
                        <li>Drag the file into the box above and click <b>Upload &amp; Save</b>.</li>
                    </ol>

                    <div style="overflow-x:auto;margin:8px 0;"><table style="border-collapse:collapse;font-size:11px;color:#1e293b;background:#fff;"><tr><td style="border:1px solid #fcd34d;padding:3px 7px;font-weight:700;background:#fef3c7;white-space:nowrap;">voucher_codes</td><td style="border:1px solid #fcd34d;padding:3px 7px;font-weight:700;background:#fef3c7;white-space:nowrap;">status</td><td style="border:1px solid #fcd34d;padding:3px 7px;font-weight:700;background:#fef3c7;white-space:nowrap;">created_date</td><td style="border:1px solid #fcd34d;padding:3px 7px;font-weight:700;background:#fef3c7;white-space:nowrap;">used_date</td><td style="border:1px solid #fcd34d;padding:3px 7px;font-weight:700;background:#fef3c7;white-space:nowrap;">expires_at</td><td style="border:1px solid #fcd34d;padding:3px 7px;font-weight:700;background:#fef3c7;white-space:nowrap;">duration_minutes</td><td style="border:1px solid #fcd34d;padding:3px 7px;font-weight:700;background:#fef3c7;white-space:nowrap;">notes</td></tr><tr><td style="border:1px solid #fcd34d;padding:3px 7px;white-space:nowrap;">VCH-2026-401</td><td style="border:1px solid #fcd34d;padding:3px 7px;white-space:nowrap;">active</td><td style="border:1px solid #fcd34d;padding:3px 7px;white-space:nowrap;">2026-11-10 09:00:00</td><td style="border:1px solid #fcd34d;padding:3px 7px;white-space:nowrap;"></td><td style="border:1px solid #fcd34d;padding:3px 7px;white-space:nowrap;">2026-12-31 23:59:00</td><td style="border:1px solid #fcd34d;padding:3px 7px;white-space:nowrap;">60</td><td style="border:1px solid #fcd34d;padding:3px 7px;white-space:nowrap;">1 hour pass</td></tr><tr><td style="border:1px solid #fcd34d;padding:3px 7px;white-space:nowrap;">VCH-2026-402</td><td style="border:1px solid #fcd34d;padding:3px 7px;white-space:nowrap;">active</td><td style="border:1px solid #fcd34d;padding:3px 7px;white-space:nowrap;">2026-11-10 09:00:00</td><td style="border:1px solid #fcd34d;padding:3px 7px;white-space:nowrap;"></td><td style="border:1px solid #fcd34d;padding:3px 7px;white-space:nowrap;">2026-12-31 23:59:00</td><td style="border:1px solid #fcd34d;padding:3px 7px;white-space:nowrap;">120</td><td style="border:1px solid #fcd34d;padding:3px 7px;white-space:nowrap;">2 hour pass</td></tr><tr><td style="border:1px solid #fcd34d;padding:3px 7px;white-space:nowrap;">VCH-2026-403</td><td style="border:1px solid #fcd34d;padding:3px 7px;white-space:nowrap;">active</td><td style="border:1px solid #fcd34d;padding:3px 7px;white-space:nowrap;">2026-11-10 09:00:00</td><td style="border:1px solid #fcd34d;padding:3px 7px;white-space:nowrap;"></td><td style="border:1px solid #fcd34d;padding:3px 7px;white-space:nowrap;">2027-01-15 23:59:00</td><td style="border:1px solid #fcd34d;padding:3px 7px;white-space:nowrap;">180</td><td style="border:1px solid #fcd34d;padding:3px 7px;white-space:nowrap;">3 hour pass</td></tr><tr><td style="border:1px solid #fcd34d;padding:3px 7px;white-space:nowrap;">VCH-2026-404</td><td style="border:1px solid #fcd34d;padding:3px 7px;white-space:nowrap;">used</td><td style="border:1px solid #fcd34d;padding:3px 7px;white-space:nowrap;">2026-11-01 08:00:00</td><td style="border:1px solid #fcd34d;padding:3px 7px;white-space:nowrap;">2026-11-05 14:30:00</td><td style="border:1px solid #fcd34d;padding:3px 7px;white-space:nowrap;">2026-12-31 23:59:00</td><td style="border:1px solid #fcd34d;padding:3px 7px;white-space:nowrap;">120</td><td style="border:1px solid #fcd34d;padding:3px 7px;white-space:nowrap;">Already used</td></tr><tr><td style="border:1px solid #fcd34d;padding:3px 7px;white-space:nowrap;">VCH-2026-405</td><td style="border:1px solid #fcd34d;padding:3px 7px;white-space:nowrap;">inactive</td><td style="border:1px solid #fcd34d;padding:3px 7px;white-space:nowrap;">2026-11-10 09:00:00</td><td style="border:1px solid #fcd34d;padding:3px 7px;white-space:nowrap;"></td><td style="border:1px solid #fcd34d;padding:3px 7px;white-space:nowrap;">2027-02-28 23:59:00</td><td style="border:1px solid #fcd34d;padding:3px 7px;white-space:nowrap;">240</td><td style="border:1px solid #fcd34d;padding:3px 7px;white-space:nowrap;">Disabled for now</td></tr><tr><td style="border:1px solid #fcd34d;padding:3px 7px;white-space:nowrap;">VCH-2026-406</td><td style="border:1px solid #fcd34d;padding:3px 7px;white-space:nowrap;">active</td><td style="border:1px solid #fcd34d;padding:3px 7px;white-space:nowrap;"></td><td style="border:1px solid #fcd34d;padding:3px 7px;white-space:nowrap;"></td><td style="border:1px solid #fcd34d;padding:3px 7px;white-space:nowrap;"></td><td style="border:1px solid #fcd34d;padding:3px 7px;white-space:nowrap;"></td><td style="border:1px solid #fcd34d;padding:3px 7px;white-space:nowrap;">Only the code is required</td></tr></table></div>
                    <a href="data:application/vnd.openxmlformats-officedocument.spreadsheetml.sheet;base64,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" download="vouchers_example.xlsx" class="dl-btn dl-excel">
                        <i class="fas fa-download"></i> Download example Excel (6 vouchers)
                    </a>
                    <div style="color:#64748b;margin-top:6px;">Replace the example codes with your own before you upload.</div>
                    <div style="color:#64748b;margin-top:6px;">Only the <b>first sheet</b> is read. Old <code>.xls</code> files are not supported &mdash; save them as <code>.xlsx</code> first.</div>
                </div>

                <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:8px;padding:8px 10px;color:#991b1b;">
                    <b>Remember:</b> each code can be up to 20 characters, with no spaces. Codes already saved are skipped. Replace the example codes with your own before you upload.
                </div>
            </div>

            <!-- Progress bar -->
            <div id="viProgressWrap" style="display:none;margin-top:14px;background:#f1f5f9;border-radius:99px;height:5px;overflow:hidden;">
                <div id="viProgressBar" style="height:100%;width:0%;background:linear-gradient(90deg,#8b5cf6,#a78bfa);border-radius:99px;transition:width 0.35s ease;"></div>
            </div>

            <!-- Result banner -->
            <div id="viResult" style="display:none;margin-top:14px;border-radius:10px;padding:12px 14px;font-size:12px;font-family:monospace;line-height:1.7;white-space:pre-line;"></div>

            <!-- Actions -->
            <div style="display:flex;gap:10px;margin-top:20px;justify-content:flex-end;">
                <button onclick="closeVoucherModal()" style="background:#f1f5f9;color:#64748b;border:none;padding:10px 20px;border-radius:10px;font-family:'Poppins',sans-serif;font-size:13px;font-weight:600;cursor:pointer;">
                    Cancel
                </button>
                <button id="viUploadBtn" onclick="viUpload()" disabled style="text-decoration:none;background:#8b5cf6;color:#fff;border:none;padding:10px 24px;border-radius:10px;font-family:'Poppins',sans-serif;font-size:13px;font-weight:600;cursor:pointer;display:inline-flex;align-items:center;gap:8px;opacity:0.45;box-shadow:0 4px 14px rgba(139,92,246,0.25);">
                    <div id="viSpinner" style="display:none;width:13px;height:13px;border:2px solid rgba(255,255,255,0.3);border-top-color:#fff;border-radius:50%;animation:viSpin 0.7s linear infinite;"></div>
                    <i class="fas fa-upload" id="viIcon"></i>
                    <span id="viLabel">Upload &amp; Save</span>
                </button>
            </div>
        </div>
    </div>

    <script>

    /* ══════════════════════════════════════════════════
        NEW STUDENT MODAL
    ══════════════════════════════════════════════════ */
    function openNewStudentModal() {
        document.getElementById('newStudentForm').reset();
        document.getElementById('newStudentModal').style.display = 'flex';
        setTimeout(() => document.getElementById('nsFname').focus(), 50);
    }
    function closeNewStudentModal() {
        document.getElementById('newStudentModal').style.display = 'none';
    }
    document.getElementById('newStudentModal').addEventListener('click', function(e) {
        if (e.target === this) closeNewStudentModal();
    });
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closeNewStudentModal();
    });

    /* ══════════════════════════════════════════════════
        STUDENT IMPORT
    ══════════════════════════════════════════════════ */
    
    let siFile = null;

    function openStudentImportModal() {
        document.getElementById('studentImportModal').style.display = 'flex';
        siReset();
    }
    function closeStudentImportModal() {
        document.getElementById('studentImportModal').style.display = 'none';
    }
    document.getElementById('studentImportModal').addEventListener('click', function(e) {
        if (e.target === this) closeStudentImportModal();
    });

    function handleStudentImportFile(input) {
        if (input.files && input.files[0]) siSetFile(input.files[0]);
    }
    function siDragOver(e) {
        e.preventDefault();
        const dz = document.getElementById('siDropZone');
        dz.style.borderColor = '#0ea5e9';
        dz.style.background  = 'rgba(14,165,233,0.04)';
    }
    function siDragLeave(e) {
        const dz = document.getElementById('siDropZone');
        dz.style.borderColor = '#e2e8f0';
        dz.style.background  = '#fafbff';
    }
    function siDrop(e) {
        e.preventDefault();
        siDragLeave(e);
        if (e.dataTransfer.files.length) siSetFile(e.dataTransfer.files[0]);
    }
    function siSetFile(file) {
        const ext = file.name.split('.').pop().toLowerCase();
        if (ext === 'xls') {
            siShowResult('error', '❌ Old .xls files are not supported. Open it in Excel and use Save As → Excel Workbook (.xlsx), then upload that.');
            return;
        }
        if (!['csv', 'txt', 'xlsx'].includes(ext)) {
            siShowResult('error', '❌ Only .csv, .txt or .xlsx files are accepted.');
            return;
        }
        siFile = file;
        const tag = document.getElementById('siFileTag');
        tag.textContent = (ext === 'xlsx' ? '📊 ' : '📋 ') + file.name + ' (' + (file.size / 1024).toFixed(1) + ' KB)';
        tag.style.display = 'inline-block';
        const btn = document.getElementById('siUploadBtn');
        btn.disabled = false;
        btn.style.opacity = '1';
        document.getElementById('siResult').style.display = 'none';
    }
    async function siUpload() {
        if (!siFile) return;
        siSetLoading(true);
        siAnimateProgress();
        const fd = new FormData();
        fd.append('student_file', siFile);
        try {
            const res  = await fetch('import_students.php', { method: 'POST', body: fd });
            const data = await res.json();
            siFinishProgress();
            if (data.success) {
                let msg = '✅ ' + data.message;
                if (data.skipped && data.skipped.length > 0) {
                    msg += '\n\n⚠️ Skipped (duplicate IDs):\n' + data.skipped.join(', ');
                }
                siShowResult('success', msg);
                siFile = null;
                document.getElementById('siFileTag').style.display = 'none';
                document.getElementById('studentImportFileInput').value = '';
                const btn = document.getElementById('siUploadBtn');
                btn.disabled = true;
                btn.style.opacity = '0.45';
                setTimeout(() => location.reload(), 1500);
            } else {
                siShowResult('error', '❌ ' + data.message);
            }
        } catch(err) {
            siFinishProgress();
            siShowResult('error', '❌ Network error. Make sure import_students.php and spreadsheet_reader.php are in the same folder.');
        }
        siSetLoading(false);
    }
    function siSetLoading(on) {
        document.getElementById('siSpinner').style.display = on ? 'block' : 'none';
        document.getElementById('siIcon').style.display    = on ? 'none'  : 'inline';
        document.getElementById('siLabel').textContent     = on ? 'Importing…' : 'Upload & Import';
        document.getElementById('siUploadBtn').disabled    = on;
    }
    let siProgressTimer;
    function siAnimateProgress() {
        const wrap = document.getElementById('siProgressWrap');
        const bar  = document.getElementById('siProgressBar');
        wrap.style.display = 'block'; bar.style.width = '0%';
        let w = 0;
        siProgressTimer = setInterval(() => {
            w = Math.min(w + Math.random() * 9, 88);
            bar.style.width = w + '%';
        }, 120);
    }
    function siFinishProgress() {
        clearInterval(siProgressTimer);
        const bar = document.getElementById('siProgressBar');
        bar.style.width = '100%';
        setTimeout(() => {
            document.getElementById('siProgressWrap').style.display = 'none';
            bar.style.width = '0%';
        }, 500);
    }
    function siShowResult(type, msg) {
        const el = document.getElementById('siResult');
        el.style.display = 'block';
        if (type === 'success') {
            el.style.background = '#f0fdf4'; el.style.border = '1px solid #bbf7d0'; el.style.color = '#166534';
        } else {
            el.style.background = '#fef2f2'; el.style.border = '1px solid #fecaca'; el.style.color = '#991b1b';
        }
        el.textContent = msg;
    }
    function siReset() {
        siFile = null;
        document.getElementById('studentImportFileInput').value = '';
        document.getElementById('siFileTag').style.display = 'none';
        document.getElementById('siResult').style.display  = 'none';
        document.getElementById('siProgressWrap').style.display = 'none';
        const btn = document.getElementById('siUploadBtn');
        btn.disabled = true; btn.style.opacity = '0.45';
        siSetLoading(false);
        const dz = document.getElementById('siDropZone');
        dz.style.borderColor = '#e2e8f0'; dz.style.background = '#fafbff';
    }

    /* ══════════════════════════════════════════════════
       VOUCHER IMPORT  (drop file only — no expiration)
    ══════════════════════════════════════════════════ */
    let viFile = null;

    function openVoucherModal() {
        document.getElementById('voucherImportModal').style.display = 'flex';
        viReset();
    }
    function closeVoucherModal() {
        document.getElementById('voucherImportModal').style.display = 'none';
    }
    document.getElementById('voucherImportModal').addEventListener('click', function(e) {
        if (e.target === this) closeVoucherModal();
    });

    function handleVoucherFile(input) {
        if (input.files && input.files[0]) viSetFile(input.files[0]);
    }
    function viDragOver(e) {
        e.preventDefault();
        const dz = document.getElementById('viDropZone');
        dz.style.borderColor = '#8b5cf6';
        dz.style.background  = 'rgba(139,92,246,0.05)';
    }
    function viDragLeave(e) {
        const dz = document.getElementById('viDropZone');
        dz.style.borderColor = '#e2e8f0';
        dz.style.background  = '#fafbff';
    }
    function viDrop(e) {
        e.preventDefault();
        viDragLeave(e);
        if (e.dataTransfer.files.length) viSetFile(e.dataTransfer.files[0]);
    }
    function viSetFile(file) {
        const ext = file.name.split('.').pop().toLowerCase();
        if (ext === 'xls') {
            viShowResult('error', '❌ Old .xls files are not supported. Open it in Excel and use Save As → Excel Workbook (.xlsx), then upload that.');
            return;
        }
        if (!['csv', 'txt', 'xlsx'].includes(ext)) {
            viShowResult('error', '❌ Only .csv, .txt or .xlsx files are accepted.');
            return;
        }
        viFile = file;
        const icons = { csv: '📋', txt: '📄', xlsx: '📊' };
        const tag = document.getElementById('viFileTag');
        tag.textContent = (icons[ext] || '📎') + ' ' + file.name + ' (' + (file.size / 1024).toFixed(1) + ' KB)';
        tag.style.display = 'inline-block';
        const btn = document.getElementById('viUploadBtn');
        btn.disabled = false;
        btn.style.opacity = '1';
        document.getElementById('viResult').style.display = 'none';
    }
    async function viUpload() {
        if (!viFile) return;
        viSetLoading(true);
        viAnimateProgress();

        const fd = new FormData();
        fd.append('voucher_file', viFile);

        try {
            const res  = await fetch('import_vouchers.php', { method: 'POST', body: fd });
            const text = await res.text();
            let data;
            try {
                data = JSON.parse(text);
            } catch (e) {
                viFinishProgress();
                viShowResult('error', '❌ import_vouchers.php did not return valid JSON:\n\n' + text.substring(0, 400));
                viSetLoading(false);
                return;
            }
            viFinishProgress();

            if (data.success) {
                let msg = '✅ ' + data.message;
                if (data.duplicates && data.duplicates.length > 0) {
                    msg += '\n\nAlready in database:\n' + data.duplicates.join(', ');
                }
                if (data.invalid && data.invalid.length > 0) {
                    msg += '\n\n⚠️ Invalid codes skipped:\n' + data.invalid.join(', ');
                }
                viShowResult('success', msg);
                viFile = null;
                document.getElementById('viFileTag').style.display = 'none';
                document.getElementById('voucherFileInput').value  = '';
            } else {
                viShowResult('error', '❌ ' + data.message);
            }
        } catch(err) {
            viFinishProgress();
            viShowResult('error', '❌ Network error. Make sure import_vouchers.php and spreadsheet_reader.php are in the same folder.');
        }
        viSetLoading(false);
    }
    function viSetLoading(on) {
        document.getElementById('viSpinner').style.display = on ? 'block' : 'none';
        document.getElementById('viIcon').style.display    = on ? 'none'  : 'inline';
        document.getElementById('viLabel').textContent     = on ? 'Uploading…' : 'Upload & Save';
        const btn = document.getElementById('viUploadBtn');
        btn.disabled      = on || !viFile;
        btn.style.opacity = btn.disabled ? '0.45' : '1';
    }
    let viProgressTimer;
    function viAnimateProgress() {
        const wrap = document.getElementById('viProgressWrap');
        const bar  = document.getElementById('viProgressBar');
        wrap.style.display = 'block'; bar.style.width = '0%';
        let w = 0;
        viProgressTimer = setInterval(() => {
            w = Math.min(w + Math.random() * 9, 88);
            bar.style.width = w + '%';
        }, 120);
    }
    function viFinishProgress() {
        clearInterval(viProgressTimer);
        const bar = document.getElementById('viProgressBar');
        bar.style.width = '100%';
        setTimeout(() => {
            document.getElementById('viProgressWrap').style.display = 'none';
            bar.style.width = '0%';
        }, 500);
    }
    function viShowResult(type, msg) {
        const el = document.getElementById('viResult');
        el.style.display = 'block';
        if (type === 'success') {
            el.style.background = '#f0fdf4';
            el.style.border     = '1px solid #bbf7d0';
            el.style.color      = '#166534';
        } else {
            el.style.background = '#fef2f2';
            el.style.border     = '1px solid #fecaca';
            el.style.color      = '#991b1b';
        }
        el.textContent = msg;
    }
    function viReset() {
        viFile = null;
        document.getElementById('voucherFileInput').value  = '';
        document.getElementById('viFileTag').style.display = 'none';
        document.getElementById('viResult').style.display  = 'none';
        document.getElementById('viProgressWrap').style.display = 'none';
        viSetLoading(false);
        const dz = document.getElementById('viDropZone');
        dz.style.borderColor = '#e2e8f0';
        dz.style.background  = '#fafbff';
    }

    /* ══════════════════════════════════════════════════
       QR CODE MODAL
    ══════════════════════════════════════════════════ */
    function showQR(sid, name) {
        document.getElementById('qrTarget').src           = `qrcodes/${sid}.png`;
        document.getElementById('targetName').textContent = name;
        document.getElementById('targetID').textContent   = sid;
        document.getElementById('qrModal').style.display  = 'flex';
    }

    /* ══════════════════════════════════════════════════
       LIVE SEARCH
    ══════════════════════════════════════════════════ */
    let searchField = 'all';

    const input      = document.getElementById('studentSearch');
    const clearBtn   = document.getElementById('clear-search');
    const countBadge = document.getElementById('search-count');
    const visibleEl  = document.getElementById('visible-count');
    const totalEl    = document.getElementById('total-count');
    const noResults  = document.getElementById('no-results');
    const noResQuery = document.getElementById('no-results-query');
    const tbody      = document.getElementById('studentTableBody');
    const allRows    = () => [...tbody.querySelectorAll('tr:not(#no-results)')];

    window.addEventListener('DOMContentLoaded', () => {
        const total = allRows().length;
        totalEl.textContent   = total;
        visibleEl.textContent = total;
    });

    function filterStudents() {
        const raw   = input.value;
        const query = raw.trim().toLowerCase();
        clearBtn.style.display = raw.length ? 'block' : 'none';
        let visible = 0;
        allRows().forEach(row => {
            const sid   = (row.dataset.id    || '').toLowerCase();
            const name  = (row.dataset.name  || '').toLowerCase();
            const email = (row.dataset.email || '').toLowerCase();
            let haystack = '';
            if      (searchField === 'id')    haystack = sid;
            else if (searchField === 'name')  haystack = name;
            else if (searchField === 'email') haystack = email;
            else                              haystack = sid + ' ' + name + ' ' + email;
            const matches = !query || haystack.includes(query);
            if (matches) {
                row.classList.remove('hidden-row');
                visible++;
                highlightCells(row, query);
            } else {
                row.classList.add('hidden-row');
            }
        });
        visibleEl.textContent = visible;
        noResults.style.display = (visible === 0 && query) ? 'table-row' : 'none';
        if (visible === 0) noResQuery.textContent = '"' + raw + '"';
        if (query) {
            countBadge.style.display = 'inline';
            countBadge.textContent   = visible + ' result' + (visible !== 1 ? 's' : '');
        } else {
            countBadge.style.display = 'none';
        }
    }

    function highlightCells(row, query) {
        const cellMap = {
            '.cell-id':    ['all','id'],
            '.cell-name':  ['all','name'],
            '.cell-email': ['all','email'],
        };
        Object.entries(cellMap).forEach(([sel, fields]) => {
            const cell = row.querySelector(sel);
            if (!cell) return;
            if (cell.dataset.original === undefined) cell.dataset.original = cell.textContent;
            const original = cell.dataset.original;
            if (!query || !fields.includes(searchField)) { cell.innerHTML = original; return; }
            const escaped = query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
            cell.innerHTML = original.replace(new RegExp('(' + escaped + ')', 'gi'), '<mark class="highlight">$1</mark>');
        });
    }

    function clearSearch() {
        input.value = '';
        clearBtn.style.display = 'none';
        filterStudents();
        input.focus();
    }

    function setChip(el, field) {
        document.querySelectorAll('#filter-chips .chip').forEach(c => c.classList.remove('active'));
        el.classList.add('active');
        searchField = field;
        const placeholders = {
            all:   'Search by name, ID, or email…',
            name:  'Search by name…',
            id:    'Search by student ID…',
            email: 'Search by email…',
        };
        input.placeholder = placeholders[field];
        filterStudents();
    }

    document.addEventListener('keydown', e => {
        if ((e.ctrlKey && e.key === 'k') || (e.key === '/' && document.activeElement !== input)) {
            e.preventDefault(); input.focus(); input.select();
        }
        if (e.key === 'Escape' && document.activeElement === input) clearSearch();
    });
    </script>

</body>
</html>
<?php $conn->close(); ?>
