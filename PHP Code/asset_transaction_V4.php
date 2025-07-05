<?php
session_start();
$conn = new mysqli("localhost", "root", "", "track");

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$popupMessage = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_POST['user_id'];
    $transaction_type = $_POST['transaction_type'];
    $asset_id = $_POST['asset_id'];

    // Get asset details
    $stmt = $conn->prepare("SELECT id FROM asset_registration WHERE asset_id = ?");
    $stmt->bind_param("s", $asset_id);
    $stmt->execute();
    $asset = $stmt->get_result()->fetch_assoc();
    $asset_type_id = $asset['id'];

    // Get user name
    $user = $conn->query("SELECT full_name FROM users WHERE user_id = $user_id")->fetch_assoc();
    $user_name = $user['full_name'];

    // Insert into asset_transactions
    $stmt = $conn->prepare("INSERT INTO asset_transactions (transaction_type, asset_type_id, asset_id, user_id) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("sisi", $transaction_type, $asset_type_id, $asset_id, $user_id);
    $stmt->execute();

    // Update asset status
    $new_status = ($transaction_type == 'issue') ? 'issued' : 'free';
    $stmt = $conn->prepare("UPDATE asset_registration SET status = ? WHERE asset_id = ?");
    $stmt->bind_param("ss", $new_status, $asset_id);
    $stmt->execute();

    $popupMessage = ($transaction_type == 'issue') 
        ? "✅ Asset issued to <strong>$user_name</strong>" 
        : "✅ Asset returned from <strong>$user_name</strong>";
}
?>
<!DOCTYPE html>
<html>
<head>
  <title>Asset Issue / Return</title>
  <style>
    body { font-family: Arial; background: #f4f6f9; padding: 30px; }
    .form-group { margin-bottom: 15px; }
    label { font-weight: bold; }
    select, button { padding: 10px; width: 100%; border-radius: 5px; }
    .form-container { max-width: 600px; margin: auto; background: white; padding: 25px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
    .popup { background: #28a745; color: white; padding: 15px; text-align: center; margin-bottom: 20px; border-radius: 5px; display: <?= $popupMessage ? 'block' : 'none' ?>; }
  </style>
</head>
<body>

<div class="form-container">
  <h2>Asset Issue / Return</h2>
  <div class="popup"><?= $popupMessage ?></div>

  <form method="POST">
    <div class="form-group">
      <label>User</label>
      <select name="user_id" required>
        <option value="">Select User</option>
        <?php
        $users = $conn->query("SELECT user_id, full_name FROM users WHERE status='Active'");
        while ($row = $users->fetch_assoc()) {
          echo "<option value='{$row['user_id']}'>{$row['full_name']}</option>";
        }
        ?>
      </select>
    </div>

    <div class="form-group">
      <label>Transaction Type</label>
      <select name="transaction_type" id="transaction_type" required>
        <option value="">Select Type</option>
        <option value="issue">Issue</option>
        <option value="return">Return</option>
      </select>
    </div>

    <div class="form-group">
      <label>Asset</label>
      <select name="asset_id" id="asset_id" required>
        <option value="">Select Asset</option>
      </select>
    </div>

    <button type="submit">Submit</button>
  </form>
</div>

<script>
  const userSelect = document.querySelector('[name="user_id"]');
  const typeSelect = document.querySelector('#transaction_type');
  const assetSelect = document.querySelector('#asset_id');

  function loadAssets() {
    const userId = userSelect.value;
    const type = typeSelect.value;

    if (userId && type) {
      fetch(`load_assets.php?user_id=${userId}&type=${type}`)
        .then(res => res.json())
        .then(data => {
          assetSelect.innerHTML = '<option value="">Select Asset</option>';
          data.forEach(item => {
            assetSelect.innerHTML += `<option value="${item.asset_id}">${item.asset_id} (${item.asset_type})</option>`;
          });
        });
    }
  }

  userSelect.addEventListener('change', loadAssets);
  typeSelect.addEventListener('change', loadAssets);
</script>

</body>
</html>
