<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}
$conn = new mysqli("localhost", "root", "", "track");

$popupMessage = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_POST['user_id'];
    $transaction_type = $_POST['transaction_type'];
    $asset_id = $_POST['asset_id'];

    $stmt = $conn->prepare("SELECT id FROM asset_registration WHERE asset_id = ?");
    $stmt->bind_param("s", $asset_id);
    $stmt->execute();
    $asset = $stmt->get_result()->fetch_assoc();
    $asset_type_id = $asset['id'];

    $user = $conn->query("SELECT full_name FROM users WHERE user_id = $user_id")->fetch_assoc();
    $user_name = $user['full_name'];

    $stmt = $conn->prepare("INSERT INTO asset_transactions (transaction_type, asset_type_id, asset_id, user_id) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("sisi", $transaction_type, $asset_type_id, $asset_id, $user_id);
    $stmt->execute();

    $new_status = ($transaction_type == 'issue') ? 'issued' : 'free';
    $stmt = $conn->prepare("UPDATE asset_registration SET status = ? WHERE asset_id = ?");
    $stmt->bind_param("ss", $new_status, $asset_id);
    $stmt->execute();

    $popupMessage = ($transaction_type == 'issue') 
        ? "Asset is <strong>issued to</strong> $user_name" 	
        : "Asset is <strong>returned from</strong> $user_name";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Asset Step Form</title>
  <style>
    body {
      font-family: 'Segoe UI', sans-serif;
      background: #f2f7f7;
      margin: 0;
      padding: 40px 20px;
    }

    h2 {
      text-align: center;
      margin-bottom: 40px;
      font-size: 32px;
    }

    .form-container {
      display: flex;
      flex-wrap: wrap;
      justify-content: center;
      align-items: flex-end;
      gap: 30px;
      max-width: 1000px;
      margin: 0 auto 30px;
    }

    .step {
      flex: 1 1 200px;
      text-align: center;
      position: relative;
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

    .step.active .circle {
      background: #007bff;
    }

    .line {
      position: absolute;
      top: 20px;
      left: 50%;
      width: 100%;
      height: 2px;
      background: repeating-linear-gradient(to right, #ccc, #ccc 4px, transparent 4px, transparent 8px);
      z-index: -1;
    }

    .step.active ~ .step .line {
      background: repeating-linear-gradient(to right, #007bff, #007bff 4px, transparent 4px, transparent 8px);
    }

    label {
      display: block;
      margin-bottom: 5px;
      font-weight: bold;
    }

    select, button {
      width: 100%;
      padding: 10px;
      font-size: 14px;
      border-radius: 5px;
      border: 1px solid #ccc;
    }

    button {
      background: #f76c6c;
      color: white;
      border: none;
      cursor: pointer;
    }

    button:hover {
      background: #C11B17;
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

    #overlay, #popup {
      display: none;
      position: fixed;
      z-index: 9999;
    }

    #overlay {
      top: 0; left: 0; right: 0; bottom: 0;
      background-color: rgba(0, 0, 0, 0.5);
    }

    #popup {
      background: #28a745; /* ✅ Green */
      color: white;
      padding: 30px 40px;
      border-radius: 10px;
      text-align: center;
      position: fixed;
      top: 35%;
      left: 50%;
      transform: translate(-50%, -50%);
      box-shadow: 0 0 20px rgba(0,0,0,0.3);
      font-size: 18px;
      opacity: 1;
      transition: opacity 0.5s ease;
      min-width: 300px;
    }

    .link {
      text-align: center;
      margin-top: 30px;
    }

    .link a {
      color: #007bff;
      text-decoration: none;
      font-weight: bold;
    }

    .recent-transactions {
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

<h2>Asset Issue / Return</h2>
<a href="export.php" class="download-button">⬇️ Download Report</a>
<form method="POST" id="assetForm">
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
      <button type="submit" id="submitBtn">Submit</button>
      <div class="spinner" id="spinner"></div>
    </div>
  </div>
</form>

<div class="recent-transactions">
  <h3>Recent Transactions</h3>
  <table>
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
    spinner.style.display = 'block';
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
