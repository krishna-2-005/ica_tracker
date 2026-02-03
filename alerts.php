<?php
session_start();
include 'db_connect.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'teacher') {
    header('Location: login.php');
    exit;
}

$teacher_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $alert_id = (int)$_POST['alert_id'];
    $response = mysqli_real_escape_string($conn, $_POST['response']);
    $query = "UPDATE alerts SET response=?, status='responded', responded_at=NOW() WHERE id=? AND teacher_id=?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "sii", $response, $alert_id, $teacher_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

$alerts_query = "SELECT * FROM alerts WHERE teacher_id=?";
$stmt = mysqli_prepare($conn, $alerts_query);
mysqli_stmt_bind_param($stmt, "i", $teacher_id);
mysqli_stmt_execute($stmt);
$alerts_result = mysqli_stmt_get_result($stmt);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Alerts</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <h2> alerts & Notifications</h2>
    <link rel="icon" type="image/png" href="nmimsvertical.jpg">
    <link rel="apple-touch-icon" href="nmimsvertical.jpg">
        <table>
            <tr><th>Message</th><th>Status</th><th>Response</th><th>Action</th></tr>
            <?php while ($alert = mysqli_fetch_assoc($alerts_result)) { ?>
                <tr>
                    <td><?php echo htmlspecialchars($alert['message']); ?></td>
                    <td><?php echo htmlspecialchars($alert['status']); ?></td>
                    <td><?php echo htmlspecialchars($alert['response'] ?: '-'); ?></td>
                    <td>
                        <?php if ($alert['status'] == 'pending') { ?>
                            <form method="POST">
                                <input type="hidden" name="alert_id" value="<?php echo $alert['id']; ?>">
                                <textarea name="response" required></textarea>
                                <button type="submit">Respond</button>
                            </form>
                        <?php } ?>
                    </td>
                </tr>
            <?php } ?>
        </table>
        <p><a href="teacher_dashboard.php">Back to Dashboard</a></p>
    </div>
</body>
</html>
