<?php
include('config.php');
session_start();

if (!isset($_SESSION["usertype"]) || $_SESSION["usertype"] !== "admin") {
    header("Location: admin_login.php");
    exit;
}

$id = intval($_GET['id']);
$result = $conn->query("SELECT * FROM trainers WHERE trainer_id = $id");
$trainer = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Trainer Details</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css" />

</head>
<body>
<?php include('admin_navbar.php'); ?>
<div class="container mt-5">
    <h2>Trainer Details</h2>
    <div class="card mt-3">
        <div class="card-body">
            <img src="uploads/<?= $trainer['profile_picture'] ?>" width="120" class="mb-3">
            <p><strong>Name:</strong> <?= $trainer['first_name'] . ' ' . $trainer['last_name'] ?></p>
            <p><strong>Specialization:</strong> <?= $trainer['specialization'] ?></p>
            <p><strong>Certification:</strong> <?= $trainer['certification'] ?></p>
            <p><strong>Experience:</strong> <?= $trainer['experience_years'] ?> years</p>
            <p><strong>Hourly Rate:</strong> $<?= $trainer['hourly_rate'] ?></p>
            <p><strong>Email:</strong> <?= $trainer['email'] ?></p>
            <p><strong>Bio:</strong> <?= $trainer['bio'] ?></p>
            <a href="view_trainers.php" class="btn btn-secondary">Back to List</a>
        </div>
    </div>
</div>
</body>
</html>
