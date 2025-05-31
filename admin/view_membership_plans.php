<?php
include('config.php');
session_start();

if (!isset($_SESSION["usertype"]) || $_SESSION["usertype"] !== "admin") {
    header("Location: admin_login.php");
    exit;
}

$message = "";

// Handle deletion
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    $conn->query("DELETE FROM membership_plans WHERE plan_id = $delete_id");
    $message = "Plan deleted successfully.";
}

$result = $conn->query("SELECT * FROM membership_plans");
?>

<!DOCTYPE html>
<html>
<head>
    <title>View Membership Plans</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <script>
        function confirmDelete(planId) {
            if (confirm("Are you sure you want to delete this plan?")) {
                window.location.href = "view_membership_plans.php?delete_id=" + planId;
            }
        }
    </script>
</head>
<body>
<?php include('admin_navbar.php'); ?>
<div class="container mt-5 mb-5">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>Membership Plans</h3>
        <a href="add_membership_plan.php" class="btn btn-primary d-flex">Add New Plan</a>
    </div>
    <?php if ($message): ?>
        <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    <table class="table table-bordered table-striped">
        <thead class="thead-dark">
            <tr>
                <th>ID</th>
                <th>Plan Name</th>
                <th>Description</th>
                <th>Duration (Days)</th>
                <th>Price (USD)</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= $row['plan_id'] ?></td>
                <td><?= htmlspecialchars($row['plan_name']) ?></td>
                <td><?= nl2br(htmlspecialchars($row['description'])) ?></td>
                <td><?= $row['duration_days'] ?></td>
                <td>$<?= number_format($row['price'], 2) ?></td>
                <td>
                    <a href="edit_membership_plan.php?id=<?= $row['plan_id'] ?>" class="btn btn-sm btn-warning">Edit</a>
                    <button onclick="confirmDelete(<?= $row['plan_id'] ?>)" class="btn btn-sm btn-danger">Delete</button>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>
</body>
</html>
