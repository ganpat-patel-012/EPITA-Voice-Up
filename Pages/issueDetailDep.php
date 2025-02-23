<?php
include '../config/db.php';
session_start();

if (!isset($_SESSION['d_id'])) {
    die("Error: User not logged in.");
}

$user_id = $_SESSION['d_id'];
$issue_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($issue_id === 0) {
    die("Error: Invalid issue ID.");
}

// Handle status update (AJAX request)
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['status'])) {
    $new_status = trim($_POST['status']);
    if (!in_array($new_status, ['Acknowledged', 'Work In Progress', 'Solved'])) {
        die("Error: Invalid status.");
    }
    $query = "UPDATE issue SET i_status = ? WHERE i_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "si", $new_status, $issue_id);
    $success = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    mysqli_close($conn);
    die($success ? "Success" : "Error updating status.");
}

// Fetch issue details
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
mysqli_close($conn);

if (!$issue) {
    die("Error: Issue not found.");
}
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
    <link rel="stylesheet" href="../CSS/issueDetailDep.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
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

<main>
    <h1 class="issue-title">📌 Issue Details</h1>

    <div class="issue-container">
        <h2 class="issue-heading"><?php echo htmlspecialchars($issue['i_title']); ?></h2>
        
        <p><strong>Description:</strong> <?php echo nl2br(htmlspecialchars($issue['i_desc'])); ?></p>
        
        <p><strong>Status:</strong> 
            <span id="issue-status" class="status-badge">
                <?php echo ucfirst(htmlspecialchars($issue['i_status'])); ?>
            </span>
        </p>
        
        <p><strong>Created At:</strong> 
            <?php echo date("F j, Y, g:i a", strtotime($issue['i_created_at'])); ?>
        </p>

        <!-- Status Update Buttons -->
        <div class="status-buttons">
            <button class="status-btn acknowledged" onclick="updateStatus('Acknowledged')">✅ Acknowledged</button>
            <button class="status-btn progress" onclick="updateStatus('Work In Progress')">🚧 Work In Progress</button>
            <button class="status-btn solved" onclick="updateStatus('Solved')">🎯 Solved</button>
        </div>
    </div>


    <?php if (!empty($issue['i_image'])): ?>
        <h3>Issue Image</h3>
        <img src="../<?php echo htmlspecialchars($issue['i_image']); ?>" alt="Issue Image" class="issue-image" style="max-width: 100%; height: auto;">
    <?php endif; ?>

    <div class="vote-display">
        <div class="vote-item upvote">
            👍 <strong>Upvotes:</strong> <span><?php echo $issue['upvotes']; ?></span>
        </div>
        <div class="vote-item downvote">
            👎 <strong>Downvotes:</strong> <span><?php echo $issue['downvotes']; ?></span>
        </div>
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

<!-- JavaScript for AJAX status update -->
<script>
function updateStatus(newStatus) {
    var issueId = <?php echo $issue_id; ?>;
    
    var xhr = new XMLHttpRequest();
    xhr.open("POST", "<?php echo $_SERVER['PHP_SELF'] . '?id=' . $issue_id; ?>", true);
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

    xhr.onreadystatechange = function () {
        if (xhr.readyState === 4 && xhr.status === 200) {
            if (xhr.responseText === "Success") {
                document.getElementById("issue-status").innerText = newStatus;
                alert("Status updated to: " + newStatus);
            } else {
                alert("Error: " + xhr.responseText);
            }
        }
    };

    xhr.send("status=" + encodeURIComponent(newStatus));
}
</script>

</body>
</html>
