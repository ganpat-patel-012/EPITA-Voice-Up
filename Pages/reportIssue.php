<?php
session_start();
include '../config/db.php';

// Ensure user is logged in
if (!isset($_SESSION['u_id'])) {
    die("Please log in to report an issue.");
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get the input values
    $title = htmlspecialchars(trim($_POST['i_title']));
    $desc = htmlspecialchars(trim($_POST['i_desc']));
    $userId = $_SESSION['u_id'];
    $address = htmlspecialchars(trim($_POST['i_address']));
    $city = htmlspecialchars(trim($_POST['i_city']));
    $pincode = htmlspecialchars(trim($_POST['i_pincode']));
    
    // Optional fields
    $lat = htmlspecialchars(trim($_POST['i_lat'])) ?? null;
    $long = htmlspecialchars(trim($_POST['i_long'])) ?? null;

    // Handle image upload
    $targetDir = "../issueImage/";
    $imageFileType = strtolower(pathinfo($_FILES['i_image']['name'], PATHINFO_EXTENSION));
    $targetFile = $targetDir . uniqid() . '.' . $imageFileType; // Generate a unique file name

    // Validate and move the uploaded file
    if (move_uploaded_file($_FILES['i_image']['tmp_name'], $targetFile)) {
        // Insert the issue into the database
        $query = "INSERT INTO issues (i_title, i_desc, i_u_id, i_address, i_city, i_pincode, i_lat, i_long, i_status, i_image) VALUES (?, ?, ?, ?, ?, ?, ?, 'Reported', ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssissdds", $title, $desc, $userId, $address, $city, $pincode, $lat, $long, $targetFile);
        
        if ($stmt->execute()) {
            echo "<p>Issue reported successfully!</p>";
        } else {
            echo "<p>Error reporting issue: " . $stmt->error . "</p>";
        }
    } else {
        echo "<p>Error uploading image.</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report an Issue</title>
    <link rel="stylesheet" href="../CSS/headerFooter.css">
    <link rel="stylesheet" href="../CSS/reportIssue.css">
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
        <a href="userprofile.php">Profile</a>
    </nav>
</header>

<main>
    <h1>Report an Issue</h1>
    <form action="" method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label for="i_title">Issue Title:</label>
            <input type="text" id="i_title" name="i_title" required>
        </div>
        <div class="form-group">
            <label for="i_desc">Description:</label>
            <textarea id="i_desc" name="i_desc" required></textarea>
        </div>
        <div class="form-group">
            <label for="i_address">Address:</label>
            <input type="text" id="i_address" name="i_address">
        </div>
        <div class="form-group">
            <label for="i_city">City:</label>
            <input type="text" id="i_city" name="i_city" required>
        </div>
        <div class="form-group">
            <label for="i_pincode">Pincode:</label>
            <input type="text" id="i_pincode" name="i_pincode" required>
        </div>
        <div class="form-group">
            <label for="i_lat">Latitude:</label>
            <input type="text" id="i_lat" name="i_lat" placeholder="Optional" readonly>
        </div>
        <div class="form-group">
            <label for="i_long">Longitude:</label>
            <input type="text" id="i_long" name="i_long" placeholder="Optional" readonly>
        </div>
        <div class="form-group">
            <label for="i_image">Upload Image:</label>
            <input type="file" id="i_image" name="i_image" accept="image/*" required>
        </div>
        <button type="submit" class="btn">Submit Issue</button>
    </form>
</main>

<footer>
    Copyright © 2025 Voice Up
</footer>

<script>
    // Get current position for latitude and longitude
    navigator.geolocation.getCurrentPosition(function(position) {
        const lat = position.coords.latitude;
        const long = position.coords.longitude;

        document.getElementById('i_lat').value = lat;
        document.getElementById('i_long').value = long;

        // Reverse geocoding to get city and pincode
        fetch(`https://nominatim.openstreetmap.org/reverse?lat=${lat}&lon=${long}&format=json`)
            .then(response => response.json())
            .then(data => {
                if (data.address) {
                    document.getElementById('i_city').value = data.address.city || data.address.town || data.address.village || '';
                    document.getElementById('i_pincode').value = data.address.postcode || '';
                }
            })
            .catch(err => {
                console.error("Error fetching location data: ", err);
            });
    }, function() {
        alert("Unable to retrieve your location.");
    });
</script>

</body>
</html>
