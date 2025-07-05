<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "track";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (isset($_GET['user_id'])) {
    $userId = $_GET['user_id'];
    $query = "SELECT asset_id, asset_type FROM asset_registration WHERE status = 'issued' AND asset_id IN 
              (SELECT asset_id FROM asset_transactions WHERE user_id = $userId AND transaction_type = 'issue')";
    $result = $conn->query($query);
    
    $assets = [];
    while ($row = $result->fetch_assoc()) {
        $assets[] = $row;
    }

    echo json_encode($assets);
}
?>
