<?php
session_start();

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Redirect if not logged in
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "track");

$popupMessage = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate input
    if (!isset($_POST['user_id'], $_POST['transaction_type'], $_POST['asset_id'])) {
        die("All fields are required.");
    }

    $user_id = $_POST['user_id'];
    $transaction_type = $_POST['transaction_type'];
    $asset_id = $_POST['asset_id'];

    // Fetch the asset's ID and type from the asset_registration table
    $stmt = $conn->prepare("SELECT id, asset_type FROM asset_registration WHERE asset_id = ?");
    $stmt->bind_param("s", $asset_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $asset = $result->fetch_assoc();

    if (!$asset) {
        die("Asset not found.");
    }

    $asset_type_id = $asset['id'];
    $asset_type = $asset['asset_type'];

    // Fetch the user's full name
    $user = $conn->query("SELECT full_name FROM users WHERE user_id = $user_id")->fetch_assoc();
    $user_name = $user['full_name'];

    // Insert transaction record into asset_transactions table
    $stmt = $conn->prepare("INSERT INTO asset_transactions (transaction_type, asset_type_id, asset_id, user_id) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("sisi", $transaction_type, $asset_type_id, $asset_id, $user_id);
    $stmt->execute();

    // Update asset status
    $new_status = ($transaction_type == 'issue') ? 'issued' : 'free';
    $stmt = $conn->prepare("UPDATE asset_registration SET status = ? WHERE asset_id = ?");
    $stmt->bind_param("ss", $new_status, $asset_id);
    $stmt->execute();

    // Redirect with popup message
    $popupMessage = ($transaction_type == 'issue') 
        ? "Asset <strong>{$asset_id}</strong> is <strong>issued to</strong> {$user_name}" 	
        : "Asset <strong>{$asset_id}</strong> is <strong>returned by</strong> {$user_name}";

    header("Location: ".$_SERVER['PHP_SELF']."?message=" . urlencode($popupMessage));
    exit();
}

// Handle popup message from redirect
if (isset($_GET['message'])) {
    $popupMessage = $_GET['message'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Asset Transaction Form</title>
  <style>
    /* Add your styles here */
    .form-container {
      display: flex;
      flex-wrap: wrap;
      justify-content: center;
      align-items: flex-end;
      gap: 30px;
      max-width: 1000px;
      margin: 0 auto 30px;
    }
    h2 {
      text-align: center;
      margin-bottom: 40px;
      font-size: 32px;
    }
    .step { border: 1px solid #ccc; padding: 1rem; border-radius: 10px; }
	.step.active .circle {
      background: green;
    }
    .circle {
      width: 40px;
      height: 40px;
      background: #ccc;
      border-radius: 50%;
      margin: 0 auto 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: bold;
      color: white;
      transition: background 0.3s;
    }
    .spinner {
      display: none;
      width: 30px;
      height: 30px;
      border: 4px solid #ccc;
      border-top-color:  #008000;
      border-radius: 50%;
      animation: spin 0.8s linear infinite;
      margin: 20px auto 0;
    }
	@keyframes spin {
      to { transform: rotate(360deg); }
    }
	body {
      font-family: 'Segoe UI', sans-serif;
      background: #f2f7f7;
      margin: 0;
      padding: 40px 20px;
    }
	
    #popup {
  position: fixed;
  left: 50%;
  top: 20%;
  transform: translate(-50%, -50%);
  background: #e0ffe0;
  padding: 1rem;
  border: 2px solid #0a0;
  border-radius: 10px;
  z-index: 1001;
  display: none;
}
    #overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100vw;
  height: 100vh;
  background: rgba(0,0,0,0.3);
  z-index: 1000;
  display: none;
}
	recent-transactions {
      max-width: 1000px;
      margin: 40px auto;
      background: white;
      padding: 20px;
      border-radius: 10px;
      box-shadow: 0 0 10px rgba(0,0,0,0.05);
    }

    .recent-transactions h3 {
      text-align: center;
      margin-bottom: 20px;
    }

    .recent-transactions table {
      width: 100%;
      border-collapse: collapse;
    }

    .recent-transactions th, .recent-transactions td {
      padding: 10px;
      text-align: left;
      border-bottom: 1px solid #ddd;
    }
	.download-button {
      position: absolute;
      top: 45px;
      right: 40px;
      background: #007bff;
      color: white;
      padding: 8px 14px;
      border-radius: 5px;
      text-decoration: none;
      font-size: 14px;
    }
	
  </style>
</head>
<body>

<h2>Asset Issue & Return</h2>
<a href="export.php" class="download-button">⬇️ Download Report</a>

<form method="POST" id="assetForm" action="">
  <div class="form-container">
    <div class="step" id="step1">
      <div class="circle">1</div>
      <label for="user_id">User</label>
      <select name="user_id" id="user_id" required>
        <option value="">Select User</option>
        <?php
        $users = $conn->query("SELECT user_id, full_name FROM users WHERE status = 'Active'");
        while ($row = $users->fetch_assoc()) {
          echo "<option value='{$row['user_id']}'>{$row['full_name']}</option>";
        }
        ?>
      </select>
    </div>
    <div class="step" id="step2">
      <div class="circle">2</div>
      <label for="transaction_type">Type</label>
      <select name="transaction_type" id="transaction_type" required>
        <option value="">Select Type</option>
        <option value="issue">Issue</option>
        <option value="return">Return</option>
      </select>
    </div>
    <div class="step" id="step3">
      <div class="circle">3</div>
      <label for="asset_id">Asset</label>
      <select name="asset_id" id="asset_id" required>
        <option value="">Select Asset</option>
      </select>
    </div>
    <div class="step" id="step4">
      <div class="circle">✔</div>
      <label>Done</label>
      <button type="submit" id="submitBtn" name="submit">Submit</button>
      <div class="spinner" id="spinner">⏳ Submitting...</div>
    </div>
  </div>
</form>

<div class="recent-transactions">
  <h3>Recent Transactions</h3>
  <table border="1" cellpadding="5">
    <thead>
      <tr>
        <th>Type</th>
        <th>Asset ID</th>
        <th>User</th>
        <th>Date</th>
      </tr>
    </thead>
    <tbody>
      <?php
      $recent = $conn->query("SELECT atx.transaction_type, atx.asset_id, u.full_name, atx.transaction_date FROM asset_transactions atx JOIN users u ON u.user_id = atx.user_id ORDER BY atx.transaction_date DESC LIMIT 3");
      while ($r = $recent->fetch_assoc()): ?>
        <tr>
          <td><?= ucfirst($r['transaction_type']) ?></td>
          <td><?= $r['asset_id'] ?></td>
          <td><?= $r['full_name'] ?></td>
          <td><?= date('d M Y, h:i A', strtotime($r['transaction_date'])) ?></td>
        </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
</div>

<!-- Popup -->
<div id="overlay"></div>
<div id="popup"></div>

<script>
  const userSelect = document.getElementById('user_id');
  const typeSelect = document.getElementById('transaction_type');
  const assetSelect = document.getElementById('asset_id');
  const submitBtn = document.getElementById('submitBtn');
  const spinner = document.getElementById('spinner');

  function updateActive() {
    document.getElementById('step1').classList.toggle('active', !!userSelect.value);
    document.getElementById('step2').classList.toggle('active', !!typeSelect.value);
    document.getElementById('step3').classList.toggle('active', !!assetSelect.value);
    document.getElementById('step4').classList.toggle('active', userSelect.value && typeSelect.value && assetSelect.value);
  }

  function loadAssets() {
    const userId = userSelect.value;
    const type = typeSelect.value;
    if (userId && type) {
      assetSelect.innerHTML = '<option value="">Loading...</option>';
      fetch(`load_assets.php?user_id=${userId}&type=${type}`)
        .then(res => res.json())
        .then(data => {
          assetSelect.innerHTML = '<option value="">Select Asset</option>';
          data.forEach(asset => {
            assetSelect.innerHTML += `<option value="${asset.asset_id}">${asset.asset_id} (${asset.asset_type})</option>`;
          });
          updateActive();
        });
    }
  }

  userSelect.addEventListener('change', () => { loadAssets(); updateActive(); });
  typeSelect.addEventListener('change', () => { loadAssets(); updateActive(); });
  assetSelect.addEventListener('change', updateActive);

  document.getElementById('assetForm').addEventListener('submit', function () {
    submitBtn.disabled = true;
    spinner.style.display = 'inline-block';
  });

  function showPopup(message) {
    const popup = document.getElementById("popup");
    const overlay = document.getElementById("overlay");
    popup.innerHTML = `✅ ${message}`;
    overlay.style.display = "block";
    popup.style.display = "block";

    setTimeout(() => {
      popup.style.opacity = 0;
      overlay.style.opacity = 0;
    }, 2500);

    setTimeout(() => {
      popup.style.display = "none";
      overlay.style.display = "none";
      popup.style.opacity = 1;
      overlay.style.opacity = 1;
    }, 3000);
  }

  <?php if (!empty($popupMessage)): ?>
    showPopup(`<?= $popupMessage ?>`);
  <?php endif; ?>

  updateActive();
</script>

</body>
</html>
