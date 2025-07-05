<?php
include 'db_connection.php'; // Your database connection file

$user_id = $_GET['user_id'];
$sql = "SELECT asset_id, asset_type FROM asset_registration WHERE user_id='$user_id' AND status='issued'";
$result = $conn->query($sql);

$assets = [];
while ($row = $result->fetch_assoc()) {
    $assets[] = $row;
}

echo json_encode($assets);
?>
