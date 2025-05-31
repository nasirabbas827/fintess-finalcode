<?php
session_start();
include('../config.php');

if (!isset($_SESSION['member_id']) || $_SESSION['user_type'] != 'Member') {
    header("Location: ../login.php");
    exit;
}

$member_id = $_SESSION['member_id'];
$appointment_id = intval($_GET['appointment_id'] ?? 0);
$errors = [];
$success_msg = "";

// Validate appointment belongs to member and is completed
$appt_sql = "SELECT a.*, t.trainer_id, t.first_name, t.last_name
             FROM appointments a
             JOIN trainers t ON a.trainer_id = t.trainer_id
             WHERE a.appointment_id = ? AND a.member_id = ? AND a.status = 'completed'";
$stmt = mysqli_prepare($conn, $appt_sql);
mysqli_stmt_bind_param($stmt, "ii", $appointment_id, $member_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$appointment = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$appointment) {
    die("Invalid or inaccessible appointment.");
}

// Check if review already exists
$review_sql = "SELECT * FROM trainer_reviews WHERE appointment_id = ? AND member_id = ?";
$stmt = mysqli_prepare($conn, $review_sql);
mysqli_stmt_bind_param($stmt, "ii", $appointment_id, $member_id);
mysqli_stmt_execute($stmt);
$review_res = mysqli_stmt_get_result($stmt);
$existing_review = mysqli_fetch_assoc($review_res);
mysqli_stmt_close($stmt);

if ($existing_review) {
    die("You have already reviewed this session.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    $rating = intval($_POST['rating'] ?? 0);
    $review_text = trim($_POST['review_text'] ?? '');

    if ($rating < 1 || $rating > 5) {
        $errors[] = "Rating must be between 1 and 5.";
    }

    if (empty($errors)) {
        $insert_sql = "INSERT INTO trainer_reviews (member_id, trainer_id, appointment_id, rating, review_text, review_date)
                       VALUES (?, ?, ?, ?, ?, NOW())";
        $stmt = mysqli_prepare($conn, $insert_sql);
        mysqli_stmt_bind_param($stmt, "iiiis", $member_id, $appointment['trainer_id'], $appointment_id, $rating, $review_text);

        if (mysqli_stmt_execute($stmt)) {
            $success_msg = "Thank you for your review.";
        } else {
            $errors[] = "Failed to submit review. Please try again.";
        }
        mysqli_stmt_close($stmt);
    }
}

?>

<!DOCTYPE html>
<html>
<head>
    <title>Rate Session</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">

</head>
<body>
<?php include('navbar.php'); ?>

<div class="container mt-5">
        <div class="card mx-auto" style="max-width: 500px;">
        <div class="card-body">
    <h2>Rate Your Session with <?= htmlspecialchars($appointment['first_name'] . ' ' . $appointment['last_name']) ?></h2>

    <?php if ($success_msg): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success_msg) ?></div>
        <a href="make_appointment.php" class="btn btn-primary">Back to Appointments</a>
    <?php else: ?>

        <?php if ($errors): ?>
            <div class="alert alert-danger">
                <ul>
                    <?php foreach ($errors as $err): ?>
                        <li><?= htmlspecialchars($err) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="post">
            <div class="form-group">
                <label for="rating">Rating (1 to 5)</label>
                <select name="rating" id="rating" class="form-control" required>
                    <option value="">-- Select Rating --</option>
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <option value="<?= $i ?>"><?= $i ?></option>
                    <?php endfor; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="review_text">Review (optional)</label>
                <textarea name="review_text" id="review_text" rows="4" class="form-control"></textarea>
            </div>

            <button type="submit" name="submit_review" class="btn btn-success">Submit Review</button>
        </form>
    <?php endif; ?>
</div>
</div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
