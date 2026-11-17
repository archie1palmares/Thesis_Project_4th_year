<?php if (session_status() === PHP_SESSION_NONE) { session_start(); } ?>
<?php 
$conn = new mysqli("localhost", "root", "", "login");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$total_entries = $conn->query("SELECT COUNT(*) as total FROM checkin_logs")->fetch_assoc()['total'];
$peak_entries = $conn->query("SELECT COUNT(*) as total FROM checkin_logs WHERE is_peak_hour = 1")->fetch_assoc()['total'];

// ── Date search (from the search bar above the table) ──
$searchDate = $_GET['search_date'] ?? '';
$dt = DateTime::createFromFormat('Y-m-d', $searchDate);
if (!$dt || $dt->format('Y-m-d') !== $searchDate) { $searchDate = ''; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Activity Logs | LibAdmin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="style2.css">
    <style>
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
    
/* Shared user account shortcut */
.sidebar-user { position:absolute; left:12px; right:12px; bottom:14px; display:flex; align-items:center; gap:10px; padding:12px; border:1px solid #E8EAF0; border-radius:14px; text-decoration:none; color:#17213C; background:#fff; transition:background .2s,border-color .2s,transform .2s; z-index:5; }
.sidebar-user:hover { background:#F6F5FF; border-color:#C9C7FF; transform:translateY(-2px); }
.sidebar-user-avatar { width:38px; height:38px; flex:0 0 38px; border-radius:50%; display:flex; align-items:center; justify-content:center; background:rgba(99,102,241,.12); color:#6366F1; font-weight:700; font-size:14px; }
.sidebar-user-details { min-width:0; }
.sidebar-user-name { display:block; font-size:13px; font-weight:600; overflow:hidden; white-space:nowrap; text-overflow:ellipsis; }
.sidebar-user-caption { display:block; font-size:11px; color:#8B8D97; margin-top:3px; }
.sidebar-user-arrow { margin-left:auto; color:#8B8D97; font-size:12px; }
@media(max-width:800px) { .sidebar-user { position:static; margin-top:20px; } }

/* ── Date search bar ── */
.date-search { display:flex; align-items:center; gap:10px; flex-wrap:wrap; margin-bottom:18px; }
.date-search-field { position:relative; }
.date-search-field i { position:absolute; left:13px; top:50%; transform:translateY(-50%); color:#94a3b8; font-size:.85rem; pointer-events:none; }
.date-search-field input { padding:11px 14px 11px 38px; border:1.5px solid #e2e8f0; border-radius:12px; font-size:.88rem; color:#1e293b; background:#fbfcfe; outline:none; transition:border-color .2s, box-shadow .2s; }
.date-search-field input:focus { border-color:var(--primary); box-shadow:0 0 0 3px rgba(99,102,241,.12); background:#fff; }
.btn-search, .btn-clear { display:inline-flex; align-items:center; gap:8px; padding:11px 18px; border-radius:12px; font-size:.85rem; font-weight:600; cursor:pointer; text-decoration:none; border:none; transition:all .2s; }
.btn-search { background:var(--primary); color:#fff; }
.btn-search:hover { background:var(--primary-hover); transform:translateY(-1px); }
.btn-clear { background:#f1f5f9; color:#475569; }
.btn-clear:hover { background:#e2e8f0; }
.date-search-info { margin-left:auto; font-size:.82rem; color:#64748b; font-weight:500; }
@media(max-width:600px) { .date-search-info { margin-left:0; width:100%; } }

/* ── Export button + modal ── */
.page-header { display:flex; justify-content:space-between; align-items:center; gap:20px; flex-wrap:wrap; margin-bottom:30px; }
.btn-export-main { display:inline-flex; align-items:center; gap:10px; padding:12px 22px; border-radius:12px; border:none; background:var(--primary); color:#fff; font-size:.9rem; font-weight:600; cursor:pointer; transition:all .3s cubic-bezier(.4,0,.2,1); }
.btn-export-main:hover { background:var(--primary-hover); transform:translateY(-2px); box-shadow:0 10px 15px rgba(99,102,241,.25); }

.export-overlay { display:none; position:fixed; inset:0; background:rgba(15,23,42,.5); backdrop-filter:blur(4px); z-index:2000; justify-content:center; align-items:center; padding:20px; }
.export-overlay.open { display:flex; }
.export-modal { background:#fff; width:100%; max-width:480px; border-radius:20px; padding:28px; box-shadow:0 20px 40px rgba(0,0,0,.2); animation:slideUp .3s ease-out; max-height:92vh; overflow-y:auto; }
.export-modal h3 { font-size:1.2rem; font-weight:800; color:#1e293b; margin-bottom:4px; }
.export-modal .sub { font-size:.85rem; color:#64748b; margin-bottom:22px; }
.export-label { display:block; font-size:.72rem; font-weight:700; text-transform:uppercase; letter-spacing:.5px; color:#94a3b8; margin:18px 0 8px; }
.format-grid { display:grid; grid-template-columns:1fr 1fr; gap:10px; }
.format-option input, .mode-option input { position:absolute; opacity:0; pointer-events:none; }
.format-option { position:relative; }
.format-option label { display:flex; align-items:center; gap:10px; padding:13px 14px; border:1.5px solid #e2e8f0; border-radius:12px; cursor:pointer; font-size:.88rem; font-weight:600; color:#334155; transition:all .2s; }
.format-option label i { font-size:1.15rem; }
.format-option label:hover { border-color:#c7d2fe; background:#f8f9ff; }
.format-option input:checked + label { border-color:var(--primary); background:rgba(99,102,241,.07); box-shadow:0 0 0 3px rgba(99,102,241,.12); }
.format-option input:focus-visible + label { outline:2px solid var(--primary); outline-offset:2px; }
.mode-row { display:flex; gap:8px; flex-wrap:wrap; }
.mode-option { position:relative; }
.mode-option label { display:block; padding:9px 15px; border:1.5px solid #e2e8f0; border-radius:99px; cursor:pointer; font-size:.82rem; font-weight:600; color:#475569; transition:all .2s; }
.mode-option input:checked + label { background:var(--primary); border-color:var(--primary); color:#fff; }
.mode-option input:focus-visible + label { outline:2px solid var(--primary); outline-offset:2px; }
.date-fields { display:none; gap:12px; margin-top:12px; }
.date-fields.show { display:flex; }
.date-fields .field { flex:1; }
.date-fields small { display:block; font-size:.72rem; font-weight:600; color:#64748b; margin-bottom:5px; }
.date-fields input[type=date] { width:100%; padding:11px 12px; border:1.5px solid #e2e8f0; border-radius:10px; font-size:.88rem; color:#1e293b; background:#fbfcfe; outline:none; }
.date-fields input[type=date]:focus { border-color:var(--primary); box-shadow:0 0 0 3px rgba(99,102,241,.12); }
.export-error { display:none; margin-top:14px; padding:10px 12px; border-radius:10px; background:#fef2f2; border:1px solid #fecaca; color:#b91c1c; font-size:.82rem; }
.export-error.show { display:block; }
.export-actions { display:flex; gap:10px; margin-top:24px; }
.export-actions button { flex:1; padding:12px; border-radius:12px; font-size:.9rem; font-weight:600; cursor:pointer; border:none; transition:all .2s; }
.btn-cancel { background:#f1f5f9; color:#475569; }
.btn-cancel:hover { background:#e2e8f0; }
.btn-go { background:var(--primary); color:#fff; }
.btn-go:hover { background:var(--primary-hover); }
@media(max-width:480px) { .format-grid { grid-template-columns:1fr; } .date-fields { flex-direction:column; } }
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
            <!-- <a href="admin.php"           class="nav-btn"><i class="fas fa-chart-line"></i> Dashboard</a> -->
            <a href="manage_students.php" class="nav-btn"><i class="fas fa-users"></i> Students</a>
            <a href="voucher_management.php" class="nav-btn"><i class="fas fa-ticket"></i> Vouchers</a>
            <a href="activity_logs.php"   class="nav-btn active"><i class="fas fa-history"></i> Logs</a>
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

    <!-- MAIN CONTENT -->
    <div class="main-content no-print">
        <header class="page-header">
            <div>
                <h1 style="font-weight:800;">Entry Records</h1>
                <p style="color:var(--text-muted);">Monitoring <?php echo $total_entries; ?> total student sessions.</p>
            </div>

            <!-- EXPORT BUTTON -->
            <button type="button" class="btn-export-main" id="openExport">
                <i class="fas fa-download"></i> Export
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
            <!-- DATE SEARCH BAR -->
            <form method="get" class="date-search no-print" id="dateSearchForm">
                <div class="date-search-field">
                    <i class="fas fa-calendar-day"></i>
                    <input type="date" name="search_date" id="searchDate"
                           value="<?= htmlspecialchars($searchDate) ?>" max="<?= date('Y-m-d') ?>"
                           aria-label="Search records by date">
                </div>
                <button type="submit" class="btn-search"><i class="fas fa-magnifying-glass"></i> Search</button>
                <?php if ($searchDate !== ''): ?>
                    <a href="activity_logs.php" class="btn-clear"><i class="fas fa-xmark"></i> Clear</a>
                <?php endif; ?>
                <span class="date-search-info" id="dateSearchInfo"></span>
            </form>

            <table style="width:100%;">
                <thead>
                    <tr>
                        <th>Attendance Time</th>
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
                            LEFT JOIN students ON checkin_logs.student_id = students.student_id ";
                    if ($searchDate !== '') {
                        $stmt = $conn->prepare($sql . "WHERE DATE(checkin_logs.checkin_time) = ? ORDER BY checkin_logs.id DESC");
                        $stmt->bind_param('s', $searchDate);
                        $stmt->execute();
                        $result = $stmt->get_result();
                    } else {
                        $result = $conn->query($sql . "ORDER BY checkin_logs.id DESC");
                    }
                    $matchCount = $result ? $result->num_rows : 0;
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
                        echo "<tr><td colspan='8' style='text-align:center;padding:50px;color:var(--text-muted);'>" . ($searchDate !== '' ? "No records found for " . date('F d, Y', strtotime($searchDate)) . "." : "No activity logs found.") . "</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>


    <!-- ══════════ EXPORT MODAL ══════════ -->
    <div class="export-overlay no-print" id="exportOverlay" aria-hidden="true">
        <div class="export-modal" role="dialog" aria-modal="true" aria-labelledby="exportTitle">
            <h3 id="exportTitle"><i class="fas fa-download" style="color:var(--primary);"></i> Export Records</h3>
            <p class="sub">Choose a file type and which date to export.</p>

            <span class="export-label">File type</span>
            <div class="format-grid">
                <div class="format-option">
                    <input type="radio" name="fmt" id="fmtPdf" value="pdf" checked>
                    <label for="fmtPdf"><i class="fas fa-file-pdf" style="color:#dc2626;"></i> PDF</label>
                </div>
                <div class="format-option">
                    <input type="radio" name="fmt" id="fmtExcel" value="excel">
                    <label for="fmtExcel"><i class="fas fa-file-excel" style="color:#15803d;"></i> Excel</label>
                </div>
                <div class="format-option">
                    <input type="radio" name="fmt" id="fmtCsv" value="csv">
                    <label for="fmtCsv"><i class="fas fa-file-csv" style="color:#0369a1;"></i> CSV</label>
                </div>
                <div class="format-option">
                    <input type="radio" name="fmt" id="fmtWord" value="word">
                    <label for="fmtWord"><i class="fas fa-file-word" style="color:#1d4ed8;"></i> Word</label>
                </div>
            </div>

            <span class="export-label">Date</span>
            <div class="mode-row">
                <div class="mode-option">
                    <input type="radio" name="mode" id="modeAll" value="all" checked>
                    <label for="modeAll">All dates</label>
                </div>
                <div class="mode-option">
                    <input type="radio" name="mode" id="modeDate" value="date">
                    <label for="modeDate">Specific date</label>
                </div>
                <div class="mode-option">
                    <input type="radio" name="mode" id="modeRange" value="range">
                    <label for="modeRange">Date range</label>
                </div>
            </div>

            <div class="date-fields" id="fieldsDate">
                <div class="field">
                    <small>Select date</small>
                    <input type="date" id="expDate" max="<?= date('Y-m-d') ?>">
                </div>
            </div>
            <div class="date-fields" id="fieldsRange">
                <div class="field">
                    <small>From</small>
                    <input type="date" id="expFrom" max="<?= date('Y-m-d') ?>">
                </div>
                <div class="field">
                    <small>To</small>
                    <input type="date" id="expTo" max="<?= date('Y-m-d') ?>">
                </div>
            </div>

            <div class="export-error" id="exportError"></div>

            <div class="export-actions">
                <button type="button" class="btn-cancel" id="cancelExport">Cancel</button>
                <button type="button" class="btn-go" id="goExport"><i class="fas fa-download"></i> Export</button>
            </div>
        </div>
    </div>

    <script>
    // Show how many records match the date search
    (function () {
        const info = document.getElementById('dateSearchInfo');
        if (!info) return;
        const total = <?= (int)$matchCount ?>;
        info.textContent = <?= $searchDate !== '' ? json_encode(date('F d, Y', strtotime($searchDate))) : "''" ?>
            ? total + ' record' + (total === 1 ? '' : 's') + ' on ' + <?= $searchDate !== '' ? json_encode(date('F d, Y', strtotime($searchDate))) : "''" ?>
            : 'Showing all ' + total + ' records';
    })();

    (function () {
        const overlay = document.getElementById('exportOverlay');
        const errBox  = document.getElementById('exportError');
        const fDate   = document.getElementById('fieldsDate');
        const fRange  = document.getElementById('fieldsRange');
        const val = n => document.querySelector('input[name="' + n + '"]:checked').value;

        function openModal() {
            // If a date is being searched, pre-select "Specific date" with that date
            const searched = <?= json_encode($searchDate) ?>;
            if (searched) {
                document.getElementById('modeDate').checked = true;
                document.getElementById('expDate').value = searched;
                syncMode();
            }
            overlay.classList.add('open'); overlay.setAttribute('aria-hidden', 'false');
        }
        function closeModal() { overlay.classList.remove('open'); overlay.setAttribute('aria-hidden', 'true'); errBox.classList.remove('show'); }
        function showError(m) { errBox.textContent = m; errBox.classList.add('show'); }

        function syncMode() {
            const m = val('mode');
            fDate.classList.toggle('show', m === 'date');
            fRange.classList.toggle('show', m === 'range');
            errBox.classList.remove('show');
        }

        document.getElementById('openExport').addEventListener('click', openModal);
        document.getElementById('cancelExport').addEventListener('click', closeModal);
        overlay.addEventListener('click', e => { if (e.target === overlay) closeModal(); });
        document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });
        document.querySelectorAll('input[name="mode"]').forEach(r => r.addEventListener('change', syncMode));

        document.getElementById('goExport').addEventListener('click', function () {
            const fmt  = val('fmt');
            const mode = val('mode');
            const params = new URLSearchParams({ format: fmt, mode: mode });

            if (mode === 'date') {
                const d = document.getElementById('expDate').value;
                if (!d) return showError('Please select a date.');
                params.set('date', d);
            } else if (mode === 'range') {
                const from = document.getElementById('expFrom').value;
                const to   = document.getElementById('expTo').value;
                if (!from || !to) return showError('Please select both a start and an end date.');
                if (from > to)    return showError('The start date must be before the end date.');
                params.set('from', from);
                params.set('to', to);
            }

            const url = 'export_logs.php?' + params.toString();
            if (fmt === 'pdf') { window.open(url, '_blank'); }   // opens a print-ready page
            else               { window.location.href = url; }   // downloads the file
            closeModal();
        });
    })();
    </script>

</body>
</html>
<?php $conn->close(); ?>
