<?php
session_start();
include('../config.php');

if (!isset($_SESSION['member_id']) || $_SESSION['user_type'] != 'Member') {
    header("Location: ../login.php");
    exit;
}

$member_id = $_SESSION['member_id'];

// Fetch attendance records for member
$attendance_sql = "SELECT attendance_id, check_in, check_out, trainer_id, notes FROM attendance WHERE member_id = ? ORDER BY check_in DESC";
$stmt = mysqli_prepare($conn, $attendance_sql);
mysqli_stmt_bind_param($stmt, "i", $member_id);
mysqli_stmt_execute($stmt);
$attendance_result = mysqli_stmt_get_result($stmt);
$attendance_records = mysqli_fetch_all($attendance_result, MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

// Fetch fitness progress records for member
$fitness_sql = "SELECT progress_id, trainer_id, record_date, weight, body_fat_percentage, muscle_mass, measurements, notes FROM fitness_progress WHERE member_id = ? ORDER BY record_date DESC";
$stmt2 = mysqli_prepare($conn, $fitness_sql);
mysqli_stmt_bind_param($stmt2, "i", $member_id);
mysqli_stmt_execute($stmt2);
$fitness_result = mysqli_stmt_get_result($stmt2);
$fitness_records = mysqli_fetch_all($fitness_result, MYSQLI_ASSOC);
mysqli_stmt_close($stmt2);

?>

<!DOCTYPE html>
<html>
<head>
    <title>My Attendance and Fitness Progress</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">

</head>
<body>
<?php include('navbar.php'); ?>

<div class="container mt-5">
    <h2>Attendance Records</h2>
    <?php if (empty($attendance_records)): ?>
        <p>No attendance records found.</p>
    <?php else: ?>
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>Check-in</th>
                    <th>Check-out</th>
                    <th>Trainer ID</th>
                    <th>Notes</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($attendance_records as $att): ?>
                    <tr>
                        <td><?= htmlspecialchars($att['check_in']) ?></td>
                        <td><?= htmlspecialchars($att['check_out']) ?: 'N/A' ?></td>
                        <td><?= htmlspecialchars($att['trainer_id']) ?></td>
                        <td><?= nl2br(htmlspecialchars($att['notes'])) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <hr>

    <h2>Fitness Progress Records</h2>
    <?php if (empty($fitness_records)): ?>
        <p>No fitness progress records found.</p>
    <?php else: ?>
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>Record Date</th>
                    <th>Weight (kg)</th>
                    <th>Body Fat (%)</th>
                    <th>Muscle Mass</th>
                    <th>Measurements</th>
                    <th>Trainer ID</th>
                    <th>Notes</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($fitness_records as $fp): ?>
                    <tr>
                        <td><?= htmlspecialchars($fp['record_date']) ?></td>
                        <td><?= htmlspecialchars($fp['weight']) ?></td>
                        <td><?= htmlspecialchars($fp['body_fat_percentage']) ?></td>
                        <td><?= htmlspecialchars($fp['muscle_mass']) ?></td>
                        <td><?= nl2br(htmlspecialchars($fp['measurements'])) ?></td>
                        <td><?= htmlspecialchars($fp['trainer_id']) ?></td>
                        <td><?= nl2br(htmlspecialchars($fp['notes'])) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
