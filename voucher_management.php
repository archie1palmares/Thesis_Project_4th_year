<?php if (session_status() === PHP_SESSION_NONE) { session_start(); } ?>
<?php
date_default_timezone_set('Asia/Manila');

$conn = new mysqli("localhost", "root", "", "login");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");


/* Search and filter */
$search = trim($_GET['search'] ?? '');
$status_filter = $_GET['status'] ?? '';

$where = [];
$params = [];
$types = '';

if ($search !== '') {
    $where[] = "(voucher_codes LIKE ? OR notes LIKE ?)";
    $search_value = "%" . $search . "%";
    $params[] = $search_value;
    $params[] = $search_value;
    $types .= "ss";
}

if ($status_filter !== '') {
    $where[] = "Status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

$where_sql = '';

if (!empty($where)) {
    $where_sql = "WHERE " . implode(" AND ", $where);
}

/* Voucher list */
$sql = "
    SELECT
        ID,
        voucher_codes,
        Status,
        CreatedDate,
        UsedDate,
        expires_at,
        duration_minutes,
        notes,
        created_at,
        updated_at
    FROM Voucher_codes
    $where_sql
    ORDER BY ID DESC
";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Database error: " . $conn->error);
}

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

/* Statistics */
$total = 0;
$active = 0;
$used = 0;
$expired = 0;
$inactive = 0;

$stats = $conn->query("
    SELECT
        COUNT(*) AS total,
        SUM(Status = 'active' AND (expires_at IS NULL OR expires_at > NOW())) AS active,
        SUM(Status = 'used') AS used,
        SUM(Status = 'active' AND expires_at IS NOT NULL AND expires_at <= NOW()) AS expired,
        SUM(Status = 'inactive') AS inactive
    FROM Voucher_codes
");

if ($stats) {
    $stats_row = $stats->fetch_assoc();

    $total = (int)($stats_row['total'] ?? 0);
    $active = (int)($stats_row['active'] ?? 0);
    $used = (int)($stats_row['used'] ?? 0);
    $expired = (int)($stats_row['expired'] ?? 0);
    $inactive = (int)($stats_row['inactive'] ?? 0);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Voucher List | LibFlow AI</title>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

<style>
* {
    box-sizing: border-box;
}

body {
    margin: 0;
    font-family: Arial, sans-serif;
    background: #f6f7fb;
    color: #333;
}

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

.sidebar h2 {
    margin: 0 0 35px;
    padding-left: 15px;
    color: #6c63ff;
    font-size: 21px;
}

.nav-btn {
    display: flex;
    align-items: center;
    text-decoration: none;
    color: #64748b;
    padding: 12px 20px;
    border-radius: 12px;
    font-family: 'Poppins', sans-serif;
    font-weight: 500;
    margin-bottom: 10px;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    opacity: 0;
    animation: navSlideIn 0.45s cubic-bezier(0.22, 1, 0.36, 1) forwards;
}

.nav-btn:hover {
    background-color: rgba(99, 102, 241, 0.05);
    color: #6366f1;
    transform: translateX(5px);
}

.nav-btn.active {
    background-color: #6366f1;
    color: #fff;
    box-shadow: 0 4px 15px rgba(99, 102, 241, 0.3);
}

.nav-btn i {
    margin-right: 15px;
    font-size: 1.1rem;
    width: 25px;
    text-align: center;
}


@keyframes navSlideIn {
    from {
        opacity: 0;
        transform: translateX(-18px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

.sidebar .nav-btn:nth-of-type(1) { animation-delay: 0.05s; }
.sidebar .nav-btn:nth-of-type(2) { animation-delay: 0.11s; }
.sidebar .nav-btn:nth-of-type(3) { animation-delay: 0.17s; }
.sidebar .nav-btn:nth-of-type(4) { animation-delay: 0.23s; }
.sidebar .nav-btn:nth-of-type(5) { animation-delay: 0.29s; }
.sidebar .nav-btn:nth-of-type(6) { animation-delay: 0.35s; }
.sidebar .nav-btn:nth-of-type(7) { animation-delay: 0.41s; }
.sidebar .nav-btn:nth-of-type(8) { animation-delay: 0.47s; }

@media (prefers-reduced-motion: reduce) {
    .nav-btn {
        animation: none !important;
        opacity: 1 !important;
        transform: none !important;
    }
}

.main-content {
    margin-left: 260px;
    padding: 40px;
    width: calc(100% - 260px);
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
}

.page-header h1 {
    margin: 0;
    font-size: 28px;
    color: #17213c;
}

.page-header p {
    margin: 6px 0 0;
    color: #888;
    font-size: 13px;
}

.stats {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 15px;
    margin-bottom: 25px;
}

.stat-card {
    background: #fff;
    border-radius: 14px;
    padding: 18px;
    box-shadow: 0 5px 20px rgba(0,0,0,.04);
}

.stat-card i {
    float: right;
    font-size: 20px;
    color: #6c63ff;
}

.stat-card small {
    display: block;
    color: #888;
    font-size: 11px;
    text-transform: uppercase;
    font-weight: bold;
}

.stat-card strong {
    display: block;
    margin-top: 7px;
    font-size: 26px;
    color: #17213c;
}

.card {
    background: #fff;
    border-radius: 16px;
    padding: 22px;
    box-shadow: 0 5px 20px rgba(0,0,0,.04);
}

.toolbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 15px;
    margin-bottom: 20px;
}

.search-form {
    display: flex;
    gap: 8px;
    flex: 1;
}

.search-form input,
.search-form select {
    height: 40px;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 0 12px;
    font-size: 13px;
    outline: none;
}

.search-form input {
    width: 300px;
}

.search-form input:focus,
.search-form select:focus {
    border-color: #6c63ff;
}

.btn {
    height: 40px;
    border: 0;
    border-radius: 8px;
    padding: 0 16px;
    cursor: pointer;
    font-size: 13px;
    font-weight: bold;
}

.btn-primary {
    background: #6c63ff;
    color: #fff;
}

.btn-light {
    background: #f1f2f6;
    color: #555;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
}

.table-wrap {
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
}

th {
    background: #f8f9fc;
    color: #777;
    font-size: 11px;
    text-transform: uppercase;
    text-align: left;
    padding: 13px 12px;
    border-bottom: 1px solid #eee;
}

td {
    padding: 14px 12px;
    border-bottom: 1px solid #f0f0f0;
    font-size: 13px;
    vertical-align: middle;
}

tr:hover td {
    background: #fafaff;
}

.voucher-code {
    color: #6c63ff;
    font-weight: bold;
    font-family: monospace;
    font-size: 13px;
}

.badge {
    display: inline-block;
    padding: 5px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: bold;
}

.badge-active {
    background: #dcfce7;
    color: #166534;
}

.badge-used {
    background: #e5e7eb;
    color: #475569;
}

.badge-expired {
    background: #fee2e2;
    color: #991b1b;
}

.badge-inactive {
    background: #fef3c7;
    color: #92400e;
}

.badge-other {
    background: #e5e7eb;
    color: #555;
}

.no-data {
    text-align: center;
    padding: 50px 20px;
    color: #999;
}

.no-data i {
    font-size: 35px;
    margin-bottom: 10px;
    color: #ccc;
}

.expired-text {
    color: #dc2626;
}

.no-expiry {
    color: #999;
}

.action-btn {
    color: #ef4444;
    text-decoration: none;
    font-size: 13px;
}

.action-btn:hover {
    text-decoration: underline;
}

@media(max-width:1100px) {
    .stats {
        grid-template-columns: repeat(3, 1fr);
    }
}

@media(max-width:800px) {
    .sidebar {
        width: 70px;
    }

    .sidebar h2 span,
    .nav-btn span {
        display: none;
    }

    .nav-btn {
        justify-content: center;
    }

    .main-content {
        margin-left: 70px;
        padding: 20px;
    }

    .stats {
        grid-template-columns: repeat(2, 1fr);
    }

    .toolbar {
        flex-direction: column;
        align-items: stretch;
    }

    .search-form {
        flex-wrap: wrap;
    }

    .search-form input {
        width: 100%;
    }
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

<div class="sidebar">

    <h2>
        <i class="fas fa-book-reader"></i>
        <span>LibAdmin</span>
    </h2>

    <a href="dashboard.php" class="nav-btn">
        <i class="fas fa-house"></i>
        <span>Home</span>
    </a>

    <!-- <a href="admin.php" class="nav-btn">
        <i class="fas fa-chart-line"></i>
        <span>Dashboard</span>
    </a> -->

    <a href="manage_students.php" class="nav-btn">
        <i class="fas fa-users"></i>
        <span>Students</span>
    </a>

    <a href="voucher_management.php" class="nav-btn active">
        <i class="fas fa-ticket"></i>
        <span>Vouchers</span>
    </a>

    <a href="activity_logs.php" class="nav-btn">
        <i class="fas fa-history"></i>
        <span>Logs</span>
    </a>

    <a href="analytics.php" class="nav-btn">
        <i class="fas fa-brain"></i>
        <span>Analysis</span>
    </a>

    <hr style="border:0;border-top:1px solid #eee;margin:20px 10px;">

    <a href="guide.php" class="nav-btn">
        <i class="fas fa-circle-question"></i>
        <span>System Guide</span>
    </a>

    <a href="logout.php" class="nav-btn" style="color:#ef4444;">
        <i class="fas fa-sign-out-alt"></i>
        <span>Logout</span>
    </a>


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

<div class="main-content">

    <div class="page-header">
        <div>
            <h1>Voucher List</h1>
            <p>Manage and monitor all Wi-Fi voucher codes.</p>
        </div>

        <a href="voucher_management.php" class="btn btn-light">
            <i class="fas fa-rotate-right"></i>&nbsp; Refresh
        </a>
    </div>

    <div class="stats">

        <div class="stat-card">
            <i class="fas fa-ticket"></i>
            <small>Total</small>
            <strong><?= $total ?></strong>
        </div>

        <div class="stat-card">
            <i class="fas fa-circle-check"></i>
            <small>Active</small>
            <strong><?= $active ?></strong>
        </div>

        <div class="stat-card">
            <i class="fas fa-check"></i>
            <small>Used</small>
            <strong><?= $used ?></strong>
        </div>

        <div class="stat-card">
            <i class="fas fa-clock"></i>
            <small>Expired</small>
            <strong><?= $expired ?></strong>
        </div>

        <div class="stat-card">
            <i class="fas fa-ban"></i>
            <small>Inactive</small>
            <strong><?= $inactive ?></strong>
        </div>

    </div>

    <div class="card">

        <div class="toolbar">

            <form method="GET" class="search-form">

                <input
                    type="text"
                    name="search"
                    placeholder="Search voucher code..."
                    value="<?= htmlspecialchars($search) ?>"
                >

                <select name="status">
                    <option value="">All Status</option>
                    <option value="active" <?= $status_filter === 'active' ? 'selected' : '' ?>>Active</option>
                    <option value="used" <?= $status_filter === 'used' ? 'selected' : '' ?>>Used</option>
                    <option value="expired" <?= $status_filter === 'expired' ? 'selected' : '' ?>>Expired</option>
                    <option value="inactive" <?= $status_filter === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                </select>

                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i> Search
                </button>

                <?php if ($search !== '' || $status_filter !== ''): ?>
                    <a href="voucher_management.php" class="btn btn-light">
                        Clear
                    </a>
                <?php endif; ?>

            </form>

        </div>

        <div class="table-wrap">

            <table>

                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Voucher Code</th>
                        <th>Status</th>
                        <th>Time Limit</th>
                        <th>Expires</th>
                        <th>Created</th>
                        <th>Used</th>
                        <th>Notes</th>
                    </tr>
                </thead>

                <tbody>

                <?php if ($result->num_rows > 0): ?>

                    <?php while ($row = $result->fetch_assoc()): ?>

                        <?php
                        $status = strtolower(trim($row['Status'] ?? ''));

                        if (
                            $status === 'active' &&
                            !empty($row['expires_at']) &&
                            strtotime($row['expires_at']) <= time()
                        ) {
                            $status = 'expired';
                        }

                        if ($status === 'active') {
                            $badge = 'badge-active';
                        } elseif ($status === 'used') {
                            $badge = 'badge-used';
                        } elseif ($status === 'expired') {
                            $badge = 'badge-expired';
                        } elseif ($status === 'inactive') {
                            $badge = 'badge-inactive';
                        } else {
                            $badge = 'badge-other';
                            $status = $status !== '' ? $status : 'Unknown';
                        }
                        ?>

                        <tr>

                            <td>
                                <?= (int)$row['ID'] ?>
                            </td>

                            <td>
                                <span class="voucher-code">
                                    <?= htmlspecialchars($row['voucher_codes']) ?>
                                </span>
                            </td>

                            <td>
                                <span class="badge <?= $badge ?>">
                                    <?= htmlspecialchars(ucfirst($status)) ?>
                                </span>
                            </td>

                            <td>
                                <?= (int)$row['duration_minutes'] ?> minutes
                            </td>

                            <td>
                                <?php if (!empty($row['expires_at'])): ?>

                                    <?php
                                    $expiry_time = strtotime($row['expires_at']);
                                    ?>

                                    <span class="<?= $expiry_time <= time() ? 'expired-text' : '' ?>">
                                        <?= date('M d, Y h:i A', $expiry_time) ?>
                                    </span>

                                <?php else: ?>

                                    <span class="no-expiry">
                                        No expiry
                                    </span>

                                <?php endif; ?>
                            </td>

                            <td>
                                <?= !empty($row['CreatedDate'])
                                    ? date('M d, Y h:i A', strtotime($row['CreatedDate']))
                                    : '—'
                                ?>
                            </td>

                            <td>
                                <?= !empty($row['UsedDate'])
                                    ? date('M d, Y h:i A', strtotime($row['UsedDate']))
                                    : '—'
                                ?>
                            </td>

                            <td>
                                <?= !empty($row['notes'])
                                    ? htmlspecialchars($row['notes'])
                                    : '—'
                                ?>
                            </td>

                        </tr>

                    <?php endwhile; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="8">
                            <div class="no-data">
                                <i class="fas fa-ticket"></i>
                                <div>No vouchers found.</div>
                            </div>
                        </td>
                    </tr>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

    </div>

</div>

</body>
</html>

<?php
$stmt->close();
$conn->close();
?> 
