<?php
include('config.php');
session_start();

if (!isset($_SESSION["usertype"]) || $_SESSION["usertype"] !== "admin") {
    header("Location: admin_login.php");
    exit;
}

// Delete logic
if (isset($_GET['delete_id'])) {
    $id = intval($_GET['delete_id']);
    $conn->query("DELETE FROM trainers WHERE trainer_id = $id");
    echo "<script>alert('Trainer deleted successfully.'); window.location='view_trainers.php';</script>";
    exit;
}

$result = $conn->query("SELECT * FROM trainers ORDER BY trainer_id DESC");
?>

<!DOCTYPE html>
<html>
<head>
    <title>View Trainers</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css" />

</head>
<body>
<?php include('admin_navbar.php'); ?>
<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>Trainers List</h2>
        <a href="add_trainer.php" class="btn btn-success">Add Trainer</a>
    </div>
    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Specialization</th>
                <th>Email</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= $row['trainer_id'] ?></td>
                <td><?= $row['first_name'] . ' ' . $row['last_name'] ?></td>
                <td><?= $row['specialization'] ?></td>
                <td><?= $row['email'] ?></td>
                <td>
                    <a href="view_trainer.php?id=<?= $row['trainer_id'] ?>" class="btn btn-info btn-sm">View</a>
                    <a href="edit_trainer.php?id=<?= $row['trainer_id'] ?>" class="btn btn-warning btn-sm">Edit</a>
                    <a href="view_trainers.php?delete_id=<?= $row['trainer_id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this trainer?')">Delete</a>
                </td>
            </tr>
            <?php endwhile ?>
        </tbody>
    </table>
</div>
</body>
</html>
