<?php
session_start();
include('config.php');
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require './PHPMailer/src/Exception.php';
require './PHPMailer/src/PHPMailer.php';
require './PHPMailer/src/SMTP.php';

if (!isset($_SESSION['trainer_id']) || $_SESSION['user_type'] !== 'Trainer') {
    header("Location: ../login.php");
    exit;
}

$trainer_id = $_SESSION['trainer_id'];
$success = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_booking'])) {
    $appointment_id = intval($_POST['appointment_id']);
    $new_date = $_POST['appointment_date'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $status = $_POST['status'];

    $update_sql = "UPDATE appointments SET appointment_date = ?, start_time = ?, end_time = ?, status = ? WHERE appointment_id = ? AND trainer_id = ?";
    $stmt = mysqli_prepare($conn, $update_sql);
    mysqli_stmt_bind_param($stmt, "ssssii", $new_date, $start_time, $end_time, $status, $appointment_id, $trainer_id);
    $updated = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    if ($updated) {
        $email_sql = "SELECT m.email FROM appointments a JOIN members m ON a.member_id = m.member_id WHERE a.appointment_id = ? AND a.trainer_id = ?";
        $stmt2 = mysqli_prepare($conn, $email_sql);
        mysqli_stmt_bind_param($stmt2, "ii", $appointment_id, $trainer_id);
        mysqli_stmt_execute($stmt2);
        $result = mysqli_stmt_get_result($stmt2);
        $member = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt2);

        if ($member) {
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com'; // Your SMTP host
                $mail->SMTPAuth = true;
                $mail->Username = 'nasiryt.827@gmail.com'; // SMTP username
                $mail->Password = 'mtvp ruzp aqfu tfxt'; // SMTP password
                $mail->SMTPSecure = 'tls';
                $mail->Port = 587;

                $mail->setFrom('nasiryt.827@gmail.com', 'FitnessHub');
                $mail->addAddress($member['email']);

                $mail->isHTML(true);
                $mail->Subject = "Your Appointment Has Been Updated";
                $mail->Body = "Dear Member,<br><br>Your appointment has been updated by your trainer.<br><strong>Date:</strong> $new_date<br><strong>Time:</strong> $start_time to $end_time<br><strong>Status:</strong> $status<br><br>Regards,<br>Gym Team";

                $mail->send();
                $success = "Appointment updated and email sent to the member.";
            } catch (Exception $e) {
                $error = "Update successful, but email failed: {$mail->ErrorInfo}";
            }
        }
    } else {
        $error = "Failed to update appointment.";
    }
}

// Fetch all bookings for the trainer
$query = "SELECT a.*, m.first_name, m.last_name FROM appointments a 
          JOIN members m ON a.member_id = m.member_id 
          WHERE a.trainer_id = ? ORDER BY a.appointment_date DESC";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $trainer_id);
mysqli_stmt_execute($stmt);
$bookings = mysqli_stmt_get_result($stmt);
mysqli_stmt_close($stmt);
?>

<!DOCTYPE html>
<html>
<head>
    <title>View Bookings - Trainer</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">

</head>
<body>
<?php include('navbar.php'); ?>

<div class="container mt-4">
    <h2 class="mb-4">Your Bookings</h2>

    <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php elseif ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if (mysqli_num_rows($bookings) === 0): ?>
        <p>No bookings found.</p>
    <?php else: ?>
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>Member</th>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Status</th>
                    <th>Notes</th>
                    <th>Update</th>
                </tr>
            </thead>
            <tbody>
            <?php while ($row = mysqli_fetch_assoc($bookings)): ?>
                <tr>
                    <form method="POST">
                        <input type="hidden" name="appointment_id" value="<?= $row['appointment_id'] ?>">
                        <td><?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?></td>
                        <td><input type="date" name="appointment_date" class="form-control" min="<?= date('Y-m-d'); ?>" value="<?= $row['appointment_date'] ?>" required></td>
                        <td>
                            <input type="time" name="start_time" class="form-control" value="<?= $row['start_time'] ?>" required>
                            to
                            <input type="time" name="end_time" class="form-control" value="<?= $row['end_time'] ?>" required>
                        </td>
                        <td>
                            <select name="status" class="form-control" required>
                                <option <?= $row['status'] === 'scheduled' ? 'selected' : '' ?>>scheduled</option>
                                <option <?= $row['status'] === 'completed' ? 'selected' : '' ?>>completed</option>
                                <option <?= $row['status'] === 'cancelled' ? 'selected' : '' ?>>cancelled</option>
                            </select>
                        </td>
                        <td><?= nl2br(htmlspecialchars($row['notes'])) ?></td>
                        <td><button type="submit" name="update_booking" class="btn btn-primary btn-sm">Update</button></td>
                    </form>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
</body>
</html>
