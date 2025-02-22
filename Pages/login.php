<?php
session_start();

// Redirect to dashboard if already logged in
if (isset($_SESSION['u_id'])) {
    header("Location: feed.php");
    exit();
}

// Include the database configuration
require_once '../config/db.php';  // Adjust the path based on your folder structure

$error_message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    // Prepared statement to prevent SQL injection
    $sql = "SELECT u_id, u_pass FROM User WHERE u_email = ?";
    $stmt = mysqli_prepare($conn, $sql);

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($row = mysqli_fetch_assoc($result)) {


            if ($password === $row['u_pass']) {
                $_SESSION['u_id'] = $row['u_id'];
                header("Location: feed.php");
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voice Up</title>

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
            <h2>Login to Your Account</h2>
            <?php if (!empty($error_message)) { echo "<p class='error-message'>$error_message</p>"; } ?>
            <form method="POST" action="login.php">
                <input type="email" name="email" placeholder="Email Address" required>
                <input type="password" name="password" placeholder="Password" required>
                <input type="submit" value="Login">
            </form>
            <p>Don't have an account? <a href="signup.php">Register here</a></p>
        </div>
    </div>

    <footer>
        Copyright © 2025 Voice Up
    </footer>

</body>
</html>

