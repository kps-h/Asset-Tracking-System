<?php
// Set header to return JSON
header('Content-Type: application/json');

include('db.php');

// Query to get asset types
$sql = "SELECT id, asset_type FROM asset_types";
$result = $conn->query($sql);

$asset_types = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $asset_types[] = $row;
    }
}

// Return result as JSON
echo json_encode($asset_types);

// Close the connection
$conn->close();
?>
