<?php
$conn = new mysqli("localhost", "username", "password", "track");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$asset_id = $_GET['asset_id'];

$sql = "
SELECT t.*, u.full_name 
FROM asset_transactions t 
JOIN users u ON t.user_id = u.user_id 
WHERE t.asset_id = '$asset_id' 
ORDER BY t.transaction_date DESC
";
$res = $conn->query($sql);

if ($res->num_rows > 0) {
    echo "<ul class='list-group'>";
    while ($row = $res->fetch_assoc()) {
        echo "<li class='list-group-item'>
            <strong>{$row['transaction_type']}</strong> to <b>{$row['full_name']}</b> on <i>{$row['transaction_date']}</i>
        </li>";
    }
    echo "</ul>";
} else {
    echo "<p>No transactions found for this asset.</p>";
}
?>
