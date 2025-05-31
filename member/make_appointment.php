<?php
session_start();
include('../config.php');

if (!isset($_SESSION['member_id']) || $_SESSION['user_type'] != 'Member') {
    header("Location: ../login.php");
    exit;
}

$member_id = $_SESSION['member_id'];
$errors = [];
$success_msg = "";

// Fetch all trainers for dropdown
$trainers_res = mysqli_query($conn, "SELECT trainer_id, first_name, last_name FROM trainers ORDER BY first_name, last_name");

// Handle appointment form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_appointment'])) {
    $trainer_id = intval($_POST['trainer_id'] ?? 0);
    $appointment_date = $_POST['appointment_date'] ?? '';
    $start_time = $_POST['start_time'] ?? '';
    $end_time = $_POST['end_time'] ?? '';
    $notes = trim($_POST['notes'] ?? '');

    // Basic validation
    if ($trainer_id <= 0) $errors[] = "Please select a trainer.";
    if (!$appointment_date) $errors[] = "Please select a date.";
    if (!$start_time) $errors[] = "Please enter a start time.";
    if (!$end_time) $errors[] = "Please enter an end time.";

    if ($appointment_date < date('Y-m-d')) {
        $errors[] = "Appointment date cannot be in the past.";
    }

    if ($start_time >= $end_time) {
        $errors[] = "End time must be after start time.";
    }

    if (empty($errors)) {
        // Insert appointment without availability check or conflict check
        $insert_sql = "INSERT INTO appointments (member_id, trainer_id, appointment_date, start_time, end_time, notes, status, created_at) 
                       VALUES (?, ?, ?, ?, ?, ?, 'scheduled', NOW())";
        $stmt = mysqli_prepare($conn, $insert_sql);
        mysqli_stmt_bind_param($stmt, "ssssss", $member_id, $trainer_id, $appointment_date, $start_time, $end_time, $notes);

        if (mysqli_stmt_execute($stmt)) {
            $success_msg = "Appointment booked successfully.";
        } else {
            $errors[] = "Failed to book appointment. Please try again.";
        }
        mysqli_stmt_close($stmt);
    }
}

// Fetch member's appointments
$appointments_sql = "SELECT a.*, t.first_name, t.last_name 
                     FROM appointments a 
                     JOIN trainers t ON a.trainer_id = t.trainer_id
                     WHERE a.member_id = ? 
                     ORDER BY a.appointment_date DESC, a.start_time DESC";
$stmt4 = mysqli_prepare($conn, $appointments_sql);
mysqli_stmt_bind_param($stmt4, "i", $member_id);
mysqli_stmt_execute($stmt4);
$appointments_res = mysqli_stmt_get_result($stmt4);
mysqli_stmt_close($stmt4);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Make Appointment</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<?php include('navbar.php'); ?>

<div class="container mt-5">

    <h2>Make Appointment</h2>

    <?php if ($success_msg): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success_msg) ?></div>
    <?php endif; ?>

    <?php if ($errors): ?>
        <div class="alert alert-danger">
            <ul>
                <?php foreach ($errors as $err): ?>
                    <li><?= htmlspecialchars($err) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <!-- Button to Open the Modal -->
    <button type="button" class="btn btn-primary mb-3" data-toggle="modal" data-target="#appointmentModal">
        Book New Appointment
    </button>

    <!-- The Modal -->
    <div class="modal fade" id="appointmentModal" tabindex="-1" role="dialog" aria-labelledby="appointmentModalLabel" aria-hidden="true">
      <div class="modal-dialog" role="document">
        <form method="post" onsubmit="return confirm('Confirm booking this appointment?');">
            <input type="hidden" name="book_appointment" value="1">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title" id="appointmentModalLabel">Book Appointment</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                  <span aria-hidden="true">&times;</span>
                </button>
              </div>
              <div class="modal-body">

                <div class="form-group">
                    <label for="trainer_id">Select Trainer</label>
                    <select name="trainer_id" id="trainer_id" class="form-control" required>
                        <option value="">-- Select Trainer --</option>
                        <?php 
                        // Reset trainers_res pointer in case it was read before
                        mysqli_data_seek($trainers_res, 0);
                        while ($trainer = mysqli_fetch_assoc($trainers_res)): ?>
                            <option value="<?= intval($trainer['trainer_id']) ?>"><?= htmlspecialchars($trainer['first_name'] . ' ' . $trainer['last_name']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="appointment_date">Select Date</label>
                    <input type="date" name="appointment_date" id="appointment_date" class="form-control" required min="<?= date('Y-m-d') ?>">
                </div>

                <div class="form-group">
                    <label for="start_time">Start Time</label>
                    <input type="time" name="start_time" id="start_time" class="form-control" required>
                </div>

                <div class="form-group">
                    <label for="end_time">End Time</label>
                    <input type="time" name="end_time" id="end_time" class="form-control" required>
                </div>

                <div class="form-group">
                    <label for="notes">Notes (optional)</label>
                    <textarea name="notes" id="notes" rows="3" class="form-control"></textarea>
                </div>

              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Book Appointment</button>
              </div>
            </div>
        </form>
      </div>
    </div>

    <hr>

    <h3>Your Appointments</h3>
    <?php if (mysqli_num_rows($appointments_res) == 0): ?>
        <p>You have no appointments yet.</p>
    <?php else: ?>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Trainer</th>
                    <th>Date</th>
                    <th>Start Time</th>
                    <th>End Time</th>
                    <th>Status</th>
                    <th>Notes</th>
                    <th>Booked On</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($app = mysqli_fetch_assoc($appointments_res)): ?>
                    <tr>
                        <td><?= htmlspecialchars($app['first_name'] . ' ' . $app['last_name']) ?></td>
                        <td><?= htmlspecialchars($app['appointment_date']) ?></td>
                        <td><?= htmlspecialchars(date("g:i A", strtotime($app['start_time']))) ?></td>
                        <td><?= htmlspecialchars(date("g:i A", strtotime($app['end_time']))) ?></td>
                        <td><?= htmlspecialchars(ucfirst($app['status'])) ?></td>
                        <td><?= nl2br(htmlspecialchars($app['notes'])) ?></td>
                        <td><?= htmlspecialchars($app['created_at']) ?></td>
                        <td>
                            <?php if ($app['status'] === 'completed'): ?>
                                <a href="rate_session.php?appointment_id=<?= intval($app['appointment_id']) ?>" class="btn btn-sm btn-success">Rate Session</a>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php endif; ?>

</div>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
