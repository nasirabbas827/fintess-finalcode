<?php
session_start();
include('../config.php');

if (!isset($_SESSION['member_id']) || $_SESSION['user_type'] != 'Member') {
    header("Location: ../login.php");
    exit;
}

$member_id = $_SESSION['member_id'];

// Fetch member details
$member_query = "SELECT CONCAT(first_name, ' ', last_name) as member_name, email 
                FROM members 
                WHERE member_id = ?";
$stmt = mysqli_prepare($conn, $member_query);
mysqli_stmt_bind_param($stmt, "i", $member_id);
mysqli_stmt_execute($stmt);
$member_result = mysqli_stmt_get_result($stmt);
$member = mysqli_fetch_assoc($member_result);

// Fetch the most recent membership
$membership_query = "SELECT mmp.*, mp.plan_name, mp.duration_days, mp.price, mp.description 
                    FROM member_memberships mmp
                    JOIN membership_plans mp ON mmp.plan_id = mp.plan_id
                    WHERE mmp.member_id = ? 
                    ORDER BY mmp.end_date DESC LIMIT 1";
$stmt = mysqli_prepare($conn, $membership_query);
mysqli_stmt_bind_param($stmt, "i", $member_id);
mysqli_stmt_execute($stmt);
$membership_result = mysqli_stmt_get_result($stmt);
$current_plan = mysqli_fetch_assoc($membership_result);

$today = date('Y-m-d');
$is_expired = !$current_plan || $current_plan['end_date'] < $today;

// Fetch upcoming appointments
$appointments_query = "SELECT a.appointment_id, a.appointment_date, a.start_time, a.status,
                             CONCAT(t.first_name, ' ', t.last_name) as trainer_name
                      FROM appointments a
                      JOIN trainers t ON a.trainer_id = t.trainer_id
                      WHERE a.member_id = ? AND a.appointment_date >= CURDATE()
                      ORDER BY a.appointment_date, a.start_time ASC
                      LIMIT 5";
$stmt = mysqli_prepare($conn, $appointments_query);
mysqli_stmt_bind_param($stmt, "i", $member_id);
mysqli_stmt_execute($stmt);
$appointments_result = mysqli_stmt_get_result($stmt);
$appointments = [];
while ($row = mysqli_fetch_assoc($appointments_result)) {
    $appointments[] = $row;
}

// Fetch recent workout logs
$workout_query = "SELECT wl.log_id, wl.log_date, wl.sets_completed, wl.reps_completed, wl.weight_used, wl.duration_minutes,
                        wp.plan_name
                 FROM workout_logs wl
                 JOIN workout_plans wp ON wl.plan_id = wp.plan_id
                 WHERE wl.member_id = ?
                 ORDER BY wl.log_date DESC
                 LIMIT 5";
$stmt = mysqli_prepare($conn, $workout_query);
mysqli_stmt_bind_param($stmt, "i", $member_id);
mysqli_stmt_execute($stmt);
$workout_result = mysqli_stmt_get_result($stmt);
$workouts = [];
while ($row = mysqli_fetch_assoc($workout_result)) {
    $workouts[] = $row;
}

// Fetch workout trend data
$workout_trend_query = "SELECT DATE_FORMAT(log_date, '%Y-%m') as month, COUNT(*) as count
                       FROM workout_logs
                       WHERE member_id = ? AND log_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
                       GROUP BY month
                       ORDER BY month";
$stmt = mysqli_prepare($conn, $workout_trend_query);
mysqli_stmt_bind_param($stmt, "i", $member_id);
mysqli_stmt_execute($stmt);
$workout_trend_result = mysqli_stmt_get_result($stmt);
$workout_trend = [];
while ($row = mysqli_fetch_assoc($workout_trend_result)) {
    $workout_trend[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Member Dashboard - <?php echo htmlspecialchars($member['member_name']); ?></title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .card-counter {
            box-shadow: 2px 2px 10px #DADADA;
            margin: 5px;
            padding: 20px 10px;
            background-color: #fff;
            height: 100px;
            border-radius: 5px;
            transition: .3s linear all;
        }
        .card-counter:hover {
            box-shadow: 4px 4px 20px #DADADA;
            transition: .3s linear all;
        }
        .card-counter.primary {
            background-color: #007bff;
            color: #FFF;
        }
        .card-counter.success {
            background-color: #66bb6a;
            color: #FFF;
        }
        .card-counter.warning {
            background-color: #ffc107;
            color: #FFF;
        }
        .card-counter i {
            font-size: 5em;
            opacity: 0.2;
        }
        .card-counter .count-numbers {
            position: absolute;
            right: 35px;
            top: 20px;
            font-size: 32px;
            display: block;
        }
        .card-counter .count-name {
            position: absolute;
            right: 35px;
            top: 65px;
            font-style: italic;
            text-transform: capitalize;
            opacity: 0.5;
            display: block;
            font-size: 18px;
        }
        .recent-item {
            border-left: 3px solid #007bff;
            padding-left: 10px;
            margin-bottom: 10px;
        }
        .recent-item:hover {
            background-color: #f8f9fa;
        }
        .plan-card {
            transition: transform 0.3s;
        }
        .plan-card:hover {
            transform: translateY(-5px);
        }
    </style>
</head>
<body>
<?php include('navbar.php'); ?>

<div class="container-fluid mt-4">
    <h2 class="text-center text-primary mb-4">Welcome, <?php echo htmlspecialchars($member['member_name']); ?></h2>
    <p class="text-center text-muted">Email: <?php echo htmlspecialchars($member['email']); ?></p>

    <!-- Summary Cards -->
    <div class="row">
        <div class="col-md-4">
            <div class="card-counter <?php echo $is_expired ? 'warning' : 'success'; ?>">
                <i class="fas fa-ticket-alt"></i>
                <span class="count-numbers"><?php echo $is_expired ? 'No Active Plan' : $current_plan['plan_name']; ?></span>
                <span class="count-name">Membership Status</span>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card-counter primary">
                <i class="fas fa-calendar-check"></i>
                <span class="count-numbers"><?php echo count($appointments); ?></span>
                <span class="count-name">Upcoming Appointments</span>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card-counter success">
                <i class="fas fa-dumbbell"></i>
                <span class="count-numbers"><?php echo count($workouts); ?></span>
                <span class="count-name">Recent Workouts</span>
            </div>
        </div>
    </div>

    <?php if (!$current_plan || $is_expired): ?>
        <!-- Available Plans -->
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header bg-warning text-white">
                        <h5>Available Membership Plans</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <?php
                            $plans = mysqli_query($conn, "SELECT * FROM membership_plans");
                            while ($plan = mysqli_fetch_assoc($plans)):
                            ?>
                                <div class="col-md-4 mb-3">
                                    <div class="card plan-card h-100">
                                        <div class="card-body d-flex flex-column">
                                            <h5 class="card-title"><?php echo htmlspecialchars($plan['plan_name']); ?></h5>
                                            <p class="card-text flex-grow-1"><?php echo htmlspecialchars($plan['description']); ?></p>
                                            <p><strong>Price:</strong> $<?php echo number_format($plan['price'], 2); ?></p>
                                            <p><strong>Duration:</strong> <?php echo intval($plan['duration_days']); ?> days</p>
                                            <a href="purchase_plan.php?plan_id=<?php echo intval($plan['plan_id']); ?>" class="btn btn-primary mt-auto">Purchase Plan</a>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <!-- Active Membership Content -->
        <div class="row mt-4">
            <!-- Current Plan Details -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5>Current Membership Plan</h5>
                    </div>
                    <div class="card-body">
                        <h5><?php echo htmlspecialchars($current_plan['plan_name']); ?></h5>
                        <p><strong>Ends on:</strong> <?php echo htmlspecialchars($current_plan['end_date']); ?></p>
                        <p><strong>Price:</strong> $<?php echo number_format($current_plan['price'], 2); ?></p>
                        <p><strong>Description:</strong> <?php echo htmlspecialchars($current_plan['description']); ?></p>
                        <?php if ($current_plan['payment_status'] === 'pending'): ?>
                            <a href="payment.php?membership_id=<?php echo intval($current_plan['membership_id']); ?>" class="btn btn-danger mt-3">Pay Now</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h5>Quick Actions</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($current_plan['payment_status'] === 'paid'): ?>
                            <a href="view_trainers.php" class="btn btn-primary mr-2 mb-2">View Trainers</a>
                            <a href="make_appointment.php" class="btn btn-success mr-2 mb-2">Make Appointment</a>
                            <a href="member_plans.php" class="btn btn-info mr-2 mb-2">View Plans</a>
                            <a href="view_progress.php" class="btn btn-dark mr-2 mb-2">Track Progress</a>
                        <?php else: ?>
                            <p class="text-muted">Complete your payment to access these features.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Appointments and Workouts -->
        <div class="row mt-4">
            <!-- Upcoming Appointments -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5>Upcoming Appointments</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($appointments)): ?>
                            <p class="text-muted">No upcoming appointments.</p>
                        <?php else: ?>
                            <?php foreach ($appointments as $appt): ?>
                                <div class="recent-item">
                                    <strong><?php echo htmlspecialchars($appt['trainer_name']); ?></strong>
                                    <div><?php echo date('M j, Y', strtotime($appt['appointment_date'])); ?> 
                                         at <?php echo date('g:i A', strtotime($appt['start_time'])); ?></div>
                                    <small class="text-muted">Status: <?php echo ucfirst($appt['status']); ?></small>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Recent Workout Logs -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5>Recent Workouts</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($workouts)): ?>
                            <p class="text-muted">No recent workouts logged.</p>
                        <?php else: ?>
                            <?php foreach ($workouts as $workout): ?>
                                <div class="recent-item">
                                    <strong><?php echo htmlspecialchars($workout['plan_name']); ?></strong>
                                    <div>Sets: <?php echo $workout['sets_completed'] ?? 'N/A'; ?>, 
                                         Reps: <?php echo $workout['reps_completed'] ?? 'N/A'; ?>, 
                                         Weight: <?php echo $workout['weight_used'] ?? 'N/A'; ?> kg</div>
                                    <small class="text-muted"><?php echo date('M j, Y', strtotime($workout['log_date'])); ?></small>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Workout Trend Chart -->
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h5>Workout Activity (Last 6 Months)</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="workoutChart" height="100"></canvas>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
    // Workout Trend Chart
    const workoutCtx = document.getElementById('workoutChart').getContext('2d');
    const workoutChart = new Chart(workoutCtx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode(array_column($workout_trend, 'month')); ?>,
            datasets: [{
                label: 'Workout Sessions',
                data: <?php echo json_encode(array_column($workout_trend, 'count')); ?>,
                backgroundColor: 'rgba(0, 123, 255, 0.5)',
                borderColor: 'rgba(0, 123, 255, 1)',
                borderWidth: 1,
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Number of Workouts'
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: 'Month'
                    }
                }
            }
        }
    });
</script>
</body>
</html>