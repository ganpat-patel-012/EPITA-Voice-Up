<?php
session_start();
include '../config/db.php';

// Ensure user is logged in
if (!isset($_SESSION['u_id'])) {
    die("Please log in to report an issue.");
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = htmlspecialchars(trim($_POST['i_title']));
    $desc = htmlspecialchars(trim($_POST['i_desc']));
    $userId = $_SESSION['u_id'];
    $address = htmlspecialchars(trim($_POST['i_address']));
    $city = htmlspecialchars(trim($_POST['i_city']));
    $pincode = htmlspecialchars(trim($_POST['i_pincode']));
    $department = intval($_POST['i_department']);
    
    $lat = htmlspecialchars(trim($_POST['i_lat'])) ?? null;
    $long = htmlspecialchars(trim($_POST['i_long'])) ?? null;

    // Handle image upload
    $uploadDir = 'issuePhotos/'; // Relative path for storing in DB
    $targetDir = $_SERVER['DOCUMENT_ROOT'] . '/EPITA-Voice-Up/' . $uploadDir;

    // Ensure the directory exists
    if (!file_exists($targetDir)) {
        if (!mkdir($targetDir, 0777, true)) {
            die("Failed to create directory: " . $targetDir);
        }
    }

    // Validate image file
    if (!isset($_FILES['i_image']) || $_FILES['i_image']['error'] != UPLOAD_ERR_OK) {
        die("File upload error: " . $_FILES['i_image']['error']);
    }

    // Generate unique file name
    $imageFileType = strtolower(pathinfo($_FILES['i_image']['name'], PATHINFO_EXTENSION));
    $uniqueFileName = uniqid() . '.' . $imageFileType;
    $targetFile = $targetDir . $uniqueFileName; // Absolute path for moving the file
    $dbFilePath = $uploadDir . $uniqueFileName; // Relative path for database

    // Move uploaded file
    if (move_uploaded_file($_FILES['i_image']['tmp_name'], $targetFile)) {
        // Insert into the database (store only relative path)
        $query = "INSERT INTO issue (i_title, i_desc, i_u_id, i_address, i_city, i_pincode, i_lat, i_long, i_status, i_image, i_d_id) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Reported', ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssissddssi", $title, $desc, $userId, $address, $city, $pincode, $lat, $long, $dbFilePath, $department);

        if ($stmt->execute()) {
            //echo "<p>Issue reported successfully!</p>";
        } else {
            echo "<p>Error reporting issue: " . $stmt->error . "</p>";
        }
    } else {
        die("Error moving uploaded file to target directory.");
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
    <a href="feed.php">Feed</a>
        <a href="../index.php">Home</a>
        <a href="about.php">About Us</a>
        <a href="offer.php">What We Offer</a>
        <a href="contact.php">Contact Us</a>
        <a href="userprofile.php">Profile</a>
    </nav>
</header>

<main>
    <h1>Report an Issue</h1>
    <form class = 'form-submit'action="" method="POST" enctype="multipart/form-data">
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
            <input type="text" id="i_city" name="i_city" required readonly>
        </div>
        <div class="form-group">
            <label for="i_pincode">Pincode:</label>
            <input type="text" id="i_pincode" name="i_pincode" required readonly>
        </div>
        <div class="form-group">
            <label for="i_department">Select Department:</label>
            <select id="i_department" name="i_department" required>
                <option value="">Select Department</option>
            </select>
        </div>
       
        <input type="hidden" id="i_lat" name="i_lat">
        <input type="hidden" id="i_long" name="i_long">
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
    navigator.geolocation.getCurrentPosition(function(position) {
        const lat = position.coords.latitude;
        const long = position.coords.longitude;

        document.getElementById('i_lat').value = lat;
        document.getElementById('i_long').value = long;

        // Reverse Geocode to get City and Pincode
        fetch(`https://nominatim.openstreetmap.org/reverse?lat=${lat}&lon=${long}&format=json`)
            .then(response => response.json())
            .then(data => {
                if (data.address) {
                    let city = data.address.city || data.address.town || data.address.village || '';
                    let pincode = data.address.postcode || '';

                    document.getElementById('i_city').value = city;
                    document.getElementById('i_pincode').value = pincode;

                    // Fetch and Populate Departments Based on City
                    fetch(`fetch_departments.php`)
                        .then(response => response.json())
                        .then(departments => {
                            let deptDropdown = document.getElementById('i_department');
                            deptDropdown.innerHTML = '<option value="">Select Department</option>';
                            
                            departments.forEach(dept => {
                                let option = document.createElement('option');
                                option.value = dept.d_id;
                                option.textContent = dept.d_name;
                                deptDropdown.appendChild(option);
                            });
                        })
                        .catch(err => console.error("Error fetching departments: ", err));
                }
            })
            .catch(err => console.error("Error fetching location data: ", err));
    }, function() {
        alert("Unable to retrieve your location.");
    });
</script>

</body>
</html>
