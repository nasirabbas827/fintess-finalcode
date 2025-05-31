<?php
session_start();
include('../config.php');

if (!isset($_SESSION['member_id']) || $_SESSION['user_type'] != 'Member') {
    header("Location: ../login.php");
    exit;
}

// Fetch all trainers
$sql = "SELECT * FROM trainers ORDER BY first_name, last_name";
$result = mysqli_query($conn, $sql);

// Prepare an array to hold availability by trainer
$availability_sql = "SELECT * FROM trainer_availability WHERE is_available = 1 ORDER BY day_of_week, start_time";
$availability_result = mysqli_query($conn, $availability_sql);

$availability = [];
while ($row = mysqli_fetch_assoc($availability_result)) {
    $availability[$row['trainer_id']][] = $row;
}

?>

<!DOCTYPE html>
<html>
<head>
    <title>View Trainers</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">

    <style>
        .profile-pic {
            width: 120px;
            height: 120px;
            object-fit: cover;
            border-radius: 50%;
        }
        .availability-list {
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
<?php include('navbar.php'); ?>

<div class="container mt-5">
    <h2 class="mb-4">Trainer Profiles & Availability</h2>

    <?php if (mysqli_num_rows($result) == 0): ?>
        <p>No trainers found.</p>
    <?php else: ?>
        <div class="row">
            <?php while ($trainer = mysqli_fetch_assoc($result)): ?>
                <div class="col-md-6 mb-4">
                    <div class="card">
                        <div class="card-body d-flex">
                            <img src="../admin/uploads/<?= htmlspecialchars($trainer['profile_picture']) ?>" alt="Profile Picture" class="profile-pic mr-3">
                            <div>
                                <h4><?= htmlspecialchars($trainer['first_name'] . ' ' . $trainer['last_name']) ?></h4>
                                <p><strong>Specialization:</strong> <?= htmlspecialchars($trainer['specialization']) ?></p>
                                <p><strong>Certification:</strong> <?= htmlspecialchars($trainer['certification']) ?></p>
                                <p><strong>Experience:</strong> <?= intval($trainer['experience_years']) ?> years</p>
                                <p><strong>Hourly Rate:</strong> $<?= number_format($trainer['hourly_rate'], 2) ?></p>
                                <p><strong>Email:</strong> <?= htmlspecialchars($trainer['email']) ?></p>
                                <p><strong>Bio:</strong> <?= nl2br(htmlspecialchars($trainer['bio'])) ?></p>

                                <h6>Availability:</h6>
                                <?php if (!empty($availability[$trainer['trainer_id']])): ?>
                                    <ul class="availability-list">
                                        <?php
                                        // Map numeric day to weekday name
                                        $days = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
                                        foreach ($availability[$trainer['trainer_id']] as $slot):
                                            $day_name = $days[intval($slot['day_of_week'])];
                                            $start = date("g:i A", strtotime($slot['start_time']));
                                            $end = date("g:i A", strtotime($slot['end_time']));
                                        ?>
                                        <li><?= htmlspecialchars($day_name) ?>: <?= htmlspecialchars($start) ?> - <?= htmlspecialchars($end) ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php else: ?>
                                    <p><em>No availability set.</em></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php endif; ?>
</div>

</body>
</html>
