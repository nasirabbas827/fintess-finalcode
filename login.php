<?php
include('config.php');

// Define variables
$email = $password = $user_type = "";
$email_err = $password_err = $user_type_err = $login_msg = "";

// Process form
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Validate email
    if (empty(trim($_POST["email"]))) {
        $email_err = "Please enter an email.";
    } else {
        $email = trim($_POST["email"]);
    }

    // Validate password
    if (empty(trim($_POST["password"]))) {
        $password_err = "Please enter a password.";
    } else {
        $password = trim($_POST["password"]);
    }

    // Validate user type
    if (empty($_POST["user_type"])) {
        $user_type_err = "Please select user type.";
    } else {
        $user_type = $_POST["user_type"];
    }

    // If no errors
    if (empty($email_err) && empty($password_err) && empty($user_type_err)) {

        if ($user_type == "Trainer") {
            $sql = "SELECT trainer_id, email, password FROM trainers WHERE email = ?";
        } elseif ($user_type == "Member") {
            $sql = "SELECT member_id, email, password FROM members WHERE email = ?";
        }

        if (isset($sql)) {
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "s", $email);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            if ($row = mysqli_fetch_assoc($result)) {
                if (password_verify($password, $row['password'])) {
                    session_start();
                    $_SESSION["email"] = $row['email'];
                    $_SESSION["user_type"] = $user_type;

                    if ($user_type == "Trainer") {
                        $_SESSION["trainer_id"] = $row['trainer_id'];
                        header("Location: trainer/trainer_dashboard.php");
                    } elseif ($user_type == "Member") {
                        $_SESSION["member_id"] = $row['member_id'];
                        header("Location: member/member_dashboard.php");
                    }
                    exit;
                } else {
                    $password_err = "The password you entered is incorrect.";
                }
            } else {
                $email_err = "No account found with that email.";
            }

            mysqli_stmt_close($stmt);
        }
    }

    mysqli_close($conn);
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>User Login</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="./css/style.css">
</head>
<body>
<?php include('navbar.php'); ?>
<div class="container mt-5">
    <div class="card mx-auto" style="max-width: 500px;">
        <div class="card-body">
            <h2 class="text-center">User Login</h2>
            <p class="text-center">Please fill in your credentials to log in.</p>

            <?= $login_msg; ?>

            <form action="<?= htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <div class="form-group">
                    <label>Email</label>
                    <input type="text" name="email" class="form-control <?= (!empty($email_err)) ? 'is-invalid' : ''; ?>" value="<?= $email; ?>">
                    <span class="invalid-feedback"><?= $email_err; ?></span>
                </div>

                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" class="form-control <?= (!empty($password_err)) ? 'is-invalid' : ''; ?>">
                    <span class="invalid-feedback"><?= $password_err; ?></span>
                </div>

                <div class="form-group">
                    <label>User Type</label>
                    <select name="user_type" class="form-control <?= (!empty($user_type_err)) ? 'is-invalid' : ''; ?>">
                        <option value="">Select User Type</option>
                        <option value="Trainer" <?= $user_type == "Trainer" ? 'selected' : '' ?>>Trainer</option>
                        <option value="Member" <?= $user_type == "Member" ? 'selected' : '' ?>>Member</option>
                    </select>
                    <span class="invalid-feedback"><?= $user_type_err; ?></span>
                </div>

                <div class="form-group text-center">
                    <input type="submit" value="Log in" class="btn btn-primary">
                </div>
            </form>

        </div>
    </div>
</div>
</body>
</html>
