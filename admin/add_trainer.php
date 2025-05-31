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
    $specialization = $_POST["specialization"];
    $certification = $_POST["certification"];
    $experience_years = $_POST["experience_years"];
    $bio = $_POST["bio"];
    $hourly_rate = $_POST["hourly_rate"];
    $email = $_POST["email"];
    $password = password_hash($_POST["password"], PASSWORD_DEFAULT);

    $profile_picture = '';
    if (isset($_FILES["profile_picture"]) && $_FILES["profile_picture"]["error"] == 0) {
        $target_dir = "uploads/";
        $profile_picture = basename($_FILES["profile_picture"]["name"]);
        move_uploaded_file($_FILES["profile_picture"]["tmp_name"], $target_dir . $profile_picture);
    }

    $sql = "INSERT INTO trainers (first_name, last_name, specialization, certification, experience_years, bio, hourly_rate, profile_picture, email, password)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ssssisdsss", $first_name, $last_name, $specialization, $certification, $experience_years, $bio, $hourly_rate, $profile_picture, $email, $password);

    if (mysqli_stmt_execute($stmt)) {
        $message = "Trainer added successfully.";
    } else {
        $message = "Error: " . mysqli_error($conn);
    }
    mysqli_stmt_close($stmt);
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Trainer</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<?php include('admin_navbar.php'); ?>
<div class="container mt-5 mb-5">
    <div class="card mx-auto" style="max-width: 800px;">
        <div class="card-body">
            <h2 class="mb-4">Add New Trainer</h2>
            <?php if ($message): ?>
                <div class="alert alert-info"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>
            <form method="post" enctype="multipart/form-data">
                <div class="form-row">
                    <div class="form-group col-md-6"><label>First Name</label><input type="text" name="first_name" required class="form-control"></div>
                    <div class="form-group col-md-6"><label>Last Name</label><input type="text" name="last_name" required class="form-control"></div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-6"><label>Specialization</label><input type="text" name="specialization" class="form-control"></div>
                    <div class="form-group col-md-6"><label>Certification</label><input type="text" name="certification" class="form-control"></div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-4"><label>Experience (Years)</label><input type="number" name="experience_years" class="form-control"></div>
                    <div class="form-group col-md-4"><label>Hourly Rate</label><input type="number" step="0.01" name="hourly_rate" class="form-control"></div>
                    <div class="form-group col-md-4"><label>Profile Picture</label><input type="file" name="profile_picture" class="form-control-file"></div>
                </div>
                <div class="form-group"><label>Bio</label><textarea name="bio" class="form-control"></textarea></div>
                <hr>
                <h5>Login Credentials</h5>
                <div class="form-row">
                    <div class="form-group col-md-6"><label>Email</label><input type="email" name="email" required class="form-control"></div>
                    <div class="form-group col-md-6"><label>Password</label><input type="password" name="password" required class="form-control"></div>
                </div>
                <button type="submit" class="btn btn-primary">Add Trainer</button>
                <a href="view_trainers.php" class="btn btn-secondary ml-2">View Trainers</a>
            </form>
        </div>
    </div>
</div>
</body>
</html>
