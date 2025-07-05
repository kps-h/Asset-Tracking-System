<?php
if (isset($_GET['id'])) {
    $conn = new mysqli("localhost", "root", "", "track");
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $id = intval($_GET['id']);
    $stmt = $conn->prepare("DELETE FROM asset_registration WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    $conn->close();
}

// Redirect back to report page
header("Location: assetreg_report_V4.php"); // change this to your report page filename
exit;
