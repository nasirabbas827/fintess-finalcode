<?php
include('config.php');
session_start();

if (!isset($_SESSION["usertype"]) || $_SESSION["usertype"] !== "admin") {
    header("Location: admin_login.php");
    exit;
}

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $first_name = $_POST["first_name"];
    $last_name = $_POST["last_name"];
    $dob = $_POST["dob"];
    $gender = $_POST["gender"];
    $address = $_POST["address"];
    $emergency_contact_name = $_POST["emergency_contact_name"];
    $emergency_contact_phone = $_POST["emergency_contact_phone"];
    $health_conditions = $_POST["health_conditions"];
    $fitness_goals = $_POST["fitness_goals"];
    $height = $_POST["height"];
    $weight = $_POST["weight"];
    $email = $_POST["email"];
    $password = password_hash($_POST["password"], PASSWORD_DEFAULT);

    // Handle profile picture upload
    $profile_picture = '';
    if (isset($_FILES["profile_picture"]) && $_FILES["profile_picture"]["error"] == 0) {
        $target_dir = "uploads/";
        $profile_picture = basename($_FILES["profile_picture"]["name"]);
        $target_file = $target_dir . $profile_picture;
        move_uploaded_file($_FILES["profile_picture"]["tmp_name"], $target_file);
    }

    $sql = "INSERT INTO members (first_name, last_name, date_of_birth, gender, address, emergency_contact_name, emergency_contact_phone, health_conditions, fitness_goals, height, weight, profile_picture, email, password)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ssssssssssssss", $first_name, $last_name, $dob, $gender, $address, $emergency_contact_name, $emergency_contact_phone, $health_conditions, $fitness_goals, $height, $weight, $profile_picture, $email, $password);

    if (mysqli_stmt_execute($stmt)) {
        $message = "Member added successfully.";
    } else {
        $message = "Failed to add member: " . mysqli_error($conn);
    }

    mysqli_stmt_close($stmt);
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Member</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>

<?php include('admin_navbar.php'); ?>

<div class="container mt-5 mb-5">
    <div class="card mx-auto" style="max-width: 800px;">
        <div class="card-body">
            <h2 class="mb-4">Add New Member</h2>
            <?php if ($message): ?>
                <div class="alert alert-info"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>
            <form method="post" enctype="multipart/form-data">
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>First Name</label>
                        <input type="text" name="first_name" required class="form-control">
                    </div>
                    <div class="form-group col-md-6">
                        <label>Last Name</label>
                        <input type="text" name="last_name" required class="form-control">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label>Date of Birth</label>
                        <input type="date" name="dob" class="form-control">
                    </div>
                    <div class="form-group col-md-4">
                        <label>Gender</label>
                        <select name="gender" class="form-control">
                            <option value="">Select</option>
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="form-group col-md-4">
                        <label>Profile Picture</label>
                        <input type="file" name="profile_picture" class="form-control-file">
                    </div>
                </div>

                <div class="form-group">
                    <label>Address</label>
                    <textarea name="address" class="form-control"></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>Emergency Contact Name</label>
                        <input type="text" name="emergency_contact_name" class="form-control">
                    </div>
                    <div class="form-group col-md-6">
                        <label>Emergency Contact Phone</label>
                        <input type="text" name="emergency_contact_phone" class="form-control">
                    </div>
                </div>

                <div class="form-group">
                    <label>Health Conditions</label>
                    <textarea name="health_conditions" class="form-control"></textarea>
                </div>

                <div class="form-group">
                    <label>Fitness Goals</label>
                    <textarea name="fitness_goals" class="form-control"></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label>Height (cm)</label>
                        <input type="number" step="0.01" name="height" class="form-control">
                    </div>
                    <div class="form-group col-md-4">
                        <label>Weight (kg)</label>
                        <input type="number" step="0.01" name="weight" class="form-control">
                    </div>
                </div>

                <hr>
                <h5>Login Credentials</h5>
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>Email</label>
                        <input type="email" name="email" required class="form-control">
                    </div>
                    <div class="form-group col-md-6">
                        <label>Password</label>
                        <input type="password" name="password" required class="form-control">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">Add Member</button>
                <a href="view_members.php" class="btn btn-secondary ml-2">View Members</a>
            </form>
        </div>
    </div>
</div>

</body>
</html>
