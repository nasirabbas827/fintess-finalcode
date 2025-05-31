<?php
include('config.php');

// Fetch membership plans
$plans_query = "SELECT plan_name, description, price, duration_days 
                FROM membership_plans 
                ORDER BY price ASC";
$plans_result = mysqli_query($conn, $plans_query);
$plans = [];
while ($row = mysqli_fetch_assoc($plans_result)) {
    $plans[] = $row;
}

// Fetch trainers
$trainers_query = "SELECT CONCAT(first_name, ' ', last_name) as trainer_name, specialization, profile_picture 
                  FROM trainers 
                  ORDER BY experience_years DESC 
                  LIMIT 3";
$trainers_result = mysqli_query($conn, $trainers_query);
$trainers = [];
while ($row = mysqli_fetch_assoc($trainers_result)) {
    $trainers[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FitnessHub - FitnessHub System</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="./css/style.css">
    <style>
        :root {
            --primary: #007bff;
            --secondary: #6c757d;
            --success: #28a745;
            --info: #17a2b8;
            --warning: #ffc107;
            --danger: #dc3545;
            --light: #f8f9fa;
            --dark: #343a40;
            --accent: #ff6f61;
            --highlight: #6f42c1;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, var(--light) 0%, #e0e7ff 100%);
            color: var(--dark);
        }
        .navbar {
            background-color: var(--dark);
            box-shadow: 0 4px 6px rgba(0,0,0,0.2);
        }
        .navbar-brand, .nav-link {
            color: white !important;
        }
        .navbar-brand:hover, .nav-link:hover {
            color: var(--accent) !important;
        }
        .hero-section {
            height: 600px;
            background: linear-gradient(rgba(0,0,0,0.7), rgba(0,0,0,0.7)), url('./images/hotel.jpg') center/cover no-repeat;
            color: white;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            border-radius: 0 0 30px 30px;
            animation: fadeIn 1.5s ease-in-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        .hero-section .btn-primary {
            background-color: var(--accent);
            border-color: var(--accent);
            padding: 12px 30px;
            font-size: 1.2rem;
            transition: transform 0.3s, background-color 0.3s;
        }
        .hero-section .btn-primary:hover {
            background-color: var(--highlight);
            border-color: var(--highlight);
            transform: scale(1.05);
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 6px 12px rgba(0,0,0,0.15);
            transition: transform 0.3s, box-shadow 0.3s;
            background-color: white;
        }
        .card:hover {
            transform: translateY(-8px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
        }
        .card-img-top {
            height: 220px;
            object-fit: cover;
            border-radius: 15px 15px 0 0;
        }
        .section {
            padding: 80px 0;
        }
        .section-title {
            color: var(--highlight);
            margin-bottom: 40px;
            font-weight: 700;
            position: relative;
        }
        .section-title::after {
            content: '';
            width: 60px;
            height: 4px;
            background-color: var(--accent);
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
        }
        .feature-icon {
            font-size: 3em;
            color: var(--accent);
            margin-bottom: 20px;
            transition: color 0.3s;
        }
        .card:hover .feature-icon {
            color: var(--highlight);
        }
        footer {
            background-color: var(--dark);
            color: white;
            padding: 30px 0;
            border-top: 4px solid var(--accent);
        }
        .btn-success {
            background-color: var(--success);
            border-color: var(--success);
        }
        .btn-success:hover {
            background-color: var(--highlight);
            border-color: var(--highlight);
        }
    </style>
</head>
<body>
    <?php include('navbar.php'); ?>

    <!-- Hero Section -->
    <div class="hero-section">
        <h1 class="display-4 fw-bold">Welcome to FitnessHub</h1>
        <p class="lead mb-4">Transform your fitness journey with our state-of-the-art FitnessHub system</p>
        <?php if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true): ?>
            <a href="login.php" class="btn btn-primary"><i class="fas fa-sign-in-alt me-2"></i>Join Now</a>
        <?php else: ?>
            <a href="dashboard.php" class="btn btn-primary"><i class="fas fa-tachometer-alt me-2"></i>Go to Dashboard</a>
        <?php endif; ?>
    </div>

    <!-- About Section -->
    <div class="section bg-light">
        <div class="container">
            <h2 class="text-center section-title">About FitnessHub</h2>
            <div class="row align-items-center">
                <div class="col-md-6">
                    <p class="lead">FitnessHub is your ultimate FitnessHub solution, designed to streamline your fitness journey. From personalized workout plans to expert trainers and seamless appointment scheduling, we empower you to achieve your fitness goals.</p>
                    <p>Our platform connects members with top-tier trainers, tracks progress, and offers tailored diet and workout plans to ensure success. Join us today to experience fitness like never before!</p>
                </div>
                <div class="col-md-6">
                    <img src="https://images.pexels.com/photos/1229356/pexels-photo-1229356.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=2" alt="About FitnessHub" class="img-fluid rounded" style="box-shadow: 0 6px 12px rgba(0,0,0,0.15);">
                </div>
            </div>
        </div>
    </div>

    <!-- Features Section -->
    <div class="section">
        <div class="container">
            <h2 class="text-center section-title">Why Choose FitnessHub?</h2>
            <div class="row text-center">
                <div class="col-md-4 mb-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <i class="fas fa-dumbbell feature-icon"></i>
                            <h5 class="card-title">Personalized Plans</h5>
                            <p class="card-text">Custom workout and diet plans tailored to your fitness goals.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <i class="fas fa-calendar-check feature-icon"></i>
                            <h5 class="card-title">Easy Scheduling</h5>
                            <p class="card-text">Book appointments with expert trainers at your convenience.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <i class="fas fa-chart-line feature-icon"></i>
                            <h5 class="card-title">Track Progress</h5>
                            <p class="card-text">Monitor your fitness journey with detailed progress tracking.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Membership Plans Section -->
    <div class="section bg-light">
        <div class="container">
            <h2 class="text-center section-title">Membership Plans</h2>
            <div class="row">
                <?php foreach ($plans as $plan): ?>
                    <div class="col-md-4 mb-4">
                        <div class="card h-100">
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title"><?php echo htmlspecialchars($plan['plan_name']); ?></h5>
                                <p class="card-text flex-grow-1"><?php echo htmlspecialchars($plan['description']); ?></p>
                                <p><strong>Price:</strong> $<?php echo number_format($plan['price'], 2); ?></p>
                                <p><strong>Duration:</strong> <?php echo intval($plan['duration_days']); ?> days</p>
                                <?php if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true): ?>
                                    <a href="login.php" class="btn btn-primary mt-auto">Join Now</a>
                                <?php else: ?>
                                    <a href="purchase_plan.php?plan_id=<?php echo intval($plan['plan_id']); ?>" class="btn btn-primary mt-auto">Select Plan</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Trainers Section -->
    <div class="section">
        <div class="container">
            <h2 class="text-center section-title">Meet Our Trainers</h2>
            <div class="row">
                <?php foreach ($trainers as $trainer): ?>
                    <div class="col-md-4 mb-4">
                        <div class="card h-100">
                            <img src="admin/uploads/<?php echo htmlspecialchars($trainer['profile_picture'] ?: './images/default-trainer.jpg'); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($trainer['trainer_name']); ?>">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($trainer['trainer_name']); ?></h5>
                                <p class="card-text"><strong>Specialization:</strong> <?php echo htmlspecialchars($trainer['specialization'] ?: 'General Fitness'); ?></p>
                                <?php if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true): ?>
                                    <a href="login.php" class="btn btn-primary">Book a Session</a>
                                <?php else: ?>
                                    <a href="make_appointment.php" class="btn btn-primary">Book a Session</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Call to Action Section -->
    <div class="section bg-light">
        <div class="container text-center">
            <h2 class="section-title">Start Your Fitness Journey Today</h2>
            <p class="lead mb-4">Join FitnessHub and take the first step towards a healthier you!</p>
            <?php if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true): ?>
                <a href="login.php" class="btn btn-primary btn-lg"><i class="fas fa-user-plus me-2"></i>Sign Up Now</a>
            <?php else: ?>
                <a href="dashboard.php" class="btn btn-primary btn-lg"><i class="fas fa-tachometer-alt me-2"></i>Go to Dashboard</a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Footer -->
    <footer class="text-center">
        <div class="container">
            <p class="mb-0">© 2025 FitnessHub. All rights reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>