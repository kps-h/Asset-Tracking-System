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


		// Fetch the asset's ID, type, and status from the asset_registration table
		$stmt = $conn->prepare("SELECT id, asset_type, status FROM asset_registration WHERE asset_id = ?");
		$stmt->bind_param("s", $asset_id);
		$stmt->execute();
		$result = $stmt->get_result();
		$asset = $result->fetch_assoc();

		if (!$asset) {
			die("Asset not found.");
		}

		$asset_type_id = $asset['id'];
		$asset_type = $asset['asset_type'];
		$asset_status = $asset['status'];
		
		// If the asset is already issued, show a message and don't allow the issue to proceed
		if ($transaction_type == 'issue') {
		if ($asset_status == 'issued') {
			die("Error: The asset <strong>{$asset_id}</strong> is already issued and cannot be issued again until it is returned.");
		}

		// Fetch the user's full name and status
$user = $conn->query("SELECT full_name, status FROM users WHERE user_id = $user_id")->fetch_assoc();
$user_name = $user['full_name'] ?? 'Unknown User';
$user_status = strtolower($user['status'] ?? '');

// Only restrict if the status is not 'sell' or 'dispose'
if (!in_array($user_status, ['sell', 'dispose'])) {
    // Check if the user already has an asset of the same type issued
    $check = $conn->prepare("
        SELECT ar.asset_id 
        FROM asset_registration ar
        JOIN asset_transactions atx ON ar.asset_id = atx.asset_id
        WHERE atx.user_id = ? AND ar.asset_type = ? AND ar.status = 'issued'
        ORDER BY atx.transaction_date DESC
        LIMIT 1
    ");
    $check->bind_param("is", $user_id, $asset_type);
    $check->execute();
    $alreadyIssued = $check->get_result()->fetch_assoc();

    if ($alreadyIssued) {
        $errorMessage = "The user <strong>{$user_name}</strong> already has a <strong>{$asset_type}</strong> asset issued ({$alreadyIssued['asset_id']}). Please return it before issuing another.";
        header("Location: " . $_SERVER['PHP_SELF'] . "?error=" . urlencode($errorMessage));
        exit();
    }
}

	}
		// Fetch the user's full name
		$user = $conn->query("SELECT full_name FROM users WHERE user_id = $user_id")->fetch_assoc();
		$user_name = $user['full_name'] ?? 'Unknown User';  // ✅ Do this before using $user_name

		// Insert transaction record into asset_transactions table
		$stmt = $conn->prepare("INSERT INTO asset_transactions (transaction_type, asset_type_id, asset_id, user_id) VALUES (?, ?, ?, ?)");
		$stmt->bind_param("sisi", $transaction_type, $asset_type_id, $asset_id, $user_id);
		$stmt->execute();

		// Update asset status (only change to "issued" or "free")
		$new_status = ($transaction_type == 'issue') ? 'issued' : 'free';
		$stmt = $conn->prepare("UPDATE asset_registration SET status = ? WHERE asset_id = ?");
		$stmt->bind_param("ss", $new_status, $asset_id);
		$stmt->execute();

		// Redirect with a message
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
	$errorPopup = "";
	if (isset($_GET['error'])) {
		$errorPopup = $_GET['error'];
	}
	?>

	<!DOCTYPE html>
	<html lang="en">
	<head>
	  <meta charset="UTF-8" />
	  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
	  <title>Asset Transaction Form</title>
	  <style>
	  html, body {
	  height: 100%;
	   overflow: hidden; 
	}
	  body {
		font-family: 'Segoe UI', sans-serif;
		background-color: #f9fafa;
		color: #333;
		margin: 0;
		padding: 0;
	  }

	  h2 {
		text-align: center;
		margin: 30px 0 20px;
		font-size: 32px;
		color: #2c3e50;
	  }

	  .download-button {
	  display: inline-block;
	  background: #2980b9;
	  color: white;
	  padding: 10px 16px;
	  border-radius: 6px;
	  text-decoration: none;
	  font-weight: bold;
	  transition: background 0.3s;
	  /* Remove the lines below */
	  /* position: absolute;
		 top: 30px;
		 right: 30px; */
	}


	  .download-button:hover {
		background: #1c5980;
	  }

	  .form-container {
	  display: flex;
	  flex-wrap: nowrap;
	  overflow-x: auto;
	  justify-content: flex-start;
	  gap: 20px;
	  max-width: 1100px;
	  margin: 0 auto 40px;
	  padding: 0 20px;
	  scroll-snap-type: x mandatory;
	}


	  .step {
	  flex: 0 0 220px;
	  scroll-snap-align: start;
	  background: #fff;
	  border: 1px solid #ddd;
	  padding: 20px;
	  border-radius: 12px;
	  text-align: center;
	  box-shadow: 0 2px 4px rgba(0,0,0,0.05);
	}

	  .step.active {
		border-color: #27ae60;
	  }

	  .circle {
		width: 40px;
		height: 40px;
		background: #bdc3c7;
		border-radius: 50%;
		margin: 0 auto 10px;
		display: flex;
		align-items: center;
		justify-content: center;
		color: white;
		font-weight: bold;
		transition: background 0.3s;
	  }

	  .step.active .circle {
		background: #27ae60;
	  }

	  select, button {
		width: 100%;
		padding: 10px;
		margin-top: 10px;
		border-radius: 6px;
		border: 1px solid #ccc;
		font-size: 14px;
	  }

	  button {
		background-color: #2ecc71;
		color: white;
		border: none;
		cursor: pointer;
		font-weight: bold;
		transition: background 0.3s ease;
	  }

	  button:hover {
		background-color: #27ae60;
	  }

	  .spinner {
		display: none;
		margin-top: 10px;
		border: 4px solid #ccc;
		border-top-color: #2ecc71;
		border-radius: 50%;
		width: 24px;
		height: 24px;
		animation: spin 0.8s linear infinite;
	  }

	  @keyframes spin {
		to { transform: rotate(360deg); }
	  }

	  #popup {
		position: fixed;
		left: 50%;
		top: 20%;
		transform: translate(-50%, -50%);
		background: #e0ffe0;
		padding: 1rem;
		border: 2px solid #2ecc71;
		border-radius: 10px;
		z-index: 1001;
		display: none;
		color: #2e7d32;
		font-weight: bold;
		box-shadow: 0 2px 10px rgba(0,0,0,0.1);
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
	form[method="GET"] {
	  max-width: 1000px;
	  margin: 0 auto 30px;
	  display: flex;
	  flex-wrap: wrap;
	  gap: 12px;
	  justify-content: center;
	}
	form[method="GET"] select,
	form[method="GET"] button {
	  flex: 0 0 auto;
	  width: 160px;
	  padding: 8px 10px;
	  font-size: 14px;
	}
	form[method="GET"] button {
	  background-color: #2980b9;
	  color: white;
	  border: none;
	  border-radius: 6px;
	  font-weight: bold;
	  cursor: pointer;
	  transition: background 0.3s;
	}

	form[method="GET"] button:hover {
	  background-color: #1c5980;
	}

	  .recent-transactions h3 {
		text-align: center;
		margin-bottom: 20px;
		color: #2c3e50;
	  }

	  .recent-transactions table {
		width: 100%;
		border-collapse: collapse;
	  }

	  .recent-transactions th,
	  .recent-transactions td {
		padding: 12px 10px;
		text-align: left;
		border-bottom: 1px solid #eee;
		font-size: 14px;
	  }

	  .recent-transactions th {
		background-color: #ecf0f1;
		font-weight: 600;
	  }

	  .recent-transactions tr:hover {
		background-color: #f9f9f9;
	  }

	  @media screen and (max-width: 768px) {
		.form-container {
		  flex-direction: column;
		  align-items: stretch;
		}

		.download-button {
		  position: static;
		  display: block;
		  margin: 0 auto 20px;
		  text-align: center;
		}

		form[method="GET"] {
		  flex-direction: column;
		  align-items: stretch;
		}
	  }
	  .form-container {
	  -webkit-overflow-scrolling: touch;
	  scrollbar-width: none;
	}
	.form-container::-webkit-scrollbar {
	  display: none;
	}
	.progress-container {
	  width: 100%;
	  max-width: 1000px;
	  margin: 0 auto 20px;
	  height: 8px;
	  background: #e0e0e0;
	  border-radius: 4px;
	  overflow: hidden;
	}

	.progress-bar {
	  height: 100%;
	  width: 0%;
	  background: #27ae60;
	  transition: width 0.4s ease;
	}
	.table-wrapper {
	  overflow-x: auto;
	  width: 100%;
	}
	.report-title {
	  text-align: center;
	  margin-bottom: 20px;
	  color: #2c3e50;
	}
	.search-bar {
	  padding: 8px 10px;
	  border-radius: 6px;
	  border: 1px solid #ccc;
	  font-size: 14px;
	  width: 300px;
	}

	.download-button {
	  margin-bottom: 10px;
	  margin-right: 580px;
	}
	.report-toolbar {
	  max-width: 1800px;
	  margin: 0 auto 20px;
	  display: flex;
	  justify-content: space-between;
	  align-items: center;
	  padding: 0 20px;
	  gap: 500px; 
	}
	.recent-transactions {
	  max-width: 1800px;
	  margin: 0 auto;
	  background: white;
	  padding: 20px;
	  border-radius: 10px;
	  box-shadow: 0 0 12px rgba(0, 0, 0, 0.05);
	  height: 540px; /* Set a fixed height */
	  overflow-y: auto; /* Enable vertical scrolling */
	}

	.table-wrapper {
	  width: 100%;
	  height: 100%; /* This ensures the table takes the full height of the parent */
	  overflow-x: auto;
	  overflow-y: auto; /* Enable vertical scrolling within the table wrapper */
	}

	.table-wrapper table {
	  min-width: 1800px;
	}
	/* Style for Issue - Green */
	.issue {
		color: #27ae60; /* Green */
		font-weight: bold;
	}

	/* Style for Return - Red */
	.return {
		color: #e74c3c; /* Red */
		//font-weight: bold;
	}
	.step-label {
	  font-weight: bold;
	  color: #555;
	  margin-bottom: 8px;
	  font-size: 14px;
	}

	.step.active .step-label {
	  color: #27ae60;
	}

	.step.current {
	  border-color: #3498db;
	  background: #eaf6ff;
	}

	.step.current .circle {
	  background: #3498db;
	}
	.step.active .circle {
	  background: #27ae60;
	}
	.step.active {
	  border-color: #27ae60;
	}
	#current-step-header {
	  display: none;
	}
	</style>
	</head>
	<body>

	<h2>Asset Issue & Return</h2>
	<h3 id="current-step-header" style="text-align: center; margin-bottom: 20px; color: #2980b9;"></h3>

	<form method="POST" id="assetForm" action="">
	<div class="progress-container">
	  <div class="progress-bar" id="progressBar"></div>
	</div>

	  <div class="form-container" id="formSteps">
	  <div class="step" id="step1" data-step="1">
		<div class="circle">1</div>
		<label for="transaction_type">Type</label>
		<select name="transaction_type" id="transaction_type" required>
		  <option value="">Select Type</option>
		  <option value="issue">Issue</option>
		  <option value="return">Return</option>
		</select>
	  </div>

	  <div class="step" id="step2" data-step="2">
		<div class="circle">2</div>
		<label for="user_id">User</label>
		<select name="user_id" id="user_id" required>
		  <option value="">Select User</option>
		  <?php
		  $users = $conn->query("
		SELECT user_id, full_name 
		FROM users 
		WHERE status IN ('Active', 'Sell', 'Dispose')
	");

		  while ($row = $users->fetch_assoc()) {
			echo "<option value='{$row['user_id']}'>{$row['full_name']}</option>";
		  }
		  ?>
		</select>
	  </div>

	  <div class="step" id="step3" data-step="3">
		<div class="circle">3</div>
		<label for="asset_id">Asset</label>
		<select name="asset_id" id="asset_id" required>
		  <option value="">Select Asset</option>
		</select>
	  </div>

	  <div class="step" id="step4" data-step="4">
		<div class="circle">✔</div>
		<label>Done</label>
		<button type="submit" id="submitBtn" name="submit">Submit</button>
		<div class="spinner" id="spinner">⏳ Submitting...</div>
	  </div>
	</div>


	</form>

	<form method="GET" style="max-width:1000px;margin:20px auto;display:flex;gap:20px;flex-wrap:wrap;">
	  <select name="user_filter">
		<option value="">All Users</option>
		<?php
		$users = $conn->query("SELECT user_id, full_name FROM users WHERE status = 'Active'");
		while ($u = $users->fetch_assoc()) {
			$selected = ($_GET['user_filter'] ?? '') == $u['user_id'] ? 'selected' : '';
			echo "<option value='{$u['user_id']}' $selected>{$u['full_name']}</option>";
		}
		?>
	  </select>

	  <select name="type_filter">
		<option value="">All Types</option>
		<option value="issue" <?= ($_GET['type_filter'] ?? '') == 'issue' ? 'selected' : '' ?>>Issue</option>
		<option value="return" <?= ($_GET['type_filter'] ?? '') == 'return' ? 'selected' : '' ?>>Return</option>
	  </select>

	  <button type="submit">Filter</button>
	</form>
	<?php
	$userFilter = $_GET['user_filter'] ?? '';
	$typeFilter = $_GET['type_filter'] ?? '';

	$whereClauses = [];
	if ($userFilter) $whereClauses[] = "u.user_id = " . intval($userFilter);
	if ($typeFilter) $whereClauses[] = "atx.transaction_type = '" . $conn->real_escape_string($typeFilter) . "'";

	$whereSQL = $whereClauses ? 'WHERE ' . implode(' AND ', $whereClauses) : '';
	?>

	<div class="recent-transactions">
	  <h3>Recent Transactions</h3>
	  <a href="export.php" class="download-button" style="margin-bottom: 10px;">⬇️ Download Report</a>
	  <input type="text" placeholder="Search..." class="search-bar" id="search-input" />
	 <div class="table-wrapper">
	  <table id="transactions-table" border="1" cellpadding="5">
		<thead>
		  <tr>
			<th>Username</th>
			<th>Asset ID</th>
			<th>Asset Type</th>
			<th>Windows Key</th>
			<th>Office Key</th>
			<th>Anti-Defection Key</th>
			<th>Registry Key</th>
			<th>Status</th>
			<th>Date</th>
		  </tr>
		</thead>
		<tbody>
		  <?php
		  // Execute the updated query
		  $recent = $conn->query("
			SELECT 
				atx.transaction_type, 
				atx.asset_id, 
				u.full_name, 
				ar.asset_type,
				wt.key AS windows_key, 
				ot.key AS office_key,
				ad.key AS anti_defection_key,
				rk.key AS registry_key,
				ar.status,
				atx.transaction_date
			FROM asset_transactions atx
			JOIN users u ON u.user_id = atx.user_id
			JOIN asset_registration ar ON ar.asset_id = atx.asset_id
			LEFT JOIN windows_table wt ON wt.id = ar.windows_key
			LEFT JOIN office_keys ot ON ot.id = ar.office_key
			LEFT JOIN anti_defection ad ON ad.id = ar.anti_defection_key
			LEFT JOIN registry_keys rk ON rk.id = ar.registry_key
			$whereSQL
			ORDER BY atx.transaction_date DESC
		  ");

		  while ($r = $recent->fetch_assoc()): ?>
			<tr>
			  <td><?= htmlspecialchars($r['full_name']) ?></td>
			  <td><?= htmlspecialchars($r['asset_id']) ?></td>
			  <td><?= htmlspecialchars($r['asset_type']) ?></td>
			  <td><?= htmlspecialchars($r['windows_key'] ?? '—') ?></td>
			  <td><?= htmlspecialchars($r['office_key'] ?? '—') ?></td>
			  <td><?= htmlspecialchars($r['anti_defection_key'] ?? '—') ?></td>
			  <td><?= htmlspecialchars($r['registry_key'] ?? '—') ?></td>
			  <td>
	  <span class="<?= $r['transaction_type'] == 'issue' ? 'issue' : 'return' ?>">
		<?= ucfirst($r['transaction_type']) ?>
	  </span>
	</td>
			  <td><?= date('d M Y, h:i A', strtotime($r['transaction_date'])) ?></td>
			</tr>
		  <?php endwhile; ?>
		</tbody>
	  </table>
	</div>
	</div>
	<!-- Popup -->
	<div id="overlay"></div>
	<div id="popup" style="display: none;"></div>

	<script>
	  const userSelect = document.getElementById('user_id');
	  const typeSelect = document.getElementById('transaction_type');
	  const assetSelect = document.getElementById('asset_id');
	 
	 const submitBtn = document.getElementById('submitBtn');
	  const spinner = document.getElementById('spinner');
	  
	  function updateActive() {
	  const steps = [typeSelect, userSelect, assetSelect]; 
	  const stepElements = [
		document.getElementById('step1'),
		document.getElementById('step2'),
		document.getElementById('step3'),
		document.getElementById('step4')
	  ];

	  // Clear all states
	  stepElements.forEach(el => el.classList.remove('active', 'current'));

	  let firstIncomplete = stepElements.length - 1;

	  steps.forEach((input, index) => {
		if (input.value) {
		  stepElements[index].classList.add('active');
		} else if (firstIncomplete === stepElements.length - 1) {
		  firstIncomplete = index;
		}
	  });

	  stepElements[firstIncomplete].classList.add('current');
	  updateStepHeader();
	}

	function updateStepHeader() {
	  const header = document.getElementById('current-step-header');
	  if (!userSelect.value) {
		header.textContent = "Step 1: Select Transaction Type";
	  } else if (!typeSelect.value) {
		header.textContent = "Step 2: Select a User";
	  } else if (!assetSelect.value) {
		header.textContent = "Step 3: Choose an Asset";
	  } else {
		header.textContent = "Step 4: Review and Submit";
	  }
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
					if (data.length === 0) {
						assetSelect.innerHTML += '<option value="">No assets available for this user and type</option>';
					} else {
						data.forEach(asset => {
							// Prevent issuing already issued assets
							if (asset.status === 'issued') {
								assetSelect.innerHTML += `<option value="${asset.asset_id}" disabled>${asset.asset_id} (${asset.asset_type}) - Already Issued</option>`;
							} else {
								assetSelect.innerHTML += `<option value="${asset.asset_id}">${asset.asset_id} (${asset.asset_type})</option>`;
							}
						});
					}
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

	  function showPopup(message, isError = false) {
	  const popup = document.getElementById("popup");
	  const overlay = document.getElementById("overlay");

	  popup.innerHTML = (isError ? "❌ " : "✅ ") + message;
	  popup.style.background = isError ? "#ffe0e0" : "#e0ffe0";
	  popup.style.borderColor = isError ? "#e74c3c" : "#2ecc71";
	  popup.style.color = isError ? "#c0392b" : "#2e7d32";

	  overlay.style.display = "block";
	  popup.style.display = "block";

	  setTimeout(() => {
		popup.style.opacity = 0;
		overlay.style.opacity = 0;
	  }, 3000);

	  setTimeout(() => {
		popup.style.display = "none";
		overlay.style.display = "none";
		popup.style.opacity = 1;
		overlay.style.opacity = 1;
	  }, 3500);
	}

	<?php if (!empty($popupMessage)): ?>
	  showPopup(`<?= $popupMessage ?>`);
	<?php endif; ?>

	<?php if (!empty($errorPopup)): ?>
	  showPopup(`<?= $errorPopup ?>`, true);
	<?php endif; ?>

	  updateActive();
	  function updateProgress() {
	  let percent = 0;
	  if (userSelect.value) percent += 25;
	  if (typeSelect.value) percent += 25;
	  if (assetSelect.value) percent += 25;
	  if (userSelect.value && typeSelect.value && assetSelect.value) percent += 25;
	  document.getElementById('progressBar').style.width = percent + '%';
	}
	userSelect.addEventListener('change', updateProgress);
	typeSelect.addEventListener('change', updateProgress);
	assetSelect.addEventListener('change', updateProgress);
	updateProgress();

	document.querySelector('.search-bar').addEventListener('input', function () {
	  let filter = this.value.toLowerCase();
	  let table = document.getElementById('transactions-table'); // Make sure the table has the correct ID
	  let rows = table.getElementsByTagName('tr');
	  
	  // Loop through all table rows (skip header row)
	  for (let i = 1; i < rows.length; i++) {
		let row = rows[i];
		let cells = row.getElementsByTagName('td');
		
		let found = false;
		// Check each cell in the row for a match with the search term
		for (let j = 0; j < cells.length; j++) {
		  if (cells[j].textContent.toLowerCase().indexOf(filter) > -1) {
			found = true;
			break; // No need to continue checking other cells if one matches
		  }
		}
		
		// Show or hide row based on whether a match was found
		if (found) {
		  row.style.display = '';
		} else {
		  row.style.display = 'none';
		}
	  }
	});
	function reorderStepsForType(type) {
	  const formSteps = document.getElementById('formSteps');

	  const typeStep = document.getElementById('step1');
	  const userStep = document.getElementById('step2');
	  const assetStep = document.getElementById('step3');
	  const doneStep = document.getElementById('step4');

	  // Always Type → User → Asset → Done
	  formSteps.innerHTML = '';
	  formSteps.appendChild(typeStep);
	  formSteps.appendChild(userStep);
	  formSteps.appendChild(assetStep);
	  formSteps.appendChild(doneStep);

	  updateActive();
	  updateProgress();
	}

	function updateStepNumbers() {
	  const steps = document.querySelectorAll('#formSteps .step');
	  steps.forEach((step, index) => {
		const circle = step.querySelector('.circle');
		if (circle) {
		  circle.textContent = (index === steps.length - 1) ? '✔' : (index + 1);
		}
	  });
	}

	// Auto-fill user when returning
	assetSelect.addEventListener('change', function () {
	  const transactionType = typeSelect.value;
	  if (transactionType === 'return' && assetSelect.value) {
		fetch(`get_user_by_asset_Test_B_1.php?asset_id=${assetSelect.value}`)
		  .then(res => res.json())
		  .then(data => {
			if (data.user_id) {
			  userSelect.value = data.user_id;
			  updateActive();
			  updateProgress();
			}
		  });
	  }
	});

	typeSelect.addEventListener('change', function () {
	  reorderStepsForType(typeSelect.value);
	  loadAssets();
	});

	</script>

	</body>
	</html>
