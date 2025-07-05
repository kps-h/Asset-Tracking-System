<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "track";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
  die(json_encode(["error" => "DB connection failed"]));
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['asset_type'])) {
  $asset_type = $_POST['asset_type'];

  // Generate prefix: First letter of asset_type (uppercase)
  $prefix = strtoupper(substr($asset_type, 0, 1));

  // Find highest number for this prefix
  $stmt = $conn->prepare("SELECT asset_id FROM asset_registration WHERE asset_id LIKE CONCAT(?, '%') ORDER BY asset_id DESC LIMIT 1");
  $stmt->bind_param("s", $prefix);
  $stmt->execute();
  $result = $stmt->get_result();

  $next_number = 1;
  if ($row = $result->fetch_assoc()) {
    $last_id = $row['asset_id'];
    $num_part = (int)substr($last_id, 1);
    $next_number = $num_part + 1;
  }

  $new_asset_id = $prefix . str_pad($next_number, 3, "0", STR_PAD_LEFT);

  echo json_encode(["asset_id" => $new_asset_id]);
  exit;
}

echo json_encode(["error" => "Invalid request"]);
?>
