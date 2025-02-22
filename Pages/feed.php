<?php
session_start();
include '../config/db.php';

// Get logged-in user's city
$user_id = $_SESSION['u_id'];
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

// Fetch issues with dynamic vote counts
$issue_query = mysqli_query($conn, "
    SELECT i.*, 
           COALESCE(SUM(CASE WHEN v.v_type = 'up' THEN 1 ELSE 0 END), 0) AS upvotes,
           COALESCE(SUM(CASE WHEN v.v_type = 'down' THEN 1 ELSE 0 END), 0) AS downvotes
    FROM issue i
    LEFT JOIN votes v ON i.i_id = v.v_i_id
    WHERE i.i_city = '$user_city'
    GROUP BY i.i_id
    ORDER BY $sort_order
");
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
        .sort-container {
            margin-bottom: 20px;
            text-align: right;
        }
        .sort-container select {
            padding: 8px;
            border-radius: 5px;
        }
    </style>
</head>
<body>
<header>
    <div class="logo">
        <img src="../images/logo-vu.png" alt="Voice Up Logo" height="50">
    </div>
    <nav>
        <a href="../index.php">Home</a>
        <a href="about.php">About Us</a>
        <a href="offer.php">What We Offer</a>
        <a href="contact.php">Contact Us</a>
    </nav>
</header>

<div class="container">
    <div class="sidebar">
        <h3>Dashboard</h3>
        <p>Quick stats, user info, and useful links.</p>
    </div>
    <div class="feed">
    <div class="sort-container">
        <label for="sort">Sort by:</label>
        <select id="sort" onchange="sortIssues()">
            <option value="latest" <?= $sort_by == 'latest' ? 'selected' : '' ?>>Latest</option>
            <option value="oldest" <?= $sort_by == 'oldest' ? 'selected' : '' ?>>Oldest</option>
            <option value="upvotes_desc" <?= $sort_by == 'upvotes_desc' ? 'selected' : '' ?>>Upvotes (High to Low)</option>
            <option value="upvotes_asc" <?= $sort_by == 'upvotes_asc' ? 'selected' : '' ?>>Upvotes (Low to High)</option>
            <option value="downvotes_desc" <?= $sort_by == 'downvotes_desc' ? 'selected' : '' ?>>Downvotes (High to Low)</option>
            <option value="downvotes_asc" <?= $sort_by == 'downvotes_asc' ? 'selected' : '' ?>>Downvotes (Low to High)</option>
        </select>
    </div>

    <?php while ($issue = mysqli_fetch_assoc($issue_query)) { ?>
        <div class="issue-card">
            <img class="issue-img" src="../<?php echo $issue['i_image']; ?>" alt="Issue Image">
            <div class="issue-content">
                <div class="issue-title"><?php echo htmlspecialchars($issue['i_title']); ?></div>
                <div class="issue-desc"><?php echo htmlspecialchars($issue['i_desc']); ?></div>
                <p class="issue-date"><strong>Created at:</strong> <?php echo date("F j, Y, g:i A", strtotime($issue['i_created_at'])); ?></p>
                <p class="issue-loc"><strong>Location:</strong> <?php echo htmlspecialchars($issue['i_address']); ?></p>
                <div class="vote-buttons">
                    <button class="upvote" onclick="vote(<?php echo $issue['i_id']; ?>, 'up')">▲ <?php echo $issue['upvotes']; ?></button>
                    <button class="downvote" onclick="vote(<?php echo $issue['i_id']; ?>, 'down')">▼ <?php echo $issue['downvotes']; ?></button>
                </div>
            </div>
        </div> <!-- Correct placement of issue-card closing div -->
    <?php } ?>
</div> <!-- Now correctly closing .feed after all issues are added -->

</div>

<footer>
    <p>Copyright © 2025 Voice Up</p>
</footer>

<script>
function sortIssues() {
    var sortValue = document.getElementById('sort').value;
    window.location.href = "?sort=" + sortValue;
}

function vote(issueId, type) {
    fetch('vote.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `issue_id=${issueId}&type=${type}`
    })
    .then(response => response.text())
    .then(data => location.reload());
}
</script>

</body>
</html>
