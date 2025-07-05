<?php
// Include database connection
include('db.php');

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get the form data
    $transaction_type = $_POST['transaction_type'];
    $asset_id = $_POST['asset_id'];
    $user_id = $_POST['user'];

    // Check if the asset is already issued
    if ($transaction_type == 'issue') {
        $query = "SELECT status FROM asset_registration WHERE asset_id = ?";
        if ($stmt = $conn->prepare($query)) {
            $stmt->bind_param("s", $asset_id);
            $stmt->execute();
            $stmt->bind_result($status);
            $stmt->fetch();
            $stmt->close();

            if ($status == 'issued') {
                // If asset is already issued, prevent the transaction
                echo "This asset is already issued to another user.";
                exit; // Exit the script if the asset is already issued
            }
        }
    }

    // Proceed with inserting the transaction
    if ($transaction_type == 'issue') {
        // Update the asset's status to 'issued'
        $update_query = "UPDATE asset_registration SET status = 'issued' WHERE asset_id = ?";
        if ($stmt = $conn->prepare($update_query)) {
            $stmt->bind_param("s", $asset_id);
            $stmt->execute();
            $stmt->close();
        }

        // Insert transaction record in asset_transactions or a similar table (not shown in this example)
        // Your query for inserting the transaction should go here.

        echo "Asset issued successfully!";
    } elseif ($transaction_type == 'return') {
        // If the asset is returned, update the status to 'free'
        $update_query = "UPDATE asset_registration SET status = 'free' WHERE asset_id = ?";
        if ($stmt = $conn->prepare($update_query)) {
            $stmt->bind_param("s", $asset_id);
            $stmt->execute();
            $stmt->close();
        }

        echo "Asset returned successfully!";
    }
}
?>
