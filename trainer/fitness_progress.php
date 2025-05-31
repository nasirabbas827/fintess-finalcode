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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $member_id = intval($_POST['member_id']);
    $record_date = $_POST['record_date'];
    $weight = !empty($_POST['weight']) ? floatval($_POST['weight']) : null;
    $body_fat_percentage = !empty($_POST['body_fat_percentage']) ? floatval($_POST['body_fat_percentage']) : null;
    $muscle_mass = !empty($_POST['muscle_mass']) ? floatval($_POST['muscle_mass']) : null;
    $measurements = trim($_POST['measurements']);
    $notes = trim($_POST['notes']);
    $edit_id = !empty($_POST['edit_id']) ? intval($_POST['edit_id']) : null;

    if ($edit_id) {
        // Update
        $stmt = mysqli_prepare($conn, "UPDATE fitness_progress SET member_id=?, record_date=?, weight=?, body_fat_percentage=?, muscle_mass=?, measurements=?, notes=? WHERE progress_id=? AND trainer_id=?");
        mysqli_stmt_bind_param($stmt, "issdddssii", $member_id, $record_date, $weight, $body_fat_percentage, $muscle_mass, $measurements, $notes, $edit_id, $trainer_id);
        $updated = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        $success = $updated ? "Progress record updated successfully." : "Failed to update progress record.";
    } else {
        // Insert
        $stmt = mysqli_prepare($conn, "INSERT INTO fitness_progress (member_id, trainer_id, record_date, weight, body_fat_percentage, muscle_mass, measurements, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "iissddss", $member_id, $trainer_id, $record_date, $weight, $body_fat_percentage, $muscle_mass, $measurements, $notes);
        $inserted = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        if ($inserted) {
            // Send email to member
            $stmt2 = mysqli_prepare($conn, "SELECT email, first_name FROM members WHERE member_id = ?");
            mysqli_stmt_bind_param($stmt2, "i", $member_id);
            mysqli_stmt_execute($stmt2);
            $result = mysqli_stmt_get_result($stmt2);
            $member = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt2);

            if ($member) {
                $mail = new PHPMailer(true);
                try {
                    $mail->isSMTP();
                    $mail->Host = 'smtp.gmail.com';
                    $mail->SMTPAuth = true;
                    $mail->Username = 'nasiryt.827@gmail.com';
                    $mail->Password = 'mtvp ruzp aqfu tfxt';
                    $mail->SMTPSecure = 'tls';
                    $mail->Port = 587;

                    $mail->setFrom('nasiryt.827@gmail.com', 'FitnessHub');
                    $mail->addAddress($member['email']);
                    $mail->isHTML(true);
                    $mail->Subject = "New Fitness Progress Record Added";
                    $body = "Dear " . htmlspecialchars($member['first_name']) . ",<br><br>" .
                            "A new fitness progress record has been added on <strong>$record_date</strong>.<br>" .
                            "Please check your profile for details.<br><br>Regards,<br>Gym Team";

                    $mail->Body = $body;

                    $mail->send();
                    $success = "Progress record added and email sent.";
                } catch (Exception $e) {
                    $error = "Record added but email failed: {$mail->ErrorInfo}";
                }
            }
        } else {
            $error = "Failed to add progress record.";
        }
    }
}

// Delete
if (isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    $stmt = mysqli_prepare($conn, "DELETE FROM fitness_progress WHERE progress_id = ? AND trainer_id = ?");
    mysqli_stmt_bind_param($stmt, "ii", $delete_id, $trainer_id);
    if (mysqli_stmt_execute($stmt)) {
        $success = "Progress record deleted.";
    } else {
        $error = "Failed to delete progress record.";
    }
    mysqli_stmt_close($stmt);
}

// Fetch progress records
$stmt = mysqli_prepare($conn, "SELECT fp.*, m.first_name, m.last_name FROM fitness_progress fp 
    JOIN members m ON fp.member_id = m.member_id WHERE fp.trainer_id = ? ORDER BY fp.record_date DESC");
mysqli_stmt_bind_param($stmt, "i", $trainer_id);
mysqli_stmt_execute($stmt);
$progresses = mysqli_stmt_get_result($stmt);
mysqli_stmt_close($stmt);

// Members for select dropdown
$members = [];
$res = mysqli_query($conn, "SELECT member_id, first_name, last_name FROM members");
while ($row = mysqli_fetch_assoc($res)) $members[] = $row;
?>

<!DOCTYPE html>
<html>
<head>
    <title>Fitness Progress</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <link rel="stylesheet" href="../css/style.css">

</head>
<body>
<?php include('navbar.php'); ?>
<div class="container mt-4">
    <h2>Fitness Progress Records</h2>

    <?php if ($success): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php elseif ($error): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>

    <button class="btn btn-primary mb-3" data-toggle="modal" data-target="#progressModal" onclick="openAddModal()">Add Progress</button>

    <table class="table table-bordered table-hover">
        <thead>
        <tr>
            <th>Member</th>
            <th>Date</th>
            <th>Weight (kg)</th>
            <th>Body Fat (%)</th>
            <th>Muscle Mass (kg)</th>
            <th>Measurements (JSON)</th>
            <th>Notes</th>
            <th>Actions</th>
        </tr>
        </thead>
        <tbody>
        <?php while ($row = mysqli_fetch_assoc($progresses)): ?>
            <tr>
                <td><?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?></td>
                <td><?= $row['record_date'] ?></td>
                <td><?= $row['weight'] ?></td>
                <td><?= $row['body_fat_percentage'] ?></td>
                <td><?= $row['muscle_mass'] ?></td>
                <td><pre><?= htmlspecialchars($row['measurements']) ?></pre></td>
                <td><?= htmlspecialchars($row['notes']) ?></td>
                <td>
                    <button class="btn btn-sm btn-info mb-2" onclick='openEditModal(<?= $row["progress_id"] ?>, <?= $row["member_id"] ?>, "<?= $row["record_date"] ?>", "<?= $row["weight"] ?>", "<?= $row["body_fat_percentage"] ?>", "<?= $row["muscle_mass"] ?>", <?= json_encode($row["measurements"]) ?>, "<?= htmlspecialchars(addslashes($row["notes"])) ?>")'>Edit</button>
                    <a href="fitness_progress.php?delete=<?= $row['progress_id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this record?')">Delete</a>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>

<!-- Modal -->
<div class="modal fade" id="progressModal" tabindex="-1" role="dialog" aria-labelledby="progressModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <form method="POST" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="progressModalLabel">Add/Edit Fitness Progress</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="edit_id" id="edit_id">

                <div class="form-group">
                    <label>Member</label>
                    <select name="member_id" id="member_id" class="form-control" required>
                        <option value="">Select Member</option>
                        <?php foreach ($members as $m): ?>
                            <option value="<?= $m['member_id'] ?>"><?= htmlspecialchars($m['first_name'] . ' ' . $m['last_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Date</label>
                    <input type="date" name="record_date" id="record_date" class="form-control" required>
                </div>

                <div class="form-group">
                    <label>Weight (kg)</label>
                    <input type="number" step="0.01" name="weight" id="weight" class="form-control">
                </div>

                <div class="form-group">
                    <label>Body Fat Percentage (%)</label>
                    <input type="number" step="0.01" name="body_fat_percentage" id="body_fat_percentage" class="form-control">
                </div>

                <div class="form-group">
                    <label>Muscle Mass (kg)</label>
                    <input type="number" step="0.01" name="muscle_mass" id="muscle_mass" class="form-control">
                </div>

                <div class="form-group">
                    <label>Measurements (JSON)</label>
                    <textarea name="measurements" id="measurements" class="form-control" placeholder='e.g. {"chest": 90, "waist": 70}'></textarea>
                </div>

                <div class="form-group">
                    <label>Notes</label>
                    <textarea name="notes" id="notes" class="form-control"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary">Save Progress</button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
function openAddModal() {
    $('#edit_id').val('');
    $('#member_id').val('');
    $('#record_date').val('');
    $('#weight').val('');
    $('#body_fat_percentage').val('');
    $('#muscle_mass').val('');
    $('#measurements').val('');
    $('#notes').val('');
    $('#progressModalLabel').text('Add Fitness Progress');
}

function openEditModal(id, memberId, date, weight, bodyFat, muscleMass, measurements, notes) {
    $('#edit_id').val(id);
    $('#member_id').val(memberId);
    $('#record_date').val(date);
    $('#weight').val(weight);
    $('#body_fat_percentage').val(bodyFat);
    $('#muscle_mass').val(muscleMass);
    $('#measurements').val(measurements);
    $('#notes').val(notes);
    $('#progressModalLabel').text('Edit Fitness Progress');
    $('#progressModal').modal('show');
}
</script>

<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
