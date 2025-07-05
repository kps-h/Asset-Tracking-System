<?php
// reports.php — Module-Based Unified Report Page
?>

<!DOCTYPE html>
<html>
<head>
  <title>Unified Reports Dashboard</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      margin: 0;
      padding: 0;
      background-color: #f0f2f5;
    }
    .module-buttons {
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
      padding: 15px;
      background: #ffffff;
      border-bottom: 1px solid #ddd;
      position: sticky;
      top: 0;
      z-index: 1000;
    }
    .module-buttons button {
      padding: 10px 15px;
      border: none;
      border-radius: 4px;
      background-color: #007bff;
      color: white;
      cursor: pointer;
    }
    .module-buttons button.active {
      background-color: #0056b3;
    }
    .module-section {
      display: none;
      padding: 20px;
    }
    .module-section.active {
      display: block;
    }
    .filters, .column-select {
      margin-bottom: 15px;
    }
    .filters input, .filters select, .filters button {
      padding: 6px;
      margin-bottom: 5px;
      width: 100%;
    }
    .column-select label {
      display: block;
      font-size: 14px;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      background: white;
    }
    th, td {
      border: 1px solid #ccc;
      padding: 8px;
      text-align: left;
      font-size: 14px;
    }
    th {
      background-color: #f9f9f9;
    }
  </style>
</head>
<body>

<div class="module-buttons">
  <button onclick="showModule('asset_registration')" class="active">Asset Registration</button>
  <button onclick="showModule('asset_transactions')">Asset Transaction</button>
  <button onclick="showModule('windows_keys')">Windows Key</button>
  <button onclick="showModule('office_keys')">Office Key</button>
  <button onclick="showModule('anti_defection')">Anti-Defection Key</button>
  <button onclick="showModule('registry_keys')">Registry Key</button>
  <button onclick="showModule('users')">User Key</button>
</div>

<!-- Modules Wrapper -->
<div id="asset_registration" class="module-section active">
  <h2>Asset Registration Report</h2>
  <?php include 'modules/asset_registration.php'; ?>
</div>

<div id="asset_transactions" class="module-section">
  <h2>Asset Transactions Report</h2>
  <?php include 'modules/asset_transactions.php'; ?>
</div>

<div id="windows_keys" class="module-section">
  <h2>Windows Key Report</h2>
  <?php include 'modules/windows_keys.php'; ?>
</div>

<div id="office_keys" class="module-section">
  <h2>Office Key Report</h2>
  <?php include 'modules/office_keys.php'; ?>
</div>

<div id="anti_defection" class="module-section">
  <h2>Anti-Defection Key Report</h2>
  <?php include 'modules/anti_defection.php'; ?>
</div>

<div id="registry_keys" class="module-section">
  <h2>Registry Key Report</h2>
  <?php include 'modules/registry_keys.php'; ?>
</div>

<div id="users" class="module-section">
  <h2>User Report</h2>
  <?php include 'modules/users.php'; ?>
</div>

<script>
  function showModule(id) {
    document.querySelectorAll('.module-section').forEach(el => el.classList.remove('active'));
    document.querySelectorAll('.module-buttons button').forEach(el => el.classList.remove('active'));
    document.getElementById(id).classList.add('active');
    const buttons = document.querySelectorAll('.module-buttons button');
    buttons.forEach(btn => {
      if (btn.textContent.replace(/\s+/g, '_').toLowerCase() === id) {
        btn.classList.add('active');
      }
    });
  }
</script>

</body>
</html>
