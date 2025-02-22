<?php
$host = "localhost";
$username = "root";
$password = "root";  // Default password for MAMP
$database = "voiceup";  // Your database name
$port = 8889;  // MAMP MySQL port

// Create connection
$conn = mysqli_connect($host, $username, $password, $database, $port);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Uncomment for debugging
//echo "Connected successfully!";
?>
