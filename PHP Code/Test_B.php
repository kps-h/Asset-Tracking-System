<?php
// DB CONNECTION
$conn = new mysqli("localhost", "root", " ", "track");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$typeCounts = [];

$typeSql = "
    SELECT ar.asset_type, COUNT(*) AS total
    FROM asset_registration ar
    LEFT JOIN (
        SELECT at.asset_id, u.status
        FROM asset_transactions at
        JOIN (
            SELECT asset_id, MAX(transaction_date) AS latest_date
            FROM asset_transactions
            WHERE transaction_type = 'issue'
            GROUP BY asset_id
        ) latest ON latest.asset_id = at.asset_id AND latest.latest_date = at.transaction_date
        JOIN users u ON u.user_id = at.user_id
        WHERE at.transaction_type = 'issue'
    ) latest_tx ON ar.asset_id = latest_tx.asset_id
    WHERE IFNULL(latest_tx.status, 'Active') NOT IN ('Sell', 'Dispose')
    GROUP BY ar.asset_type
";

$typeResult = $conn->query($typeSql);

while ($row = $typeResult->fetch_assoc()) {
    $typeCounts[$row['asset_type']] = $row['total'];
}

// Delete selected rows (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_ids'])) {
    $ids = implode(',', array_map('intval', $_POST['delete_ids']));
    $conn->query("DELETE FROM asset_registration WHERE id IN ($ids)");
    exit(json_encode(['status' => 'success']));
}

// Update row (AJAX edit via modal)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    $stmt = $conn->prepare("UPDATE asset_registration SET asset_id=?, asset_type=?, vendor=?, purchase_date=?, remarks=? WHERE id=?");
    $stmt->bind_param("sssssi", $_POST['asset_id'], $_POST['asset_type'], $_POST['vendor'], $_POST['purchase_date'], $_POST['remarks'], $_POST['id']);
    $stmt->execute();
    exit(json_encode(['status' => 'updated']));
}

// Fetch data
$status_filter = $_GET['status'] ?? '';
$sql = "
SELECT 
    ar.*, 
    wt.key AS windows_key_value, 
    ok.key AS office_key_value, 
    ad.key AS anti_defection_key_value, 
    rk.key AS registry_key_value,
    u.full_name AS assigned_user
FROM asset_registration ar
LEFT JOIN windows_table wt ON ar.windows_key = wt.id
LEFT JOIN office_keys ok ON ar.office_key = ok.id
LEFT JOIN anti_defection ad ON ar.anti_defection_key = ad.id
LEFT JOIN registry_keys rk ON ar.registry_key = rk.id
LEFT JOIN (
    SELECT at1.asset_id, at1.user_id
    FROM asset_transactions at1
    INNER JOIN (
        SELECT asset_id, MAX(transaction_date) AS max_date
        FROM asset_transactions
        WHERE transaction_type = 'issue'
        GROUP BY asset_id
    ) latest ON at1.asset_id = latest.asset_id AND at1.transaction_date = latest.max_date
    WHERE at1.transaction_type = 'issue'
) latest_issue ON ar.asset_id = latest_issue.asset_id
LEFT JOIN users u ON latest_issue.user_id = u.user_id
" . ($status_filter ? " WHERE ar.status='$status_filter'" : "");


$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Asset Registration Report</title>
    <link href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .modal-dialog { max-width: 600px; }
		table.dataTable {
  width: 100%;
  max-width: none;
  margin: 0 auto;
  border-collapse: collapse;
  border-spacing: 0;
}
.asset-cards-wrapper {
  display: flex;
  flex-wrap: nowrap;
  overflow-x: hidden;
 overflow-y: hidden;
  gap: 1rem;
  margin-top: 1.5rem;
  margin-bottom: 1.5rem;
  padding-left:370px;
  text-align: center;
}

.asset-cards-wrapper .card {
  min-width: 180px;
  max-height: 88px;
  background-color: #f76c6c ;
  opacity: 0.9;
}
.asset-cards-wrapper .card-title,
.asset-cards-wrapper .card-text {
  color: #ffffff; 
  opacity: 2.0;
}
    </style>
</head>
<body class="bg-light">
<div class="container-fluid py-4 px-3">
    <h2>Asset Registration Report</h2>

    <!-- Filter -->
    <div class="mb-3">
        <label for="statusFilter">Filter by Status:</label>
        <select id="statusFilter" class="form-select w-25">
            <option value="">All</option>
            <option value="issued" <?= $status_filter === 'issued' ? 'selected' : '' ?>>Issued</option>
            <option value="free" <?= $status_filter === 'free' ? 'selected' : '' ?>>Free</option>
        </select>
    </div>

    <button class="btn btn-danger mb-2" id="deleteSelected">Delete Selected</button>
	<a href="asset_registration_V2.php" class="btn btn-success mb-2">Add New Asset</a>

    <?php if (!empty($typeCounts)): ?>
    <div class="asset-cards-wrapper">
        <?php foreach ($typeCounts as $type => $count): ?>
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title text-uppercase"><?= htmlspecialchars($type) ?></h5>
                    <p class="card-text fs-4"><?= $count ?> </p>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
    <div class="table-responsive">
    <table id="assetTable" class="table table-bordered table-striped">
        <thead>
        <tr>
            <th><input type="checkbox" id="selectAll"></th>
            <th>ID</th>
            <th>Asset ID</th>
            <th>Asset Type</th>
            <th>Vendor</th>
            <th>Purchase Date</th>
			<th>Windows Key</th>
            <th>Office Key</th>
            <th>Anti Defection Key</th>
            <th>Registry Key</th>
            <th>Status</th>
			<th>Assigned To</th>
            <th>Remarks</th>
            <th>Actions</th>
        </tr>
        </thead>
        <tbody>
        <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><input type="checkbox" class="row-check" value="<?= $row['id'] ?>"></td>
                <td><?= $row['id'] ?></td>
                <td><?= htmlspecialchars($row['asset_id']) ?></td>
                <td><?= htmlspecialchars($row['asset_type']) ?></td>
                <td><?= htmlspecialchars($row['vendor']) ?></td>
                <td><?= htmlspecialchars($row['purchase_date']) ?></td>
				<td><?= htmlspecialchars($row['windows_key_value']) ?></td>
                <td><?= htmlspecialchars($row['office_key_value']) ?></td>
                <td><?= htmlspecialchars($row['anti_defection_key_value']) ?></td>
                <td><?= htmlspecialchars($row['registry_key_value']) ?></td>
                <td><span class="badge bg-<?= $row['status'] === 'issued' ? 'danger' : 'success' ?>"><?= ucfirst($row['status']) ?></span></td>
				<td><?= $row['status'] === 'issued' ? htmlspecialchars($row['assigned_user']) : '-' ?></td>
                <td><?= htmlspecialchars($row['remarks']) ?></td>
                <td>
                    <button class="btn btn-sm btn-warning editBtn" data-row='<?= json_encode($row) ?>'>Edit</button>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
	</div>
</div>

<!-- Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
  <div class="modal-dialog">
    <form class="modal-content" id="editForm">
      <div class="modal-header">
        <h5 class="modal-title">Edit Asset</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="id" id="edit-id">
        <input type="hidden" name="update" value="1">
        <div class="mb-2"><label>Asset ID</label><input class="form-control" name="asset_id" id="edit-asset_id"></div>
        <div class="mb-2"><label>Asset Type</label><input class="form-control" name="asset_type" id="edit-asset_type"></div>
        <div class="mb-2"><label>Vendor</label><input class="form-control" name="vendor" id="edit-vendor"></div>
        <div class="mb-2"><label>Purchase Date</label><input class="form-control" type="date" name="purchase_date" id="edit-purchase_date"></div>
        <div class="mb-2"><label>Remarks</label><textarea class="form-control" name="remarks" id="edit-remarks"></textarea></div>
        <div class="mb-2">
  <label>Status</label>
  <input type="text" class="form-control" name="status" id="edit-status" readonly>
</div>

      </div>
      <div class="modal-footer">
        <button class="btn btn-primary" type="submit">Save</button>
      </div>
    </form>
  </div>
</div>
<!-- Delete Restriction Modal -->
<div class="modal fade" id="deleteWarningModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Action Not Allowed</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p>Issued assets cannot be deleted.</p>
      </div>
      <div class="modal-footer">
        <button class="btn btn-danger" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>

<script>
$(document).ready(function () {
    const table = $('#assetTable').DataTable({
        dom: 'Bfrtip',
        buttons: [
    'csv',
    'excel',
    {
        extend: 'print',
        exportOptions: {
            columns: [ 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12] // Column indexes to include
        },
        title: 'Asset Registration Report'
    }
]

    });

    $('#selectAll').on('click', function () {
        $('.row-check').prop('checked', this.checked);
    });

    $('#statusFilter').change(function () {
        window.location.href = '?status=' + $(this).val();
    });

     $('#assetTable').on('click', '.editBtn', function () {
        const row = JSON.parse($(this).attr('data-row'));
        $('#edit-id').val(row.id);
        $('#edit-asset_id').val(row.asset_id);
        $('#edit-asset_type').val(row.asset_type);
        $('#edit-vendor').val(row.vendor);
        $('#edit-purchase_date').val(row.purchase_date);
        $('#edit-remarks').val(row.remarks);
        $('#edit-status').val(row.status);
        new bootstrap.Modal(document.getElementById('editModal')).show();
    });

    $('#editForm').submit(function (e) {
        e.preventDefault();
        $.post('', $(this).serialize(), function (res) {
            if (res.status === 'updated') {
                location.reload();
            }
        }, 'json');
    });

    $('#deleteSelected').click(function () {
    const selected = $('.row-check:checked');

    if (selected.length === 0) {
        alert('Please select at least one row.');
        return;
    }

    let hasIssued = false;
    const ids = [];

    selected.each(function () {
        const row = $(this).closest('tr');
        const statusText = row.find('td:nth-child(11) span').text().trim().toLowerCase();

        if (statusText === 'issued') {
            hasIssued = true;
        } else {
            ids.push($(this).val());
        }
    });

    if (hasIssued) {
        new bootstrap.Modal(document.getElementById('deleteWarningModal')).show();
        return;
    }

    if (confirm('Are you sure you want to delete selected free assets?')) {
        $.post('', { delete_ids: ids }, function () {
            location.reload();
        }, 'json');
    }
});
});
</script>
</body>
</html>
