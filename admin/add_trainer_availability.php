<?php
include('config.php');
session_start();

if (!isset($_SESSION["usertype"]) || $_SESSION["usertype"] !== "admin") {
    header("Location: admin_login.php");
    exit;
}

$message = "";

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $trainer_id = $_POST["trainer_id"];
    $day_of_week = $_POST["day_of_week"];
    $start_time = $_POST["start_time"];
    $end_time = $_POST["end_time"];
    $is_available = isset($_POST["is_available"]) ? 1 : 0;

    $sql = "INSERT INTO trainer_availability (trainer_id, day_of_week, start_time, end_time, is_available) 
            VALUES (?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "isssi", $trainer_id, $day_of_week, $start_time, $end_time, $is_available);

    if (mysqli_stmt_execute($stmt)) {
        $message = "Availability added successfully.";
    } else {
        $message = "Error: " . mysqli_error($conn);
    }
    mysqli_stmt_close($stmt);
}

// Fetch trainers
$trainers = $conn->query("SELECT trainer_id, first_name, last_name FROM trainers");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Trainer Availability</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<?php include('admin_navbar.php'); ?>
<div class="container mt-5 mb-5">
    <div class="card mx-auto" style="max-width: 500px;">
        <div class="card-body">
            <h3 class="mb-3">Add Trainer Availability</h3>
            <?php if ($message): ?>
                <div class="alert alert-info"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>
            <form method="post">
                <div class="form-group">
                    <label>Trainer</label>
                    <select name="trainer_id" class="form-control" required>
                        <option value="">Select Trainer</option>
                        <?php while ($row = $trainers->fetch_assoc()): ?>
                            <option value="<?= $row['trainer_id'] ?>">
                                <?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Day of Week</label>
                    <select name="day_of_week" class="form-control" required>
                        <option value="">Select Day</option>
                        <?php
                        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
                        foreach ($days as $day): ?>
                            <option value="<?= $day ?>"><?= ucfirst($day) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Start Time</label>
                    <input type="time" name="start_time" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>End Time</label>
                    <input type="time" name="end_time" class="form-control" required>
                </div>
                <div class="form-check mb-3">
                    <input type="checkbox" class="form-check-input" name="is_available" id="is_available" checked>
                    <label class="form-check-label" for="is_available">Is Available</label>
                </div>
                <button type="submit" class="btn btn-success">Add Availability</button>
                <a href="view_trainer_availability.php" class="btn btn-secondary ml-2">View Availability</a>
            </form>
        </div>
    </div>
</div>
</body>
</html>
