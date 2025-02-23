<?php
include '../config/db.php';

$query = "SELECT i_id, i_lat, i_long, i_title FROM issue WHERE i_lat IS NOT NULL AND i_long IS NOT NULL";
$result = mysqli_query($conn, $query);

$locations = [];
while ($row = mysqli_fetch_assoc($result)) {
    $locations[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
<link rel="icon" type="image/png" href="../images/logo-vu.png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OpenStreetMap with Clickable Markers</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <style>
        #map { height: 100vh; width: 100%; }
    </style>
</head>
<body>

<div id="map"></div>

<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
<script>
    var map = L.map('map').setView([0, 0], 2); // Default view

    // Load OpenStreetMap tiles
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    // PHP data converted to JavaScript array
    var locations = <?php echo json_encode($locations); ?>;

    // Add markers to the map
    if (locations.length > 0) {
        var bounds = [];
        locations.forEach(function(location) {
            var marker = L.marker([location.i_lat, location.i_long]).addTo(map)
                .bindPopup("<b>" + location.i_title + "</b><br><a href='issueDetail.php?id=" + location.i_id + "' target='_blank'>View Details</a>");

            bounds.push([location.i_lat, location.i_long]);

            // Make marker clickable
            marker.on('click', function() {
                window.location.href = "issueDetail.php?id=" + location.i_id;
            });
        });

        // Adjust map to fit all markers
        map.fitBounds(bounds);
    }
</script>

</body>
</html>
