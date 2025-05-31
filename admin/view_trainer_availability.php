<?php
include('config.php');
session_start();

if (!isset($_SESSION["usertype"]) || $_SESSION["usertype"] !== "admin") {
    header("Location: admin_login.php");
    exit;
}

// Handle deletion
if (isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    $conn->query("DELETE FROM trainer_availability WHERE availability_id = $delete_id");
    echo "<script>alert('Availability deleted successfully.'); window.location='view_trainer_availability.php';</script>";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Trainer Availability</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<?php include('admin_navbar.php'); ?>
<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>Trainer Availability</h3>
        <a href="add_trainer_availability.php" class="btn btn-primary">Add Availability</a>
    </div>
    <div class="table-responsive">
        <table class="table table-bordered table-striped">
            <thead class="thead-dark">
                <tr>
                    <th>#</th>
                    <th>Trainer</th>
                    <th>Day</th>
                    <th>Start Time</th>
                    <th>End Time</th>
                    <th>Available?</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $sql = "SELECT a.*, t.first_name, t.last_name 
                        FROM trainer_availability a 
                        JOIN trainers t ON a.trainer_id = t.trainer_id 
                        ORDER BY a.day_of_week, a.start_time";
                $result = $conn->query($sql);
                $count = 1;
                while ($row = $result->fetch_assoc()):
                ?>
                <tr>
                    <td><?= $count++ ?></td>
                    <td><?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?></td>
                    <td><?= ucfirst($row['day_of_week']) ?></td>
                    <td><?= $row['start_time'] ?></td>
                    <td><?= $row['end_time'] ?></td>
                    <td><?= $row['is_available'] ? 'Yes' : 'No' ?></td>
                    <td>
                        <a href="edit_trainer_availability.php?id=<?= $row['availability_id'] ?>" class="btn btn-sm btn-info">Edit</a>
                        <a href="?delete=<?= $row['availability_id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this availability?');">Delete</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
