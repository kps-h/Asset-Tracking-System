	<?php
	$conn = new mysqli("localhost", "username", " ", "track");
	if ($conn->connect_error) {
		die("Connection failed: " . $conn->connect_error);
	}

	if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'save_edits') {
		$updatedRows = $_POST['rows'];

		foreach ($updatedRows as $row) {
			$windows_key_id = getKeyId($conn, 'windows_table', $row['windows_key']);
			$office_key_id = getKeyId($conn, 'office_keys', $row['office_key']);
			$anti_defection_key_id = getKeyId($conn, 'anti_defection', $row['anti_defection_key']);
			$registry_key_id = getKeyId($conn, 'registry_keys', $row['registry_key']);

			$stmt = $conn->prepare("UPDATE asset_registration SET asset_id=?, asset_type=?, vendor=?, purchase_date=?, windows_key=?, office_key=?, anti_defection_key=?, registry_key=?, remarks=? WHERE id=?");
			$stmt->bind_param(
				"ssssiiiisi",
				$row['asset_id'],
				$row['asset_type'],
				$row['vendor'],
				$row['purchase_date'],
				$windows_key_id,
				$office_key_id,
				$anti_defection_key_id,
				$registry_key_id,
				$row['remarks'],
				$row['id']
			);
			$stmt->execute();
			$stmt->close();
		}

		echo json_encode(['status' => 'success']);
		exit;
	}

	function getKeyId($conn, $table, $keyVal) {
		$stmt = $conn->prepare("SELECT id FROM $table WHERE `key` = ?");
		$stmt->bind_param("s", $keyVal);
		$stmt->execute();
		$stmt->bind_result($id);
		$stmt->fetch();
		$stmt->close();
		return $id ?? 0;
	}

	$sql = "
	SELECT 
		ar.*, 
		wt.key AS windows_key_value, 
		ok.key AS office_key_value, 
		ad.key AS anti_defection_key_value, 
		rk.key AS registry_key_value
	FROM asset_registration ar
	LEFT JOIN windows_table wt ON ar.windows_key = wt.id
	LEFT JOIN office_keys ok ON ar.office_key = ok.id
	LEFT JOIN anti_defection ad ON ar.anti_defection_key = ad.id
	LEFT JOIN registry_keys rk ON ar.registry_key = rk.id
	";
	$result = $conn->query($sql);
	?>
	<!DOCTYPE html>
	<html lang="en">
	<head>
		<meta charset="UTF-8">
		<title>Asset Report</title>
		<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
		<link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
		<link href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css" rel="stylesheet">
		<style>
			thead.custom-red-header th {
				background-color: #f76c6c !important;
				color: white !important;
			}
			td[contenteditable="true"] {
				background-color: #fffbe6;
			}
			td a.asset-link {
				color: #007bff;
				cursor: pointer;
				text-decoration: underline;
			}
			.btn-group > .btn {
				background-color: #565e64;
				color: white;
				border-radius: 0;
			}
			.btn-group > .btn:not(:last-child) {
				border-right: 1px solid #fff !important;
			}
			.btn-group > .btn:not(:first-child) {
				border-left: 1px solid #fff !important;
			}
		</style>
	</head>
	<body class="bg-light">
	<div class="container py-4">
		<div class="d-flex justify-content-between align-items-center mb-3">
			<h3>Asset Registration Report</h3>
			<div class="btn-group">
				<button id="addAsset" class="btn">Add New Asset</button>
				<button id="deleteSelected" class="btn">Delete</button>
				<button class="edit-btn btn btn-sm btn-warning" data-id="<?= $row['id'] ?>">Edit</button>
			</div>
		</div>

		<table id="assetTable" class="table table-bordered table-hover">
			<thead class="custom-red-header">
			<tr>
				<th><input type="checkbox" id="selectAll"></th>
				<th>ID</th>
				<th>Asset ID</th>
				<th>Type</th>
				<th>Vendor</th>
				<th>Purchase Date</th>
				<th>Windows Key</th>
				<th>Office Key</th>
				<th>Anti Defection</th>
				<th>Registry Key</th>
				<th>Remarks</th>
				<th>Status</th>
			</tr>
			</thead>
			<tbody>
			<?php while ($row = $result->fetch_assoc()): ?>
				<tr data-id="<?= $row['id'] ?>">
                    <td><input type="checkbox" class="row-select"></td>
                    <td><?= $row['id'] ?></td>
                    <td contenteditable="true"><a class="asset-link"><?= htmlspecialchars($row['asset_id']) ?></a></td>
					<td contenteditable="true"><?= htmlspecialchars($row['asset_type']) ?></td>
					<td contenteditable="true"><?= htmlspecialchars($row['vendor']) ?></td>
					<td contenteditable="true"><?= htmlspecialchars($row['purchase_date']) ?></td>
					<td contenteditable="true"><?= htmlspecialchars($row['windows_key_value']) ?></td>
					<td contenteditable="true"><?= htmlspecialchars($row['office_key_value']) ?></td>
					<td contenteditable="true"><?= htmlspecialchars($row['anti_defection_key_value']) ?></td>
					<td contenteditable="true"><?= htmlspecialchars($row['registry_key_value']) ?></td>
					<td contenteditable="true"><?= nl2br(htmlspecialchars($row['remarks'])) ?></td>
					<td><span class="badge bg-<?= $row['status'] == 'issued' ? 'danger' : 'success' ?>"><?= ucfirst($row['status']) ?></span></td>
				</tr>
			<?php endwhile; ?>
			</tbody>
		</table>
	</div>

	<!-- Transaction Modal -->
	<div class="modal fade" id="transactionModal" tabindex="-1" aria-hidden="true">
		<div class="modal-dialog modal-dialog-scrollable">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title">Asset Transaction History</h5>
					<button class="btn-close" data-bs-dismiss="modal"></button>
				</div>
				<div class="modal-body" id="transactionHistory">Loading...</div>
			</div>
		</div>
	</div>
<!-- Modal -->
<div id="editModal" class="modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background-color: rgba(0,0,0,0.5); justify-content:center; align-items:center;">
  <div class="modal-content" style="width: 90%; height: 90%; background: white; position: relative;">
    <span onclick="document.getElementById('editModal').style.display='none'" style="position:absolute; top:10px; right:20px; cursor:pointer; font-size:24px;">&times;</span>
    <iframe id="editFrame" style="width: 100%; height: 100%; border: none;"></iframe>
  </div>
</div>
	<!-- Scripts -->
	<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
	<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
	<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
	<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
	<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap5.min.js"></script>
	<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
	<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>

	<script>
	$(document).ready(function () {
		$('#assetTable').DataTable({
			dom: 'Bfrtip',
			buttons: ['csv', 'print'],
			paging: true,
			order: [[1, 'desc']],
		});

		$('#selectAll').on('click', function () {
			$('.row-select').prop('checked', this.checked);
		});

		$('#deleteSelected').click(function () {
			const selected = $('.row-select:checked').map(function () {
				return $(this).closest('tr').data('id');
			}).get();

			if (selected.length === 0 || !confirm("Are you sure to delete selected rows?")) return;

			$.post('delete_assets.php', { ids: selected }, function () {
				location.reload();
			});
		});

		$('#addAsset').click(function () {
			window.location.href = "asset_registration_V2.php";
		});

		$(document).on('click', '.asset-link', function () {
			const assetId = $(this).text().trim();
			$.get('get_transactions.php', { asset_id: assetId }, function (data) {
				$('#transactionHistory').html(data);
				new bootstrap.Modal('#transactionModal').show();
			});
		});
	});
	
  document.querySelectorAll('.edit-btn').forEach(button => {
    button.addEventListener('click', function () {
      const id = this.dataset.id;
      document.getElementById('editFrame').src = 'asset_registration_V2.php?id=' + id;
      document.getElementById('editModal').style.display = 'flex';
    });
  });
  
	</script>
	</body>
	</html>
