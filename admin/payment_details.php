<?php
session_start();
include('config.php');

if (!isset($_SESSION["usertype"]) || $_SESSION["usertype"] !== "admin") {
    header("Location: admin_login.php");
    exit;
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: payments.php");
    exit;
}

$payment_id = intval($_GET['id']);

// Fetch payment and related data
$query = "SELECT p.*, 
                 m.first_name as member_first, m.last_name as member_last, m.email as member_email,
                 mp.plan_name, mp.duration_days, mp.price as plan_price,
                 mm.start_date, mm.end_date, mm.is_active as membership_active
          FROM payments p
          JOIN member_memberships mm ON p.membership_id = mm.membership_id
          JOIN members m ON mm.member_id = m.member_id
          JOIN membership_plans mp ON mm.plan_id = mp.plan_id
          WHERE p.payment_id = ?";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $payment_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) == 0) {
    header("Location: payments.php");
    exit;
}

$payment = mysqli_fetch_assoc($result);

// Format dates
$payment_date = date('F j, Y \a\t g:i A', strtotime($payment['payment_date']));
$membership_start = date('F j, Y', strtotime($payment['start_date']));
$membership_end = date('F j, Y', strtotime($payment['end_date']));

// Badge class for status
$status_class = match ($payment['status']) {
    'completed' => 'success',
    'pending' => 'warning',
    'failed' => 'danger',
    'refunded' => 'secondary',
    default => 'info',
};

// Check if receipt image exists
$receipt_path = '';
if (!empty($payment['transaction_image'])) {
    $potential_path = '../images/' . basename($payment['transaction_image']);
    if (file_exists($potential_path)) {
        $receipt_path = $potential_path;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payment Details - #<?php echo $payment_id; ?></title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .detail-card { border-radius: 10px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .detail-header { border-radius: 10px 10px 0 0 !important; }
        .detail-label { font-weight: 600; color: #6c757d; }
        .receipt-container { max-width: 100%; margin-top: 20px; border: 1px solid #dee2e6; border-radius: 5px; padding: 10px; }
        .receipt-image { max-width: 100%; height: auto; }
        .status-badge { font-size: 1rem; padding: 8px 15px; }
    </style>
</head>
<body>

<?php include('admin_navbar.php'); ?>

<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>
            <a href="payments.php" class="btn btn-outline-secondary mr-2"><i class="fas fa-arrow-left"></i></a>
            Payment Details - #<?php echo $payment_id; ?>
        </h2>
        <div class="btn-group">
            <button onclick="window.print()" class="btn btn-info"><i class="fas fa-print"></i> Print</button>
        </div>
    </div>

    <div class="row">
        <!-- Payment Info -->
        <div class="col-md-6">
            <div class="card detail-card">
                <div class="card-header bg-primary text-white detail-header"><h5 class="mb-0">Payment Information</h5></div>
                <div class="card-body">
                    <div class="row mb-3"><div class="col-md-4 detail-label">Payment Status:</div><div class="col-md-8"><span class="badge badge-<?php echo $status_class; ?> status-badge"><?php echo ucfirst($payment['status']); ?></span></div></div>
                    <div class="row mb-3"><div class="col-md-4 detail-label">Amount:</div><div class="col-md-8">$<?php echo number_format($payment['amount'], 2); ?></div></div>
                    <div class="row mb-3"><div class="col-md-4 detail-label">Payment Date:</div><div class="col-md-8"><?php echo $payment_date; ?></div></div>
                    <div class="row mb-3"><div class="col-md-4 detail-label">Payment Method:</div><div class="col-md-8"><?php echo $payment['payment_method'] ? ucwords(str_replace('_', ' ', $payment['payment_method'])) : 'Not specified'; ?></div></div>
                    <div class="row mb-3"><div class="col-md-4 detail-label">Transaction ID:</div><div class="col-md-8"><?php echo $payment['transaction_id'] ?: 'N/A'; ?></div></div>
                    <div class="row"><div class="col-md-4 detail-label">Notes:</div><div class="col-md-8"><?php echo $payment['notes'] ? nl2br(htmlspecialchars($payment['notes'])) : 'No notes available'; ?></div></div>
                </div>
            </div>

            <?php if ($receipt_path): ?>
            <div class="card detail-card">
                <div class="card-header bg-info text-white detail-header"><h5 class="mb-0">Payment Receipt</h5></div>
                <div class="card-body text-center">
                    <div class="receipt-container"><img src="<?php echo $receipt_path; ?>" alt="Payment Receipt" class="receipt-image"></div>
                    <a href="<?php echo $receipt_path; ?>" download class="btn btn-primary mt-3"><i class="fas fa-download"></i> Download Receipt</a>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Member & Membership Info -->
        <div class="col-md-6">
            <div class="card detail-card">
                <div class="card-header bg-success text-white detail-header"><h5 class="mb-0">Member Information</h5></div>
                <div class="card-body">
                    <div class="row mb-3"><div class="col-md-4 detail-label">Member Name:</div><div class="col-md-8"><?php echo htmlspecialchars($payment['member_first'] . ' ' . $payment['member_last']); ?></div></div>
                    <div class="row mb-3"><div class="col-md-4 detail-label">Email:</div><div class="col-md-8"><a href="mailto:<?php echo htmlspecialchars($payment['member_email']); ?>"><?php echo htmlspecialchars($payment['member_email']); ?></a></div></div>
                </div>
            </div>

            <div class="card detail-card">
                <div class="card-header bg-warning text-white detail-header"><h5 class="mb-0">Membership Information</h5></div>
                <div class="card-body">
                    <div class="row mb-3"><div class="col-md-4 detail-label">Plan Name:</div><div class="col-md-8"><?php echo htmlspecialchars($payment['plan_name']); ?></div></div>
                    <div class="row mb-3"><div class="col-md-4 detail-label">Plan Duration:</div><div class="col-md-8"><?php echo $payment['duration_days']; ?> days</div></div>
                    <div class="row mb-3"><div class="col-md-4 detail-label">Plan Price:</div><div class="col-md-8">$<?php echo number_format($payment['plan_price'], 2); ?></div></div>
                    <div class="row mb-3"><div class="col-md-4 detail-label">Membership Period:</div><div class="col-md-8"><?php echo $membership_start; ?> to <?php echo $membership_end; ?></div></div>
                    <div class="row"><div class="col-md-4 detail-label">Membership Status:</div><div class="col-md-8"><span class="badge <?php echo $payment['membership_active'] ? 'badge-success' : 'badge-secondary'; ?>"><?php echo $payment['membership_active'] ? 'Active' : 'Inactive'; ?></span></div></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
