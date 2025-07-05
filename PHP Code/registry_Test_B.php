<?php
$conn = new mysqli("localhost", "root", "", "track");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_ids'])) {
    // Sanitize and prepare the IDs
    $ids = array_map('intval', $_POST['delete_ids']);
    $idList = implode(',', $ids);

    // Step 1: Unassign from any table (e.g., asset_registration) where registry_key matches
    $conn->query("UPDATE asset_registration SET registry_key = NULL WHERE registry_key IN ($idList)");

    // Step 2: Delete from registry_keys
    $conn->query("DELETE FROM registry_keys WHERE id IN ($idList)");

    // Return response
    exit(json_encode(['status' => 'success']));
}


// Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    $stmt = $conn->prepare("UPDATE registry_keys SET `key`=?, remarks=? WHERE id=?");
    $stmt->bind_param("ssi", $_POST['key'], $_POST['remarks'], $_POST['id']);
    $stmt->execute();
    exit(json_encode(['status' => 'updated']));
}

// Fetch
$result = $conn->query("SELECT * FROM registry_keys");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Registry Key Report</title>
    <link href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4">
    <h2>Registry Key License Report</h2>

    <button class="btn btn-danger mb-2" id="deleteSelected">Delete Selected</button>
    <a href="registry_key.php" class="btn btn-success mb-2">Add New Entry</a>

    <table id="registryKeysTable" class="table table-bordered table-striped">
        <thead>
        <tr>
            <th><input type="checkbox" id="selectAll"></th>
            <th>ID</th>
            <th>Key</th>
            <th>Remarks</th>
            <th>Actions</th>
        </tr>
        </thead>
        <tbody>
        <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><input type="checkbox" class="row-check" value="<?= $row['id'] ?>"></td>
                <td><?= $row['id'] ?></td>
                <td><?= htmlspecialchars($row['key']) ?></td>
                <td><?= htmlspecialchars($row['remarks']) ?></td>
                <td><button class="btn btn-warning btn-sm editBtn" data-row='<?= json_encode($row) ?>'>Edit</button></td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
  <div class="modal-dialog">
    <form class="modal-content" id="editForm">
      <div class="modal-header">
        <h5 class="modal-title">Edit Registry Key License</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="id" id="edit-id">
        <input type="hidden" name="update" value="1">
        <div class="mb-2"><label>Key</label><input class="form-control" name="key" id="edit-key"></div>
        <div class="mb-2"><label>Remarks</label><textarea class="form-control" name="remarks" id="edit-remarks"></textarea></div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-primary" type="submit">Save</button>
      </div>
    </form>
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
    const table = $('#registryKeysTable').DataTable({
        dom: 'Bfrtip',
        buttons: [
            { extend: 'csv', exportOptions: { columns: [2, 3] }},
            { extend: 'excel', exportOptions: { columns: [2, 3] }},
            { extend: 'print', exportOptions: { columns: [2, 3] }}
        ]
    });

    $('#selectAll').on('click', function () {
        $('.row-check').prop('checked', this.checked);
    });

    $('#registryKeysTable').on('click', '.editBtn', function () {
        const row = JSON.parse($(this).attr('data-row'));
        $('#edit-id').val(row.id);
        $('#edit-key').val(row.key);
        $('#edit-remarks').val(row.remarks);
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

        const ids = selected.map(function () {
            return $(this).val();
        }).get();

        if (confirm('Are you sure you want to delete selected records?')) {
            $.post('', { delete_ids: ids }, function (res) {
                if (res.status === 'success') {
                    location.reload();
                } else {
                    alert('Deletion failed.');
                }
            }, 'json');
        }
    });
});
</script>
</body>
</html>
