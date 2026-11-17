<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include 'connect.php';
date_default_timezone_set('Asia/Manila');

/* ─────────────────────────────────────────────────────────────
   Home page data
   Peak window = 10:00 AM – 3:00 PM (same rule as Logs & Analysis)
───────────────────────────────────────────────────────────── */
const PEAK_START = 10;   // first peak hour
const PEAK_END   = 15;   // peak ends at 3:00 PM (exclusive)

// Runs a prepared query. Returns the rows, or null if the query failed
// (so one missing table can never break the whole page).
function db_rows($conn, $sql, $types = '', $params = []) {
    try {
        $stmt = $conn->prepare($sql);
        if (!$stmt) return null;
        if ($types !== '') $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $res  = $stmt->get_result();
        $rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
        $stmt->close();
        return $rows;
    } catch (Throwable $e) {
        return null;
    }
}
function db_value($conn, $sql, $types = '', $params = []) {
    $rows = db_rows($conn, $sql, $types, $params);
    if ($rows === null || !$rows) return null;
    return array_values($rows[0])[0];
}
function h12($h) { $h = $h % 24; $x = $h % 12; return [$x === 0 ? 12 : $x, $h >= 12 ? 'PM' : 'AM']; }
function hour_label($h) { [$x, $ap] = h12($h); return "$x $ap"; }
function hour_range($h) {
    [$a, $ap1] = h12($h); [$b, $ap2] = h12($h + 1);
    return $ap1 === $ap2 ? "$a – $b $ap1" : "$a $ap1 – $b $ap2";
}
function e($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

$now          = new DateTime('now');
$todayStart   = $now->format('Y-m-d') . ' 00:00:00';
$tomorrow     = (clone $now)->modify('+1 day')->format('Y-m-d') . ' 00:00:00';
$yesterday    = (clone $now)->modify('-1 day')->format('Y-m-d') . ' 00:00:00';
$yesterdayNow = (clone $now)->modify('-1 day')->format('Y-m-d H:i:s');
$weekStart    = (clone $now)->modify('-6 days')->format('Y-m-d') . ' 00:00:00';

// Headline numbers
$todayCount     = (int)(db_value($conn, "SELECT COUNT(*) FROM checkin_logs WHERE checkin_time >= ? AND checkin_time < ?", 'ss', [$todayStart, $tomorrow]) ?? 0);
$yesterdayCount = (int)(db_value($conn, "SELECT COUNT(*) FROM checkin_logs WHERE checkin_time >= ? AND checkin_time < ?", 'ss', [$yesterday, $yesterdayNow]) ?? 0);
$studentCount   = db_value($conn, "SELECT COUNT(*) FROM students WHERE isActive = 1");
$voucherActive  = db_value($conn, "SELECT COUNT(*) FROM Voucher_codes WHERE Status = 'active'");
$voucherTotal   = db_value($conn, "SELECT COUNT(*) FROM Voucher_codes");

// Check-ins by hour today
$hourCounts = array_fill(0, 24, 0);
foreach (db_rows($conn, "SELECT HOUR(checkin_time) AS h, COUNT(*) AS c FROM checkin_logs WHERE checkin_time >= ? AND checkin_time < ? GROUP BY HOUR(checkin_time)", 'ss', [$todayStart, $tomorrow]) ?? [] as $r) {
    $hourCounts[(int)$r['h']] = (int)$r['c'];
}
$hoursWithData = array_keys(array_filter($hourCounts));
$hStart = $hoursWithData ? min(7,  min($hoursWithData)) : 7;
$hEnd   = $hoursWithData ? max(18, max($hoursWithData)) : 18;

// Last 7 days
$dayMap = [];
foreach (db_rows($conn, "SELECT DATE(checkin_time) AS d, COUNT(*) AS c, SUM(HOUR(checkin_time) BETWEEN " . PEAK_START . " AND " . (PEAK_END - 1) . ") AS p FROM checkin_logs WHERE checkin_time >= ? AND checkin_time < ? GROUP BY DATE(checkin_time)", 'ss', [$weekStart, $tomorrow]) ?? [] as $r) {
    $dayMap[$r['d']] = ['c' => (int)$r['c'], 'p' => (int)$r['p']];
}
$days = [];
for ($i = 6; $i >= 0; $i--) {
    $d   = (clone $now)->modify("-$i days");
    $key = $d->format('Y-m-d');
    $days[] = [
        'key'   => $key,
        'label' => $i === 0 ? 'Today' : $d->format('D j'),
        'title' => $d->format('l, M j'),
        'c'     => $dayMap[$key]['c'] ?? 0,
        'p'     => $dayMap[$key]['p'] ?? 0,
    ];
}
$weekTotal = array_sum(array_column($days, 'c'));
$weekPeak  = array_sum(array_column($days, 'p'));
$weekAvg   = round($weekTotal / 7, 1);
$peakShare = $weekTotal > 0 ? (int)round($weekPeak / $weekTotal * 100) : null;

// Who checked in: today's latest, or the latest overall if nobody yet today
$listSql = "SELECT l.student_id, l.checkin_time, l.voucher_code, s.fullname, s.course, s.year, s.section
            FROM checkin_logs l
            LEFT JOIN students s ON l.student_id = s.student_id ";
$recent = db_rows($conn, $listSql . "WHERE l.checkin_time >= ? AND l.checkin_time < ? ORDER BY l.checkin_time DESC, l.id DESC LIMIT 10", 'ss', [$todayStart, $tomorrow]) ?? [];
$listIsToday = count($recent) > 0;
if (!$listIsToday) {
    $recent = db_rows($conn, $listSql . "ORDER BY l.checkin_time DESC, l.id DESC LIMIT 10") ?? [];
}

// Right-now status + greeting
$curHour   = (int)$now->format('G');
$isPeakNow = ($curHour >= PEAK_START && $curHour < PEAK_END);
$greeting  = $curHour < 12 ? 'Good morning' : ($curHour < 18 ? 'Good afternoon' : 'Good evening');

// Chart data handed to the page's JavaScript
$hourLabels = $hourVals = $hourTips = [];
for ($h = $hStart; $h <= $hEnd; $h++) {
    $hourLabels[] = hour_label($h);
    $hourVals[]   = $hourCounts[$h];
    $hourTips[]   = ['title' => hour_range($h), 'extra' => ($h >= PEAK_START && $h < PEAK_END) ? 'Peak window' : ''];
}
$busiestHour = null;
foreach ($hourCounts as $h => $c) { if ($c > 0 && ($busiestHour === null || $c > $hourCounts[$busiestHour])) $busiestHour = $h; }

$chartData = [
    'hours' => [
        'labels' => $hourLabels, 'values' => $hourVals, 'tips' => $hourTips,
        'peakFrom' => PEAK_START - $hStart, 'peakTo' => PEAK_END - $hStart,
        'empty' => 'No check-ins yet today',
        'aria'  => $todayCount > 0
            ? "Check-ins by hour today: $todayCount in total, busiest hour " . hour_label($busiestHour) . " with {$hourCounts[$busiestHour]}."
            : 'Check-ins by hour today: none yet.',
    ],
    'days' => [
        'labels' => array_column($days, 'label'),
        'values' => array_column($days, 'c'),
        'tips'   => array_map(fn($d) => ['title' => $d['title'], 'extra' => $d['p'] . ' in peak window'], $days),
        'empty'  => 'No check-ins in the last 7 days',
        'aria'   => "Check-ins per day for the last 7 days: $weekTotal in total, average $weekAvg per day.",
    ],
];
$chartJson = json_encode($chartData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);

// Change vs the same time yesterday
$diff = $todayCount - $yesterdayCount;
if     ($diff > 0) $deltaText = '▲ ' . $diff . ' more than this time yesterday';
elseif ($diff < 0) $deltaText = '▼ ' . abs($diff) . ' fewer than this time yesterday';
else               $deltaText = 'Same as this time yesterday';
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
    /* ── Home page ───────────────────────────────────────────── */
    .home {
        --series:        #6366f1;   /* the one chart colour (brand indigo, validated) */
        --series-hover:  #4f46e5;
        --wash:          rgba(99,102,241,.11);
        --track:         #e0e7ff;
        --ink:           #17213C;
        --ink-2:         #475569;
        --ink-muted:     #64748b;
        --hairline:      #eceff4;
        --axis:          #cbd5e1;
        --surface:       #ffffff;
    }

    @keyframes swipeUp {
        from { opacity: 0; transform: translateY(24px); }
        to   { opacity: 1; transform: translateY(0); }
    }
    .rise { opacity: 0; animation: swipeUp .55s cubic-bezier(.22,1,.36,1) forwards; }
    .d1 { animation-delay: .05s; } .d2 { animation-delay: .12s; } .d3 { animation-delay: .19s; }
    .d4 { animation-delay: .26s; } .d5 { animation-delay: .33s; }

    /* Welcome band */
    .hp-band {
        background: linear-gradient(150deg, #101B33 0%, #1F2E5C 100%);
        border-radius: 24px; padding: 32px 38px; color: #F4F1E8;
        margin-bottom: 22px; position: relative; overflow: hidden;
        display: flex; justify-content: space-between; align-items: flex-end; gap: 28px; flex-wrap: wrap;
    }
    .hp-band::before {
        content: ''; position: absolute; width: 280px; height: 280px; border-radius: 50%;
        border: 1px dashed rgba(201,162,39,.28); top: -110px; right: -70px; pointer-events: none;
    }
    .hp-band > * { position: relative; z-index: 1; }
    .hp-eyebrow { font-size: .72rem; letter-spacing: .08em; text-transform: uppercase; color: #C9A227; font-weight: 600; margin-bottom: 8px; }
    .hp-band h1 { font-size: 1.75rem; font-weight: 600; line-height: 1.25; margin-bottom: 14px; }
    .hp-status {
        display: inline-flex; align-items: center; gap: 9px; padding: 7px 14px; border-radius: 99px;
        background: rgba(255,255,255,.08); border: 1px solid rgba(255,255,255,.14);
        font-size: .8rem; font-weight: 500; color: #E7E4DA;
    }
    .hp-status i { font-size: .85rem; }
    .hp-status.peak i   { color: #fbbf24; }
    .hp-status.normal i { color: #4ade80; }
    .hp-status small { color: #AEB4C8; font-weight: 400; }

    .hp-hero { text-align: right; }
    .hp-hero .label { font-size: .8rem; color: #C7CADA; margin-bottom: 2px; }
    .hp-hero .value { font-size: 4.25rem; font-weight: 600; line-height: 1; letter-spacing: -.02em; color: #fff; }
    .hp-hero .delta { font-size: .8rem; color: #C7CADA; margin-top: 8px; }

    /* Cards */
    .hp-card {
        background: var(--surface); border: 1px solid #EEF0F6; border-radius: 20px; padding: 22px 24px;
        box-shadow: 0 10px 30px rgba(16,27,51,.03); min-width: 0;
    }
    .hp-card h2 { font-size: .98rem; font-weight: 600; color: var(--ink); }
    .hp-card .sub { font-size: .78rem; color: var(--ink-muted); margin-top: 2px; }
    .hp-card-head { display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; margin-bottom: 14px; flex-wrap: wrap; }
    .hp-card-head a { font-size: .78rem; font-weight: 600; color: #4f46e5; text-decoration: none; white-space: nowrap; }
    .hp-card-head a:hover { text-decoration: underline; }

    /* Stat tiles */
    .hp-kpis { display: grid; grid-template-columns: repeat(4, 1fr); gap: 18px; margin-bottom: 22px; }
    .hp-kpi .label { font-size: .75rem; font-weight: 600; color: var(--ink-muted); }
    .hp-kpi .value { font-size: 2rem; font-weight: 600; color: var(--ink); line-height: 1.15; margin: 6px 0 4px; }
    .hp-kpi .note  { font-size: .76rem; color: var(--ink-muted); }
    .meter { height: 8px; border-radius: 99px; background: var(--track); overflow: hidden; margin: 8px 0 8px; }
    .meter > span { display: block; height: 100%; border-radius: 99px; background: var(--series); }

    /* Chart rows */
    .hp-row { display: grid; gap: 18px; margin-bottom: 22px; }
    .hp-row.charts { grid-template-columns: 1.4fr 1fr; }
    .hp-row.lower  { grid-template-columns: 1fr; margin-bottom: 8px; }

    .chart { position: relative; width: 100%; height: 236px; }
    .chart svg { display: block; overflow: visible; }
    .chart .empty-msg {
        position: absolute; left: 0; right: 0; top: 40%; text-align: center; pointer-events: none;
        font-size: .85rem; color: var(--ink-muted);
    }
    .key { display: inline-flex; align-items: center; gap: 7px; font-size: .75rem; color: var(--ink-muted); }
    .key i { display: inline-block; width: 14px; height: 10px; border-radius: 3px; background: var(--wash); border: 1px solid rgba(99,102,241,.25); }

    .tip {
        position: absolute; z-index: 5; pointer-events: none; background: #fff; border: 1px solid #E3E6EE; border-radius: 10px;
        box-shadow: 0 8px 22px rgba(16,27,51,.12); padding: 8px 11px; font-size: .75rem; color: var(--ink-2);
        white-space: nowrap; opacity: 0; transition: opacity .12s; line-height: 1.45;
    }
    .tip.show { opacity: 1; }
    .tip .v { display: flex; align-items: center; gap: 7px; font-size: .95rem; font-weight: 700; color: var(--ink); }
    .tip .v i { display: inline-block; width: 12px; height: 3px; border-radius: 2px; background: var(--series); }
    .tip .v span { font-size: .75rem; font-weight: 500; color: var(--ink-muted); }

    /* Table twin of each chart */
    details.viz-table { margin-top: 10px; font-size: .78rem; }
    details.viz-table summary { cursor: pointer; color: var(--ink-muted); font-weight: 500; width: fit-content; }
    details.viz-table summary:hover { color: var(--ink); }
    details.viz-table table { margin-top: 8px; width: 100%; border-collapse: collapse; }
    details.viz-table th, details.viz-table td { padding: 5px 8px; text-align: left; border-bottom: 1px solid var(--hairline); font-size: .76rem; }
    details.viz-table th { color: var(--ink-muted); font-weight: 600; text-transform: none; background: none; border-bottom: 1px solid var(--axis); }
    details.viz-table td.n { font-variant-numeric: tabular-nums; }
    .viz-scroll { max-height: 190px; overflow: auto; }

    /* Who checked in */
    .who-wrap { overflow-x: auto; }
    table.who { width: 100%; border-collapse: collapse; min-width: 640px; }
    table.who td.nw { white-space: nowrap; }
    table.who th { text-align: left; padding: 8px 10px; font-size: .68rem; text-transform: uppercase; letter-spacing: .05em; color: var(--ink-muted); font-weight: 600; border-bottom: 1px solid var(--hairline); background: none; }
    table.who td { padding: 10px 10px; border-bottom: 1px solid #F3F4F8; font-size: .82rem; color: var(--ink-2); vertical-align: middle; }
    table.who tr:last-child td { border-bottom: none; }
    .who-name { display: flex; align-items: center; gap: 11px; min-width: 0; }
    .avatar { width: 34px; height: 34px; flex: 0 0 34px; border-radius: 50%; display: flex; align-items: center; justify-content: center; background: rgba(99,102,241,.12); color: #4f46e5; font-size: .72rem; font-weight: 700; }
    .who-name strong { display: block; color: var(--ink); font-weight: 600; font-size: .84rem; }
    .who-name small  { display: block; color: var(--ink-muted); font-size: .72rem; font-family: monospace; }
    .who code { background: #f1f5f9; padding: 3px 8px; border-radius: 7px; color: #4338ca; font-weight: 600; font-size: .76rem; }
    .pill { display: inline-flex; align-items: center; gap: 6px; padding: 3px 10px; border-radius: 99px; font-size: .7rem; font-weight: 700; white-space: nowrap; }
    .pill.peak   { background: #fef3c7; color: #92400e; }
    .pill.normal { background: #d1fae5; color: #065f46; }
    .pill i { font-size: .62rem; }
    .empty-list { text-align: center; padding: 34px 10px; color: var(--ink-muted); font-size: .86rem; }
    .empty-list i { display: block; font-size: 1.6rem; margin-bottom: 10px; opacity: .35; }

    /* Quick access */
    .quick { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; }
    .quick a {
        display: flex; align-items: center; gap: 13px; padding: 12px 14px; border-radius: 14px; text-decoration: none;
        border: 1px solid #EEF0F6; color: inherit; transition: border-color .2s, background .2s, transform .2s;
    }
    .quick a:hover { border-color: #C9C7FF; background: #F8F8FF; transform: translateY(-2px); }
    .quick .ico { width: 38px; height: 38px; flex: 0 0 38px; border-radius: 11px; display: flex; align-items: center; justify-content: center; font-size: .95rem; }
    .quick strong { display: block; font-size: .85rem; color: var(--ink); font-weight: 600; }
    .quick small  { display: block; font-size: .73rem; color: var(--ink-muted); margin-top: 1px; }
    .quick .go { margin-left: auto; color: #A3A8B8; font-size: .75rem; }

    .updated { text-align: right; font-size: .72rem; color: var(--ink-muted); margin: 14px 2px 0; }

    @media (max-width: 1100px) {
        .quick { grid-template-columns: repeat(2, 1fr); }
        .hp-kpis { grid-template-columns: repeat(2, 1fr); }
        .hp-row.charts, .hp-row.lower { grid-template-columns: 1fr; }
    }
    @media (max-width: 800px) {
        /* on phones the fixed sidebar would cover the page, so stack it above the content */
        body { flex-direction: column; }
        .sidebar { position: relative; width: 100%; height: auto; box-shadow: none; border-bottom: 1px solid #eee; }
        .main-content.home { margin-left: 0; width: 100%; padding: 20px 16px; }
        .hp-band { padding: 26px 22px; }
        .hp-hero { text-align: left; }
        .hp-hero .value { font-size: 3.4rem; }
    }
    @media (max-width: 520px) { .hp-kpis, .quick { grid-template-columns: 1fr; } }
    @media (prefers-reduced-motion: reduce) {
        .rise, .nav-btn { animation: none !important; opacity: 1 !important; transform: none !important; }
        .tip { transition: none; }
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
    </style>
</head>
<body>

    <!-- SIDEBAR -->
    <div class="sidebar">
        <h2 style="color: var(--primary); margin-bottom: 40px; padding-left: 20px;">
            <i class="fas fa-book-reader"></i> LibAdmin
        </h2>
        <div class="nav-container">
            <a href="dashboard.php" class="nav-btn active"><i class="fas fa-house"></i> Home</a>
            <a href="manage_students.php" class="nav-btn"><i class="fas fa-users"></i> Students</a>
            <a href="voucher_management.php" class="nav-btn"><i class="fas fa-ticket"></i> Vouchers</a>
            <a href="activity_logs.php"   class="nav-btn"><i class="fas fa-history"></i> Logs</a>
            <a href="analytics.php"       class="nav-btn"><i class="fas fa-brain"></i> Analysis</a>

            <hr style="border:0;border-top:1px solid #eee;margin:20px 10px;">
            <a href="guide.php"           class="nav-btn"><i class="fas fa-circle-question"></i> System Guide</a>
            <a href="logout.php" class="nav-btn" style="color:#ff4d4d;"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>

        <?php
        $sidebarName = 'My Account';
        $sidebarInitials = 'U';
        $firstName = '';
        if (isset($_SESSION['email'])) {
            $sidebarDb = new mysqli('localhost', 'root', '', 'login');
            if (!$sidebarDb->connect_error) {
                $sidebarStmt = $sidebarDb->prepare('SELECT firstName, lastName FROM users WHERE email = ? LIMIT 1');
                if ($sidebarStmt) {
                    $sidebarStmt->bind_param('s', $_SESSION['email']);
                    $sidebarStmt->execute();
                    $sidebarUser = $sidebarStmt->get_result()->fetch_assoc();
                    if ($sidebarUser) {
                        $firstName = trim($sidebarUser['firstName']);
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
    <div class="main-content home">

        <!-- Welcome band: one hero figure -->
        <section class="hp-band rise d1">
            <div>
                <div class="hp-eyebrow"><?= e($now->format('l, F j, Y')) ?></div>
                <h1><?= e($greeting) ?><?= $firstName !== '' ? ', ' . e($firstName) : '' ?></h1>
                <span class="hp-status <?= $isPeakNow ? 'peak' : 'normal' ?>">
                    <i class="fas <?= $isPeakNow ? 'fa-fire' : 'fa-clock' ?>"></i>
                    <?= $isPeakNow ? 'Peak hour now' : 'Normal hour' ?>
                    <small>· Peak window 10:00 AM – 3:00 PM</small>
                </span>
            </div>
            <div class="hp-hero">
                <div class="label">Attendance today</div>
                <div class="value"><?= number_format($todayCount) ?></div>
                <div class="delta"><?= e($deltaText) ?></div>
            </div>
        </section>

        <!-- Stat tiles -->
        <section class="hp-kpis">
            <div class="hp-card hp-kpi rise d2">
                <div class="label">Last 7 days</div>
                <div class="value"><?= number_format($weekTotal) ?></div>
                <div class="note">Attendance · avg <?= e($weekAvg) ?> a day</div>
            </div>
            <div class="hp-card hp-kpi rise d2">
                <div class="label">Peak-hour share</div>
                <div class="value"><?= $peakShare === null ? '—' : $peakShare . '%' ?></div>
                <div class="meter" role="img" aria-label="<?= $peakShare === null ? 'No check-ins in the last 7 days' : $peakShare . ' percent of check-ins in the last 7 days were in the peak window' ?>"><span style="width: <?= (int)($peakShare ?? 0) ?>%"></span></div>
                <div class="note"><?= $peakShare === null ? 'No check-ins in the last 7 days' : number_format($weekPeak) . ' of ' . number_format($weekTotal) . ' fell in the peak window' ?></div>
            </div>
            <div class="hp-card hp-kpi rise d3">
                <div class="label">Active students</div>
                <div class="value"><?= $studentCount === null ? '—' : number_format((int)$studentCount) ?></div>
                <div class="note">registered library users</div>
            </div>
            <div class="hp-card hp-kpi rise d3">
                <div class="label">Active vouchers</div>
                <div class="value"><?= $voucherActive === null ? '—' : number_format((int)$voucherActive) ?></div>
                <div class="note"><?= $voucherTotal === null ? 'ready to use' : 'of ' . number_format((int)$voucherTotal) . ' vouchers' ?></div>
            </div>
        </section>

        <!-- Charts -->
        <section class="hp-row charts">
            <div class="hp-card rise d4">
                <div class="hp-card-head">
                    <div>
                        <h2>Attendance by hour today</h2>
                        <div class="sub">How busy the library has been, hour by hour</div>
                    </div>
                    <span class="key"><i></i> Peak window 10 AM – 3 PM</span>
                </div>
                <div class="chart" id="chartHours" role="img" aria-label="<?= e($chartData['hours']['aria']) ?>"></div>
                <details class="viz-table">
                    <summary>View as table</summary>
                    <div class="viz-scroll"><table>
                        <thead><tr><th>Hour</th><th>Attendance</th><th>Window</th></tr></thead>
                        <tbody>
                        <?php foreach ($hourVals as $i => $v): ?>
                            <tr><td><?= e($hourTips[$i]['title']) ?></td><td class="n"><?= (int)$v ?></td><td><?= e($hourTips[$i]['extra'] ?: 'Normal') ?></td></tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table></div>
                </details>
            </div>

            <div class="hp-card rise d5">
                <div class="hp-card-head">
                    <div>
                        <h2>Attendance, last 7 days</h2>
                        <div class="sub">Daily total, ending today</div>
                    </div>
                    <a href="analytics.php">Analysis <i class="fas fa-arrow-right"></i></a>
                </div>
                <div class="chart" id="chartDays" role="img" aria-label="<?= e($chartData['days']['aria']) ?>"></div>
                <details class="viz-table">
                    <summary>View as table</summary>
                    <div class="viz-scroll"><table>
                        <thead><tr><th>Day</th><th>Attendance</th><th>In peak window</th></tr></thead>
                        <tbody>
                        <?php foreach ($days as $d): ?>
                            <tr><td><?= e($d['title']) ?></td><td class="n"><?= (int)$d['c'] ?></td><td class="n"><?= (int)$d['p'] ?></td></tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table></div>
                </details>
            </div>
        </section>

        <!-- Who checked in + quick access -->
        <section class="hp-row lower">
            <div class="hp-card rise d5">
                <div class="hp-card-head">
                    <div>
                        <h2><?= $listIsToday ? 'Who Attendance in today' : 'Latest Attendance' ?></h2>
                        <div class="sub"><?= $listIsToday ? 'Most recent first' : 'Nobody has Attendance yet today — showing the most recent records' ?></div>
                    </div>
                    <a href="activity_logs.php">View all logs <i class="fas fa-arrow-right"></i></a>
                </div>

                <?php if ($recent): ?>
                <div class="who-wrap">
                    <table class="who">
                        <thead>
                            <tr><th>Student</th><th>Course</th><th>Time</th><th>Voucher</th><th>Status</th></tr>
                        </thead>
                        <tbody>
                        <?php foreach ($recent as $row):
                            $ts      = strtotime($row['checkin_time']);
                            $hour    = (int)date('G', $ts);
                            $isPeak  = ($hour >= PEAK_START && $hour < PEAK_END);
                            $name    = $row['fullname'] ?? 'Unknown Student';
                            $parts   = preg_split('/\s+/', trim($name));
                            $initial = strtoupper(mb_substr($parts[0], 0, 1) . (count($parts) > 1 ? mb_substr(end($parts), 0, 1) : ''));
                            $course  = trim(($row['course'] ?? '') . ' ' . ($row['year'] ?? '') . ' ' . ($row['section'] ?? ''));
                            $timeTxt = $listIsToday ? date('h:i A', $ts) : date('M j, h:i A', $ts);
                        ?>
                            <tr>
                                <td>
                                    <div class="who-name">
                                        <span class="avatar"><?= e($initial) ?></span>
                                        <span><strong><?= e($name) ?></strong><small><?= e($row['student_id']) ?></small></span>
                                    </div>
                                </td>
                                <td><?= $course !== '' ? e($course) : '—' ?></td>
                                <td class="nw"><?= e($timeTxt) ?></td>
                                <td class="nw"><?= !empty($row['voucher_code']) ? '<code>' . e($row['voucher_code']) . '</code>' : '—' ?></td>
                                <td class="nw"><span class="pill <?= $isPeak ? 'peak' : 'normal' ?>"><i class="fas <?= $isPeak ? 'fa-fire' : 'fa-check' ?>"></i> <?= $isPeak ? 'Peak' : 'Normal' ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="empty-list"><i class="fas fa-clipboard-list"></i>No Attendance recorded yet.</div>
                <?php endif; ?>
            </div>

            <div class="hp-card rise d5">
                <div class="hp-card-head">
                    <div>
                        <h2>Quick access</h2>
                        <div class="sub">Jump to where you need to go</div>
                    </div>
                </div>
                <nav class="quick">
                    <a href="manage_students.php">
                        <span class="ico" style="background:rgba(14,165,233,.1);color:#0284c7;"><i class="fas fa-users"></i></span>
                        <span><strong>Students</strong><small>Register, import, QR passes</small></span>
                        <i class="fas fa-arrow-right go"></i>
                    </a>
                    <a href="voucher_management.php">
                        <span class="ico" style="background:rgba(139,92,246,.1);color:#7c3aed;"><i class="fas fa-ticket"></i></span>
                        <span><strong>Vouchers</strong><small>Create and manage codes</small></span>
                        <i class="fas fa-arrow-right go"></i>
                    </a>
                    <a href="activity_logs.php">
                        <span class="ico" style="background:rgba(245,158,11,.12);color:#b45309;"><i class="fas fa-history"></i></span>
                        <span><strong>Logs</strong><small>Search by date, export files</small></span>
                        <i class="fas fa-arrow-right go"></i>
                    </a>
                    <a href="analytics.php">
                        <span class="ico" style="background:rgba(168,85,247,.1);color:#9333ea;"><i class="fas fa-brain"></i></span>
                        <span><strong>Analysis</strong><small>Peak hours and accuracy</small></span>
                        <i class="fas fa-arrow-right go"></i>
                    </a>
                </nav>
            </div>
        </section>

        <div class="updated">Updated <?= e($now->format('h:i A')) ?> · refreshes every minute</div>
    </div>

    <script type="application/json" id="homeData"><?= $chartJson ?></script>
    <script>
    (function () {
        var DATA = JSON.parse(document.getElementById('homeData').textContent);
        var NS = 'http://www.w3.org/2000/svg';
        var C = { series: '#6366f1', hover: '#4f46e5', wash: 'rgba(99,102,241,0.11)', grid: '#eceff4',
                  axis: '#cbd5e1', ink: '#17213C', muted: '#64748b', surface: '#ffffff' };

        function svgEl(name, attrs, parent) {
            var n = document.createElementNS(NS, name);
            for (var k in attrs) n.setAttribute(k, attrs[k]);
            if (parent) parent.appendChild(n);
            return n;
        }
        function svgText(parent, x, y, str, attrs) {
            var t = svgEl('text', Object.assign({ x: x, y: y, 'font-size': 11, fill: C.muted, 'font-family': 'inherit' }, attrs || {}), parent);
            t.textContent = str;
            return t;
        }
        // Whole-number axis: top value and step, never more than 4 intervals
        function niceScale(v) {
            if (v <= 4) return { max: 4, step: 1 };
            var steps = [1, 2, 5, 10, 20, 25, 50, 100, 200, 250, 500, 1000, 2000, 5000, 10000];
            for (var i = 0; i < steps.length; i++) {
                if (Math.ceil(v / steps[i]) <= 4) return { max: Math.ceil(v / steps[i]) * steps[i], step: steps[i] };
            }
            return { max: v, step: Math.ceil(v / 4) };
        }
        function makeTip(host) {
            var tip = document.createElement('div');
            tip.className = 'tip';
            host.appendChild(tip);
            return {
                show: function (x, y, title, value, unit, extra) {
                    tip.textContent = '';
                    var v = document.createElement('div'); v.className = 'v';
                    var key = document.createElement('i'); v.appendChild(key);
                    v.appendChild(document.createTextNode(String(value)));
                    var u = document.createElement('span'); u.textContent = unit; v.appendChild(u);
                    var t = document.createElement('div'); t.textContent = title;
                    tip.appendChild(v); tip.appendChild(t);
                    if (extra) { var ex = document.createElement('div'); ex.style.color = C.muted; ex.textContent = extra; tip.appendChild(ex); }
                    tip.classList.add('show');
                    var w = tip.offsetWidth, h = tip.offsetHeight, hw = host.clientWidth;
                    var left = Math.max(0, Math.min(x - w / 2, hw - w));
                    var top = y - h - 10; if (top < 0) top = y + 14;
                    tip.style.left = left + 'px'; tip.style.top = top + 'px';
                },
                hide: function () { tip.classList.remove('show'); }
            };
        }
        function plural(n, one, many) { return n === 1 ? one : many; }

        /* ── Column chart: one series, thin rounded columns, peak window washed behind ── */
        function drawBars(host, d) {
            host.textContent = '';
            var W = Math.max(host.clientWidth || 520, 240), H = host.clientHeight || 236;
            var m = { l: 30, r: 6, t: 22, b: 26 }, pw = W - m.l - m.r, ph = H - m.t - m.b;
            var n = d.values.length, slot = pw / n;
            var maxV = Math.max.apply(null, d.values.concat([0]));
            var sc = niceScale(maxV);
            var y = function (v) { return m.t + ph - (v / sc.max) * ph; };
            var svg = svgEl('svg', { width: W, height: H, viewBox: '0 0 ' + W + ' ' + H }, host);

            // peak window wash (behind everything)
            var pf = Math.max(0, d.peakFrom), pt = Math.min(n, d.peakTo);
            if (pt > pf) svgEl('rect', { x: m.l + pf * slot, y: m.t, width: (pt - pf) * slot, height: ph, fill: C.wash }, svg);

            // gridlines + y ticks
            for (var v = 0; v <= sc.max; v += sc.step) {
                svgEl('line', { x1: m.l, x2: W - m.r, y1: y(v), y2: y(v), stroke: v === 0 ? C.axis : C.grid, 'stroke-width': 1 }, svg);
                svgText(svg, m.l - 8, y(v) + 4, v, { 'text-anchor': 'end' });
            }

            // x labels (every other one when slots are narrow)
            var skip = slot < 40 ? 2 : 1;
            d.labels.forEach(function (lab, i) {
                if (i % skip === 0) svgText(svg, m.l + slot * (i + 0.5), H - 8, lab, { 'text-anchor': 'middle' });
            });

            var tip = makeTip(host);
            var bw = Math.max(6, Math.min(24, slot * 0.62));
            var topIdx = d.values.indexOf(maxV);

            d.values.forEach(function (val, i) {
                var cx = m.l + slot * (i + 0.5), x = cx - bw / 2, y0 = y(0), yt = y(val), g = svgEl('g', {}, svg);
                var bar = null;
                if (val > 0) {
                    var r = Math.min(4, bw / 2, y0 - yt);
                    bar = svgEl('path', { fill: C.series,
                        d: 'M' + x + ',' + y0 + ' L' + x + ',' + (yt + r) + ' Q' + x + ',' + yt + ' ' + (x + r) + ',' + yt +
                           ' L' + (x + bw - r) + ',' + yt + ' Q' + (x + bw) + ',' + yt + ' ' + (x + bw) + ',' + (yt + r) + ' L' + (x + bw) + ',' + y0 + ' Z' }, g);
                }
                if (val > 0 && i === topIdx) svgText(svg, cx, yt - 6, val, { 'text-anchor': 'middle', fill: C.ink, 'font-weight': 700, 'font-size': 12 });

                // hit area: the whole column slot, taller than the mark
                var hit = svgEl('rect', { x: m.l + slot * i, y: m.t, width: slot, height: ph + m.b, fill: 'transparent', tabindex: 0,
                    'aria-label': d.tips[i].title + ': ' + val + ' ' + plural(val, 'check-in', 'check-ins') }, g);
                var on = function () { if (bar) bar.setAttribute('fill', C.hover); tip.show(cx, yt, d.tips[i].title, val, plural(val, 'check-in', 'check-ins'), d.tips[i].extra); };
                var off = function () { if (bar) bar.setAttribute('fill', C.series); tip.hide(); };
                hit.addEventListener('pointerenter', on); hit.addEventListener('pointerleave', off);
                hit.addEventListener('focus', on); hit.addEventListener('blur', off);
            });

            if (maxV === 0) { var em = document.createElement('div'); em.className = 'empty-msg'; em.textContent = d.empty; host.appendChild(em); }
        }

        /* ── Line + area chart: one series, crosshair that snaps to the day ── */
        function drawLine(host, d) {
            host.textContent = '';
            var W = Math.max(host.clientWidth || 360, 240), H = host.clientHeight || 236;
            var m = { l: 30, r: 24, t: 22, b: 26 }, pw = W - m.l - m.r, ph = H - m.t - m.b;
            var n = d.values.length, step = pw / (n - 1);
            var maxV = Math.max.apply(null, d.values.concat([0]));
            var sc = niceScale(maxV);
            var X = function (i) { return m.l + step * i; };
            var Y = function (v) { return m.t + ph - (v / sc.max) * ph; };
            var svg = svgEl('svg', { width: W, height: H, viewBox: '0 0 ' + W + ' ' + H }, host);

            for (var v = 0; v <= sc.max; v += sc.step) {
                svgEl('line', { x1: m.l, x2: W - m.r, y1: Y(v), y2: Y(v), stroke: v === 0 ? C.axis : C.grid, 'stroke-width': 1 }, svg);
                svgText(svg, m.l - 8, Y(v) + 4, v, { 'text-anchor': 'end' });
            }
            var skip = step < 46 ? 2 : 1;
            d.labels.forEach(function (lab, i) {
                if ((n - 1 - i) % skip === 0) svgText(svg, X(i), H - 8, lab, { 'text-anchor': 'middle', 'font-weight': i === n - 1 ? 700 : 400, fill: i === n - 1 ? C.ink : C.muted });
            });

            var pts = d.values.map(function (val, i) { return X(i) + ',' + Y(val); });
            svgEl('path', { d: 'M' + pts.join(' L') + ' L' + X(n - 1) + ',' + Y(0) + ' L' + X(0) + ',' + Y(0) + ' Z', fill: C.series, 'fill-opacity': 0.1 }, svg);
            svgEl('path', { d: 'M' + pts.join(' L'), fill: 'none', stroke: C.series, 'stroke-width': 2, 'stroke-linejoin': 'round', 'stroke-linecap': 'round' }, svg);

            // end dot with a 2px surface ring + its value as the one direct label
            var last = n - 1;
            svgEl('circle', { cx: X(last), cy: Y(d.values[last]), r: 6, fill: C.surface }, svg);
            svgEl('circle', { cx: X(last), cy: Y(d.values[last]), r: 4, fill: C.series }, svg);
            svgText(svg, X(last) + 6, Y(d.values[last]) - 12, d.values[last], { 'text-anchor': 'end', fill: C.ink, 'font-weight': 700, 'font-size': 12 });

            // hover layer
            var cross = svgEl('line', { y1: m.t, y2: Y(0), stroke: C.axis, 'stroke-width': 1, visibility: 'hidden' }, svg);
            var ringO = svgEl('circle', { r: 6, fill: C.surface, visibility: 'hidden' }, svg);
            var ringI = svgEl('circle', { r: 4, fill: C.series, visibility: 'hidden' }, svg);
            var tip = makeTip(host);
            d.values.forEach(function (val, i) {
                var hit = svgEl('rect', { x: X(i) - step / 2, y: m.t, width: step, height: ph + m.b, fill: 'transparent', tabindex: 0,
                    'aria-label': d.tips[i].title + ': ' + val + ' ' + plural(val, 'check-in', 'check-ins') }, svg);
                var on = function () {
                    cross.setAttribute('x1', X(i)); cross.setAttribute('x2', X(i)); cross.setAttribute('visibility', 'visible');
                    [ringO, ringI].forEach(function (c) { c.setAttribute('cx', X(i)); c.setAttribute('cy', Y(val)); c.setAttribute('visibility', 'visible'); });
                    tip.show(X(i), Y(val), d.tips[i].title, val, plural(val, 'check-in', 'check-ins'), d.tips[i].extra);
                };
                var off = function () { [cross, ringO, ringI].forEach(function (c) { c.setAttribute('visibility', 'hidden'); }); tip.hide(); };
                hit.addEventListener('pointerenter', on); hit.addEventListener('pointerleave', off);
                hit.addEventListener('focus', on); hit.addEventListener('blur', off);
            });

            if (maxV === 0) { var em = document.createElement('div'); em.className = 'empty-msg'; em.textContent = d.empty; host.appendChild(em); }
        }

        var hoursHost = document.getElementById('chartHours'), daysHost = document.getElementById('chartDays');
        function render() { drawBars(hoursHost, DATA.hours); drawLine(daysHost, DATA.days); }
        render();

        var lastW = [hoursHost.clientWidth, daysHost.clientWidth], raf = 0;
        if (window.ResizeObserver) {
            new ResizeObserver(function () {
                var w = [hoursHost.clientWidth, daysHost.clientWidth];
                if (w[0] === lastW[0] && w[1] === lastW[1]) return;
                lastW = w; cancelAnimationFrame(raf); raf = requestAnimationFrame(render);
            }).observe(document.querySelector('.main-content'));
        }

        // Keep the numbers fresh without touching anything while the tab is in the background
        setInterval(function () { if (!document.hidden) location.reload(); }, 60000);
    })();
    </script>

</body>
</html>
<?php $conn->close(); ?>
