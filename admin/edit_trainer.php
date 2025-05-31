<?php
include('config.php');
session_start();

if (!isset($_SESSION["usertype"]) || $_SESSION["usertype"] !== "admin") {
    header("Location: admin_login.php");
    exit;
}

$id = intval($_GET['id']);
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
    $new_password = $_POST["password"]; // can be blank

    $profile_picture = $_POST["existing_picture"];
    if (isset($_FILES["profile_picture"]) && $_FILES["profile_picture"]["error"] == 0) {
        $target_dir = "uploads/";
        $profile_picture = basename($_FILES["profile_picture"]["name"]);
        move_uploaded_file($_FILES["profile_picture"]["tmp_name"], $target_dir . $profile_picture);
    }

    // With password change
    if (!empty($new_password)) {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $sql = "UPDATE trainers SET first_name=?, last_name=?, specialization=?, certification=?, experience_years=?, bio=?, hourly_rate=?, profile_picture=?, email=?, password=? WHERE trainer_id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssisdsssi", $first_name, $last_name, $specialization, $certification, $experience_years, $bio, $hourly_rate, $profile_picture, $email, $hashed_password, $id);
    } else {
        // Without password change
        $sql = "UPDATE trainers SET first_name=?, last_name=?, specialization=?, certification=?, experience_years=?, bio=?, hourly_rate=?, profile_picture=?, email=? WHERE trainer_id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssisdssi", $first_name, $last_name, $specialization, $certification, $experience_years, $bio, $hourly_rate, $profile_picture, $email, $id);
    }

    if ($stmt->execute()) {
        $message = "Trainer updated successfully.";
    } else {
        $message = "Update failed: " . $conn->error;
    }
    $stmt->close();
}

$result = $conn->query("SELECT * FROM trainers WHERE trainer_id = $id");
$trainer = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Trainer</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">

</head>
<body>
<?php include('admin_navbar.php'); ?>
<div class="container mt-5 mb-5">
    <div class="card mx-auto" style="max-width: 800px;">
        <div class="card-body">
            <h2 class="mb-4">Edit Trainer</h2>
            <?php if ($message): ?>
                <div class="alert alert-info"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>
            <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="existing_picture" value="<?= $trainer['profile_picture'] ?>">
                <div class="form-row">
                    <div class="form-group col-md-6"><label>First Name</label><input type="text" name="first_name" value="<?= $trainer['first_name'] ?>" required class="form-control"></div>
                    <div class="form-group col-md-6"><label>Last Name</label><input type="text" name="last_name" value="<?= $trainer['last_name'] ?>" required class="form-control"></div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-6"><label>Specialization</label><input type="text" name="specialization" value="<?= $trainer['specialization'] ?>" class="form-control"></div>
                    <div class="form-group col-md-6"><label>Certification</label><input type="text" name="certification" value="<?= $trainer['certification'] ?>" class="form-control"></div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-4"><label>Experience</label><input type="number" name="experience_years" value="<?= $trainer['experience_years'] ?>" class="form-control"></div>
                    <div class="form-group col-md-4"><label>Hourly Rate</label><input type="number" step="0.01" name="hourly_rate" value="<?= $trainer['hourly_rate'] ?>" class="form-control"></div>
                    <div class="form-group col-md-4"><label>Profile Picture</label><input type="file" name="profile_picture" class="form-control-file"></div>
                </div>
                <div class="form-group"><label>Bio</label><textarea name="bio" class="form-control"><?= $trainer['bio'] ?></textarea></div>
                  <div class="form-row">
                <div class="form-group col-md-6"><label>Email</label><input type="email" name="email" value="<?= $trainer['email'] ?>" required class="form-control"></div>
                <div class="form-group col-md-6"><label>Password <small class="text-muted">(Leave blank to keep current)</small></label><input type="password" name="password" class="form-control"></div>
        </div>

                <button type="submit" class="btn btn-primary">Update</button>
                <a href="view_trainers.php" class="btn btn-secondary ml-2">Back</a>
            </form>
        </div>
    </div>
</div>
</body>
</html>
