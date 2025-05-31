<?php
session_start();
include('config.php'); // Assuming config.php handles database connection

if (!isset($_SESSION['trainer_id']) || $_SESSION['user_type'] != 'Trainer') {
    header("Location: ../login.php");
    exit;
}

$trainer_id = $_SESSION['trainer_id'];

// Function to fetch data from the database
function fetchData($conn, $query) {
    $result = mysqli_query($conn, $query);
    if ($result) {
        return mysqli_fetch_all($result, MYSQLI_ASSOC);
    } else {
        return [];
    }
}

// Fetch Trainer's Name
$trainerNameQuery = "SELECT first_name, last_name FROM trainers WHERE trainer_id = $trainer_id";
$trainerNameData = fetchData($conn, $trainerNameQuery);
$trainerFirstName = $trainerNameData[0]['first_name'] ?? 'Trainer';
$trainerLastName = $trainerNameData[0]['last_name'] ?? '';

// Total Members Assigned to this Trainer (assuming a direct relationship or through plans/appointments)
// We'll count members who have appointments, diet plans, workout plans, or fitness progress records with this trainer
$totalAssignedMembersQuery = "
    SELECT COUNT(DISTINCT m.member_id) AS total_assigned_members
    FROM members m
    LEFT JOIN appointments a ON m.member_id = a.member_id AND a.trainer_id = $trainer_id
    LEFT JOIN diet_plans dp ON m.member_id = dp.member_id AND dp.trainer_id = $trainer_id
    LEFT JOIN workout_plans wp ON m.member_id = wp.member_id AND wp.trainer_id = $trainer_id
    LEFT JOIN fitness_progress fp ON m.member_id = fp.member_id AND fp.trainer_id = $trainer_id
    WHERE a.trainer_id = $trainer_id OR dp.trainer_id = $trainer_id OR wp.trainer_id = $trainer_id OR fp.trainer_id = $trainer_id
";
$totalAssignedMembersData = fetchData($conn, $totalAssignedMembersQuery);
$totalAssignedMembers = $totalAssignedMembersData[0]['total_assigned_members'] ?? 0;

// Upcoming Appointments for the Trainer (today and next 7 days)
$upcomingAppointmentsQuery = "SELECT a.*, m.first_name, m.last_name
                              FROM appointments a
                              JOIN members m ON a.member_id = m.member_id
                              WHERE a.trainer_id = $trainer_id
                              AND a.appointment_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
                              AND a.status = 'scheduled'
                              ORDER BY a.appointment_date, a.start_time
                              LIMIT 5";
$upcomingAppointments = fetchData($conn, $upcomingAppointmentsQuery);

// Recent Fitness Progress Updates by this Trainer
$recentProgressUpdatesQuery = "SELECT fp.*, m.first_name, m.last_name
                               FROM fitness_progress fp
                               JOIN members m ON fp.member_id = m.member_id
                               WHERE fp.trainer_id = $trainer_id
                               ORDER BY fp.record_date DESC
                               LIMIT 5";
$recentProgressUpdates = fetchData($conn, $recentProgressUpdatesQuery);

// Members with active diet plans assigned by this trainer
$activeDietPlansQuery = "SELECT dp.*, m.first_name, m.last_name
                         FROM diet_plans dp
                         JOIN members m ON dp.member_id = m.member_id
                         WHERE dp.trainer_id = $trainer_id AND dp.is_active = 1
                         LIMIT 5";
$activeDietPlans = fetchData($conn, $activeDietPlansQuery);

// Members with active workout plans assigned by this trainer
$activeWorkoutPlansQuery = "SELECT wp.*, m.first_name, m.last_name
                            FROM workout_plans wp
                            JOIN members m ON wp.member_id = m.member_id
                            WHERE wp.trainer_id = $trainer_id AND wp.is_active = 1
                            LIMIT 5";
$activeWorkoutPlans = fetchData($conn, $activeWorkoutPlansQuery);


// Trainer Availability for the week (for quick reference)
$trainerAvailabilityQuery = "SELECT day_of_week, start_time, end_time FROM trainer_availability WHERE trainer_id = $trainer_id AND is_available = 1 ORDER BY FIELD(day_of_week, 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'), start_time";
$trainerAvailability = fetchData($conn, $trainerAvailabilityQuery);

// Close the database connection (if not handled globally by config.php at the end of script)
// mysqli_close($conn);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Trainer Dashboard</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .dashboard-card {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 20px;
            text-align: center;
        }
        .dashboard-card h3 {
            color: #28a745; /* Trainer specific color */
            font-size: 1.5rem;
            margin-bottom: 10px;
        }
        .dashboard-card p {
            font-size: 2.5rem;
            font-weight: bold;
            color: #333;
        }
        .list-group-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .list-group-item span.badge {
            min-width: 60px; /* Ensure badges have a consistent width */
        }
    </style>
</head>
<body>
<?php include('navbar.php'); ?>
    
<div class="container mt-5 mb-5">
    <div class="jumbotron text-center bg-success text-white">
        <h1 class="display-4">Welcome, <?= htmlspecialchars($trainerFirstName . ' ' . $trainerLastName) ?>!</h1>
        <p class="lead">Email: <?= htmlspecialchars($_SESSION['email']) ?></p>
        <hr class="my-4">
        <p>Your personal hub to manage members, appointments, and progress.</p>
    </div>

    <div class="row">
        <div class="col-md-4">
            <div class="dashboard-card">
                <h3>Assigned Members</h3>
                <p><?php echo $totalAssignedMembers; ?></p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="dashboard-card">
                <h3>Upcoming Appointments</h3>
                <?php
                $upcomingCountQuery = "SELECT COUNT(*) AS count FROM appointments WHERE trainer_id = $trainer_id AND appointment_date >= CURDATE() AND status = 'scheduled'";
                $upcomingCountData = fetchData($conn, $upcomingCountQuery);
                $upcomingCount = $upcomingCountData[0]['count'] ?? 0;
                ?>
                <p><?php echo $upcomingCount; ?></p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="dashboard-card">
                <h3>Active Plans</h3>
                <?php
                $activePlansCountQuery = "SELECT (SELECT COUNT(*) FROM diet_plans WHERE trainer_id = $trainer_id AND is_active = 1) + (SELECT COUNT(*) FROM workout_plans WHERE trainer_id = $trainer_id AND is_active = 1) AS total_active_plans";
                $activePlansCountData = fetchData($conn, $activePlansCountQuery);
                $activePlansCount = $activePlansCountData[0]['total_active_plans'] ?? 0;
                ?>
                <p><?php echo $activePlansCount; ?></p>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-md-6">
            <div class="dashboard-card text-left">
                <h3>Your Upcoming Appointments</h3>
                <?php if (!empty($upcomingAppointments)): ?>
                    <ul class="list-group">
                        <?php foreach ($upcomingAppointments as $appointment): ?>
                            <li class="list-group-item">
                                <div>
                                    <strong><?php echo htmlspecialchars($appointment['first_name'] . ' ' . $appointment['last_name']); ?></strong>
                                </div>
                                <div>
                                    <span class="badge badge-primary"><?php echo date("M d", strtotime($appointment['appointment_date'])); ?></span>
                                    <span class="badge badge-info"><?php echo date("h:i A", strtotime($appointment['start_time'])); ?></span>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p>No upcoming appointments in the next 7 days.</p>
                <?php endif; ?>
            </div>
        </div>
        <div class="col-md-6">
            <div class="dashboard-card text-left">
                <h3>Recent Fitness Progress Updates</h3>
                <?php if (!empty($recentProgressUpdates)): ?>
                    <ul class="list-group">
                        <?php foreach ($recentProgressUpdates as $progress): ?>
                            <li class="list-group-item">
                                <div>
                                    <strong><?php echo htmlspecialchars($progress['first_name'] . ' ' . $progress['last_name']); ?></strong>
                                    (Weight: <?php echo htmlspecialchars($progress['weight']); ?> lbs)
                                </div>
                                <div>
                                    <small><?php echo date("M d, Y", strtotime($progress['record_date'])); ?></small>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p>No recent fitness progress updates recorded by you.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-md-6">
            <div class="dashboard-card text-left">
                <h3>Active Diet Plans You Assigned</h3>
                <?php if (!empty($activeDietPlans)): ?>
                    <ul class="list-group">
                        <?php foreach ($activeDietPlans as $plan): ?>
                            <li class="list-group-item">
                                <div>
                                    <strong><?php echo htmlspecialchars($plan['plan_name']); ?></strong> for <?php echo htmlspecialchars($plan['first_name'] . ' ' . $plan['last_name']); ?>
                                </div>
                                <div>
                                    <small>Created: <?php echo date("M d, Y", strtotime($plan['created_at'])); ?></small>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p>No active diet plans assigned by you.</p>
                <?php endif; ?>
            </div>
        </div>
        <div class="col-md-6">
            <div class="dashboard-card text-left">
                <h3>Active Workout Plans You Assigned</h3>
                <?php if (!empty($activeWorkoutPlans)): ?>
                    <ul class="list-group">
                        <?php foreach ($activeWorkoutPlans as $plan): ?>
                            <li class="list-group-item">
                                <div>
                                    <strong><?php echo htmlspecialchars($plan['plan_name']); ?></strong> for <?php echo htmlspecialchars($plan['first_name'] . ' ' . $plan['last_name']); ?>
                                </div>
                                <div>
                                    <small>Created: <?php echo date("M d, Y", strtotime($plan['created_at'])); ?></small>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p>No active workout plans assigned by you.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-md-12">
            <div class="dashboard-card text-left">
                <h3>Your Weekly Availability</h3>
                <?php if (!empty($trainerAvailability)): ?>
                    <ul class="list-group">
                        <?php
                        $groupedAvailability = [];
                        foreach ($trainerAvailability as $avail) {
                            $groupedAvailability[$avail['day_of_week']][] = date("h:i A", strtotime($avail['start_time'])) . ' - ' . date("h:i A", strtotime($avail['end_time']));
                        }
                        $dayOrder = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
                        foreach ($dayOrder as $day) {
                            echo '<li class="list-group-item">';
                            echo '<strong>' . ucfirst($day) . ':</strong> ';
                            if (isset($groupedAvailability[$day])) {
                                echo implode(', ', $groupedAvailability[$day]);
                            } else {
                                echo 'Not available';
                            }
                            echo '</li>';
                        }
                        ?>
                    </ul>
                <?php else: ?>
                    <p>No availability set. Please update your availability in your profile.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

</div>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.16.0/umd/popper.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>