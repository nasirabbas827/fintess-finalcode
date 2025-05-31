<?php
session_start();
include('../config.php');

if (!isset($_SESSION['member_id']) || $_SESSION['user_type'] != 'Member') {
    header("Location: ../login.php");
    exit;
}

$member_id = $_SESSION['member_id'];
$plan_id = isset($_GET['plan_id']) ? intval($_GET['plan_id']) : 0;

if ($plan_id <= 0) {
    die("Invalid plan selected.");
}

// Fetch plan details
$sql = "SELECT * FROM membership_plans WHERE plan_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $plan_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$plan = mysqli_fetch_assoc($result);

if (!$plan) {
    die("Selected plan does not exist.");
}

$errors = [];
$success_msg = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Calculate membership start and end dates
    $start_date = date('Y-m-d');
    $duration_days = intval($plan['duration_days']);
    $end_date = date('Y-m-d', strtotime("+$duration_days days"));

    // Insert new membership record with payment_status 'pending'
    $insert_sql = "INSERT INTO member_memberships (member_id, plan_id, start_date, end_date, is_active, auto_renew, payment_status) VALUES (?, ?, ?, ?, 1, 0, 'pending')";
    $insert_stmt = mysqli_prepare($conn, $insert_sql);
    mysqli_stmt_bind_param($insert_stmt, "iiss", $member_id, $plan_id, $start_date, $end_date);

    if (mysqli_stmt_execute($insert_stmt)) {
        $membership_id = mysqli_insert_id($conn);
        // Redirect to a payment page or show success
        // For now just success message:
        $success_msg = "Membership purchase initiated successfully. Please complete payment.";
    } else {
        $errors[] = "Failed to create membership record. Please try again.";
    }
    mysqli_stmt_close($insert_stmt);
}

mysqli_stmt_close($stmt);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Purchase Membership Plan</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<?php include('navbar.php'); ?>

<div class="container mt-5">
    <div class="card mx-auto" style="max-width: 600px;">
        <div class="card-body">
            <h2 class="card-title">Purchase Membership Plan</h2>

            <?php if ($success_msg): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success_msg) ?></div>
                <a href="member_dashboard.php" class="btn btn-primary">Go to Dashboard</a>
            <?php else: ?>

                <?php if ($errors): ?>
                    <div class="alert alert-danger">
                        <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <p><strong>Plan Name:</strong> <?= htmlspecialchars($plan['plan_name']) ?></p>
                <p><strong>Description:</strong> <?= htmlspecialchars($plan['description']) ?></p>
                <p><strong>Price:</strong> $<?= number_format($plan['price'], 2) ?></p>
                <p><strong>Duration:</strong> <?= intval($plan['duration_days']) ?> days</p>

                <form method="post">
                    <button type="submit" class="btn btn-success">Confirm Purchase</button>
                    <a href="member_dashboard.php" class="btn btn-secondary">Cancel</a>
                </form>

            <?php endif; ?>
        </div>
    </div>
</div>

</body>
</html>
