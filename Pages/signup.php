<?php
require_once '../config/db.php';  // Adjust the path based on your folder structure
$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve and sanitize form data
    $u_firstname = htmlspecialchars(trim($_POST['u_firstname']));
    $u_lastname = htmlspecialchars(trim($_POST['u_lastname']));
    $u_email = htmlspecialchars(trim($_POST['u_email']));
    $u_phone = !empty($_POST['u_phone']) ? htmlspecialchars(trim($_POST['u_phone'])) : null;
    $u_pass = htmlspecialchars(trim($_POST['u_pass']));
    $u_street = htmlspecialchars(trim($_POST['u_street']));
    $u_city = htmlspecialchars(trim($_POST['u_city']));
    $u_pincode = htmlspecialchars(trim($_POST['u_pincode']));

    // Check password length
    if (strlen($u_pass) < 8) {
        $message = "Password must be at least 8 characters long.";
    } else {
        // Check if the email already exists
        $checkEmailSql = "SELECT * FROM User WHERE u_email = ?";
        $checkStmt = mysqli_prepare($conn, $checkEmailSql);
        mysqli_stmt_bind_param($checkStmt, "s", $u_email);
        mysqli_stmt_execute($checkStmt);
        $result = mysqli_stmt_get_result($checkStmt);

        if (mysqli_num_rows($result) > 0) {
            $message = "User already exists with this email address.";
        } else {
            // Prepare SQL query for insertion
            $sql = "INSERT INTO User (u_firstname, u_lastname, u_email, u_pass, u_phone, u_created_at, u_street, u_city, u_pincode) 
                    VALUES (?, ?, ?, ?, ?, NOW(), ?, ?, ?)";

            $stmt = mysqli_prepare($conn, $sql);
            if (!$stmt) {
                $message = "Statement preparation failed: " . mysqli_error($conn);
            } else {
                // Hash the password before storing
                $hashed_pass = password_hash($u_pass, PASSWORD_DEFAULT);
                mysqli_stmt_bind_param($stmt, "sssssisi", $u_firstname, $u_lastname, $u_email, $hashed_pass, $u_phone, $u_street, $u_city, $u_pincode);

                if (mysqli_stmt_execute($stmt)) {
                    $message = "User successfully registered!";
                } else {
                    $message = "Error: " . mysqli_error($conn);
                }

                mysqli_stmt_close($stmt);
            }
        }

        mysqli_stmt_close($checkStmt);
    }
}

// Close the database connection
mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
<link rel="icon" type="image/png" href="../images/logo-vu.png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voice Up</title>

    <link rel="stylesheet" href="../CSS/headerFooter.css">
    <link rel="stylesheet" href="../CSS/homePage.css">
    <link rel="stylesheet" href="../CSS/signup.css">
    
    
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

    <div class="form-container">
        <h2>Create an Account</h2>
        <form method="POST" action="signup.php">
            <div class="form-group">
                <input type="text" name="u_firstname" placeholder="First Name" required>
                <input type="text" name="u_lastname" placeholder="Last Name" required>
            </div>
            <div class="form-group">
                <input type="email" name="u_email" placeholder="Email Address" required>
                <input type="text" name="u_phone" placeholder="Phone Number">
            </div>
            <div class="form-group">
                <input type="password" name="u_pass" placeholder="Password (min. 8 characters)" required>
                <input type="text" name="u_pincode" placeholder="Pincode" required>
            </div>
            <div class="form-group">
                <input type="text" name="u_street" placeholder="Street Address" required>
                <input type="text" name="u_city" placeholder="City" required>
            </div>
            <input type="submit" value="Register">
        </form>
        <p>Already have an account? <a href="login.php">Login here</a></p>

        <!-- Display message -->
        <?php if (!empty($message)): ?>
            <p class="message"><?php echo $message; ?></p>
        <?php endif; ?>
    </div>
    </div>

    <footer>
        Copyright © 2025 Voice Up
    </footer>

</body>
</html>
