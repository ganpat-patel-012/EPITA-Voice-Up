<?php
session_start();
include '../config/db.php';

// Ensure user is logged in
if (!isset($_SESSION['u_id'])) {
    echo json_encode([]);
    exit;
}

$userId = $_SESSION['u_id'];

// Query to fetch departments where d_city matches u_city
$query = "SELECT d.d_id, d.d_name FROM department d 
          JOIN user u ON d.d_city = u.u_city 
          WHERE u.u_id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

$departments = [];
while ($row = $result->fetch_assoc()) {
    $departments[] = $row;
}

// Return JSON response
echo json_encode($departments);
?>
