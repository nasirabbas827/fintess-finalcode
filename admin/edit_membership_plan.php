<?php
include('config.php');
session_start();

if (!isset($_SESSION["usertype"]) || $_SESSION["usertype"] !== "admin") {
    header("Location: admin_login.php");
    exit;
}

$id = intval($_GET['id']);
$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $plan_name = $_POST["plan_name"];
    $description = $_POST["description"];
    $duration_days = $_POST["duration_days"];
    $price = $_POST["price"];

    $sql = "UPDATE membership_plans SET plan_name=?, description=?, duration_days=?, price=? WHERE plan_id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssidi", $plan_name, $description, $duration_days, $price, $id);

    if ($stmt->execute()) {
        $message = "Plan updated successfully.";
    } else {
        $message = "Update failed: " . $conn->error;
    }
    $stmt->close();
}

$result = $conn->query("SELECT * FROM membership_plans WHERE plan_id = $id");
$plan = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Membership Plan</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<?php include('admin_navbar.php'); ?>
<div class="container mt-5 mb-5">
    <div class="card mx-auto" style="max-width: 500px;">
        <div class="card-body">
            <h3 class="mb-4">Edit Membership Plan</h3>
            <?php if ($message): ?>
                <div class="alert alert-info"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>
            <form method="post">
                <div class="form-group">
                    <label>Plan Name</label>
                    <input type="text" name="plan_name" value="<?= htmlspecialchars($plan['plan_name']) ?>" required class="form-control">
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" class="form-control"><?= htmlspecialchars($plan['description']) ?></textarea>
                </div>
                <div class="form-group">
                    <label>Duration (Days)</label>
                    <input type="number" name="duration_days" value="<?= $plan['duration_days'] ?>" required class="form-control">
                </div>
                <div class="form-group">
                    <label>Price (USD)</label>
                    <input type="number" step="0.01" name="price" value="<?= $plan['price'] ?>" required class="form-control">
                </div>
                <button type="submit" class="btn btn-primary">Update Plan</button>
                <a href="view_membership_plans.php" class="btn btn-secondary ml-2">Back</a>
            </form>
        </div>
    </div>
</div>
</body>
</html>
