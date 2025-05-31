<?php
session_start();
include('config.php');

if (!isset($_SESSION["usertype"]) || $_SESSION["usertype"] !== "admin") {
    header("Location: admin_login.php");
    exit;
}

// Initialize filter variables
$filter_member = isset($_GET['member']) ? $_GET['member'] : '';
$filter_status = isset($_GET['status']) ? $_GET['status'] : '';
$filter_date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$filter_date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';

// Build the base query
$query = "SELECT p.*, m.first_name, m.last_name, mp.plan_name 
          FROM payments p
          JOIN member_memberships mm ON p.membership_id = mm.membership_id
          JOIN members m ON mm.member_id = m.member_id
          JOIN membership_plans mp ON mm.plan_id = mp.plan_id
          WHERE 1=1";

// Add filters to query
if (!empty($filter_member)) {
    $query .= " AND (m.first_name LIKE '%$filter_member%' OR m.last_name LIKE '%$filter_member%')";
}
if (!empty($filter_status)) {
    $query .= " AND p.status = '$filter_status'";
}
if (!empty($filter_date_from)) {
    $query .= " AND DATE(p.payment_date) >= '$filter_date_from'";
}
if (!empty($filter_date_to)) {
    $query .= " AND DATE(p.payment_date) <= '$filter_date_to'";
}

$query .= " ORDER BY p.payment_date DESC";

// Execute query
$result = mysqli_query($conn, $query);
$payments = [];
while ($row = mysqli_fetch_assoc($result)) {
    $payments[] = $row;
}

// Get total amounts for summary
$total_query = "SELECT 
                SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END) as total_completed,
                SUM(CASE WHEN status = 'pending' THEN amount ELSE 0 END) as total_pending,
                SUM(CASE WHEN status = 'failed' THEN amount ELSE 0 END) as total_failed
                FROM payments";
$total_result = mysqli_query($conn, $total_query);
$totals = mysqli_fetch_assoc($total_result);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payments Management</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .summary-card {
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 20px;
            color: white;
        }
        .summary-card.completed {
            background-color: #28a745;
        }
        .summary-card.pending {
            background-color: #ffc107;
            color: #212529;
        }
        .summary-card.failed {
            background-color: #dc3545;
        }
        .payment-status {
            padding: 5px 10px;
            border-radius: 3px;
            font-weight: bold;
            font-size: 0.8rem;
        }
        .status-completed {
            background-color: #d4edda;
            color: #155724;
        }
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        .status-failed {
            background-color: #f8d7da;
            color: #721c24;
        }
        .status-refunded {
            background-color: #e2e3e5;
            color: #383d41;
        }
        .filter-section {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>

<?php include('admin_navbar.php'); ?>

<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Payments Management</h2>
        
    </div>

    <!-- Summary Cards -->
    <div class="row">
        <div class="col-md-4">
            <div class="summary-card completed">
                <h5>Completed Payments</h5>
                <h3>$<?php echo number_format($totals['total_completed'], 2); ?></h3>
            </div>
        </div>
        <div class="col-md-4">
            <div class="summary-card pending">
                <h5>Pending Payments</h5>
                <h3>$<?php echo number_format($totals['total_pending'], 2); ?></h3>
            </div>
        </div>
        <div class="col-md-4">
            <div class="summary-card failed">
                <h5>Failed Payments</h5>
                <h3>$<?php echo number_format($totals['total_failed'], 2); ?></h3>
            </div>
        </div>
    </div>

    <!-- Filter Section -->
    <div class="filter-section">
        <form method="GET" action="payments.php">
            <div class="form-row">
                <div class="form-group col-md-3">
                    <label for="member">Member Name</label>
                    <input type="text" class="form-control" id="member" name="member" 
                           value="<?php echo htmlspecialchars($filter_member); ?>" placeholder="Search by name">
                </div>
                <div class="form-group col-md-2">
                    <label for="status">Payment Status</label>
                    <select class="form-control" id="status" name="status">
                        <option value="">All Statuses</option>
                        <option value="completed" <?php echo $filter_status == 'completed' ? 'selected' : ''; ?>>Completed</option>
                        <option value="pending" <?php echo $filter_status == 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="failed" <?php echo $filter_status == 'failed' ? 'selected' : ''; ?>>Failed</option>
                        <option value="refunded" <?php echo $filter_status == 'refunded' ? 'selected' : ''; ?>>Refunded</option>
                    </select>
                </div>
                <div class="form-group col-md-2">
                    <label for="date_from">From Date</label>
                    <input type="date" class="form-control" id="date_from" name="date_from" 
                           value="<?php echo htmlspecialchars($filter_date_from); ?>">
                </div>
                <div class="form-group col-md-2">
                    <label for="date_to">To Date</label>
                    <input type="date" class="form-control" id="date_to" name="date_to" 
                           value="<?php echo htmlspecialchars($filter_date_to); ?>">
                </div>
                <div class="form-group col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary mr-2">
                        <i class="fas fa-filter"></i> Filter
                    </button>
                    <a href="payments.php" class="btn btn-secondary">
                        <i class="fas fa-sync-alt"></i> Reset
                    </a>
                </div>
            </div>
        </form>
    </div>

    <!-- Payments Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="thead-dark">
                        <tr>
                            <th>Payment ID</th>
                            <th>Member</th>
                            <th>Membership Plan</th>
                            <th>Amount</th>
                            <th>Date</th>
                            <th>Method</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($payments)): ?>
                            <tr>
                                <td colspan="8" class="text-center">No payments found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($payments as $payment): ?>
                                <tr>
                                    <td><?php echo $payment['payment_id']; ?></td>
                                    <td><?php echo htmlspecialchars($payment['first_name'] . ' ' . $payment['last_name']); ?></td>
                                    <td><?php echo htmlspecialchars($payment['plan_name']); ?></td>
                                    <td>$<?php echo number_format($payment['amount'], 2); ?></td>
                                    <td><?php echo date('M j, Y h:i A', strtotime($payment['payment_date'])); ?></td>
                                    <td><?php echo ucfirst(str_replace('_', ' ', $payment['payment_method'])); ?></td>
                                    <td>
                                        <span class="payment-status status-<?php echo $payment['status']; ?>">
                                            <?php echo ucfirst($payment['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="payment_details.php?id=<?php echo $payment['payment_id']; ?>" 
                                           class="btn btn-sm btn-info" title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </a>

                                       
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>


        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>