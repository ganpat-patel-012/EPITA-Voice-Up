<?php
session_start();
include '../config/db.php';

// Ensure user is logged in
if (!isset($_SESSION['d_id'])) {
    die("Please log in to access this page.");
}

$user_id = $_SESSION['d_id'];

// Get logged-in user's city
$user_query = mysqli_query($conn, "SELECT d_city FROM department WHERE d_id = '$user_id'");
$user = mysqli_fetch_assoc($user_query);
$user_city = $user['d_city'];

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

// Fetch user-raised issues
$issueQuery = "SELECT i_id, i_title, i_desc, i_status, i_created_at FROM issue WHERE i_d_id = ?";
$issueStmt = $conn->prepare($issueQuery);
$issueStmt->bind_param("i", $user_id);
$issueStmt->execute();
$issuesResult = $issueStmt->get_result();
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
    <link rel="stylesheet" href="../CSS/feed.css">
    <link rel="stylesheet" href="../CSS/profileUser.css">
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
        <a href="depDash.php">Dashboard</a>
        <a href="../index.php">Home</a>
        <a href="about.php">About Us</a>
        <a href="offer.php">What We Offer</a>
        <a href="contact.php">Contact Us</a>
    </nav>
</header>

<div class="container">
    <div class="sidebar">
        <div class="sidebar-buttons">
            <a href="userlead.php" class="btn">Leaderboard</a>
            <a href="map.php" class="btn">MapView</a>
            <a href="logout.php" class="btn-log">Logout</a>
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
    <h2><i class="fas fa-exclamation-circle"></i> Issues Raised by You</h2>
    <table class="issue-table">
        <thead>
            <tr>
                <th>Issue ID</th>
                <th>Title</th>
                <th>Description</th>
                <th>Status</th>
                <th>Created At</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($issue = $issuesResult->fetch_assoc()) : ?>
                <tr>
                    <td><?php echo htmlspecialchars($issue['i_id']); ?></td>
                    <td><?php echo htmlspecialchars($issue['i_title']); ?></td>
                    <td><?php echo htmlspecialchars($issue['i_desc']); ?></td>
                    <td><?php echo htmlspecialchars($issue['i_status']); ?></td>
                    <td><?php echo htmlspecialchars($issue['i_created_at']); ?></td>
                    <td>
                        <a href="issueDetailDep.php?id=<?php echo $issue['i_id']; ?>" class="btn">View Details</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
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
    fetch('', {
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