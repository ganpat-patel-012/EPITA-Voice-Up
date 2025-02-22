<?php
require_once '../config/db.php';  // Adjust the path based on your folder structure
session_start();
$message = "";

// Get user ID from session
$user_id = $_SESSION['u_id'];

// Fetch existing user data
$sql = "SELECT u_firstname, u_lastname, u_email, u_pass, u_phone, u_street, u_city, u_pincode FROM User WHERE u_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($result && mysqli_num_rows($result) > 0) {
    $user = mysqli_fetch_assoc($result);
} else {
    $message = "User not found.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve and sanitize form data
    $u_firstname = htmlspecialchars(trim($_POST['u_firstname']));
    $u_lastname = htmlspecialchars(trim($_POST['u_lastname']));
    $u_email = htmlspecialchars(trim($_POST['u_email']));
    $u_pass = htmlspecialchars(trim($_POST['u_pass']));
    $u_phone = !empty($_POST['u_phone']) ? htmlspecialchars(trim($_POST['u_phone'])) : null;
    $u_street = htmlspecialchars(trim($_POST['u_street']));
    $u_city = htmlspecialchars(trim($_POST['u_city']));
    $u_pincode = htmlspecialchars(trim($_POST['u_pincode']));

    // Prepare SQL query for updating
    $sql = "UPDATE User SET u_firstname = ?, u_lastname = ?, u_email = ?, u_pass = ?, u_phone = ?, u_street = ?, u_city = ?, u_pincode = ? WHERE u_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        $message = "Statement preparation failed: " . mysqli_error($conn);
    } else {
        mysqli_stmt_bind_param($stmt, "ssssssssi", $u_firstname, $u_lastname, $u_email, $u_pass, $u_phone, $u_street, $u_city, $u_pincode, $user_id);
        
        if (mysqli_stmt_execute($stmt)) {
            $message = "User information updated successfully!";
        } else {
            $message = "Error: " . mysqli_error($conn);
        }

        mysqli_stmt_close($stmt); // Close the statement here
    }
}

mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User Information</title>
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
    <a href="feed.php">Feed</a>
        <a href="../index.php">Home</a>
        <a href="about.php">About Us</a>
        <a href="offer.php">What We Offer</a>
        <a href="contact.php">Contact Us</a>
    </nav>
</header>

<div class="main-container">
    <div class="form-container">
        <h2>Edit User Information</h2>
        <form method="POST" action="editProfile.php">
            <div class="form-group">
                <input type="text" name="u_firstname" placeholder="First Name" value="<?php echo htmlspecialchars($user['u_firstname']); ?>" required>
                <input type="text" name="u_lastname" placeholder="Last Name" value="<?php echo htmlspecialchars($user['u_lastname']); ?>" required>
            </div>
            <div class="form-group">
                <input type="email" name="u_email" placeholder="Email Address" value="<?php echo htmlspecialchars($user['u_email']); ?>" required>
                <input type="text" name="u_phone" placeholder="Phone Number" value="<?php echo htmlspecialchars($user['u_phone']); ?>">
            </div>
            <div class="form-group">
                <input type="text" name="u_pass" placeholder="Password (min. 8 characters)" value="<?php echo htmlspecialchars($user['u_pass']); ?>" required>
                <input type="text" name="u_pincode" placeholder="Pincode" value="<?php echo htmlspecialchars($user['u_pincode']); ?>" required>
            </div>
            <div class="form-group">
                <input type="text" name="u_street" placeholder="Street Address" value="<?php echo htmlspecialchars($user['u_street']); ?>" required>
                <input type="text" name="u_city" placeholder="City" value="<?php echo htmlspecialchars($user['u_city']); ?>" required>
            </div>
            <input type="submit" value="Update">
        </form>
        <p>Go back to user profile? <a href="profileUser.php">My Profile</a></p>

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
