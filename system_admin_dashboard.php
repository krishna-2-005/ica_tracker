<?php
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/includes/activity_logger.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'system_admin') {
    header('Location: admin_login.php');
    exit;
}

$systemAdminId      = (int)$_SESSION['user_id'];
$systemAdminNameRaw = isset($_SESSION['name']) ? trim((string)$_SESSION['name']) : '';
$systemAdminNameDisplay = $systemAdminNameRaw !== '' ? format_person_display($systemAdminNameRaw) : 'SYSTEM ADMIN';

$todayStart = date('Y-m-d 00:00:00');
$todayEnd   = date('Y-m-d 23:59:59');
$weekStart  = date('Y-m-d 00:00:00', strtotime('monday this week'));
$weekEnd    = date('Y-m-d H:i:s');
$todayLabel = date('d M Y') . ', 12:00 AM - 11:59 PM';
$weekLabel  = date('d M Y', strtotime($weekStart)) . ' - ' . date('d M Y');

$sensitiveActions = [
    'marks_csv_bulk_upload','marks_manual_update','db_snapshot_downloaded',
    'assignment_created','assignment_deleted','assignment_updated',
    'subject_created','subject_deleted','password_reset_requested',
    'admin_login_failed','login_failed',
];

function sa_scalar(mysqli $conn, string $sql, array $p = [], string $t = ''): int {
    $s = mysqli_prepare($conn, $sql); if (!$s) return 0;
    if ($p) mysqli_stmt_bind_param($s, $t, ...$p);
    mysqli_stmt_execute($s);
    $r = mysqli_stmt_get_result($s); $v = 0;
    if ($r && ($row = mysqli_fetch_row($r))) $v = (int)$row[0];
    if ($r) mysqli_free_result($r);
    mysqli_stmt_close($s);
    return $v;
}

// ── Stat cards ────────────────────────────────────────────────
$todayActiveUsers  = sa_scalar($conn, "SELECT COUNT(DISTINCT actor_id) FROM activity_logs WHERE actor_id IS NOT NULL AND action IN ('login_success','admin_login_success') AND created_at BETWEEN ? AND ?", [$todayStart,$todayEnd], 'ss');
$weekActiveUsers   = sa_scalar($conn, "SELECT COUNT(DISTINCT actor_id) FROM activity_logs WHERE actor_id IS NOT NULL AND action IN ('login_success','admin_login_success') AND created_at BETWEEN ? AND ?", [$weekStart,$weekEnd], 'ss');
$todayFailedLogins = sa_scalar($conn, "SELECT COUNT(*) FROM activity_logs WHERE action IN ('login_failed','admin_login_failed') AND created_at BETWEEN ? AND ?", [$todayStart,$todayEnd], 'ss');
$sensitivePH    = implode(',', array_fill(0, count($sensitiveActions), '?'));
$sensitiveTypes = str_repeat('s', count($sensitiveActions)) . 'ss';
$todaySensitive = sa_scalar($conn, "SELECT COUNT(*) FROM activity_logs WHERE action IN ($sensitivePH) AND created_at BETWEEN ? AND ?", array_merge($sensitiveActions, [$todayStart,$todayEnd]), $sensitiveTypes);
$totalUsers    = sa_scalar($conn, "SELECT COUNT(*) FROM users WHERE role != 'system_admin'");
$totalTeachers = sa_scalar($conn, "SELECT COUNT(*) FROM users WHERE role = 'teacher'");
$totalClasses  = sa_scalar($conn, "SELECT COUNT(*) FROM classes");
$totalSubjects = sa_scalar($conn, "SELECT COUNT(*) FROM subjects");

// ── Chart 1: Logins per day last 7 days ──────────────────────
$loginDays = []; $loginCounts = [];
for ($i = 6; $i >= 0; $i--) {
    $day = date('Y-m-d', strtotime("-$i days"));
    $loginDays[]   = date('d M', strtotime("-$i days"));
    $loginCounts[] = sa_scalar($conn, "SELECT COUNT(*) FROM activity_logs WHERE action IN ('login_success','admin_login_success') AND DATE(created_at) = ?", [$day], 's');
}

// ── Chart 2: Top 6 action types ──────────────────────────────
$actionLabels = []; $actionCounts = [];
$r = mysqli_query($conn, "SELECT action, COUNT(*) AS cnt FROM activity_logs WHERE action IS NOT NULL AND action <> '' GROUP BY action ORDER BY cnt DESC LIMIT 6");
if ($r) { while ($row = mysqli_fetch_assoc($r)) { $actionLabels[] = str_replace('_',' ',$row['action']); $actionCounts[] = (int)$row['cnt']; } mysqli_free_result($r); }

// ── Chart 3: Today breakdown donut ───────────────────────────
$donutData   = [$todayActiveUsers, $todayFailedLogins, $todaySensitive];
$donutLabels = ['Active Logins','Failed Logins','Sensitive Actions'];

// ── Chart 4: Failed logins last 7 days ───────────────────────
$failedDays = []; $failedCounts = [];
for ($i = 6; $i >= 0; $i--) {
    $day = date('Y-m-d', strtotime("-$i days"));
    $failedDays[]   = date('d M', strtotime("-$i days"));
    $failedCounts[] = sa_scalar($conn, "SELECT COUNT(*) FROM activity_logs WHERE action IN ('login_failed','admin_login_failed') AND DATE(created_at) = ?", [$day], 's');
}

// ── Chart 5: Role distribution ───────────────────────────────
$roleLabels = []; $roleCounts = [];
$r = mysqli_query($conn, "SELECT role, COUNT(*) AS cnt FROM users GROUP BY role ORDER BY cnt DESC");
if ($r) { while ($row = mysqli_fetch_assoc($r)) { $roleLabels[] = ucfirst($row['role']); $roleCounts[] = (int)$row['cnt']; } mysqli_free_result($r); }

mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Admin Dashboard – ICA Tracker</title>
    <link rel="icon" type="image/png" href="nmimsvertical.jpg">
    <link rel="stylesheet" href="ica_tracker.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        .sa-stats { display:grid; grid-template-columns:repeat(auto-fit,minmax(170px,1fr)); gap:10px; margin-bottom:14px; }
        .sa-stat  { background:#fff; border-radius:7px; border:1px solid #e5e7eb; padding:10px 12px; display:flex; align-items:center; gap:10px; box-shadow:0 1px 4px rgba(0,0,0,.06); text-decoration:none; transition:transform .15s,box-shadow .15s; }
        .sa-stat:hover { transform:translateY(-2px); box-shadow:0 4px 12px rgba(166,25,46,.12); }
        .sa-stat-icon { width:34px;height:34px;border-radius:7px;display:flex;align-items:center;justify-content:center;font-size:1rem;flex-shrink:0; }
        .si-red    { background:rgba(166,25,46,.1);  color:#A6192E; }
        .si-blue   { background:rgba(37,99,235,.1);  color:#2563eb; }
        .si-amber  { background:rgba(217,119,6,.1);  color:#d97706; }
        .si-purple { background:rgba(124,58,237,.1); color:#7c3aed; }
        .si-green  { background:rgba(22,163,74,.1);  color:#16a34a; }
        .si-teal   { background:rgba(13,148,136,.1); color:#0d9488; }
        .sa-stat-info h4 { margin:0 0 2px; font-size:.68rem; color:#6b7280; text-transform:uppercase; letter-spacing:.04em; }
        .sa-stat-val { font-size:1.4rem; font-weight:700; color:#111827; line-height:1; }
        .sa-stat-sub { font-size:.68rem; color:#9ca3af; margin-top:2px; }

        .charts-row { display:grid; grid-template-columns:2fr 1fr; gap:12px; margin-bottom:12px; }
        .charts-row2 { display:grid; grid-template-columns:1fr 1fr 1fr; gap:12px; margin-bottom:12px; }
        @media(max-width:900px){ .charts-row,.charts-row2{ grid-template-columns:1fr; } }

        .chart-card { background:#fff; border-radius:7px; border:1px solid #e5e7eb; padding:10px 13px; box-shadow:0 1px 4px rgba(0,0,0,.06); }
        .chart-card-title { font-size:.78rem; font-weight:700; color:#111827; margin-bottom:8px; display:flex; align-items:center; gap:6px; }
        .chart-card-title i { color:#A6192E; font-size:.75rem; }
        .chart-card canvas { max-height:200px; }

        .quick-links { display:grid; grid-template-columns:repeat(auto-fit,minmax(140px,1fr)); gap:8px; margin-bottom:14px; }
        .quick-link { background:#fff; border:1px solid #e5e7eb; border-radius:7px; padding:9px 12px; text-decoration:none; color:#374151; font-size:.78rem; font-weight:600; display:flex; align-items:center; gap:8px; transition:background .15s,border-color .15s; }
        .quick-link:hover { background:#fdf1f3; border-color:#A6192E; color:#A6192E; text-decoration:none; }
        .quick-link i { color:#A6192E; font-size:.85rem; }

        .section-label { font-size:.72rem; font-weight:700; color:#6b7280; text-transform:uppercase; letter-spacing:.06em; margin:0 0 8px; }

        body.dark-mode .sa-stat,.dark-mode .chart-card { background:#252d3d; border-color:#334155; }
        body.dark-mode .sa-stat-info h4 { color:#94a3b8; }
        body.dark-mode .sa-stat-val { color:#f1f5f9; }
        body.dark-mode .quick-link { background:#252d3d; border-color:#334155; color:#cbd5e1; }
    </style>
</head>
<body>
<div class="dashboard">
    <div class="sidebar">
        <h2>ICA Tracker</h2>
        <a href="system_admin_dashboard.php" class="active"><i class="fas fa-shield-alt"></i> <span>Dashboard</span></a>
        <a href="system_admin_activity_feed.php"><i class="fas fa-stream"></i> <span>Activity Feed</span></a>
        <a href="system_admin_export_sql.php"><i class="fas fa-database"></i> <span>Backup & Restore</span></a>
        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a>
    </div>

    <div class="main-content">
        <div class="header">
            <h2>Welcome, <?php echo htmlspecialchars($systemAdminNameDisplay ?: $systemAdminNameRaw); ?>!</h2>
        </div>

        <div class="container">

            <p class="section-label">Overview</p>
            <div class="sa-stats">
                <a class="sa-stat" href="system_admin_activity_feed.php?scope=today&activity_preset=active_logins">
                    <div class="sa-stat-icon si-red"><i class="fas fa-users"></i></div>
                    <div class="sa-stat-info"><h4>Today Active</h4><div class="sa-stat-val"><?php echo $todayActiveUsers; ?></div><div class="sa-stat-sub"><?php echo htmlspecialchars($todayLabel); ?></div></div>
                </a>
                <a class="sa-stat" href="system_admin_activity_feed.php?scope=week&activity_preset=active_logins">
                    <div class="sa-stat-icon si-blue"><i class="fas fa-user-check"></i></div>
                    <div class="sa-stat-info"><h4>Week Active</h4><div class="sa-stat-val"><?php echo $weekActiveUsers; ?></div><div class="sa-stat-sub"><?php echo htmlspecialchars($weekLabel); ?></div></div>
                </a>
                <a class="sa-stat" href="system_admin_activity_feed.php?scope=today&activity_preset=failed_logins">
                    <div class="sa-stat-icon si-amber"><i class="fas fa-exclamation-triangle"></i></div>
                    <div class="sa-stat-info"><h4>Failed Logins</h4><div class="sa-stat-val"><?php echo $todayFailedLogins; ?></div><div class="sa-stat-sub">Today</div></div>
                </a>
                <a class="sa-stat" href="system_admin_activity_feed.php?scope=today&activity_preset=sensitive_actions">
                    <div class="sa-stat-icon si-purple"><i class="fas fa-lock"></i></div>
                    <div class="sa-stat-info"><h4>Sensitive Actions</h4><div class="sa-stat-val"><?php echo $todaySensitive; ?></div><div class="sa-stat-sub">Today</div></div>
                </a>
                <div class="sa-stat">
                    <div class="sa-stat-icon si-green"><i class="fas fa-chalkboard-teacher"></i></div>
                    <div class="sa-stat-info"><h4>Teachers</h4><div class="sa-stat-val"><?php echo $totalTeachers; ?></div><div class="sa-stat-sub">Registered</div></div>
                </div>
                <div class="sa-stat">
                    <div class="sa-stat-icon si-teal"><i class="fas fa-layer-group"></i></div>
                    <div class="sa-stat-info"><h4>Classes</h4><div class="sa-stat-val"><?php echo $totalClasses; ?></div><div class="sa-stat-sub">Active</div></div>
                </div>
            </div>

            <p class="section-label">Analytics</p>
            <div class="charts-row">
                <div class="chart-card">
                    <div class="chart-card-title"><i class="fas fa-sign-in-alt"></i> Logins – Last 7 Days</div>
                    <canvas id="loginChart"></canvas>
                </div>
                <div class="chart-card">
                    <div class="chart-card-title"><i class="fas fa-chart-pie"></i> Today's Breakdown</div>
                    <canvas id="donutChart"></canvas>
                </div>
            </div>

            <div class="charts-row2">
                <div class="chart-card" style="grid-column:span 2;">
                    <div class="chart-card-title"><i class="fas fa-chart-bar"></i> Top Action Types (All Time)</div>
                    <canvas id="actionChart"></canvas>
                </div>
                <div class="chart-card">
                    <div class="chart-card-title"><i class="fas fa-times-circle"></i> Failed Logins – Last 7 Days</div>
                    <canvas id="failedChart"></canvas>
                </div>
            </div>

            <p class="section-label">Quick Actions</p>
            <div class="quick-links">
                <a href="system_admin_activity_feed.php" class="quick-link"><i class="fas fa-stream"></i> Activity Feed</a>
                <a href="system_admin_export_sql.php" class="quick-link"><i class="fas fa-database"></i> Backup & Restore</a>
                <a href="system_admin_activity_feed.php?scope=today&activity_preset=failed_logins" class="quick-link"><i class="fas fa-exclamation-circle"></i> Failed Logins</a>
                <a href="system_admin_activity_feed.php?scope=today&activity_preset=sensitive_actions" class="quick-link"><i class="fas fa-lock"></i> Sensitive Actions</a>
                <a href="system_admin_activity_feed.php?scope=all" class="quick-link"><i class="fas fa-history"></i> Full Audit Log</a>
            </div>

        </div>

        <div class="footer-bottom">
            &copy; <?php echo date('Y'); ?> Kuchuru Sai Krishna Reddy – STME. All rights reserved.
        </div>
    </div>
</div>

<script>
const RED = '#A6192E', LIGHT = 'rgba(166,25,46,0.1)';
const opts = { responsive:true, plugins:{ legend:{ display:false } }, scales:{ y:{ beginAtZero:true, ticks:{ font:{ size:10 } } }, x:{ ticks:{ font:{ size:10 } } } } };

new Chart(document.getElementById('loginChart'), {
    type:'line',
    data:{ labels:<?php echo json_encode($loginDays); ?>, datasets:[{ label:'Logins', data:<?php echo json_encode($loginCounts); ?>, borderColor:RED, backgroundColor:LIGHT, borderWidth:2, pointBackgroundColor:RED, fill:true, tension:0.4 }] },
    options:{ ...opts, plugins:{ legend:{ display:false } } }
});

new Chart(document.getElementById('donutChart'), {
    type:'doughnut',
    data:{ labels:<?php echo json_encode($donutLabels); ?>, datasets:[{ data:<?php echo json_encode($donutData); ?>, backgroundColor:['#A6192E','#f59e0b','#7c3aed'], borderWidth:2, borderColor:'#fff' }] },
    options:{ responsive:true, plugins:{ legend:{ position:'bottom', labels:{ font:{ size:10 }, boxWidth:10 } } }, cutout:'65%' }
});

new Chart(document.getElementById('actionChart'), {
    type:'bar',
    data:{ labels:<?php echo json_encode($actionLabels); ?>, datasets:[{ data:<?php echo json_encode($actionCounts); ?>, backgroundColor:['#A6192E','#2563eb','#16a34a','#d97706','#7c3aed','#0d9488'], borderRadius:4 }] },
    options:{ ...opts }
});

new Chart(document.getElementById('failedChart'), {
    type:'bar',
    data:{ labels:<?php echo json_encode($failedDays); ?>, datasets:[{ data:<?php echo json_encode($failedCounts); ?>, backgroundColor:'rgba(220,38,38,0.7)', borderRadius:4 }] },
    options:{ ...opts }
});
</script>
<script>
if (localStorage.getItem('theme') === 'dark') document.body.classList.add('dark-mode');
</script>
</body>
</html>
