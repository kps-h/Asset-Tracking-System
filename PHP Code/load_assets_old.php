<?php
$conn = new mysqli("localhost", "root", "", "track"); // Replace with your DB name

$user_id = $_GET['user_id'];
$type = $_GET['type'];

$assets = [];

if ($type == "issue") {
    $result = $conn->query("SELECT asset_id, asset_type FROM asset_registration WHERE status = 'free'");
} else {
    $result = $conn->query("
        SELECT ar.asset_id, ar.asset_type
        FROM asset_registration ar
        JOIN asset_transactions atx ON ar.asset_id = atx.asset_id
        WHERE atx.user_id = '$user_id' AND ar.status = 'issued'
        GROUP BY ar.asset_id
    ");
}

while ($row = $result->fetch_assoc()) {
    $assets[] = $row;
}

echo json_encode($assets);
?>