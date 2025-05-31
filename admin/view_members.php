<?php
include('config.php');
session_start();

if (!isset($_SESSION["usertype"]) || $_SESSION["usertype"] !== "admin") {
    header("Location: admin_login.php");
    exit;
}

$message = "";

// Handle Delete
if (isset($_GET['delete'])) {
    $member_id = intval($_GET['delete']);
    $query = "DELETE FROM members WHERE member_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $member_id);
    if (mysqli_stmt_execute($stmt)) {
        $message = "Member deleted successfully.";
    } else {
        $message = "Error deleting member: " . mysqli_error($conn);
    }
    mysqli_stmt_close($stmt);
}

// Fetch members
$sql = "SELECT member_id, first_name, last_name, email, gender, emergency_contact_phone FROM members ORDER BY member_id DESC";
$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>View Members</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>

<?php include('admin_navbar.php'); ?>

<div class="container mt-5 mb-5">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>Members List</h2>
        <a href="add_member.php" class="btn btn-success">Add Member</a>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-info"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <div class="table-responsive p-3">
        <table class="table table-bordered table-striped table-hover">
            <thead class="thead-dark">
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Gender</th>
                    <th>Phone</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                    <tr>
                        <td><?= $row['member_id'] ?></td>
                        <td><?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?></td>
                        <td><?= htmlspecialchars($row['email']) ?></td>
                        <td><?= htmlspecialchars($row['gender']) ?></td>
                        <td><?= htmlspecialchars($row['emergency_contact_phone']) ?></td>
                        <td>
                            <a href="edit_member.php?id=<?= $row['member_id'] ?>" class="btn btn-primary btn-sm">Edit</a>
                            <a href="view_members.php?delete=<?= $row['member_id'] ?>" 
                               onclick="return confirm('Are you sure you want to delete this member?');"
                               class="btn btn-danger btn-sm">Delete</a>
                            <a href="view_member.php?id=<?= $row['member_id'] ?>" class="btn btn-info btn-sm">View Details</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
                <?php if (mysqli_num_rows($result) == 0): ?>
                    <tr>
                        <td colspan="6" class="text-center">No members found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>
