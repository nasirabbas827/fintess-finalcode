<?php
include('config.php');
session_start();

if (!isset($_SESSION["usertype"]) || $_SESSION["usertype"] !== "admin") {
    header("Location: admin_login.php");
    exit;
}

$id = intval($_GET['id']);
$message = "";

// Fetch availability
$stmt = $conn->prepare("SELECT * FROM trainer_availability WHERE availability_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$availability = $result->fetch_assoc();
$stmt->close();

if (!$availability) {
    echo "Availability not found.";
    exit;
}

// Update handler
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $day_of_week = $_POST["day_of_week"];
    $start_time = $_POST["start_time"];
    $end_time = $_POST["end_time"];
    $is_available = isset($_POST["is_available"]) ? 1 : 0;

    $stmt = $conn->prepare("UPDATE trainer_availability SET day_of_week=?, start_time=?, end_time=?, is_available=? WHERE availability_id=?");
    $stmt->bind_param("sssii", $day_of_week, $start_time, $end_time, $is_available, $id);

    if ($stmt->execute()) {
        $message = "Availability updated successfully.";
    } else {
        $message = "Error: " . $conn->error;
    }
    $stmt->close();

    // Refresh data
    $result = $conn->query("SELECT * FROM trainer_availability WHERE availability_id = $id");
    $availability = $result->fetch_assoc();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Trainer Availability</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<?php include('admin_navbar.php'); ?>
<div class="container mt-5 mb-5">
    <div class="card mx-auto" style="max-width: 500px;">
        <div class="card-body">
            <h3 class="mb-3">Edit Availability</h3>
            <?php if ($message): ?>
                <div class="alert alert-info"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>
            <form method="post">
                <div class="form-group">
                    <label>Day of Week</label>
                    <select name="day_of_week" class="form-control" required>
                        <?php
                        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
                        foreach ($days as $day): ?>
                            <option value="<?= $day ?>" <?= $availability['day_of_week'] == $day ? 'selected' : '' ?>><?= ucfirst($day) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Start Time</label>
                    <input type="time" name="start_time" class="form-control" required value="<?= $availability['start_time'] ?>">
                </div>
                <div class="form-group">
                    <label>End Time</label>
                    <input type="time" name="end_time" class="form-control" required value="<?= $availability['end_time'] ?>">
                </div>
                <div class="form-check mb-3">
                    <input type="checkbox" class="form-check-input" name="is_available" id="is_available" <?= $availability['is_available'] ? 'checked' : '' ?>>
                    <label class="form-check-label" for="is_available">Is Available</label>
                </div>
                <button type="submit" class="btn btn-success">Update</button>
                <a href="view_trainer_availability.php" class="btn btn-secondary ml-2">Back</a>
            </form>
        </div>
    </div>
</div>
</body>
</html>
