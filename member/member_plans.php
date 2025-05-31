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

// Handle adding workout log
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_workout_log'])) {
    $plan_id = intval($_POST['plan_id'] ?? 0);
    $log_date = $_POST['log_date'] ?? '';
    $sets_completed = intval($_POST['sets_completed'] ?? 0);
    $reps_completed = intval($_POST['reps_completed'] ?? 0);
    $weight_used = floatval($_POST['weight_used'] ?? 0);
    $duration_minutes = intval($_POST['duration_minutes'] ?? 0);
    $notes = trim($_POST['notes'] ?? '');

    // Basic validation
    if ($plan_id <= 0) $errors[] = "Invalid workout plan.";
    if (!$log_date) $errors[] = "Please enter log date.";
    if ($sets_completed <= 0) $errors[] = "Sets completed must be > 0.";
    if ($reps_completed <= 0) $errors[] = "Reps completed must be > 0.";
    if ($duration_minutes <= 0) $errors[] = "Duration must be > 0 minutes.";

    if (empty($errors)) {
        $insert_sql = "INSERT INTO workout_logs 
            (member_id, plan_id, log_date, sets_completed, reps_completed, weight_used, duration_minutes, notes)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $insert_sql);
        mysqli_stmt_bind_param($stmt, "ssssssss", $member_id, $plan_id, $log_date, $sets_completed, $reps_completed, $weight_used, $duration_minutes, $notes);
        if (mysqli_stmt_execute($stmt)) {
            $success_msg = "Workout log added successfully.";
        } else {
            $errors[] = "Failed to add workout log.";
        }
        mysqli_stmt_close($stmt);
    }
}

// Handle deletion of workout log
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_log_id'])) {
    $log_id = intval($_POST['delete_log_id']);
    // Verify log belongs to this member before deleting
    $check_sql = "SELECT log_id FROM workout_logs WHERE log_id = ? AND member_id = ?";
    $stmt = mysqli_prepare($conn, $check_sql);
    mysqli_stmt_bind_param($stmt, "ii", $log_id, $member_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);
    if (mysqli_stmt_num_rows($stmt) === 1) {
        mysqli_stmt_close($stmt);
        $delete_sql = "DELETE FROM workout_logs WHERE log_id = ?";
        $stmt_del = mysqli_prepare($conn, $delete_sql);
        mysqli_stmt_bind_param($stmt_del, "i", $log_id);
        if (mysqli_stmt_execute($stmt_del)) {
            $success_msg = "Workout log deleted successfully.";
        } else {
            $errors[] = "Failed to delete workout log.";
        }
        mysqli_stmt_close($stmt_del);
    } else {
        $errors[] = "Workout log not found or you don't have permission to delete it.";
        mysqli_stmt_close($stmt);
    }
}

// Fetch active workout plans for member
$workout_plans_sql = "SELECT * FROM workout_plans WHERE member_id = ? AND is_active = 1 ORDER BY created_at DESC";
$stmt = mysqli_prepare($conn, $workout_plans_sql);
mysqli_stmt_bind_param($stmt, "i", $member_id);
mysqli_stmt_execute($stmt);
$workout_plans_res = mysqli_stmt_get_result($stmt);
$workout_plans = mysqli_fetch_all($workout_plans_res, MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

// Fetch active diet plans for member
$diet_plans_sql = "SELECT * FROM diet_plans WHERE member_id = ? AND is_active = 1 ORDER BY created_at DESC";
$stmt2 = mysqli_prepare($conn, $diet_plans_sql);
mysqli_stmt_bind_param($stmt2, "i", $member_id);
mysqli_stmt_execute($stmt2);
$diet_plans_res = mysqli_stmt_get_result($stmt2);
$diet_plans = mysqli_fetch_all($diet_plans_res, MYSQLI_ASSOC);
mysqli_stmt_close($stmt2);

function fetchWorkoutLogs($conn, $member_id, $plan_id) {
    $sql = "SELECT * FROM workout_logs WHERE member_id = ? AND plan_id = ? ORDER BY log_date DESC";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $member_id, $plan_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $logs = mysqli_fetch_all($result, MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);
    return $logs;
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>My Workout and Diet Plans</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <style>
        /* Make table horizontally scrollable on small screens */
        .table-responsive { overflow-x: auto; }
        .card-header button { min-width: 150px; }
        .log-notes { max-width: 200px; word-wrap: break-word; white-space: pre-wrap; }
    </style>
</head>
<body>
<?php include('navbar.php'); ?>

<div class="container mt-5">
    <h2>Active Workout Plans</h2>

    <?php if ($success_msg): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success_msg) ?></div>
    <?php endif; ?>

    <?php if ($errors): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $err): ?>
                    <li><?= htmlspecialchars($err) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if (empty($workout_plans)): ?>
        <p>You have no active workout plans.</p>
    <?php else: ?>
        <?php foreach ($workout_plans as $plan): ?>
            <div class="card mb-4 shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <strong><?= htmlspecialchars($plan['plan_name']) ?></strong>
                    <button class="btn btn-sm btn-primary" data-toggle="modal" data-target="#addWorkoutLogModal" data-planid="<?= $plan['plan_id'] ?>" data-planname="<?= htmlspecialchars($plan['plan_name']) ?>">Add Workout Log</button>
                </div>
                <div class="card-body">
                    <p><?= nl2br(htmlspecialchars($plan['description'])) ?></p>

                    <h5 class="mt-4">Workout Logs</h5>
                    <?php 
                    $logs = fetchWorkoutLogs($conn, $member_id, $plan['plan_id']);
                    if (empty($logs)): ?>
                        <p>No workout logs yet.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered table-hover">
                                <thead class="thead-light">
                                    <tr>
                                        <th>Date</th>
                                        <th>Sets</th>
                                        <th>Reps</th>
                                        <th>Weight Used (kg)</th>
                                        <th>Duration (min)</th>
                                        <th>Notes</th>
                                        <th style="width: 90px;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($logs as $log): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($log['log_date']) ?></td>
                                            <td><?= htmlspecialchars($log['sets_completed']) ?></td>
                                            <td><?= htmlspecialchars($log['reps_completed']) ?></td>
                                            <td><?= htmlspecialchars($log['weight_used']) ?></td>
                                            <td><?= htmlspecialchars($log['duration_minutes']) ?></td>
                                            <td class="log-notes"><?= nl2br(htmlspecialchars($log['notes'])) ?></td>
                                            <td>
                                                <form method="post" onsubmit="return confirm('Are you sure you want to delete this log?');" style="margin-bottom:0;">
                                                    <input type="hidden" name="delete_log_id" value="<?= $log['log_id'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <hr>
    <h2>Active Diet Plans</h2>
    <?php if (empty($diet_plans)): ?>
        <p>You have no active diet plans.</p>
    <?php else: ?>
        <ul class="list-group">
            <?php foreach ($diet_plans as $diet): ?>
                <li class="list-group-item">
                    <h5><?= htmlspecialchars($diet['plan_name']) ?></h5>
                    <p><?= nl2br(htmlspecialchars($diet['description'])) ?></p>
                    <small class="text-muted">Created at: <?= htmlspecialchars($diet['created_at']) ?></small>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>

<!-- Add Workout Log Modal -->
<div class="modal fade" id="addWorkoutLogModal" tabindex="-1" role="dialog" aria-labelledby="addWorkoutLogModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <form method="post" class="modal-content" novalidate>
      <input type="hidden" name="add_workout_log" value="1">
      <input type="hidden" name="plan_id" id="modal_plan_id" value="">
      <div class="modal-header">
        <h5 class="modal-title" id="addWorkoutLogModalLabel">Add Workout Log</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
          <div class="form-group">
              <label for="log_date">Date</label>
              <input type="date" class="form-control" name="log_date" id="log_date" required max="<?= date('Y-m-d') ?>">
          </div>
          <div class="form-group">
              <label for="sets_completed">Sets Completed</label>
              <input type="number" min="1" class="form-control" name="sets_completed" id="sets_completed" required>
          </div>
          <div class="form-group">
              <label for="reps_completed">Reps Completed</label>
              <input type="number" min="1" class="form-control" name="reps_completed" id="reps_completed" required>
          </div>
          <div class="form-group">
              <label for="weight_used">Weight Used (kg)</label>
              <input type="number" step="0.1" min="0" class="form-control" name="weight_used" id="weight_used" required>
          </div>
          <div class="form-group">
              <label for="duration_minutes">Duration (minutes)</label>
              <input type="number" min="1" class="form-control" name="duration_minutes" id="duration_minutes" required>
          </div>
          <div class="form-group">
              <label for="notes">Notes (optional)</label>
              <textarea class="form-control" name="notes" id="notes" rows="3"></textarea>
          </div>
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn btn-primary">Add Log</button>
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
      </div>
    </form>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
$('#addWorkoutLogModal').on('show.bs.modal', function (event) {
    var button = $(event.relatedTarget);
    var planId = button.data('planid');
    var planName = button.data('planname');
    var modal = $(this);
    modal.find('.modal-title').text('Add Workout Log for "' + planName + '"');
    modal.find('#modal_plan_id').val(planId);
    modal.find('form')[0].reset();
});
</script>

</body>
</html>
