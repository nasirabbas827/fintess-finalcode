<?php
session_start();
include('config.php');

if (!isset($_SESSION["usertype"]) || $_SESSION["usertype"] !== "admin") {
    header("Location: admin_login.php");
    exit;
}

// Get trainer_id from URL
$trainer_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($trainer_id <= 0) {
    header("Location: admin_home.php");
    exit;
}

// Get trainer details
$trainer_query = "SELECT CONCAT(first_name, ' ', last_name) as trainer_name 
                 FROM trainers 
                 WHERE trainer_id = ?";
$stmt = mysqli_prepare($conn, $trainer_query);
mysqli_stmt_bind_param($stmt, "i", $trainer_id);
mysqli_stmt_execute($stmt);
$trainer_result = mysqli_stmt_get_result($stmt);
$trainer = mysqli_fetch_assoc($trainer_result);
if (!$trainer) {
    header("Location: admin_home.php");
    exit;
}

// Get reviews for the trainer
$reviews_query = "SELECT tr.review_id, tr.rating, tr.review_text, tr.review_date,
                        CONCAT(m.first_name, ' ', m.last_name) as member_name
                 FROM trainer_reviews tr
                 JOIN members m ON tr.member_id = m.member_id
                 WHERE tr.trainer_id = ?
                 ORDER BY tr.review_date DESC";
$stmt = mysqli_prepare($conn, $reviews_query);
mysqli_stmt_bind_param($stmt, "i", $trainer_id);
mysqli_stmt_execute($stmt);
$reviews_result = mysqli_stmt_get_result($stmt);
$reviews = [];
while ($row = mysqli_fetch_assoc($reviews_result)) {
    $reviews[] = $row;
}

// Get average rating
$avg_rating_query = "SELECT AVG(rating) as avg_rating, COUNT(review_id) as review_count
                    FROM trainer_reviews
                    WHERE trainer_id = ?";
$stmt = mysqli_prepare($conn, $avg_rating_query);
mysqli_stmt_bind_param($stmt, "i", $trainer_id);
mysqli_stmt_execute($stmt);
$avg_result = mysqli_stmt_get_result($stmt);
$avg_data = mysqli_fetch_assoc($avg_result);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Trainer Reviews - <?php echo htmlspecialchars($trainer['trainer_name']); ?></title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .card-counter {
            box-shadow: 2px 2px 10px #DADADA;
            margin: 5px;
            padding: 20px 10px;
            background-color: #fff;
            height: 100px;
            border-radius: 5px;
        }
        .card-counter.info {
            background-color: #26c6da;
            color: #FFF;
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
        .review-item {
            border-left: 3px solid #007bff;
            padding-left: 10px;
            margin-bottom: 10px;
        }
        .review-item:hover {
            background-color: #f8f9fa;
        }
        .star-rating {
            color: #ffc107;
        }
    </style>
</head>
<body>
<?php include('admin_navbar.php'); ?>

<div class="container-fluid mt-4">
    <h2 class="text-center text-primary mb-4">Reviews for <?php echo htmlspecialchars($trainer['trainer_name']); ?></h2>

    <!-- Summary Card -->
    <div class="row">
        <div class="col-md-4">
            <div class="card-counter info">
                <i class="fas fa-star"></i>
                <span class="count-numbers"><?php echo number_format($avg_data['avg_rating'], 1); ?> / 5</span>
                <span class="count-name"><?php echo $avg_data['review_count']; ?> Reviews</span>
            </div>
        </div>
    </div>

    <!-- Reviews List -->
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h5>Member Reviews</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($reviews)): ?>
                        <p class="text-muted">No reviews yet for this trainer.</p>
                    <?php else: ?>
                        <?php foreach ($reviews as $review): ?>
                            <div class="review-item">
                                <strong><?php echo htmlspecialchars($review['member_name']); ?></strong>
                                <div class="star-rating">
                                    <?php 
                                    for ($i = 1; $i <= 5; $i++) {
                                        echo $i <= $review['rating'] ? '★' : '☆';
                                    }
                                    ?>
                                    (<?php echo $review['rating']; ?>/5)
                                </div>
                                <p><?php echo htmlspecialchars($review['review_text'] ?? 'No comments provided'); ?></p>
                                <small class="text-muted"><?php echo date('M j, Y, g:i A', strtotime($review['review_date'])); ?></small>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    <a href="admin_home.php" class="btn btn-sm btn-outline-primary mt-2">Back to Dashboard</a>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>