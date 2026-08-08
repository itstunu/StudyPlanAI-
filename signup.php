<?php
session_start();

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $host = 'localhost';
    $dbname = 'student_assistant';
    $username = 'root';
    $password = '';

    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $pass = $_POST['password'];
        $confirm = $_POST['confirm_password'];

        if ($pass !== $confirm) {
            $error = "Passwords do not match!";
        } elseif (strlen($pass) < 6) {
            $error = "Password must be at least 6 characters!";
        } else {
            $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $check->execute([$email]);

            if ($check->rowCount() > 0) {
                $error = "Email already exists!";
            } else {
                $hashed = password_hash($pass, PASSWORD_DEFAULT);
                $insert = $pdo->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
                $insert->execute([$name, $email, $hashed]);

                $_SESSION['user_id'] = $pdo->lastInsertId();
                $_SESSION['user_name'] = $name;
                $_SESSION['user_email'] = $email;

                header("Location: dashboard.php");
                exit();
            }
        }
    } catch (PDOException $e) {
        $error = "Registration failed. Please try again.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Assistant | Sign Up</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
</head>
<body class="bg-primary d-flex align-center justify-center h-100">

<div class="container">
    <div class="card fade-in" style="max-width:450px;margin:auto;">
        <div class="text-center mb-3">
            <div style="font-size:48px;">🎓</div>
            <h1>Create Account</h1>
            <p>Join Student Assistant to organize your study routine</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <input type="text" name="name" class="form-control" placeholder="Full Name" required>
            </div>

            <div class="form-group">
                <input type="email" name="email" class="form-control" placeholder="Email Address" required>
            </div>

            <div class="form-group">
                <input type="password" name="password" class="form-control" placeholder="Password (min 6 characters)" required>
            </div>

            <div class="form-group">
                <input type="password" name="confirm_password" class="form-control" placeholder="Confirm Password" required>
            </div>

            <button type="submit" class="btn w-100">Create Account</button>
        </form>

        <p class="text-center mt-3">
            Already have an account?
            <a href="index.php">Login here</a>
        </p>
    </div>
</div>

</body>
</html>
