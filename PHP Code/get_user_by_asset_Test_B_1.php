<?php
$conn = new mysqli("localhost", "root", "", "track");

if (isset($_GET['asset_id'])) {
    $asset_id = $_GET['asset_id'];

    $stmt = $conn->prepare("
        SELECT user_id 
        FROM asset_transactions 
        WHERE asset_id = ? AND transaction_type = 'issue' 
        ORDER BY transaction_date DESC 
        LIMIT 1
    ");
    $stmt->bind_param("s", $asset_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

    echo json_encode($result);
}
?>
