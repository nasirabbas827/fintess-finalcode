<?php
include('config.php');
session_start();

if (!isset($_SESSION["usertype"]) || $_SESSION["usertype"] !== "admin") {
    header("Location: admin_login.php");
    exit;
}

if (!isset($_GET['id'])) {
    header("Location: view_members.php");
    exit;
}

$member_id = intval($_GET['id']);

$query = "SELECT * FROM members WHERE member_id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $member_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$member = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$member) {
    die("Member not found.");
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Member Details</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>

<?php include('admin_navbar.php'); ?>

<div class="container mt-5 mb-5">
    <div class="card mx-auto p-4" style="max-width: 700px;">
        <h3 class="mb-4">Member Details</h3>
        <table class="table table-bordered">
            <tr><th>ID</th><td><?= $member['member_id'] ?></td></tr>
            <tr><th>Name</th><td><?= htmlspecialchars($member['first_name'] . ' ' . $member['last_name']) ?></td></tr>
            <tr><th>Email</th><td><?= htmlspecialchars($member['email']) ?></td></tr>
            <tr><th>Date of Birth</th><td><?= htmlspecialchars($member['date_of_birth']) ?></td></tr>
            <tr><th>Gender</th><td><?= htmlspecialchars($member['gender']) ?></td></tr>
            <tr><th>Address</th><td><?= htmlspecialchars($member['address']) ?></td></tr>
            <tr><th>Emergency Contact</th><td><?= htmlspecialchars($member['emergency_contact_name']) ?> - <?= htmlspecialchars($member['emergency_contact_phone']) ?></td></tr>
            <tr><th>Health Conditions</th><td><?= htmlspecialchars($member['health_conditions']) ?></td></tr>
            <tr><th>Fitness Goals</th><td><?= htmlspecialchars($member['fitness_goals']) ?></td></tr>
            <tr><th>Height (cm)</th><td><?= htmlspecialchars($member['height']) ?></td></tr>
            <tr><th>Weight (kg)</th><td><?= htmlspecialchars($member['weight']) ?></td></tr>
            <tr><th>Profile Picture</th>
                <td>
                    <?php if ($member['profile_picture']): ?>
                        <img src="uploads/<?= htmlspecialchars($member['profile_picture']) ?>" width="100">
                    <?php else: ?>
                        No picture
                    <?php endif; ?>
                </td>
            </tr>
        </table>
        <a href="view_members.php" class="btn btn-secondary">Back to List</a>
    </div>
</div>

</body>
</html>
