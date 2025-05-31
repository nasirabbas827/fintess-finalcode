<?php
session_start();
include('../config.php');

if (!isset($_SESSION['member_id']) || $_SESSION['user_type'] != 'Member') {
    header("Location: ../login.php");
    exit;
}

$member_id = $_SESSION['member_id'];
$membership_id = isset($_GET['membership_id']) ? intval($_GET['membership_id']) : 0;

if ($membership_id <= 0) {
    die("Invalid membership ID.");
}

// Verify membership belongs to the logged-in member
$sql = "SELECT mmp.*, mp.plan_name, mp.price FROM member_memberships mmp
        JOIN membership_plans mp ON mmp.plan_id = mp.plan_id
        WHERE mmp.membership_id = ? AND mmp.member_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ii", $membership_id, $member_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$membership = mysqli_fetch_assoc($result);

if (!$membership) {
    die("Membership not found or access denied.");
}

$errors = [];
$success_msg = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $payment_method = $_POST['payment_method'] ?? '';
    $transaction_id = trim($_POST['transaction_id'] ?? '');
    $notes = trim($_POST['notes'] ?? '');

    // Handle file upload for transaction image
    $transaction_image = null;
    if (!empty($_FILES['transaction_image']['name'])) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        if (in_array($_FILES['transaction_image']['type'], $allowed_types)) {
            $ext = pathinfo($_FILES['transaction_image']['name'], PATHINFO_EXTENSION);
            $new_name = uniqid('txn_') . '.' . $ext;
            $upload_dir = __DIR__ . '/../images/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
            $target_path = $upload_dir . $new_name;
            if (move_uploaded_file($_FILES['transaction_image']['tmp_name'], $target_path)) {
                $transaction_image = $new_name;
            } else {
                $errors[] = "Failed to upload transaction image.";
            }
        } else {
            $errors[] = "Invalid image type. Allowed: JPEG, PNG, GIF.";
        }
    }

    if (!$payment_method) {
        $errors[] = "Please select a payment method.";
    }

    if (empty($errors)) {
        $status = 'completed';  // Mark payment as completed immediately

        $insert_sql = "INSERT INTO payments (membership_id, amount, payment_method, transaction_id, transaction_image, status, notes) 
                       VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt2 = mysqli_prepare($conn, $insert_sql);
        mysqli_stmt_bind_param($stmt2, "idsssss",
            $membership_id,
            $membership['price'],
            $payment_method,
            $transaction_id,
            $transaction_image,
            $status,
            $notes
        );

        if (mysqli_stmt_execute($stmt2)) {
            // Update member_memberships payment_status to 'paid'
            $update_sql = "UPDATE member_memberships SET payment_status = ? WHERE membership_id = ?";
            $stmt3 = mysqli_prepare($conn, $update_sql);
            $paid_status = 'paid';
            mysqli_stmt_bind_param($stmt3, "si", $paid_status, $membership_id);
            mysqli_stmt_execute($stmt3);
            mysqli_stmt_close($stmt3);

            $success_msg = "Payment recorded successfully and marked as completed.";
        } else {
            $errors[] = "Failed to save payment. Please try again.";
        }
        mysqli_stmt_close($stmt2);
    }
    mysqli_stmt_close($stmt);
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Make Payment</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<?php include('navbar.php'); ?>

<div class="container mt-5">
    <div class="card mx-auto" style="max-width: 500px;">
        <div class="card-body">
            <h2>Make Payment for Plan: <?= htmlspecialchars($membership['plan_name']) ?></h2>
            <p><strong>Amount to Pay:</strong> $<?= number_format($membership['price'], 2) ?></p>

            <?php if ($success_msg): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success_msg) ?></div>
                <a href="member_dashboard.php" class="btn btn-primary">Back to Dashboard</a>
            <?php else: ?>

                <?php if ($errors): ?>
                    <div class="alert alert-danger">
                        <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="post" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="payment_method">Payment Method</label>
                        <select name="payment_method" id="payment_method" class="form-control" required>
                            <option value="">Select Method</option>
                            <option value="credit_card">Credit Card</option>
                            <option value="debit_card">Debit Card</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="cash">Cash</option>
                            <option value="other">Other</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="transaction_id">Transaction ID / Reference</label>
                        <input type="text" name="transaction_id" id="transaction_id" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label for="transaction_image">Upload Transaction Image (optional)</label>
                        <input type="file" name="transaction_image" id="transaction_image" class="form-control-file" accept="image/*">
                    </div>

                    <div class="form-group">
                        <label for="notes">Notes (optional)</label>
                        <textarea name="notes" id="notes" rows="3" class="form-control"></textarea>
                    </div>

                    <button type="submit" class="btn btn-success">Submit Payment</button>
                    <a href="member_dashboard.php" class="btn btn-secondary">Cancel</a>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

</body>
</html>
