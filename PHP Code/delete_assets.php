<?php
$conn = new mysqli("localhost", "username", "password", "track");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$ids = $_POST['ids'];
$idList = implode(",", array_map("intval", $ids));
$conn->query("DELETE FROM asset_registration WHERE id IN ($idList)");

echo "Selected records deleted successfully.";
?>
