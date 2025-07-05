<?php
// DB connection
$host = "localhost";
$user = "root";
$pass = "";
$db   = "track"; // replace with your actual DB name

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

// --- Handle AJAX Add Asset Type ---
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['ajax_add_type'])) {
    $type = trim($_POST['asset_type']);
    if ($type === '') {
        echo json_encode(["success" => false, "message" => "Type is empty"]);
        exit;
    }

    $check = $conn->prepare("SELECT id FROM asset_types WHERE asset_type = ?");
    $check->bind_param("s", $type);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        echo json_encode(["success" => false, "message" => "Type already exists"]);
    } else {
        $stmt = $conn->prepare("INSERT INTO asset_types (asset_type) VALUES (?)");
        $stmt->bind_param("s", $type);
        $stmt->execute();
        echo json_encode(["success" => true]);
    }
    exit;
}

// Fetch dropdown data
function fetchOptions($conn, $table) {
  $column = match ($table) {
    'asset_types' => 'asset_type',
    'windows_table', 'office_keys', 'anti_defection', 'registry_keys' => 'key',
    default => 'key'
  };

  $query = "SELECT id, `$column` AS `label` FROM `$table` ORDER BY id ASC";
  $result = $conn->query($query);
  if (!$result) {
    die("Query Error on `$table`: " . $conn->error);
  }

  $options = [];
  while ($row = $result->fetch_assoc()) {
    $options[] = $row;
  }
  return $options;
}

$assetTypes = fetchOptions($conn, 'asset_types');
$windowsKeys = fetchOptions($conn, 'windows_table');
$officeKeys = fetchOptions($conn, 'office_keys');
$antiDefectionKeys = fetchOptions($conn, 'anti_defection');
$registryKeys = fetchOptions($conn, 'registry_keys');

// Handle form submission
$message = "";
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['asset_id'])) {
    // Convert empty keys to NULL
    $windows_key         = !empty($_POST['windows_key']) ? (int)$_POST['windows_key'] : NULL;
    $office_key          = !empty($_POST['office_key']) ? (int)$_POST['office_key'] : NULL;
    $anti_defection_key  = !empty($_POST['anti_defection_key']) ? (int)$_POST['anti_defection_key'] : NULL;
    $registry_key        = !empty($_POST['registry_key']) ? (int)$_POST['registry_key'] : NULL;

    // Prepare SQL to insert asset registration data
    $stmt = $conn->prepare("INSERT INTO asset_registration 
      (asset_id, asset_type, vendor, purchase_date, windows_key, office_key, anti_defection_key, registry_key, remarks, status) 
      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'free')");

    $stmt->bind_param(
      "ssssiiiis",
      $_POST['asset_id'],
      $_POST['asset_type'],
      $_POST['vendor'],
      $_POST['purchase_date'],
      $windows_key,
      $office_key,
      $anti_defection_key,
      $registry_key,
      $_POST['remarks']
    );

    $message = $stmt->execute() ? "✅ Asset registered successfully!" : "❌ Error: " . $stmt->error;
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Asset Registration</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
</head>
<style>
body {
    background-color: #f2f7f7;
}
#submitBtn {
            background-color: #f76c6c !important;
            border-color: #f76c6c !important;
            color: #fff !important;
        }
		#submitBtn:hover {
    background-color: #C11B17 !important;
    border-color: #C11B17 !important;
}
#popup {
            display: none;
            position: fixed;
            top: 70%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color: #28a745;
            color: white;
            padding: 20px 30px;
            border-radius: 8px;
            font-size: 18px;
            box-shadow: 0 0 20px rgba(0,0,0,0.3);
            z-index: 9999;
            transition: opacity 0.5s ease;
            opacity: 0;
            min-width: 300px;
            text-align: center;
        }
</style>
<body>
<div class="container mt-5">
  <h2 class="mb-4">Asset Registration</h2>

  <?php if ($message): ?>
    <div class="alert alert-info"><?php echo $message; ?></div>
  <?php endif; ?>

  <form method="POST">
    <div class="row g-3">
	<div class="col-md-6">
        <label class="form-label">Asset Type</label>
        <select class="form-select" name="asset_type" id="asset_type_dropdown" required>
          <option value="">-- Select Type --</option>
          <?php foreach ($assetTypes as $type): ?>
            <option value="<?= htmlspecialchars($type['label']) ?>"><?= htmlspecialchars($type['label']) ?></option>
          <?php endforeach; ?>
          <option value="__add_new__">➕ Add + Asset Type</option>
        </select>
      </div>
	  
      <div class="col-md-6">
        <label class="form-label">Asset ID</label>
        <input type="text" class="form-control" name="asset_id" required>
      </div>
      <div class="col-md-6">
        <label class="form-label">Vendor</label>
        <input type="text" class="form-control" name="vendor" >
      </div>

      <div class="col-md-6">
        <label class="form-label">Purchase Date</label>
        <input type="date" class="form-control" name="purchase_date" >
      </div>

      <div class="col-md-6">
        <label class="form-label">Windows Key</label>
        <select name="windows_key" class="form-select">
          <option value="">-- Select Windows Key --</option>
          <?php foreach ($windowsKeys as $row): ?>
            <option value="<?= $row['id'] ?>"><?= htmlspecialchars($row['label']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-md-6">
        <label class="form-label">Office Key</label>
        <select name="office_key" class="form-select">
          <option value="">-- Select Office Key --</option>
          <?php foreach ($officeKeys as $row): ?>
            <option value="<?= $row['id'] ?>"><?= htmlspecialchars($row['label']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-md-6">
        <label class="form-label">Anti Defection Key</label>
        <select name="anti_defection_key" class="form-select">
          <option value="">-- Select Anti Defection Key --</option>
          <?php foreach ($antiDefectionKeys as $row): ?>
            <option value="<?= $row['id'] ?>"><?= htmlspecialchars($row['label']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-md-6">
        <label class="form-label">Registry Key</label>
        <select name="registry_key" class="form-select">
          <option value="">-- Select Registry Key --</option>
          <?php foreach ($registryKeys as $row): ?>
            <option value="<?= $row['id'] ?>"><?= htmlspecialchars($row['label']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-md-12">
        <label class="form-label">Remarks</label>
        <textarea class="form-control" name="remarks" rows="2"></textarea>
      </div>

      <div class="col-12 text-end">
        <button type="submit" class="btn btn-primary">Register Asset</button>
      </div>
    </div>
  </form>
</div>

<!-- Modal -->
<div class="modal fade" id="assetTypeModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form id="addTypeForm" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Add Asset Type</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="text" name="new_asset_type" class="form-control" placeholder="Enter new asset type" required>
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn btn-success">Add Type</button>
      </div>
    </form>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
$(document).ready(function () {
  const modal = new bootstrap.Modal(document.getElementById('assetTypeModal'));

  $('#asset_type_dropdown').change(function () {
    if ($(this).val() === '__add_new__') {
      modal.show();
    }
  });

  $('#addTypeForm').submit(function (e) {
    e.preventDefault();
    const newType = $('input[name="new_asset_type"]').val().trim();
    if (newType === "") return;

    $.post('', { ajax_add_type: true, asset_type: newType }, function (data) {
      if (data.success) {
        const newOption = new Option(newType, newType, true, true);
        $('#asset_type_dropdown').append(newOption).val(newType);
        modal.hide();
        $('input[name="new_asset_type"]').val('');
      } else {
        alert("❌ " + data.message);
      }
    }, 'json');
  });
});
</script>
</body>
</html>
