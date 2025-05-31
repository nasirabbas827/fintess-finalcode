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
    $plan_name = trim($_POST['plan_name']);
    $description = trim($_POST['description']);
    $member_id = intval($_POST['member_id']);
    $is_edit = !empty($_POST['edit_id']);

    if ($is_edit) {
        $edit_id = intval($_POST['edit_id']);
        $stmt = mysqli_prepare($conn, "UPDATE diet_plans SET plan_name=?, description=? WHERE diet_id=? AND trainer_id=?");
        mysqli_stmt_bind_param($stmt, "ssii", $plan_name, $description, $edit_id, $trainer_id);
        $updated = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        $success = $updated ? "Diet plan updated successfully." : "Failed to update diet plan.";
    } else {
        $stmt = mysqli_prepare($conn, "INSERT INTO diet_plans (trainer_id, member_id, plan_name, description) VALUES (?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "iiss", $trainer_id, $member_id, $plan_name, $description);
        $inserted = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        if ($inserted) {
            $stmt2 = mysqli_prepare($conn, "SELECT email FROM members WHERE member_id = ?");
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
                    $mail->Subject = "New Diet Plan Assigned";
                    $mail->Body = "Dear Member,<br><br>A new diet plan <strong>$plan_name</strong> has been assigned to you by your trainer.<br><br>Regards,<br>Gym Team";

                    $mail->send();
                    $success = "Diet plan added and email sent.";
                } catch (Exception $e) {
                    $error = "Plan added, but email failed: {$mail->ErrorInfo}";
                }
            }
        } else {
            $error = "Failed to add diet plan.";
        }
    }
}

// Delete
if (isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    $stmt = mysqli_prepare($conn, "DELETE FROM diet_plans WHERE diet_id = ? AND trainer_id = ?");
    mysqli_stmt_bind_param($stmt, "ii", $delete_id, $trainer_id);
    if (mysqli_stmt_execute($stmt)) {
        $success = "Diet plan deleted.";
    } else {
        $error = "Failed to delete diet plan.";
    }
    mysqli_stmt_close($stmt);
}

// Fetch plans
$stmt = mysqli_prepare($conn, "SELECT dp.*, m.first_name, m.last_name FROM diet_plans dp 
    JOIN members m ON dp.member_id = m.member_id WHERE dp.trainer_id = ? ORDER BY dp.created_at DESC");
mysqli_stmt_bind_param($stmt, "i", $trainer_id);
mysqli_stmt_execute($stmt);
$plans = mysqli_stmt_get_result($stmt);
mysqli_stmt_close($stmt);

// Members
$members = [];
$res = mysqli_query($conn, "SELECT member_id, first_name, last_name FROM members");
while ($row = mysqli_fetch_assoc($res)) $members[] = $row;
?>

<!DOCTYPE html>
<html>
<head>
    <title>Diet Plans</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <link rel="stylesheet" href="../css/style.css">

</head>
<body>
<?php include('navbar.php'); ?>
<div class="container mt-4">
    <h2 class="mb-3">Diet Plans</h2>

    <?php if ($success): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php elseif ($error): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>

    <button class="btn btn-primary mb-3" data-toggle="modal" data-target="#dietModal" onclick="openAddModal()">Add Diet Plan</button>

    <table class="table table-bordered table-hover">
        <thead>
        <tr>
            <th>Member</th>
            <th>Plan Name</th>
            <th>Description</th>
            <th>Created</th>
            <th>Active</th>
            <th>Actions</th>
        </tr>
        </thead>
        <tbody>
        <?php while ($row = mysqli_fetch_assoc($plans)): ?>
            <tr>
                <td><?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?></td>
                <td><?= htmlspecialchars($row['plan_name']) ?></td>
                <td><?= htmlspecialchars($row['description']) ?></td>
                <td><?= $row['created_at'] ?></td>
                <td><?= $row['is_active'] ? 'Yes' : 'No' ?></td>
                <td>
                    <button class="btn btn-sm btn-info" onclick="openEditModal(<?= $row['diet_id'] ?>, <?= $row['member_id'] ?>, '<?= htmlspecialchars(addslashes($row['plan_name'])) ?>', '<?= htmlspecialchars(addslashes($row['description'])) ?>')">Edit</button>
                    <a href="diet_plans.php?delete=<?= $row['diet_id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this plan?')">Delete</a>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>

<!-- Modal -->
<div class="modal fade" id="dietModal" tabindex="-1" role="dialog" aria-labelledby="dietModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <form method="POST" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="dietModalLabel">Add/Edit Diet Plan</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="edit_id" id="edit_id">
                <div class="form-group">
                    <label>Plan Name</label>
                    <input type="text" name="plan_name" id="plan_name" class="form-control" required>
                </div>
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
                    <label>Description</label>
                    <textarea name="description" id="description" class="form-control"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary">Save Plan</button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
function openAddModal() {
    $('#edit_id').val('');
    $('#plan_name').val('');
    $('#member_id').val('');
    $('#description').val('');
    $('#dietModalLabel').text('Add Diet Plan');
}

function openEditModal(id, memberId, name, desc) {
    $('#edit_id').val(id);
    $('#plan_name').val(name);
    $('#member_id').val(memberId);
    $('#description').val(desc);
    $('#dietModalLabel').text('Edit Diet Plan');
    $('#dietModal').modal('show');
}
</script>

<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
