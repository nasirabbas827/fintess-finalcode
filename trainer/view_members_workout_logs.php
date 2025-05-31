<?php
session_start();
include('config.php');

if (!isset($_SESSION['trainer_id']) || $_SESSION['user_type'] !== 'Trainer') {
    header("Location: ../login.php");
    exit;
}

$trainer_id = $_SESSION['trainer_id'];

// Fetch all members assigned to this trainer (optional: if you want to limit members by trainer, add conditions)
$members_query = "SELECT * FROM members ORDER BY first_name, last_name";
$members_result = mysqli_query($conn, $members_query);

// Pre-fetch workout plans and logs indexed by member and plan for performance
// 1) Fetch workout plans by trainer and members
$plans = [];
$plans_stmt = mysqli_prepare($conn, "SELECT * FROM workout_plans WHERE trainer_id = ?");
mysqli_stmt_bind_param($plans_stmt, "i", $trainer_id);
mysqli_stmt_execute($plans_stmt);
$plans_res = mysqli_stmt_get_result($plans_stmt);
while ($plan = mysqli_fetch_assoc($plans_res)) {
    $plans[$plan['member_id']][] = $plan;
}
mysqli_stmt_close($plans_stmt);

// 2) Fetch workout logs grouped by plan_id
$logs = [];
$plans_ids = [];
foreach ($plans as $member_plans) {
    foreach ($member_plans as $p) {
        $plans_ids[] = $p['plan_id'];
    }
}

if (count($plans_ids) > 0) {
    $ids_placeholder = implode(',', array_fill(0, count($plans_ids), '?'));
    $types = str_repeat('i', count($plans_ids));
    $logs_stmt = mysqli_prepare($conn, "SELECT * FROM workout_logs WHERE plan_id IN ($ids_placeholder) ORDER BY log_date DESC");
    mysqli_stmt_bind_param($logs_stmt, $types, ...$plans_ids);
    mysqli_stmt_execute($logs_stmt);
    $logs_res = mysqli_stmt_get_result($logs_stmt);
    while ($log = mysqli_fetch_assoc($logs_res)) {
        $logs[$log['plan_id']][] = $log;
    }
    mysqli_stmt_close($logs_stmt);
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Members Workout Logs</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        .plan-row { background-color: #f9f9f9; }
        .log-row { background-color: #fff; }
        .cursor-pointer { cursor: pointer; }
    </style>
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <link rel="stylesheet" href="../css/style.css">

</head>
<body>
<?php include('navbar.php'); ?>

<div class="container mt-4">
    <h2>All Members and Their Workout Logs</h2>
    <p>Trainer ID: <?= $trainer_id ?></p>

    <?php if (mysqli_num_rows($members_result) === 0): ?>
        <p>No members found.</p>
    <?php else: ?>
        <div class="accordion" id="membersAccordion">
            <?php while ($member = mysqli_fetch_assoc($members_result)): ?>
                <div class="card mb-2">
                    <div class="card-header cursor-pointer" id="heading<?= $member['member_id'] ?>" data-toggle="collapse" data-target="#collapse<?= $member['member_id'] ?>" aria-expanded="false" aria-controls="collapse<?= $member['member_id'] ?>">
                        <h5 class="mb-0">
                            <?= htmlspecialchars($member['first_name'] . ' ' . $member['last_name']) ?>
                            (ID: <?= $member['member_id'] ?>)
                        </h5>
                    </div>
                    <div id="collapse<?= $member['member_id'] ?>" class="collapse" aria-labelledby="heading<?= $member['member_id'] ?>" data-parent="#membersAccordion">
                        <div class="card-body">
                            <?php if (!isset($plans[$member['member_id']])): ?>
                                <p>No workout plans for this member.</p>
                            <?php else: ?>
                                <?php foreach ($plans[$member['member_id']] as $plan): ?>
                                    <div class="mb-3 plan-row p-3 border rounded">
                                        <h5>Plan: <?= htmlspecialchars($plan['plan_name']) ?></h5>
                                        <p>Description: <?= nl2br(htmlspecialchars($plan['description'])) ?></p>
                                        <p>Created at: <?= $plan['created_at'] ?></p>
                                        <p>Active: <?= $plan['is_active'] ? 'Yes' : 'No' ?></p>

                                        <?php if (!isset($logs[$plan['plan_id']])): ?>
                                            <p>No workout logs for this plan.</p>
                                        <?php else: ?>
                                            <table class="table table-sm table-bordered log-row">
                                                <thead>
                                                    <tr>
                                                        <th>Date</th>
                                                        <th>Sets Completed</th>
                                                        <th>Reps Completed</th>
                                                        <th>Weight Used (kg)</th>
                                                        <th>Duration (min)</th>
                                                        <th>Notes</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($logs[$plan['plan_id']] as $log): ?>
                                                        <tr>
                                                            <td><?= $log['log_date'] ?></td>
                                                            <td><?= $log['sets_completed'] ?></td>
                                                            <td><?= $log['reps_completed'] ?></td>
                                                            <td><?= $log['weight_used'] ?></td>
                                                            <td><?= $log['duration_minutes'] ?></td>
                                                            <td><?= htmlspecialchars($log['notes']) ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php endif; ?>
</div>

<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
