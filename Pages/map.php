<?php
session_start();
include '../config/db.php';

// Ensure user is logged in
if (!isset($_SESSION['u_id'])) {
    die("Please log in to access this page.");
}

$user_id = $_SESSION['u_id'];

// Get logged-in user's city
$user_query = mysqli_query($conn, "SELECT u_city FROM user WHERE u_id = '$user_id'");
$user = mysqli_fetch_assoc($user_query);
$user_city = $user['u_city'];

// Handle sorting
$sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'latest';
$valid_sorts = [
    'latest' => 'i_created_at DESC',
    'oldest' => 'i_created_at ASC',
    'upvotes_asc' => 'upvotes ASC',
    'upvotes_desc' => 'upvotes DESC',
    'downvotes_asc' => 'downvotes ASC',
    'downvotes_desc' => 'downvotes DESC'
];

$sort_order = $valid_sorts[$sort_by] ?? 'i_created_at DESC';

// Handle status filtering
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'Reported';
$valid_statuses = ['All', 'Reported', 'Acknowledged', 'Work in progress', 'Solved', 'Closed'];

// Ensure the status filter is a valid status
if (!in_array($status_filter, $valid_statuses)) {
    $status_filter = 'All'; // Default to 'All' if the value is invalid
}

$status_condition = $status_filter === 'All' ? '1' : "i.i_status = '$status_filter'"; // Show all issues if 'All' is selected

// Fetch issues with vote counts
$issue_query = mysqli_query($conn, "
    SELECT i.*, 
           COALESCE(SUM(CASE WHEN v.v_type = 'up' THEN 1 ELSE 0 END), 0) AS upvotes,
           COALESCE(SUM(CASE WHEN v.v_type = 'down' THEN 1 ELSE 0 END), 0) AS downvotes
    FROM issue i
    LEFT JOIN votes v ON i.i_id = v.v_i_id
    WHERE i.i_city = '$user_city' AND $status_condition
    GROUP BY i.i_id
    ORDER BY $sort_order
");

// Fetch user's existing votes
$user_votes_query = mysqli_query($conn, "SELECT v_i_id, v_type FROM votes WHERE v_u_id = '$user_id'");
$user_votes = [];
while ($vote = mysqli_fetch_assoc($user_votes_query)) {
    $user_votes[$vote['v_i_id']] = $vote['v_type'];
}

// Handle vote submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['issue_id'], $_POST['type'])) {
    $issue_id = $_POST['issue_id'];
    $type = $_POST['type']; // 'up' or 'down'

    // Check if the user already voted
    if (isset($user_votes[$issue_id])) {
        if ($user_votes[$issue_id] === $type) {
            echo json_encode(["status" => "error", "message" => "You already voted this way!"]);
            exit;
        } else {
            // Update vote type if switching
            mysqli_query($conn, "UPDATE votes SET v_type = '$type' WHERE v_u_id = '$user_id' AND v_i_id = '$issue_id'");
            echo json_encode(["status" => "success", "message" => "Vote updated!"]);
            exit;
        }
    } else {
        // Insert new vote
        mysqli_query($conn, "INSERT INTO votes (v_u_id, v_i_id, v_type) VALUES ('$user_id', '$issue_id', '$type')");
        echo json_encode(["status" => "success", "message" => "Vote added!"]);
        exit;
    }
}

// Fetch department issue counts
$department_query = mysqli_query($conn, "
    SELECT d.d_name,
           COUNT(i.i_id) AS total_issues,
           SUM(i.i_status = 'Reported') AS reported,
           SUM(i.i_status = 'Acknowledged') AS acknowledged,
           SUM(i.i_status = 'Work in progress') AS work_in_progress,
           SUM(i.i_status = 'Solved') AS solved,
           SUM(i.i_status = 'Closed') AS closed
    FROM department d
    LEFT JOIN issue i ON d.d_id = i.i_d_id
    WHERE i.i_city = '$user_city'
    GROUP BY d.d_id
");

$departments = [];
while ($department = mysqli_fetch_assoc($department_query)) {
    $departments[] = $department;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voice Up</title>
    <link rel="stylesheet" href="../CSS/headerFooter.css">
    <link rel="stylesheet" href="../CSS/homePage.css">
    <link rel="stylesheet" href="../CSS/feed.css">
    <style>
        .filter-container {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        .filter-container select {
            padding: 8px;
            border-radius: 5px;
            margin-right: 10px;
        }
        .disabled { opacity: 0.5; cursor: not-allowed; }
        .sidebar ul {
            list-style-type: none;
            padding: 0;
        }
        .sidebar ul li {
            margin: 10px 0;
        }
    </style>
</head>
<body>
<header>
    <div class="logo">
        <img src="../images/logo-vu.png" alt="Voice Up Logo" height="50">
    </div>
    <nav>
    <a href="feed.php">Feed</a>
        <a href="../index.php">Home</a>
        <a href="about.php">About Us</a>
        <a href="offer.php">What We Offer</a>
        <a href="contact.php">Contact Us</a>
    </nav>
</header>



<div class="container">
    <div class="sidebar">

        <!-- Add buttons for reporting an issue and accessing the profile -->
        <div class="sidebar-buttons">
            
            <a href="reportIssue.php" class="btn">Report an Issue</a> <!-- Button to report an issue -->
            <a href="profileUser.php" class="btn">My Profile</a> <!-- Button to view user profile -->
            <a href="userlead.php" class="btn">Leaderboard</a> <!-- Button to view user profile -->
            <a href="feed.php" class="btn">Feed View</a> <!-- Button to report an issue -->
            <a href="logout.php" class="btn-log">Logout</a> <!-- Button to view user profile -->
            <hr>
        </div>

        <h3>Dashboard</h3>
        <p>Quick stats, user info, and useful links.</p>
        <ul class="department-list">
            <?php foreach ($departments as $department) : ?>
                <li class="department-item">
                    <strong><?php echo htmlspecialchars($department['d_name']); ?></strong>: 
                    <span class="issue-count"><?php echo $department['total_issues']; ?> issues</span>
                    <ul class="status-list">
                        <li class="status-item">Reported: <span class="status-count"><?php echo $department['reported']; ?></span></li>
                        <li class="status-item">Acknowledged: <span class="status-count"><?php echo $department['acknowledged']; ?></span></li>
                        <li class="status-item">Work in Progress: <span class="status-count"><?php echo $department['work_in_progress']; ?></span></li>
                        <li class="status-item">Solved: <span class="status-count"><?php echo $department['solved']; ?></span></li>
                        <li class="status-item">Closed: <span class="status-count"><?php echo $department['closed']; ?></span></li>
                    </ul>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
    
    <div class="feed">
    <?php include 'mapview.php'; ?>
    </div>
    </div>
</div>

<footer>
    <p>Copyright © 2025 Voice Up</p>
</footer>

<script>
function filterIssues() {
    var sortValue = document.getElementById('sort').value;
    var statusValue = document.getElementById('status').value;
    window.location.href = "?sort=" + sortValue + "&status=" + statusValue;
}

function vote(issueId, type) {
    fetch('', { // Same file
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `issue_id=${issueId}&type=${type}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === "success") {
            location.reload();
        } else {
            alert(data.message);
        }
    });
}
</script>

</body>
</html>
