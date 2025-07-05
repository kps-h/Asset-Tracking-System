<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "track";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
  die(json_encode(["error" => "DB connection failed"]));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['asset_type'])) {
    $prefixMap = [
        'Monitor'   => 'M',
    'Mouse'     => 'MS',
    'Keyboard'  => 'KB',
    'CPU'       => 'CPU',
    'Laptop'    => 'L',
    'Printer'   => 'P',
    'UPS'       => 'UPS',
    'Scanner'   => 'S',
    'Speakers'  => 'SP',
    ];

    $type = $_POST['asset_type'];
    $prefix = $prefixMap[$type] ?? strtoupper(substr($type, 0, 1));

    $stmt = $conn->prepare("SELECT asset_id FROM asset_registration WHERE asset_id LIKE ? ORDER BY asset_id DESC LIMIT 1");
    $like = $prefix . "%";
    $stmt->bind_param("s", $like);
    $stmt->execute();
    $result = $stmt->get_result();
    $latestID = $result->fetch_assoc()['asset_id'] ?? null;

    $nextNumber = 1;
    if ($latestID) {
        $num = (int)preg_replace('/\D+/', '', $latestID);
        $nextNumber = $num + 1;
    }

    $newAssetID = $prefix . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);

    echo json_encode(["asset_id" => $newAssetID]);
}
