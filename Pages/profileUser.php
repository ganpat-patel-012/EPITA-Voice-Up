<?php
session_start();
include '../config/db.php';

// Ensure user is logged in
if (!isset($_SESSION['u_id'])) {
    die("Please log in to access this page.");
}

// Get the user ID from the session
$userId = $_SESSION['u_id'];

// Handle issue deletion
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_issue'])) {
    $issueId = $_POST['delete_issue'];

    // Delete issue from the database
    $deleteQuery = "DELETE FROM issue WHERE i_id = ? AND i_u_id = ? AND i_status = 'Reported'";
    $deleteStmt = $conn->prepare($deleteQuery);
    $deleteStmt->bind_param("ii", $issueId, $userId);
    
    if ($deleteStmt->execute()) {
        echo "<script>alert('Issue deleted successfully!'); window.location.href='profileUser.php';</script>";
    } else {
        echo "<script>alert('Error deleting issue.');</script>";
    }
}

// Fetch user details
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

// Fetch user-raised issues
$issueQuery = "SELECT i_id, i_title, i_desc, i_status, i_created_at FROM issue WHERE i_u_id = ?";
$issueStmt = $conn->prepare($issueQuery);
$issueStmt->bind_param("i", $userId);
$issueStmt->execute();
$issuesResult = $issueStmt->get_result();

$query = "SELECT 
            user.u_id, 
            CONCAT(user.u_firstname, ' ', user.u_lastname) AS u_name, 
            COALESCE(v.vote_points, 0) AS total_vote_points, 
            COALESCE(i.issue_points, 0) AS total_issue_points, 
            (COALESCE(v.vote_points, 0) + COALESCE(i.issue_points, 0)) AS total_points 
          FROM 
            (SELECT DISTINCT i.i_u_id AS u_id FROM issue i 
             UNION 
             SELECT DISTINCT v.v_u_id AS u_id FROM votes v) unique_users 
          LEFT JOIN 
            (SELECT v.v_u_id, 
                    SUM(2) + SUM(CASE WHEN i.i_status = 'closed' THEN 5 ELSE 0 END) AS vote_points 
             FROM votes v 
             JOIN issue i ON v.v_i_id = i.i_id 
             GROUP BY v.v_u_id) v 
          ON unique_users.u_id = v.v_u_id 
          LEFT JOIN 
            (SELECT i.i_u_id, 
                    SUM(10 + CASE WHEN i.i_status = 'closed' THEN 20 ELSE 0 END) AS issue_points 
             FROM issue i 
             GROUP BY i.i_u_id) i 
          ON unique_users.u_id = i.i_u_id 
          JOIN user 
          ON user.u_id = unique_users.u_id 
          WHERE user.u_id = ? 
          ORDER BY total_points DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {

} else {
    echo "No ranking data found.";
}

// Handle issue closing
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['close_issue'])) {
    $issueId = $_POST['close_issue'];
    $closeQuery = "UPDATE issue SET i_status = 'Closed' WHERE i_id = ? AND i_u_id = ? AND i_status != 'Closed'";
    $closeStmt = $conn->prepare($closeQuery);
    $closeStmt->bind_param("ii", $issueId, $userId);

    if ($closeStmt->execute()) {
        echo "<script>alert('Issue marked as closed!'); window.location.href='profileUser.php';</script>";
    } else {
        echo "<script>alert('Error closing issue: " . $closeStmt->error . "');</script>";
    }
}



?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/png" href="../images/logo-vu.png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile</title>
    <link rel="stylesheet" href="../CSS/headerFooter.css">
    <link rel="stylesheet" href="../CSS/profileUser.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
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
    <h1>User Profile</h1>
    <div class="profile-container">
        <div class="profile-card">
            <h2><i class="fas fa-user"></i> Personal Information</h2>
            <table>
                <tr><td><strong>User ID:</strong></td><td><?php echo htmlspecialchars($user['u_id']); ?></td></tr>
                <tr><td><strong>First Name:</strong></td><td><?php echo htmlspecialchars($user['u_firstname']); ?></td></tr>
                <tr><td><strong>Last Name:</strong></td><td><?php echo htmlspecialchars($user['u_lastname']); ?></td></tr>
                <tr><td><strong>Email:</strong></td><td><?php echo htmlspecialchars($user['u_email']); ?></td></tr>
                <tr><td><strong>Phone:</strong></td><td><?php echo htmlspecialchars($user['u_phone']); ?></td></tr>
                <tr><td><strong>Street:</strong></td><td><?php echo htmlspecialchars($user['u_street']); ?></td></tr>
                <tr><td><strong>City:</strong></td><td><?php echo htmlspecialchars($user['u_city']); ?></td></tr>
                <tr><td><strong>Pincode:</strong></td><td><?php echo htmlspecialchars($user['u_pincode']); ?></td></tr>
                <tr><td><strong>Account Created At:</strong></td><td><?php echo htmlspecialchars($user['u_created_at']); ?></td></tr>
            </table>
        </div>
        
        <div class="ranking-card">
            <h2><i class="fas fa-trophy"></i> Your Rewards</h2>
            <table>
                <tr>
                    <td><strong>Total Vote Points:</strong></td>
                    <td><?php echo htmlspecialchars($row['total_vote_points']); ?></td>
                </tr>
                <tr>
                    <td><strong>Total Issue Points:</strong></td>
                    <td><?php echo htmlspecialchars($row['total_issue_points']); ?></td>
                </tr>
                <tr>
                    <td><strong>Total Points:</strong></td>
                    <td><?php echo htmlspecialchars($row['total_points']); ?></td>
                </tr>
            </table>
        </div>
    </div>

    <a href="editProfile.php" class="btn">Modify Profile</a>

    <h2><i class="fas fa-exclamation-circle"></i> Issues Raised by You</h2>
    <table class="issue-table">
        <thead>
            <tr>
                <th>Issue ID</th>
                <th>Title</th>
                <th>Description</th>
                <th>Status</th>
                <th>Created At</th>
                <th>Delete It</th>
                <th>Close It</th>
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
                        <form method="POST" onsubmit="return confirm('Are you sure you want to delete this issue?');">
                            <input type="hidden" name="delete_issue" value="<?php echo $issue['i_id']; ?>">
                            <button type="submit" class="delete-btn" 
                                <?php echo ($issue['i_status'] !== 'Reported') ? 'disabled style="opacity: 0.5; cursor: not-allowed;"' : ''; ?>>
                                <i class="fas fa-trash-alt"></i> Delete
                            </button>
                        </form>
                    </td>
                    <td>
                        <form method="POST" onsubmit="return confirm('Are you sure you want to close this issue?');">
                            <input type="hidden" name="close_issue" value="<?php echo $issue['i_id']; ?>">
                            <button type="submit" class="delete-btn" 
                                <?php echo ($issue['i_status'] === 'Closed') ? 'disabled style="opacity: 0.5; cursor: not-allowed;"' : ''; ?>>
                                <i class="fas fa-check-circle"></i> Close
                            </button>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

</main>

<footer>
    Copyright © 2025 Voice Up
</footer>

</body>
</html>
