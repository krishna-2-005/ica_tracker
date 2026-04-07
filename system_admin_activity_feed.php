<?php
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/includes/activity_logger.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'system_admin') {
    header('Location: admin_login.php'); exit;
}

$systemAdminId      = (int)$_SESSION['user_id'];
$systemAdminNameRaw = isset($_SESSION['name']) ? trim((string)$_SESSION['name']) : '';
$systemAdminNameDisplay = $systemAdminNameRaw !== '' ? format_person_display($systemAdminNameRaw) : 'SYSTEM ADMIN';

$todayStart = date('Y-m-d 00:00:00'); $todayEnd = date('Y-m-d 23:59:59');
$weekStart  = date('Y-m-d 00:00:00', strtotime('monday this week')); $weekEnd = date('Y-m-d H:i:s');
$todayLabel = date('d M Y') . ', 12:00 AM - 11:59 PM';
$weekLabel  = date('d M Y', strtotime($weekStart)) . ' - ' . date('d M Y');

$unlockableActions     = ['marks_csv_bulk_upload','marks_manual_update'];
$nonInspectableActions = ['login_success','admin_login_success','logout'];
$sensitiveActions = ['marks_csv_bulk_upload','marks_manual_update','db_snapshot_downloaded','assignment_created','assignment_deleted','assignment_updated','subject_created','subject_deleted','password_reset_requested','admin_login_failed','login_failed'];

if (!isset($_SESSION['system_admin_unlocked_activity_details']) || !is_array($_SESSION['system_admin_unlocked_activity_details']))
    $_SESSION['system_admin_unlocked_activity_details'] = [];
foreach ($_SESSION['system_admin_unlocked_activity_details'] as $eid => $exp)
    if ((int)$exp < time()) unset($_SESSION['system_admin_unlocked_activity_details'][$eid]);

$scope = $_GET['scope'] ?? 'today';
if (!in_array($scope, ['today','week','all'], true)) $scope = 'today';
$startDate      = trim((string)($_GET['start_date'] ?? ''));
$endDate        = trim((string)($_GET['end_date'] ?? ''));
$actionFilter   = trim((string)($_GET['activity_action'] ?? ''));
$activityPreset = trim((string)($_GET['activity_preset'] ?? ''));
if ($activityPreset !== '' && !in_array($activityPreset, ['active_logins','failed_logins','sensitive_actions'], true)) $activityPreset = '';
$dp = '/^\d{4}-\d{2}-\d{2}$/';
if ($startDate !== '' && !preg_match($dp, $startDate)) $startDate = '';
if ($endDate   !== '' && !preg_match($dp, $endDate))   $endDate   = '';
if ($startDate !== '' && $endDate !== '' && strtotime($startDate) > strtotime($endDate)) [$startDate,$endDate] = [$endDate,$startDate];

function build_feed_url(array $p = []): string {
    $c = [];
    foreach ($p as $k => $v) { if ($v === null) continue; $s = trim((string)$v); if ($s !== '') $c[$k] = $s; }
    return 'system_admin_activity_feed.php' . (!empty($c) ? ('?' . http_build_query($c)) : '');
}
function feed_scalar(mysqli $conn, string $sql, array $p = [], string $t = ''): int {
    $s = mysqli_prepare($conn, $sql); if (!$s) return 0;
    if ($p) mysqli_stmt_bind_param($s, $t, ...$p);
    mysqli_stmt_execute($s); $r = mysqli_stmt_get_result($s); $v = 0;
    if ($r && ($row = mysqli_fetch_row($r))) $v = (int)$row[0];
    if ($r) mysqli_free_result($r); mysqli_stmt_close($s); return $v;
}
function feed_flatten(array $m, string $pfx = ''): array {
    $rows = [];
    foreach ($m as $k => $v) {
        $fk = $pfx === '' ? (string)$k : ($pfx.'.'.(string)$k);
        if (is_array($v)) {
            if (empty($v)) { $rows[] = ['key'=>$fk,'value'=>'[]']; continue; }
            if (array_keys($v) === range(0,count($v)-1)) { $rows[] = ['key'=>$fk,'value'=>implode(', ',array_map(fn($i)=>is_scalar($i)||$i===null?(string)$i:json_encode($i),$v))]; continue; }
            $rows = array_merge($rows, feed_flatten($v, $fk)); continue;
        }
        $d = $v===null?'null':(is_bool($v)?($v?'true':'false'):(trim((string)$v)?:' (empty)'));
        $rows[] = ['key'=>$fk,'value'=>$d];
    }
    return $rows;
}
function feed_name(mysqli $conn, string $tbl, string $col, int $id): string {
    if ($id<=0) return '';
    $s = mysqli_prepare($conn,"SELECT $col AS label FROM $tbl WHERE id=? LIMIT 1"); if (!$s) return '';
    mysqli_stmt_bind_param($s,'i',$id); mysqli_stmt_execute($s);
    $r = mysqli_stmt_get_result($s); $l = '';
    if ($r && ($row=mysqli_fetch_assoc($r))) $l = trim((string)($row['label']??''));
    if ($r) mysqli_free_result($r); mysqli_stmt_close($s); return $l;
}

$sensitivePH    = implode(',', array_fill(0, count($sensitiveActions), '?'));
$sensitiveTypes = str_repeat('s', count($sensitiveActions)) . 'ss';
$todayActiveUsers  = feed_scalar($conn,"SELECT COUNT(DISTINCT actor_id) FROM activity_logs WHERE actor_id IS NOT NULL AND action IN ('login_success','admin_login_success') AND created_at BETWEEN ? AND ?",[$todayStart,$todayEnd],'ss');
$weekActiveUsers   = feed_scalar($conn,"SELECT COUNT(DISTINCT actor_id) FROM activity_logs WHERE actor_id IS NOT NULL AND action IN ('login_success','admin_login_success') AND created_at BETWEEN ? AND ?",[$weekStart,$weekEnd],'ss');
$todayFailedLogins = feed_scalar($conn,"SELECT COUNT(*) FROM activity_logs WHERE action IN ('login_failed','admin_login_failed') AND created_at BETWEEN ? AND ?",[$todayStart,$todayEnd],'ss');
$todaySensitive    = feed_scalar($conn,"SELECT COUNT(*) FROM activity_logs WHERE action IN ($sensitivePH) AND created_at BETWEEN ? AND ?",array_merge($sensitiveActions,[$todayStart,$todayEnd]),$sensitiveTypes);

$availableActions = [];
$ar = mysqli_query($conn,"SELECT DISTINCT action FROM activity_logs WHERE action IS NOT NULL AND action <> '' ORDER BY action ASC");
if ($ar) { while ($row=mysqli_fetch_assoc($ar)) { $a=trim((string)($row['action']??'')); if ($a!=='') $availableActions[]=$a; } mysqli_free_result($ar); }
if ($actionFilter!=='' && !in_array($actionFilter,$availableActions,true)) $actionFilter='';

$logsSql='SELECT id,action,event_label,details,actor_name,actor_username,actor_role,created_at,metadata FROM activity_logs';
$lt=''; $lp=[]; $wh=[];
if ($startDate!==''||$endDate!=='') {
    $rs=$startDate!==''?($startDate.' 00:00:00'):'1970-01-01 00:00:00';
    $re=$endDate!==''?($endDate.' 23:59:59'):date('Y-m-d H:i:s');
    $wh[]='created_at BETWEEN ? AND ?'; $lt.='ss'; $lp[]=$rs; $lp[]=$re;
} elseif ($scope==='today') { $wh[]='created_at BETWEEN ? AND ?'; $lt.='ss'; $lp[]=$todayStart; $lp[]=$todayEnd; }
  elseif ($scope==='week')  { $wh[]='created_at BETWEEN ? AND ?'; $lt.='ss'; $lp[]=$weekStart;  $lp[]=$weekEnd;  }
if ($actionFilter!=='') { $wh[]='action=?'; $lt.='s'; $lp[]=$actionFilter; }
elseif ($activityPreset!=='') {
    if ($activityPreset==='active_logins')  $wh[]="action IN ('login_success','admin_login_success')";
    elseif ($activityPreset==='failed_logins') $wh[]="action IN ('login_failed','admin_login_failed')";
    elseif ($activityPreset==='sensitive_actions') {
        $pp=implode(',',array_fill(0,count($sensitiveActions),'?'));
        $wh[]="action IN ($pp)"; $lt.=str_repeat('s',count($sensitiveActions));
        foreach ($sensitiveActions as $sa) $lp[]=$sa;
    }
}
if (!empty($wh)) $logsSql.=' WHERE '.implode(' AND ',$wh);
$logsSql.=" ORDER BY created_at DESC, id DESC LIMIT 120";
$logs=[];
$sl=mysqli_prepare($conn,$logsSql);
if ($sl) {
    if ($lp) mysqli_stmt_bind_param($sl,$lt,...$lp);
    mysqli_stmt_execute($sl); $rl=mysqli_stmt_get_result($sl);
    if ($rl) { while ($row=mysqli_fetch_assoc($rl)) $logs[]=$row; mysqli_free_result($rl); }
    mysqli_stmt_close($sl);
}

$selectedEntryId=(int)($_GET['entry']??0); $unlockError=''; $unlockSuccess='';
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['unlock_entry_id'])) {
    $ueid=(int)($_POST['unlock_entry_id']??0); $upw=$_POST['unlock_password']??'';
    $spw=mysqli_prepare($conn,'SELECT password FROM users WHERE id=? LIMIT 1'); $pv=false;
    if ($spw) {
        mysqli_stmt_bind_param($spw,'i',$systemAdminId); mysqli_stmt_execute($spw);
        $rpw=mysqli_stmt_get_result($spw); $prow=$rpw?mysqli_fetch_assoc($rpw):null;
        if ($rpw) mysqli_free_result($rpw); mysqli_stmt_close($spw);
        if ($prow) { $st=(string)$prow['password']; $pv=password_verify($upw,$st)||md5($upw)===$st; }
    }
    if ($ueid<=0||$upw===''||!$pv) {
        $unlockError='Password verification failed.';
        log_activity($conn,['actor_id'=>$systemAdminId,'event_type'=>'audit_unlock_failed','event_label'=>'Sensitive entry unlock failed','description'=>'Failed unlock attempt.','object_type'=>'activity_logs','object_id'=>(string)$ueid,'metadata'=>['entry_id'=>$ueid]]);
    } else {
        $_SESSION['system_admin_unlocked_activity_details'][$ueid]=time()+300;
        $unlockSuccess='Entry unlocked for 5 minutes.';
        log_activity($conn,['actor_id'=>$systemAdminId,'event_type'=>'audit_unlock_success','event_label'=>'Sensitive entry unlocked','description'=>'Entry unlocked.','object_type'=>'activity_logs','object_id'=>(string)$ueid,'metadata'=>['entry_id'=>$ueid]]);
        header('Location: '.build_feed_url(['scope'=>$scope,'entry'=>$ueid,'start_date'=>$startDate,'end_date'=>$endDate,'activity_action'=>$actionFilter,'activity_preset'=>$activityPreset]).'#entry-'.$ueid); exit;
    }
}
$selectedEntry=null;
if ($selectedEntryId>0) {
    $se=mysqli_prepare($conn,"SELECT id,action,event_label,details,actor_name,actor_username,actor_role,created_at,metadata FROM activity_logs WHERE id=? LIMIT 1");
    if ($se) { mysqli_stmt_bind_param($se,'i',$selectedEntryId); mysqli_stmt_execute($se); $re=mysqli_stmt_get_result($se); $selectedEntry=$re?mysqli_fetch_assoc($re):null; if ($re) mysqli_free_result($re); mysqli_stmt_close($se); }
}
if ($selectedEntry && in_array((string)$selectedEntry['action'],$nonInspectableActions,true)) { $selectedEntry=null; $selectedEntryId=0; }

$selMetaDisplay=[]; $selDetailRows=[]; $selNeedsUnlock=false; $selUnlocked=false;
if ($selectedEntry) {
    $selNeedsUnlock=in_array((string)$selectedEntry['action'],$unlockableActions,true);
    $selUnlocked=isset($_SESSION['system_admin_unlocked_activity_details'][$selectedEntryId])&&(int)$_SESSION['system_admin_unlocked_activity_details'][$selectedEntryId]>=time();
    if (!empty($selectedEntry['metadata'])) {
        $dec=json_decode((string)$selectedEntry['metadata'],true);
        if (is_array($dec)) {
            if (isset($dec['details_rows'])&&is_array($dec['details_rows'])) $selDetailRows=$dec['details_rows'];
            $mfd=$dec; unset($mfd['details_rows']); $mrows=feed_flatten($mfd);
            $sid2=isset($dec['subject_id'])?(int)$dec['subject_id']:0;
            $cid2=isset($dec['class_id'])?(int)$dec['class_id']:0;
            $secid=isset($dec['section_id'])?(int)$dec['section_id']:0;
            $sn=feed_name($conn,'subjects','subject_name',$sid2); if ($sn!=='') $mrows[]=['key'=>'resolved.subject_name','value'=>$sn];
            if ($cid2>0) {
                $sc2=mysqli_prepare($conn,'SELECT class_name,semester,school FROM classes WHERE id=? LIMIT 1');
                if ($sc2) { mysqli_stmt_bind_param($sc2,'i',$cid2); mysqli_stmt_execute($sc2); $rc2=mysqli_stmt_get_result($sc2); $crow=$rc2?mysqli_fetch_assoc($rc2):null; if ($rc2) mysqli_free_result($rc2); mysqli_stmt_close($sc2);
                    if ($crow) { if (trim((string)($crow['class_name']??''))!=='') $mrows[]=['key'=>'resolved.class_name','value'=>trim((string)$crow['class_name'])]; if (trim((string)($crow['semester']??''))!=='') $mrows[]=['key'=>'resolved.semester','value'=>trim((string)$crow['semester'])]; if (trim((string)($crow['school']??''))!=='') $mrows[]=['key'=>'resolved.school','value'=>trim((string)$crow['school'])]; }
                }
            }
            $secn=feed_name($conn,'sections','section_name',$secid); if ($secn!=='') $mrows[]=['key'=>'resolved.section_name','value'=>$secn];
            $bk=[];
            foreach ($mrows as $mr) { if (($mr['key']??'')!=='') $bk[$mr['key']]=(string)($mr['value']??''); }
            $rf=''; $tid=isset($dec['teacher_id'])?(int)$dec['teacher_id']:0;
            if ($tid>0) { $sf2=mysqli_prepare($conn,'SELECT name FROM users WHERE id=? LIMIT 1'); if ($sf2) { mysqli_stmt_bind_param($sf2,'i',$tid); mysqli_stmt_execute($sf2); $rrf=mysqli_stmt_get_result($sf2); $frow=$rrf?mysqli_fetch_assoc($rrf):null; if ($rrf) mysqli_free_result($rrf); mysqli_stmt_close($sf2); if ($frow) $rf=trim((string)($frow['name']??'')); } }
            $pref=[['label'=>'Teacher Id','key'=>'teacher_id'],['label'=>'Teacher Unique Id','key'=>'teacher_unique_id'],['label'=>'Subject Id','key'=>'subject_id'],['label'=>'Subject Name','key'=>'resolved.subject_name'],['label'=>'Class','key'=>'class_label'],['label'=>'Faculty Name','key'=>'faculty_name']];
            foreach ($pref as $col) {
                $val='';
                if ($col['key']==='faculty_name') $val=$rf?:trim((string)($selectedEntry['actor_name']??''))?:trim((string)($selectedEntry['actor_username']??''));
                elseif ($col['key']==='class_label') { $cn=trim((string)($bk['resolved.class_name']??'')); $sn2=trim((string)($bk['resolved.section_name']??'')); $val=$cn!==''?($sn2!==''?"$cn - Section $sn2":$cn):trim((string)($bk['class_label']??'')); }
                elseif (isset($bk[$col['key']])) $val=$bk[$col['key']];
                if ($val!=='') $selMetaDisplay[]=['key'=>$col['label'],'value'=>$val];
            }
        }
    }
}
mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Feed – ICA Tracker</title>
    <link rel="icon" type="image/png" href="nmimsvertical.jpg">
    <link rel="stylesheet" href="ica_tracker.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .feed-stats { display:grid; grid-template-columns:repeat(auto-fit,minmax(160px,1fr)); gap:10px; margin-bottom:14px; }
        .feed-stat  { background:#fff; border-radius:7px; border-left:3px solid #A6192E; padding:9px 12px; box-shadow:0 1px 4px rgba(0,0,0,.06); text-decoration:none; display:block; transition:transform .15s; }
        .feed-stat:hover { transform:translateY(-2px); }
        .feed-stat.blue   { border-left-color:#2563eb; }
        .feed-stat.amber  { border-left-color:#d97706; }
        .feed-stat.purple { border-left-color:#7c3aed; }
        .feed-stat h5 { margin:0 0 2px; font-size:.68rem; color:#6b7280; text-transform:uppercase; letter-spacing:.04em; }
        .feed-stat .fval { font-size:1.3rem; font-weight:700; color:#111827; }
        .toolbar { background:#fff; border-radius:7px; border:1px solid #e5e7eb; padding:8px 12px; margin-bottom:10px; display:flex; flex-wrap:wrap; gap:8px; }
        .toolbar a { display:inline-flex; align-items:center; gap:6px; padding:5px 10px; border-radius:5px; border:1px solid #d1d5db; background:#f9fafb; color:#374151; text-decoration:none; font-size:.78rem; font-weight:600; }
        .toolbar a.active { background:#A6192E; border-color:#A6192E; color:#fff; }
        .filters-form { margin-bottom:10px; background:#fff; border:1px solid #e5e7eb; border-radius:7px; padding:8px 12px; display:grid; grid-template-columns:repeat(auto-fit,minmax(160px,1fr)); gap:8px; align-items:end; }
        .filters-form label { display:block; margin-bottom:2px; font-size:.68rem; color:#6b7280; font-weight:600; text-transform:uppercase; letter-spacing:.04em; }
        .filters-form input, .filters-form select { width:100%; margin:0; padding:4px 7px; border:1px solid #d1d5db; border-radius:5px; font-size:.78rem; background:#f9fafb; height:28px; }
        .filters-actions { display:flex; gap:6px; flex-wrap:wrap; }
        .btn-filter { border:none; border-radius:5px; background:#A6192E; color:#fff; font-size:.78rem; font-weight:600; padding:4px 10px; cursor:pointer; height:28px; }
        .btn-filter.secondary { background:#e7edf4; color:#374151; text-decoration:none; display:inline-flex; align-items:center; }
        .audit-table { width:100%; border-collapse:collapse; background:#fff; font-size:.78rem; }
        .audit-table th, .audit-table td { border-bottom:1px solid #f0f0f0; padding:5px 8px; text-align:left; vertical-align:top; }
        .audit-table th { background:#A6192E; color:#fff; font-size:.7rem; text-transform:uppercase; letter-spacing:.04em; white-space:nowrap; }
        .audit-tag { display:inline-block; background:#fdf1f3; border:1px solid #f4c6ce; color:#8b1d2d; border-radius:999px; padding:1px 7px; font-size:.68rem; font-weight:600; }
        .inline-detail-cell { background:#fcfdff; padding:6px; }
        .inline-detail-wrap { border:1px solid #e5e7eb; border-radius:7px; padding:8px; background:#fff; }
        .inline-detail-wrap h5 { margin:0 0 6px; color:#A6192E; font-size:.85rem; }
        .unlock-form { display:flex; gap:8px; align-items:center; flex-wrap:wrap; margin-top:8px; }
        .unlock-form input { max-width:240px; margin:0; }
        .detail-table { width:100%; border-collapse:collapse; }
        .detail-table th, .detail-table td { border:1px solid #e5e7eb; padding:4px 6px; font-size:.75rem; text-align:left; }
        .detail-table th { background:#f8fafc; color:#334155; text-transform:uppercase; font-size:.68rem; }
        .meta-table { width:100%; border-collapse:collapse; margin-top:6px; }
        .meta-table th, .meta-table td { border:1px solid #e5e7eb; padding:4px 6px; font-size:.75rem; text-align:left; vertical-align:top; white-space:nowrap; }
        .meta-table th { background:#f8fafc; color:#475569; text-transform:uppercase; font-size:.68rem; }
        .meta-scroll, .detail-scroll { max-height:200px; overflow:auto; border-radius:6px; }
        .note { margin:3px 0; font-size:.75rem; }
        .note.error { color:#b42318; } .note.success { color:#117a37; }
        .table-wrap { overflow-x:auto; }
    </style>
</head>
<body>
<div class="dashboard">
    <div class="sidebar">
        <h2>ICA Tracker</h2>
        <a href="system_admin_dashboard.php"><i class="fas fa-shield-alt"></i> <span>Dashboard</span></a>
        <a href="system_admin_activity_feed.php" class="active"><i class="fas fa-stream"></i> <span>Activity Feed</span></a>
        <a href="system_admin_export_sql.php"><i class="fas fa-database"></i> <span>Backup & Restore</span></a>
        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a>
    </div>
    <div class="main-content">
        <div class="header">
            <h2><i class="fas fa-stream" style="color:#A6192E;margin-right:6px;font-size:.9em;"></i>Activity Feed</h2>
        </div>
        <div class="container">
            <div class="feed-stats">
                <a class="feed-stat" href="<?php echo htmlspecialchars(build_feed_url(['scope'=>'today','activity_preset'=>'active_logins'])); ?>">
                    <h5>Today Active</h5><div class="fval"><?php echo $todayActiveUsers; ?></div>
                </a>
                <a class="feed-stat blue" href="<?php echo htmlspecialchars(build_feed_url(['scope'=>'week','activity_preset'=>'active_logins'])); ?>">
                    <h5>Week Active</h5><div class="fval"><?php echo $weekActiveUsers; ?></div>
                </a>
                <a class="feed-stat amber" href="<?php echo htmlspecialchars(build_feed_url(['scope'=>'today','activity_preset'=>'failed_logins'])); ?>">
                    <h5>Failed Logins</h5><div class="fval"><?php echo $todayFailedLogins; ?></div>
                </a>
                <a class="feed-stat purple" href="<?php echo htmlspecialchars(build_feed_url(['scope'=>'today','activity_preset'=>'sensitive_actions'])); ?>">
                    <h5>Sensitive Actions</h5><div class="fval"><?php echo $todaySensitive; ?></div>
                </a>
            </div>

            <div class="toolbar">
                <a class="<?php echo $scope==='today'?'active':''; ?>" href="<?php echo htmlspecialchars(build_feed_url(['scope'=>'today'])); ?>"><i class="fas fa-calendar-day"></i> Today</a>
                <a class="<?php echo $scope==='week'?'active':''; ?>"  href="<?php echo htmlspecialchars(build_feed_url(['scope'=>'week'])); ?>"><i class="fas fa-calendar-week"></i> This Week</a>
                <a class="<?php echo $scope==='all'?'active':''; ?>"   href="<?php echo htmlspecialchars(build_feed_url(['scope'=>'all'])); ?>"><i class="fas fa-history"></i> All</a>
            </div>

            <form method="GET" class="filters-form">
                <input type="hidden" name="scope" value="<?php echo htmlspecialchars($scope); ?>">
                <div><label for="start_date">Start Date</label><input type="date" id="start_date" name="start_date" value="<?php echo htmlspecialchars($startDate); ?>"></div>
                <div><label for="end_date">End Date</label><input type="date" id="end_date" name="end_date" value="<?php echo htmlspecialchars($endDate); ?>"></div>
                <div>
                    <label for="activity_action">Filter by Action</label>
                    <select id="activity_action" name="activity_action">
                        <option value="">All Activities</option>
                        <?php foreach ($availableActions as $an): ?>
                            <option value="<?php echo htmlspecialchars($an); ?>" <?php echo $actionFilter===$an?'selected':''; ?>><?php echo htmlspecialchars($an); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filters-actions">
                    <button type="submit" class="btn-filter">Apply</button>
                    <a class="btn-filter secondary" href="<?php echo htmlspecialchars(build_feed_url(['scope'=>$scope])); ?>">Clear</a>
                </div>
            </form>

            <div class="table-wrap">
            <table class="audit-table">
                <thead><tr><th style="width:13%;">Time</th><th style="width:15%;">Actor</th><th style="width:15%;">Action</th><th>Summary</th><th style="width:10%;">Inspect</th></tr></thead>
                <tbody>
                <?php if (empty($logs)): ?>
                    <tr><td colspan="5" style="text-align:center;padding:16px;color:#6b7280;">No activity entries found.</td></tr>
                <?php else: foreach ($logs as $row):
                    $actorLabel = trim((string)($row['actor_name']??''))?:trim((string)($row['actor_username']??'System'));
                    $eventLabel = trim((string)($row['event_label']??''))?:trim((string)$row['action']);
                    $isSensitive  = in_array((string)$row['action'],$unlockableActions,true);
                    $canInspect   = !in_array((string)$row['action'],$nonInspectableActions,true);
                    $isCurrent    = $selectedEntry && (int)$selectedEntry['id']===(int)$row['id'];
                ?>
                <tr id="entry-<?php echo (int)$row['id']; ?>">
                    <td><?php echo htmlspecialchars((string)$row['created_at']); ?></td>
                    <td><?php echo htmlspecialchars($actorLabel); ?><br><span class="audit-tag"><?php echo htmlspecialchars((string)($row['actor_role']??'n/a')); ?></span></td>
                    <td><?php echo htmlspecialchars((string)$row['action']); ?></td>
                    <td><strong><?php echo htmlspecialchars($eventLabel); ?></strong><br><span style="color:#6b7280;"><?php echo htmlspecialchars((string)($row['details']??'')); ?></span></td>
                    <td>
                        <?php if (!$canInspect): ?><span style="color:#94a3b8;">—</span>
                        <?php elseif ($isCurrent): ?><a href="<?php echo htmlspecialchars(build_feed_url(['scope'=>$scope,'start_date'=>$startDate,'end_date'=>$endDate,'activity_action'=>$actionFilter,'activity_preset'=>$activityPreset]).'#entry-'.(int)$row['id']); ?>">Close</a>
                        <?php else: ?><a href="<?php echo htmlspecialchars(build_feed_url(['scope'=>$scope,'entry'=>(int)$row['id'],'start_date'=>$startDate,'end_date'=>$endDate,'activity_action'=>$actionFilter,'activity_preset'=>$activityPreset]).'#entry-'.(int)$row['id']); ?>">Open</a>
                        <?php endif; ?>
                        <?php if ($isSensitive): ?><br><span class="audit-tag">🔒 Pwd</span><?php endif; ?>
                    </td>
                </tr>
                <?php if ($isCurrent): ?>
                <tr><td colspan="5" class="inline-detail-cell">
                    <div class="inline-detail-wrap">
                        <h5>Entry #<?php echo (int)$selectedEntry['id']; ?> — <?php echo htmlspecialchars((string)($selectedEntry['event_label']?:$selectedEntry['action'])); ?></h5>
                        <p class="note">Action: <?php echo htmlspecialchars((string)$selectedEntry['action']); ?> | <?php echo htmlspecialchars((string)$selectedEntry['created_at']); ?></p>
                        <?php if ($unlockError!==''): ?><p class="note error"><?php echo htmlspecialchars($unlockError); ?></p><?php endif; ?>
                        <?php if ($unlockSuccess!==''): ?><p class="note success"><?php echo htmlspecialchars($unlockSuccess); ?></p><?php endif; ?>
                        <?php if ($selNeedsUnlock && !$selUnlocked): ?>
                            <p class="note">Sensitive entry — enter your password to view details.</p>
                            <form class="unlock-form" method="POST">
                                <input type="hidden" name="unlock_entry_id" value="<?php echo (int)$selectedEntry['id']; ?>">
                                <input type="password" name="unlock_password" placeholder="System Admin password" required>
                                <button class="btn" type="submit">Unlock</button>
                            </form>
                        <?php else: ?>
                            <?php if (!empty($selMetaDisplay)): ?>
                                <div class="meta-scroll"><table class="meta-table">
                                    <thead><tr><?php foreach ($selMetaDisplay as $mr): ?><th><?php echo htmlspecialchars((string)($mr['key']??'')); ?></th><?php endforeach; ?></tr></thead>
                                    <tbody><tr><?php foreach ($selMetaDisplay as $mr): ?><td><?php echo htmlspecialchars((string)($mr['value']??'')); ?></td><?php endforeach; ?></tr></tbody>
                                </table></div>
                            <?php endif; ?>
                            <?php if (!empty($selDetailRows)): ?>
                                <div class="detail-scroll" style="margin-top:6px;"><table class="detail-table">
                                    <thead><tr><th>Student</th><th>SAP ID</th><th>Component</th><th>Instance</th><th>Marks</th></tr></thead>
                                    <tbody><?php foreach ($selDetailRows as $d): ?><tr>
                                        <td><?php echo htmlspecialchars((string)($d['student_name']??'')); ?></td>
                                        <td><?php echo htmlspecialchars((string)($d['sap_id']??'')); ?></td>
                                        <td><?php echo htmlspecialchars((string)($d['component_name']??'')); ?></td>
                                        <td><?php echo htmlspecialchars((string)($d['instance_number']??'')); ?></td>
                                        <td><?php echo htmlspecialchars((string)($d['marks']??'')); ?></td>
                                    </tr><?php endforeach; ?></tbody>
                                </table></div>
                            <?php else: ?><p class="note">No drill-down rows for this entry.</p>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </td></tr>
                <?php endif; ?>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
            </div>
        </div>
        <div class="footer-bottom">&copy; <?php echo date('Y'); ?> Kuchuru Sai Krishna Reddy – STME. All rights reserved.</div>
    </div>
</div>
<script>if (localStorage.getItem('theme')==='dark') document.body.classList.add('dark-mode');</script>
</body>
</html>
