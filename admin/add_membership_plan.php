<?php
include('config.php');
session_start();

if (!isset($_SESSION["usertype"]) || $_SESSION["usertype"] !== "admin") {
    header("Location: admin_login.php");
    exit;
}

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $plan_name = $_POST["plan_name"];
    $description = $_POST["description"];
    $duration_days = $_POST["duration_days"];
    $price = $_POST["price"];

    $sql = "INSERT INTO membership_plans (plan_name, description, duration_days, price) VALUES (?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ssid", $plan_name, $description, $duration_days, $price);

    if (mysqli_stmt_execute($stmt)) {
        $message = "Membership plan added successfully.";
    } else {
        $message = "Error: " . mysqli_error($conn);
    }

    mysqli_stmt_close($stmt);
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Membership Plan</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<?php include('admin_navbar.php'); ?>
<div class="container mt-5 mb-5">
    <div class="card mx-auto" style="max-width: 500px;">
        <div class="card-body">
            <h3 class="mb-4">Add Membership Plan</h3>
            <?php if ($message): ?>
                <div class="alert alert-info"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>
            <form method="post">
                <div class="form-group">
                    <label>Plan Name</label>
                    <input type="text" name="plan_name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" class="form-control"></textarea>
                </div>
                <div class="form-group">
                    <label>Duration (in Days)</label>
                    <input type="number" name="duration_days" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Price (USD)</label>
                    <input type="number" step="0.01" name="price" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-primary">Add Plan</button>
                <a href="view_membership_plans.php" class="btn btn-secondary ml-2">View Plans</a>
            </form>
        </div>
    </div>
</div>
</body>
</html>
