<?php
session_start();
include '../config/db.php';

// Ensure user is logged in
if (!isset($_SESSION['u_id'])) {
    die("Please log in to access this page.");
}

// Get the user ID from the session
$userId = $_SESSION['u_id'];

// Fetch user details from the database
$query = "SELECT * FROM user WHERE u_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
} else {
    die("User not found.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile</title>
    <link rel="stylesheet" href="../CSS/headerFooter.css">
    <link rel="stylesheet" href="../CSS/profileUser.css"> <!-- Add a CSS file for profile styling -->
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
            <a href="userprofile.php">Profile</a> <!-- Link to user profile -->
        </nav>
    </header>

    <main>
        <h1>User Profile</h1>
        <div class="profile-info">
            <p><strong>First Name:</strong> <?php echo htmlspecialchars($user['u_firstname']); ?></p>
            <p><strong>Last Name:</strong> <?php echo htmlspecialchars($user['u_lastname']); ?></p>
            <p><strong>Email:</strong> <?php echo htmlspecialchars($user['u_email']); ?></p>
            <p><strong>Phone:</strong> <?php echo htmlspecialchars($user['u_phone']); ?></p>
            <p><strong>Street:</strong> <?php echo htmlspecialchars($user['u_street']); ?></p>
            <p><strong>City:</strong> <?php echo htmlspecialchars($user['u_city']); ?></p>
            <p><strong>Pincode:</strong> <?php echo htmlspecialchars($user['u_pincode']); ?></p>
            <p><strong>Account Created At:</strong> <?php echo htmlspecialchars($user['u_created_at']); ?></p>
        </div>
        <a href="editProfile.php" class="btn">Modify Profile</a> <!-- Button to modify profile -->
    </main>

    <footer>
        Copyright © 2025 Voice Up
    </footer>

</body>
</html>
