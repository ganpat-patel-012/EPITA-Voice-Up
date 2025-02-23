<?php
include '../config/db.php';

// Ensure user is logged in
session_start();
if (!isset($_SESSION['u_id'])) {
    die("Error: User not logged in.");
}
$user_id = $_SESSION['u_id'];

// Get issue ID from URL parameter
$issue_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($issue_id === 0) {
    die("Error: Invalid issue ID.");
}

// Fetch issue details including image
$query = "SELECT i_title, i_desc, i_lat, i_long, i_created_at, i_status, i_image, 
                 (SELECT COUNT(*) FROM votes WHERE v_i_id = ? AND v_type = 'up') AS upvotes,
                 (SELECT COUNT(*) FROM votes WHERE v_i_id = ? AND v_type = 'down') AS downvotes
          FROM issue WHERE i_id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "iii", $issue_id, $issue_id, $issue_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$issue = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$issue) {
    die("Error: Issue not found.");
}

// Fetch user's existing vote
$user_votes_query = mysqli_prepare($conn, "SELECT v_type FROM votes WHERE v_u_id = ? AND v_i_id = ?");
mysqli_stmt_bind_param($user_votes_query, "ii", $user_id, $issue_id);
mysqli_stmt_execute($user_votes_query);
$user_votes_result = mysqli_stmt_get_result($user_votes_query);
$user_vote = mysqli_fetch_assoc($user_votes_result)['v_type'] ?? null;
mysqli_stmt_close($user_votes_query);

// Check if issue is closed or solved
$isClosedOrSolved = in_array($issue['i_status'], ['Closed', 'Solved']);

// Handle voting
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['issue_id'], $_POST['type'])) {
    $issue_id = intval($_POST['issue_id']);
    $type = $_POST['type'];

    if (!in_array($type, ['up', 'down'])) {
        echo json_encode(["success" => false, "message" => "Invalid vote type."]);
        exit;
    }

    // Check if user already voted
    $query = "SELECT v_type FROM votes WHERE v_u_id = ? AND v_i_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "ii", $user_id, $issue_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $existing_vote = mysqli_fetch_assoc($result)['v_type'] ?? null;
    mysqli_stmt_close($stmt);

    if ($existing_vote === $type) {
        // echo json_encode(["success" => false, "message" => "You already voted this way."]);
        exit;
    }

    // Remove previous vote if changing
    if ($existing_vote) {
        $delete_query = "DELETE FROM votes WHERE v_u_id = ? AND v_i_id = ?";
        $delete_stmt = mysqli_prepare($conn, $delete_query);
        mysqli_stmt_bind_param($delete_stmt, "ii", $user_id, $issue_id);
        mysqli_stmt_execute($delete_stmt);
        mysqli_stmt_close($delete_stmt);
    }

    // Insert new vote
    $insert_query = "INSERT INTO votes (v_u_id, v_i_id, v_type) VALUES (?, ?, ?)";
    $insert_stmt = mysqli_prepare($conn, $insert_query);
    mysqli_stmt_bind_param($insert_stmt, "iis", $user_id, $issue_id, $type);
    if (mysqli_stmt_execute($insert_stmt)) {
        echo json_encode(["success" => true, "message" => "Vote registered."]);
    } else {
        echo json_encode(["success" => false, "message" => "Database error: " . mysqli_error($conn)]);
    }
    mysqli_stmt_close($insert_stmt);
    mysqli_close($conn);
    exit;
}

mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
<link rel="icon" type="image/png" href="../images/logo-vu.png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Issue Details - Voice Up</title>
    <link rel="stylesheet" href="../CSS/headerFooter.css">
    <link rel="stylesheet" href="../CSS/homePage.css">
    <link rel="stylesheet" href="../CSS/issueDetail.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <style>
        #map { height: 400px; width: 100%; margin-top: 20px; }
        .disabled { opacity: 0.5; pointer-events: none; }
    </style>
    <script>
        function vote(issueId, type) {
            fetch("issueDetail.php?id=" + issueId, {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: `issue_id=${issueId}&type=${type}`
            })
            .then(response => response.json())
            .then(data => {
                console.log("Response from server:", data);
                alert(data.message);
                if (data.success) {
                    location.reload(); // Refresh page to update votes
                }
            })
            .catch(error => console.error("AJAX Error:", error));
        }
    </script>
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

<main>
    
    <h1>Issue Details</h1>

    <h2><?php echo htmlspecialchars($issue['i_title']); ?></h2>
    <p><strong>Description:</strong> <?php echo nl2br(htmlspecialchars($issue['i_desc'])); ?></p>
    <p><strong>Status:</strong> <?php echo ucfirst(htmlspecialchars($issue['i_status'])); ?></p>
    <p><strong>Created At:</strong> <?php echo date("F j, Y, g:i a", strtotime($issue['i_created_at'])); ?></p>

    <?php if (!empty($issue['i_image'])): ?>
        <h3>Issue Image</h3>
        <img src="../<?php echo htmlspecialchars($issue['i_image']); ?>" alt="Issue Image" class="issue-image" style="max-width: 100%; height: auto;">
    <?php endif; ?>

    <div class="vote-buttons">
        <button 
            class="upvote <?= ($user_vote === 'up' || $isClosedOrSolved) ? 'disabled' : '' ?>" 
            onclick="<?= !$isClosedOrSolved ? "vote($issue_id, 'up')" : '' ?>" 
            <?= ($user_vote === 'up' || $isClosedOrSolved) ? 'disabled' : '' ?>>
            ▲ <?php echo $issue['upvotes']; ?>
        </button>
        <button 
            class="downvote <?= ($user_vote === 'down' || $isClosedOrSolved) ? 'disabled' : '' ?>" 
            onclick="<?= !$isClosedOrSolved ? "vote($issue_id, 'down')" : '' ?>" 
            <?= ($user_vote === 'down' || $isClosedOrSolved) ? 'disabled' : '' ?>>
            ▼ <?php echo $issue['downvotes']; ?>
        </button>
    </div>

    <?php if (!empty($issue['i_lat']) && !empty($issue['i_long'])): ?>
        <h3>Issue Location</h3>
        <div id="map"></div>
        <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
        <script>
            var map = L.map('map').setView([<?php echo $issue['i_lat']; ?>, <?php echo $issue['i_long']; ?>], 12);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(map);
            L.marker([<?php echo $issue['i_lat']; ?>, <?php echo $issue['i_long']; ?>]).addTo(map)
                .bindPopup("<?php echo htmlspecialchars($issue['i_title']); ?>").openPopup();
        </script>
    <?php endif; ?>
</main>

<footer>
    <p>&copy; 2025 Voice Up</p>
</footer>

</body>
</html>
