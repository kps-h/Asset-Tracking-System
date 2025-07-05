<?php
$conn = new mysqli("localhost", "username", "password", "track");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$data = json_decode($_POST['updates'], true);

foreach ($data as $d) {
    $stmt = $conn->prepare("UPDATE asset_registration SET asset_id=?, asset_type=?, vendor=?, purchase_date=?, remarks=? WHERE id=?");
    $stmt->bind_param("sssssi", $d['asset_id'], $d['asset_type'], $d['vendor'], $d['purchase_date'], $d['remarks'], $d['id']);
    $stmt->execute();
    $stmt->close();
}

echo "Assets updated successfully.";
?>
