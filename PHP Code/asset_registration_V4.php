<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "track";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

function fetchOptions($conn, $table) {
  $column = match ($table) {
    'asset_types' => 'asset_type',
    default => 'key'
  };
  $result = $conn->query("SELECT id, `$column` AS `label` FROM `$table` ORDER BY id ASC");
  $options = [];
  while ($row = $result->fetch_assoc()) {
    $options[] = $row;
  }
  return $options;
}

$assetTypes       = fetchOptions($conn, 'asset_types');
$windowsKeys      = fetchOptions($conn, 'windows_table');
$officeKeys       = fetchOptions($conn, 'office_keys');
$antiDefectionKeys= fetchOptions($conn, 'anti_defection');
$registryKeys     = fetchOptions($conn, 'registry_keys');

$editData = null;
$isUpdate = false;
$message = "";

// ✅ If editing
if (isset($_GET['id'])) {
  $id = (int)$_GET['id'];
  $stmt = $conn->prepare("SELECT * FROM asset_registration WHERE id = ?");
  $stmt->bind_param("i", $id);
  $stmt->execute();
  $result = $stmt->get_result();
  $editData = $result->fetch_assoc();
  $stmt->close();
  $isUpdate = true;
}

// ✅ Save form
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['asset_id'])) {
  $windows_key        = !empty($_POST['windows_key']) ? (int)$_POST['windows_key'] : NULL;
  $office_key         = !empty($_POST['office_key']) ? (int)$_POST['office_key'] : NULL;
  $anti_defection_key = !empty($_POST['anti_defection_key']) ? (int)$_POST['anti_defection_key'] : NULL;
  $registry_key       = !empty($_POST['registry_key']) ? (int)$_POST['registry_key'] : NULL;
  $purchase_date      = !empty($_POST['purchase_date']) ? $_POST['purchase_date'] : NULL;
  

  if ($isUpdate) {
    // 🔁 UPDATE existing record
    $stmt = $conn->prepare("UPDATE asset_registration SET asset_id=?, asset_type=?, vendor=?, purchase_date=?, windows_key=?, office_key=?, anti_defection_key=?, registry_key=?, remarks=? WHERE id=?");
    $stmt->bind_param(
      "ssssiiiisi",
      $_POST['asset_id'],
      $_POST['asset_type'],
      $_POST['vendor'],
      $purchase_date,
      $windows_key,
      $office_key,
      $anti_defection_key,
      $registry_key,
      $_POST['remarks'],
      $id
    );
    $message = $stmt->execute() ? "✅ Asset updated successfully!" : "❌ Error: " . $stmt->error;
    $stmt->close();
  } else {
    // ➕ INSERT new record
    $stmt = $conn->prepare("INSERT INTO asset_registration 
      (asset_id, asset_type, vendor, purchase_date, windows_key, office_key, anti_defection_key, registry_key, remarks, status) 
      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'free')");
    $stmt->bind_param(
      "ssssiiiis",
      $_POST['asset_id'],
      $_POST['asset_type'],
      $_POST['vendor'],
      $purchase_date,
      $windows_key,
      $office_key,
      $anti_defection_key,
      $registry_key,
      $_POST['remarks']
    );
    $message = $stmt->execute() ? "✅ Asset registered successfully!" : "❌ Error: " . $stmt->error;
    $stmt->close();
  }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title><?= $isUpdate ? "Edit Asset" : "Register Asset" ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-4">
  <h2 class="mb-3"><?= $isUpdate ? "Edit Asset" : "Register New Asset" ?></h2>

  <?php if ($message): ?>
    <div class="alert alert-info"><?= $message ?></div>
  <?php endif; ?>

  <form method="POST">
    <div class="row g-3">
      <div class="col-md-6">
        <label class="form-label">Asset Type</label>
        <select class="form-select" name="asset_type" required>
          <option value="">-- Select Type --</option>
          <?php foreach ($assetTypes as $type): ?>
            <option value="<?= $type['label'] ?>" <?= ($editData['asset_type'] ?? '') == $type['label'] ? 'selected' : '' ?>>
              <?= $type['label'] ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-md-6">
        <label class="form-label">Asset ID</label>
        <input type="text" class="form-control" name="asset_id" value="<?= htmlspecialchars($editData['asset_id'] ?? '') ?>" required>
      </div>

      <div class="col-md-6">
        <label class="form-label">Vendor</label>
        <input type="text" class="form-control" name="vendor" value="<?= htmlspecialchars($editData['vendor'] ?? '') ?>">
      </div>

      <div class="col-md-6">
        <label class="form-label">Purchase Date</label>
        <input type="date" class="form-control" name="purchase_date" value="<?= htmlspecialchars($editData['purchase_date'] ?? '') ?>">
      </div>

      <div class="col-md-6">
        <label class="form-label">Windows Key</label>
        <select name="windows_key" class="form-select">
          <option value="">-- Select Windows Key --</option>
          <?php foreach ($windowsKeys as $row): ?>
            <option value="<?= $row['id'] ?>" <?= ($editData['windows_key'] ?? '') == $row['id'] ? 'selected' : '' ?>>
              <?= $row['label'] ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-md-6">
        <label class="form-label">Office Key</label>
        <select name="office_key" class="form-select">
          <option value="">-- Select Office Key --</option>
          <?php foreach ($officeKeys as $row): ?>
            <option value="<?= $row['id'] ?>" <?= ($editData['office_key'] ?? '') == $row['id'] ? 'selected' : '' ?>>
              <?= $row['label'] ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-md-6">
        <label class="form-label">Anti Defection Key</label>
        <select name="anti_defection_key" class="form-select">
          <option value="">-- Select Anti Defection Key --</option>
          <?php foreach ($antiDefectionKeys as $row): ?>
            <option value="<?= $row['id'] ?>" <?= ($editData['anti_defection_key'] ?? '') == $row['id'] ? 'selected' : '' ?>>
              <?= $row['label'] ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-md-6">
        <label class="form-label">Registry Key</label>
        <select name="registry_key" class="form-select">
          <option value="">-- Select Registry Key --</option>
          <?php foreach ($registryKeys as $row): ?>
            <option value="<?= $row['id'] ?>" <?= ($editData['registry_key'] ?? '') == $row['id'] ? 'selected' : '' ?>>
              <?= $row['label'] ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-md-12">
        <label class="form-label">Remarks</label>
        <textarea class="form-control" name="remarks" rows="2"><?= htmlspecialchars($editData['remarks'] ?? '') ?></textarea>
      </div>

      <div class="col-12 text-end">
        <button type="submit" class="btn btn-primary"><?= $isUpdate ? 'Update Asset' : 'Register Asset' ?></button>
      </div>
    </div>
  </form>
</div>
</body>
</html>
