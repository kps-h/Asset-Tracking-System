<?php
// Assuming you have database connection set up
include('db.php');

$asset_type_id = $_GET['asset_type_id'];
$transaction_type = $_GET['transaction_type'];  // Get the transaction type (issue or return)

// Determine the asset status filter
$status_filter = ($transaction_type === 'issue') ? 'free' : 'issued';

// Query to fetch asset IDs based on the transaction type and asset type
$sql = "SELECT asset_id FROM asset_registration WHERE asset_type = ? AND status = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('ss', $asset_type_id, $status_filter);
$stmt->execute();
$result = $stmt->get_result();

// Fetch the assets and return as JSON
$assets = [];
while ($row = $result->fetch_assoc()) {
    $assets[] = $row;
}

echo json_encode($assets);
?>
