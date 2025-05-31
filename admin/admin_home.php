<?php
session_start();
include('config.php');
if (!isset($_SESSION["usertype"]) || $_SESSION["usertype"] !== "admin") {
    header("Location: admin_login.php");
    exit;
}


// Get counts for dashboard
$counts = [];
$tables = ['members', 'trainers', 'appointments', 'payments', 'workout_plans', 'diet_plans'];

foreach ($tables as $table) {
    $query = "SELECT COUNT(*) as count FROM $table";
    $result = mysqli_query($conn, $query);
    $row = mysqli_fetch_assoc($result);
    $counts[$table] = $row['count'];
}

// Get recent payments
$recent_payments = [];
$query = "SELECT p.*, m.first_name, m.last_name 
          FROM payments p
          JOIN member_memberships mm ON p.membership_id = mm.membership_id
          JOIN members m ON mm.member_id = m.member_id
          ORDER BY p.payment_date DESC LIMIT 5";
$result = mysqli_query($conn, $query);
while ($row = mysqli_fetch_assoc($result)) {
    $recent_payments[] = $row;
}

// Get recent members
$recent_members = [];
$query = "SELECT * FROM members ORDER BY member_id DESC LIMIT 5";
$result = mysqli_query($conn, $query);
while ($row = mysqli_fetch_assoc($result)) {
    $recent_members[] = $row;
}

// Get upcoming appointments
$upcoming_appointments = [];
$query = "SELECT a.*, m.first_name as member_first, m.last_name as member_last, 
                  t.first_name as trainer_first, t.last_name as trainer_last
          FROM appointments a
          JOIN members m ON a.member_id = m.member_id
          JOIN trainers t ON a.trainer_id = t.trainer_id
          WHERE a.appointment_date >= CURDATE()
          ORDER BY a.appointment_date, a.start_time ASC LIMIT 5";
$result = mysqli_query($conn, $query);
while ($row = mysqli_fetch_assoc($result)) {
    $upcoming_appointments[] = $row;
}

// Get monthly revenue data for chart
$monthly_revenue = [];
$query = "SELECT 
            DATE_FORMAT(payment_date, '%Y-%m') as month,
            SUM(amount) as total
          FROM payments
          WHERE status = 'completed'
          GROUP BY DATE_FORMAT(payment_date, '%Y-%m')
          ORDER BY month DESC
          LIMIT 6";
$result = mysqli_query($conn, $query);
while ($row = mysqli_fetch_assoc($result)) {
    $monthly_revenue[] = $row;
}

// Get membership status counts
$membership_status = [];
$query = "SELECT 
            COUNT(CASE WHEN is_active = 1 THEN 1 END) as active,
            COUNT(CASE WHEN is_active = 0 THEN 1 END) as inactive
          FROM member_memberships";
$result = mysqli_query($conn, $query);
$membership_status = mysqli_fetch_assoc($result);

// Get trainer ratings
$trainer_ratings = [];
$query = "SELECT 
            t.trainer_id, 
            CONCAT(t.first_name, ' ', t.last_name) as trainer_name,
            AVG(tr.rating) as avg_rating,
            COUNT(tr.review_id) as review_count
          FROM trainers t
          LEFT JOIN trainer_reviews tr ON t.trainer_id = tr.trainer_id
          GROUP BY t.trainer_id
          ORDER BY avg_rating DESC";
$result = mysqli_query($conn, $query);
while ($row = mysqli_fetch_assoc($result)) {
    $trainer_ratings[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
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
        .card-counter.danger {
            background-color: #ef5350;
            color: #FFF;
        }  
        .card-counter.success {
            background-color: #66bb6a;
            color: #FFF;
        }  
        .card-counter.info {
            background-color: #26c6da;
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

    </style>
</head>
<body>

<?php include('admin_navbar.php'); ?>

<div class="container-fluid mt-4">
    <h2 class="text-center text-primary mb-4">Admin Dashboard</h2>

    <!-- Summary Cards -->
    <div class="row">
        <div class="col-md-3">
            <div class="card-counter primary">
                <i class="fas fa-users"></i>
                <span class="count-numbers"><?php echo $counts['members']; ?></span>
                <span class="count-name">Members</span>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card-counter info">
                <i class="fas fa-dumbbell"></i>
                <span class="count-numbers"><?php echo $counts['trainers']; ?></span>
                <span class="count-name">Trainers</span>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card-counter success">
                <i class="fas fa-calendar-check"></i>
                <span class="count-numbers"><?php echo $counts['appointments']; ?></span>
                <span class="count-name">Appointments</span>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card-counter danger">
                <i class="fas fa-money-bill-wave"></i>
                <span class="count-numbers">$<?php 
                    $query = "SELECT SUM(amount) as total FROM payments WHERE status = 'completed'";
                    $result = mysqli_query($conn, $query);
                    $row = mysqli_fetch_assoc($result);
                    echo number_format($row['total'], 2);
                ?></span>
                <span class="count-name">Total Revenue</span>
            </div>
        </div>
    </div>

    <!-- Charts and Recent Activities -->
    <div class="row mt-4">
        <!-- Revenue Chart -->
        <div class="chart col-md-6">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5>Monthly Revenue</h5>
                </div>
                <div class="card-body">
                    <canvas id="revenueChart" height="250"></canvas>
                </div>
            </div>
        </div>

        <!-- Membership Status -->
        <div class="chart col-md-6">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h5>Membership Status</h5>
                </div>
                <div class="card-body">
                    <canvas id="membershipChart" height="250"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activities -->
    <div class="row mt-4">
        <!-- Recent Payments -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5>Recent Payments</h5>
                </div>
                <div class="card-body">
                    <?php foreach ($recent_payments as $payment): ?>
                        <div class="recent-item">
                            <strong><?php echo $payment['first_name'] . ' ' . $payment['last_name']; ?></strong>
                            <div>$<?php echo number_format($payment['amount'], 2); ?></div>
                            <small class="text-muted"><?php echo date('M j, Y', strtotime($payment['payment_date'])); ?></small>
                        </div>
                    <?php endforeach; ?>
                    <a href="payments.php" class="btn btn-sm btn-outline-success mt-2">View All</a>
                </div>
            </div>
        </div>

        <!-- Recent Members -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-warning text-white">
                    <h5>Recent Members</h5>
                </div>
                <div class="card-body">
                    <?php foreach ($recent_members as $member): ?>
                        <div class="recent-item">
                            <strong><?php echo $member['first_name'] . ' ' . $member['last_name']; ?></strong>
                            <div><?php echo $member['email']; ?></div>
                            <small class="text-muted">Joined: <?php echo date('M j, Y', strtotime($member['date_of_birth'])); ?></small>
                        </div>
                    <?php endforeach; ?>
                    <a href="view_members.php" class="btn btn-sm btn-outline-warning mt-2">View All</a>
                </div>
            </div>
        </div>

        <!-- Upcoming Appointments -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-danger text-white">
                    <h5>Upcoming Appointments</h5>
                </div>
                <div class="card-body">
                    <?php foreach ($upcoming_appointments as $appt): ?>
                        <div class="recent-item">
                            <strong><?php echo $appt['member_first'] . ' ' . $appt['member_last']; ?></strong>
                            <div>with <?php echo $appt['trainer_first'] . ' ' . $appt['trainer_last']; ?></div>
                            <small class="text-muted">
                                <?php echo date('M j', strtotime($appt['appointment_date'])); ?> 
                                at <?php echo date('g:i A', strtotime($appt['start_time'])); ?>
                            </small>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Trainer Ratings -->
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-secondary text-white">
                    <h5>Trainer Ratings</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Trainer</th>
                                    <th>Average Rating</th>
                                    <th>Total Reviews</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($trainer_ratings as $trainer): ?>
                                    <tr>
                                        <td><?php echo $trainer['trainer_name']; ?></td>
                                        <td>
                                            <?php 
                                            if ($trainer['avg_rating']) {
                                                echo number_format($trainer['avg_rating'], 1);
                                                for ($i = 1; $i <= 5; $i++) {
                                                    echo $i <= $trainer['avg_rating'] ? '★' : '☆';
                                                }
                                            } else {
                                                echo 'No ratings yet';
                                            }
                                            ?>
                                        </td>
                                        <td><?php echo $trainer['review_count']; ?></td>
                                        <td>
                                            <a href="view_ratings.php?id=<?php echo $trainer['trainer_id']; ?>" class="btn btn-sm btn-info">View</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Revenue Chart
    const revenueCtx = document.getElementById('revenueChart').getContext('2d');
    const revenueChart = new Chart(revenueCtx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode(array_column($monthly_revenue, 'month')); ?>,
            datasets: [{
                label: 'Monthly Revenue ($)',
                data: <?php echo json_encode(array_column($monthly_revenue, 'total')); ?>,
                backgroundColor: 'rgba(0, 123, 255, 0.5)',
                borderColor: 'rgba(0, 123, 255, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    // Membership Status Chart
    const membershipCtx = document.getElementById('membershipChart').getContext('2d');
    const membershipChart = new Chart(membershipCtx, {
        type: 'doughnut',
        data: {
            labels: ['Active Memberships', 'Inactive Memberships'],
            datasets: [{
                data: [<?php echo $membership_status['active']; ?>, <?php echo $membership_status['inactive']; ?>],
                backgroundColor: [
                    'rgba(40, 167, 69, 0.5)',
                    'rgba(220, 53, 69, 0.5)'
                ],
                borderColor: [
                    'rgba(40, 167, 69, 1)',
                    'rgba(220, 53, 69, 1)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true
        }
    });
</script>

</body>
</html>