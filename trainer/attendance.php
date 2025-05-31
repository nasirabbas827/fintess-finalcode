<?php
session_start();
include('config.php');

if (!isset($_SESSION['trainer_id']) || $_SESSION['user_type'] !== 'Trainer') {
    header("Location: ../login.php");
    exit;
}

$trainer_id = $_SESSION['trainer_id'];
$success = '';
$error = '';

// Add or Edit Attendance
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $member_id = intval($_POST['member_id']);
    $check_in = $_POST['check_in'];
    $check_out = !empty($_POST['check_out']) ? $_POST['check_out'] : null;
    $notes = trim($_POST['notes']);
    $edit_id = isset($_POST['edit_id']) ? intval($_POST['edit_id']) : 0;

    if ($edit_id) {
        $stmt = mysqli_prepare($conn, "UPDATE attendance SET member_id = ?, check_in = ?, check_out = ?, notes = ? WHERE attendance_id = ? AND trainer_id = ?");
        mysqli_stmt_bind_param($stmt, "isssii", $member_id, $check_in, $check_out, $notes, $edit_id, $trainer_id);
        $updated = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        $success = $updated ? "Attendance updated." : "Failed to update.";
    } else {
        $stmt = mysqli_prepare($conn, "INSERT INTO attendance (member_id, check_in, check_out, trainer_id, notes) VALUES (?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "issis", $member_id, $check_in, $check_out, $trainer_id, $notes);
        $inserted = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        $success = $inserted ? "Attendance recorded." : "Failed to add attendance.";
    }
}

// Delete
if (isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    $stmt = mysqli_prepare($conn, "DELETE FROM attendance WHERE attendance_id = ? AND trainer_id = ?");
    mysqli_stmt_bind_param($stmt, "ii", $delete_id, $trainer_id);
    if (mysqli_stmt_execute($stmt)) {
        $success = "Attendance deleted.";
    } else {
        $error = "Deletion failed.";
    }
    mysqli_stmt_close($stmt);
}

// Fetch records
$stmt = mysqli_prepare($conn, "SELECT a.*, m.first_name, m.last_name FROM attendance a 
    JOIN members m ON a.member_id = m.member_id WHERE a.trainer_id = ? ORDER BY a.check_in DESC");
mysqli_stmt_bind_param($stmt, "i", $trainer_id);
mysqli_stmt_execute($stmt);
$records = mysqli_stmt_get_result($stmt);
mysqli_stmt_close($stmt);

// Members
$members = [];
$res = mysqli_query($conn, "SELECT member_id, first_name, last_name FROM members");
while ($row = mysqli_fetch_assoc($res)) $members[] = $row;
?>

<!DOCTYPE html>
<html>
<head>
    <title>Attendance</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <link rel="stylesheet" href="../css/style.css">

</head>
<body>
<?php include('navbar.php'); ?>
<div class="container mt-4">
    <h2 class="mb-3">Attendance</h2>

    <?php if ($success): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php elseif ($error): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>

    <button class="btn btn-primary mb-3" data-toggle="modal" data-target="#attendanceModal" onclick="openAddModal()">Add Attendance</button>

    <table class="table table-bordered">
        <thead>
        <tr>
            <th>Member</th>
            <th>Check-In</th>
            <th>Check-Out</th>
            <th>Notes</th>
            <th>Actions</th>
        </tr>
        </thead>
        <tbody>
        <?php while ($row = mysqli_fetch_assoc($records)): ?>
            <tr>
                <td><?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?></td>
                <td><?= $row['check_in'] ?></td>
                <td><?= $row['check_out'] ?? '-' ?></td>
                <td><?= htmlspecialchars($row['notes']) ?></td>
                <td>
                    <button class="btn btn-sm btn-info"
                        onclick="openEditModal(<?= $row['attendance_id'] ?>, <?= $row['member_id'] ?>, '<?= $row['check_in'] ?>', '<?= $row['check_out'] ?>', `<?= htmlspecialchars(addslashes($row['notes'])) ?>`)">
                        Edit
                    </button>
                    <a href="?delete=<?= $row['attendance_id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this record?')">Delete</a>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>

<!-- Modal -->
<div class="modal fade" id="attendanceModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <form method="POST" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="attendanceModalLabel">Add/Edit Attendance</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="edit_id" id="edit_id">
                <div class="form-group">
                    <label>Member</label>
                    <select name="member_id" id="member_id" class="form-control" required>
                        <option value="">Select Member</option>
                        <?php foreach ($members as $m): ?>
                            <option value="<?= $m['member_id'] ?>"><?= $m['first_name'] . ' ' . $m['last_name'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Check-In</label>
                    <input type="datetime-local" name="check_in" id="check_in" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Check-Out</label>
                    <input type="datetime-local" name="check_out" id="check_out" class="form-control">
                </div>
                <div class="form-group">
                    <label>Notes</label>
                    <textarea name="notes" id="notes" class="form-control"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary">Save Record</button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
function openAddModal() {
    $('#edit_id').val('');
    $('#member_id').val('');
    $('#check_in').val('');
    $('#check_out').val('');
    $('#notes').val('');
    $('#attendanceModalLabel').text('Add Attendance');
}

function openEditModal(id, memberId, checkIn, checkOut, notes) {
    $('#edit_id').val(id);
    $('#member_id').val(memberId);
    $('#check_in').val(checkIn.replace(' ', 'T'));
    $('#check_out').val(checkOut ? checkOut.replace(' ', 'T') : '');
    $('#notes').val(notes);
    $('#attendanceModalLabel').text('Edit Attendance');
    $('#attendanceModal').modal('show');
}
</script>

<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
