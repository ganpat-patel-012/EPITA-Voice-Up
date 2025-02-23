<?php
session_start();

// Redirect to dashboard if already logged in
if (isset($_SESSION['d_id'])) {
    header("Location: depDash.php");
    exit();
}

// Include the database configuration
require_once '../config/db.php';  // Adjust the path based on your folder structure

$error_message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    // Prepared statement to prevent SQL injection
    $sql = "SELECT d_id, d_pass FROM department WHERE d_email = ?";
    $stmt = mysqli_prepare($conn, $sql);

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($row = mysqli_fetch_assoc($result)) {
            if ($password === $row['d_pass']) { // Plain text comparison (not recommended)
                $_SESSION['d_id'] = $row['d_id'];
                header("Location: dashboard.php");
                exit();
            } else {
                $error_message = "Invalid password. Please try again.";
            }
        } else {
            $error_message = "No account found with that email. Please register.";
        }

        mysqli_stmt_close($stmt);
    } else {
        $error_message = "Error: " . mysqli_error($conn);
    }
}

mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
<link rel="icon" type="image/png" href="../images/logo-vu.png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Department Login</title>

    <link rel="stylesheet" href="../CSS/headerFooter.css">
    <link rel="stylesheet" href="../CSS/homePage.css">
    <link rel="stylesheet" href="../CSS/login.css">
    
</head>
<body>
    <header>
        <div class="logo">
            <img src="../images/logo-vu.png" alt="Voice Up Logo">
        </div>
        <nav>
            <a href="../index.php">Home</a>
            <a href="about.php">About Us</a>
            <a href="offer.php">What We Offer</a>
            <a href="contact.php">Contact Us</a>
        </nav>
    </header>

    <div class="main-container">
        <!-- Login Form -->
        <div class="form-container">
            <h2>Department Login</h2>
            <?php if (!empty($error_message)) { echo "<p class='error-message'>$error_message</p>"; } ?>
            <form method="POST" action="loginDep.php">
                <input type="email" name="email" placeholder="Email Address" required>
                <input type="password" name="password" placeholder="Password" required>
                <input type="submit" value="Login">
            </form>
        </div>
    </div>

    <footer>
        Copyright © 2025 Voice Up
    </footer>
</body>
</html>