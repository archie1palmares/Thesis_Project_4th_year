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
            <a href="admin.php"           class="nav-btn"><i class="fas fa-chart-line"></i> Dashboard</a>
            <a href="manage_students.php" class="nav-btn active"><i class="fas fa-users"></i> Students</a>
            <a href="activity_logs.php"   class="nav-btn"><i class="fas fa-history"></i> Logs</a>
            <a href="analytics.php"       class="nav-btn"><i class="fas fa-brain"></i> Analysis</a>
            <hr style="border:0;border-top:1px solid #eee;margin:20px 10px;">
            <a href="guide.php" class="nav-btn"><i class="fas fa-circle-question"></i> System Guide</a>
            <a href="logout.php" class="nav-btn" style="color:#ff4d4d;"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <!-- MAIN -->
    <div class="main-content">

        <!-- Header -->
        <header style="display:flex;justify-content:space-between;align-items:center;margin-bottom:30px;">
            <div>
                <h1 style="font-weight:800;">Student Registry</h1>
                <p style="color:var(--text-muted);">View and manage authorized library users.</p>
            </div>
            <div style="display:flex; gap:10px;">
                <!-- NEW: Import Students button (left of Import Vouchers) -->
                <button class="btn-modern" style="background:#0ea5e9;border:none;cursor:pointer;" onclick="openStudentImportModal()">
                    <i class="fas fa-users"></i> Import Students
                </button>
                <input type="file" id="studentImportFileInput"
                    accept=".csv,.txt"
                    style="display:none"
                    onchange="handleStudentImportFile(this)">

                <button class="btn-modern" style="background:#6c757d;border:none;cursor:pointer;" onclick="openVoucherModal()">
                    <i class="fas fa-file-import"></i> Import Vouchers
                </button>
                <input type="file" id="voucherFileInput"
                    accept=".csv,.txt"
                    style="display:none"
                    onchange="handleVoucherFile(this)">

                <a href="admin.php" class="btn-modern" style="text-decoration:none;">
                    <i class="fas fa-plus"></i> New Student
                </a>
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
                        <div style="font-size:11px;color:#64748b;">CSV or TXT · student_id, fullname, email, course, year, section</div>
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
                <div style="font-size:12px;color:#94a3b8;">Accepts .csv · .txt</div>
                <div id="siFileTag" style="display:none;margin-top:12px;background:rgba(14,165,233,0.08);border:1px solid rgba(14,165,233,0.25);color:#0ea5e9;font-size:12px;padding:5px 12px;border-radius:6px;font-family:monospace;word-break:break-all;"></div>
            </div>

            <!-- Format hint -->
            <div style="margin-top:14px;background:#eff6ff;border:1px solid #bfdbfe;border-radius:10px;padding:10px 14px;font-size:12px;color:#1e40af;line-height:1.8;">
                <strong style="display:block;margin-bottom:4px;"><i class="fas fa-circle-info"></i> Required Columns (header row)</strong>
                <code style="background:rgba(0,0,0,0.06);padding:2px 6px;border-radius:4px;">student_id, fullname, email, course, year, section</code><br><br>
                Header row is required and must match exactly (case-insensitive).<br>
                Duplicate student IDs will be skipped automatically.
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
         Import Vouchers Modal
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
                    <div style="width:38px;height:38px;border-radius:10px;background:rgba(99,102,241,0.1);display:flex;align-items:center;justify-content:center;color:var(--primary);font-size:16px;">
                        <i class="fas fa-ticket"></i>
                    </div>
                    <div>
                        <div style="font-weight:700;font-size:16px;color:#1e293b;">Import Voucher Codes</div>
                        <div style="font-size:11px;color:#64748b;">CSV or TXT · one code per line</div>
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
                <i class="fas fa-cloud-arrow-up" style="font-size:34px;color:var(--primary);margin-bottom:10px;display:block;opacity:0.7;"></i>
                <div style="font-weight:600;font-size:14px;color:#334155;margin-bottom:4px;">Drop file here or click to browse</div>
                <div style="font-size:12px;color:#94a3b8;">Accepts .csv · .txt</div>
                <div id="viFileTag" style="display:none;margin-top:12px;background:rgba(99,102,241,0.08);border:1px solid rgba(99,102,241,0.25);color:var(--primary);font-size:12px;padding:5px 12px;border-radius:6px;font-family:monospace;word-break:break-all;"></div>
            </div>

            <!-- Format hint -->
            <div style="margin-top:14px;background:#fffbeb;border:1px solid #fde68a;border-radius:10px;padding:10px 14px;font-size:12px;color:#92400e;line-height:1.7;">
                <strong style="display:block;margin-bottom:4px;"><i class="fas fa-circle-info"></i> Accepted File Formats</strong>
                <span style="display:inline-flex;align-items:center;gap:5px;margin-bottom:2px;">
                    <i class="fas fa-file-csv" style="color:#2563eb;"></i>
                    <strong>CSV (.csv)</strong> — One code per line or comma-separated.
                </span><br>
                <span style="display:inline-flex;align-items:center;gap:5px;">
                    <i class="fas fa-file-lines" style="color:#7c3aed;"></i>
                    <strong>Text (.txt)</strong> — One code per line or comma-separated.
                </span><br><br>
                Header row (e.g. "voucher_codes") is automatically skipped.<br>
                Max 100 characters per code.
            </div>

            <!-- Progress bar -->
            <div id="viProgressWrap" style="display:none;margin-top:14px;background:#f1f5f9;border-radius:99px;height:5px;overflow:hidden;">
                <div id="viProgressBar" style="height:100%;width:0%;background:linear-gradient(90deg,var(--primary),#818cf8);border-radius:99px;transition:width 0.35s ease;"></div>
            </div>

            <!-- Result banner -->
            <div id="viResult" style="display:none;margin-top:14px;border-radius:10px;padding:12px 14px;font-size:12px;font-family:monospace;line-height:1.7;white-space:pre-line;"></div>

            <!-- Actions -->
            <div style="display:flex;gap:10px;margin-top:20px;justify-content:flex-end;">
                <button onclick="closeVoucherModal()" style="background:#f1f5f9;color:#64748b;border:none;padding:10px 20px;border-radius:10px;font-family:'Poppins',sans-serif;font-size:13px;font-weight:600;cursor:pointer;">
                    Cancel
                </button>
                <button id="viUploadBtn" onclick="viUpload()" disabled style="background:var(--primary);color:#fff;border:none;padding:10px 24px;border-radius:10px;font-family:'Poppins',sans-serif;font-size:13px;font-weight:600;cursor:pointer;display:inline-flex;align-items:center;gap:8px;opacity:0.45;box-shadow:0 4px 14px rgba(99,102,241,0.25);">
                    <div id="viSpinner" style="display:none;width:13px;height:13px;border:2px solid rgba(255,255,255,0.3);border-top-color:#fff;border-radius:50%;animation:viSpin 0.7s linear infinite;"></div>
                    <i class="fas fa-upload" id="viIcon"></i>
                    <span id="viLabel">Upload &amp; Save</span>
                </button>
            </div>
        </div>
    </div>

    <script>

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
        if (!['csv', 'txt'].includes(ext)) {
            siShowResult('error', '❌ Only .csv or .txt files are accepted.');
            return;
        }
        siFile = file;
        const tag = document.getElementById('siFileTag');
        tag.textContent = '📋 ' + file.name + ' (' + (file.size / 1024).toFixed(1) + ' KB)';
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
            siShowResult('error', '❌ Network error. Make sure import_students.php is in the same folder.');
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
       VOUCHER IMPORT
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
        dz.style.borderColor = 'var(--primary)';
        dz.style.background  = 'rgba(99,102,241,0.05)';
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
        if (!['csv', 'txt'].includes(ext)) {
            viShowResult('error', '❌ Only .csv or .txt files are accepted.');
            return;
        }
        viFile = file;
        const icons = { csv: '📋', txt: '📄' };
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
            const data = await res.json();
            viFinishProgress();
            if (data.success) {
                let msg = '✅ ' + data.message;
                if (data.invalid && data.invalid.length > 0) {
                    msg += '\n\n⚠️ Invalid codes skipped:\n' + data.invalid.join(', ');
                }
                viShowResult('success', msg);
                viFile = null;
                document.getElementById('viFileTag').style.display = 'none';
                document.getElementById('voucherFileInput').value  = '';
                const btn = document.getElementById('viUploadBtn');
                btn.disabled = true;
                btn.style.opacity = '0.45';
            } else {
                viShowResult('error', '❌ ' + data.message);
            }
        } catch(err) {
            viFinishProgress();
            viShowResult('error', '❌ Network error. Make sure import_vouchers.php is in the same folder.');
        }
        viSetLoading(false);
    }
    function viSetLoading(on) {
        document.getElementById('viSpinner').style.display = on ? 'block' : 'none';
        document.getElementById('viIcon').style.display    = on ? 'none'  : 'inline';
        document.getElementById('viLabel').textContent     = on ? 'Uploading…' : 'Upload & Save';
        document.getElementById('viUploadBtn').disabled    = on;
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
        const btn = document.getElementById('viUploadBtn');
        btn.disabled = true;
        btn.style.opacity = '0.45';
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