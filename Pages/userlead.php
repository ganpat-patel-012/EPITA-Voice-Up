<?php
session_start();
include '../config/db.php';

// Ensure user is logged in
if (!isset($_SESSION['u_id'])) {
    die("Please log in to access this page.");
}

$user_id = $_SESSION['u_id'];

// Define time filters
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$date_condition_issues = "";
$date_condition_votes = "";

switch ($filter) {
    case 'last_year':
        $date_condition_issues = "AND i.i_created_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR)";
        $date_condition_votes = "AND v.v_created_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR)";
        break;
    case 'last_6_months':
        $date_condition_issues = "AND i.i_created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)";
        $date_condition_votes = "AND v.v_created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)";
        break;
    case 'last_month':
        $date_condition_issues = "AND i.i_created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
        $date_condition_votes = "AND v.v_created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
        break;
    case 'last_week':
        $date_condition_issues = "AND i.i_created_at >= DATE_SUB(NOW(), INTERVAL 1 WEEK)";
        $date_condition_votes = "AND v.v_created_at >= DATE_SUB(NOW(), INTERVAL 1 WEEK)";
        break;
    case 'all':
    default:
        $date_condition_issues = "";
        $date_condition_votes = "";
        break;
}

$sql_leaderboard = "
    SELECT user.u_id, 
           CONCAT(user.u_firstname, ' ', user.u_lastname) AS u_name,
           COALESCE(v.vote_points, 0) AS total_vote_points, 
           COALESCE(i.issue_points, 0) AS total_issue_points,
           (COALESCE(v.vote_points, 0) + COALESCE(i.issue_points, 0)) AS total_points
    FROM (
        SELECT DISTINCT i.i_u_id AS u_id FROM issue i WHERE 1=1 $date_condition_issues
        UNION
        SELECT DISTINCT v.v_u_id AS u_id FROM votes v WHERE 1=1 $date_condition_votes
    ) unique_users
    LEFT JOIN (
        SELECT v.v_u_id, 
               SUM(2) + SUM(CASE WHEN i.i_status = 'closed' THEN 5 ELSE 0 END) AS vote_points
        FROM votes v
        JOIN issue i ON v.v_i_id = i.i_id  
        WHERE 1=1 $date_condition_votes
        GROUP BY v.v_u_id
    ) v ON unique_users.u_id = v.v_u_id
    LEFT JOIN (
        SELECT i.i_u_id, 
               SUM(10 + CASE WHEN i.i_status = 'closed' THEN 20 ELSE 0 END) AS issue_points
        FROM issue i
        WHERE 1=1 $date_condition_issues
        GROUP BY i.i_u_id
    ) i ON unique_users.u_id = i.i_u_id
    JOIN user ON user.u_id = unique_users.u_id 
    ORDER BY total_points DESC;
";

//print_r($sql_leaderboard);

$result = mysqli_query($conn, $sql_leaderboard);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
<link rel="icon" type="image/png" href="../images/logo-vu.png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leaderboard - Voice Up</title>
    <link rel="stylesheet" href="../CSS/headerFooter.css">
    <link rel="stylesheet" href="../CSS/homePage.css">
    <link rel="stylesheet" href="../CSS/userlead.css">
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

<div class="leaderboard-container">
    <h2>Leaderboard</h2>
    <form method="GET">
        <label for="filter">Filter by:</label>
        <select name="filter" id="filter" onchange="this.form.submit()">
            <option value="all" <?= $filter == 'all' ? 'selected' : '' ?>>All Time</option>
            <option value="last_year" <?= $filter == 'last_year' ? 'selected' : '' ?>>Last Year</option>
            <option value="last_6_months" <?= $filter == 'last_6_months' ? 'selected' : '' ?>>Last 6 Months</option>
            <option value="last_month" <?= $filter == 'last_month' ? 'selected' : '' ?>>Last Month</option>
            <option value="last_week" <?= $filter == 'last_week' ? 'selected' : '' ?>>Last Week</option>
        </select>
    </form>
    <table class="leaderboard-table">
        <tr>
            <th>Rank</th>
            <th>Name</th>
            <th>UserID</th>
            <th>Vote Points</th>
            <th>Issue Points</th>
            <th>Total Points</th>
        </tr>
        <?php 
        $rank = 1;
        while ($row = mysqli_fetch_assoc($result)) {
            echo "<tr>
                    <td>{$rank}</td>
                    <td>{$row['u_name']}</td>
                    <td>{$row['u_id']}</td>
                    <td>{$row['total_vote_points']}</td>
                    <td>{$row['total_issue_points']}</td>
                    <td>{$row['total_points']}</td>
                  </tr>";
            $rank++;
        }
        ?>
    </table>
</div>

<footer>
    <p>Copyright &copy; 2025 Voice Up</p>
</footer>

</body>
</html>

<?php mysqli_close($conn); ?>