<?php
include 'db.php'; // Database connection

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'];
    $asset_id = $_POST['asset_id'];
    $asset_type = $_POST['asset_type'];
    $vendor = $_POST['vendor'];
    $purchase_date = $_POST['purchase_date'];
    $windows_key = $_POST['windows_key'];
    $office_key = $_POST['office_key'];
    $anti_defection_key = $_POST['anti_defection_key'];
    $registry_key = $_POST['registry_key'];
    $remarks = $_POST['remarks'];

    $query = "UPDATE asset_registration SET asset_id='$asset_id', asset_type='$asset_type', vendor='$vendor', 
              purchase_date='$purchase_date', windows_key='$windows_key', office_key='$office_key', 
              anti_defection_key='$anti_defection_key', registry_key='$registry_key', remarks='$remarks' 
              WHERE id='$id'";

    if (mysqli_query($conn, $query)) {
        echo "Asset updated successfully!";
    } else {
        echo "Error: " . mysqli_error($conn);
    }
}
?>
