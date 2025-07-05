<?php
// Set header to return JSON
header('Content-Type: application/json');

include('db.php');

// Query to get users
$sql = "SELECT user_id, full_name FROM users";
$result = $conn->query($sql);

$users = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
}

// Return result as JSON
echo json_encode($users);

// Close the connection
$conn->close();
?>
