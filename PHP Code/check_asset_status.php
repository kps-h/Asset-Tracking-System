<?php
// Include database connection
include('db.php');

// Check if asset ID is passed
if (isset($_GET['asset_id'])) {
    $asset_id = $_GET['asset_id'];

    // Query to check the current status of the asset
    $query = "SELECT status FROM asset_registration WHERE asset_id = ?";
    
    if ($stmt = $conn->prepare($query)) {
        $stmt->bind_param("s", $asset_id); // Bind the asset ID
        $stmt->execute();
        $stmt->bind_result($status); // Fetch the status
        $stmt->fetch();
        
        if ($status == 'free') {
            echo 'free'; // If the asset is free, echo 'free'
        } else {
            echo 'issued'; // If the asset is issued, echo 'issued'
        }

        $stmt->close();
    } else {
        echo 'Error checking status'; // If there's an issue with the query
    }
} else {
    echo 'Asset ID not provided';
}
?>
