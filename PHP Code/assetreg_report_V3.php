<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Asset Report</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/buttons/2.3.6/css/buttons.dataTables.min.css" rel="stylesheet">
</head>
<body>

<div class="container mt-4">
    <h2>Asset Report</h2>
    <div class="mb-3">
        <button class="btn btn-primary" id="addAsset">Add New Asset</button>
        <button class="btn btn-secondary" id="saveEdits">Edit</button>
        <button class="btn btn-danger" id="deleteSelected">Delete</button>
    </div>

    <table id="assetTable" class="display table table-bordered">
        <thead>
        <tr>
            <th><input type="checkbox" id="selectAll"></th>
            <th>Asset ID</th>
            <th>Asset Type</th>
            <th>Vendor</th>
            <th>Purchase Date</th>
            <th>Windows Key</th>
            <th>Office Key</th>
            <th>Anti Defection Key</th>
            <th>Registry Key</th>
            <th>Remarks</th>
        </tr>
        </thead>
        <tbody>
        <?php
        include 'db.php';
        $query = "SELECT * FROM asset_registration";
        $result = mysqli_query($conn, $query);
        while ($row = mysqli_fetch_assoc($result)) {
            echo "<tr data-id='{$row['id']}'>";
            echo "<td><input type='checkbox' class='row-select'></td>";
            echo "<td class='asset-link' style='cursor:pointer;color:blue;'>{$row['asset_id']}</td>";
            echo "<td>{$row['asset_type']}</td>";
            echo "<td>{$row['vendor']}</td>";
            echo "<td>{$row['purchase_date']}</td>";
            echo "<td>{$row['windows_key']}</td>";
            echo "<td>{$row['office_key']}</td>";
            echo "<td>{$row['anti_defection_key']}</td>";
            echo "<td>{$row['registry_key']}</td>";
            echo "<td>{$row['remarks']}</td>";
            echo "</tr>";
        }
        ?>
        </tbody>
    </table>
</div>

<!-- Transaction History Modal -->
<div class="modal fade" id="transactionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Transaction History</h5>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="transactionHistory"></div>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editAssetModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Asset</h5>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editAssetForm">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="mb-2"><label>Asset ID</label><input class="form-control" id="edit_asset_id" name="asset_id"></div>
                    <div class="mb-2"><label>Asset Type</label><input class="form-control" id="edit_asset_type" name="asset_type"></div>
                    <div class="mb-2"><label>Vendor</label><input class="form-control" id="edit_vendor" name="vendor"></div>
                    <div class="mb-2"><label>Purchase Date</label><input type="date" class="form-control" id="edit_purchase_date" name="purchase_date"></div>
                    <div class="mb-2"><label>Windows Key</label><input class="form-control" id="edit_windows_key" name="windows_key"></div>
                    <div class="mb-2"><label>Office Key</label><input class="form-control" id="edit_office_key" name="office_key"></div>
                    <div class="mb-2"><label>Anti Defection Key</label><input class="form-control" id="edit_anti_defection_key" name="anti_defection_key"></div>
                    <div class="mb-2"><label>Registry Key</label><input class="form-control" id="edit_registry_key" name="registry_key"></div>
                    <div class="mb-2"><label>Remarks</label><textarea class="form-control" id="edit_remarks" name="remarks"></textarea></div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-primary" id="updateAssetBtn">Update</button>
                <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.print.min.js"></script>

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

    $('#saveEdits').click(function () {
        const $selectedRows = $('.row-select:checked').closest('tr');
        if ($selectedRows.length !== 1) {
            alert('Please select exactly one row to edit.');
            return;
        }
        const $row = $selectedRows.first();
        const $tds = $row.find('td');

        $('#edit_id').val($row.data('id'));
        $('#edit_asset_id').val($tds.eq(1).text().trim());
        $('#edit_asset_type').val($tds.eq(2).text().trim());
        $('#edit_vendor').val($tds.eq(3).text().trim());
        $('#edit_purchase_date').val($tds.eq(4).text().trim());
        $('#edit_windows_key').val($tds.eq(5).text().trim());
        $('#edit_office_key').val($tds.eq(6).text().trim());
        $('#edit_anti_defection_key').val($tds.eq(7).text().trim());
        $('#edit_registry_key').val($tds.eq(8).text().trim());
        $('#edit_remarks').val($tds.eq(9).text().trim());

        new bootstrap.Modal('#editAssetModal').show();
    });

    $('#updateAssetBtn').click(function () {
        const formData = $('#editAssetForm').serializeArray();
        const dataObj = {};
        formData.forEach(field => { dataObj[field.name] = field.value; });
        $.post('', { action: 'save_edits', rows: [dataObj] }, function () {
            alert("Changes saved!");
            location.reload();
        });
    });

    $('#addAsset').click(function () {
        window.location.href = "asset_registration.php";
    });

    $(document).on('click', '.asset-link', function () {
        const assetId = $(this).text().trim();
        $.get('get_transactions.php', { asset_id: assetId }, function (data) {
            $('#transactionHistory').html(data);
            new bootstrap.Modal('#transactionModal').show();
        });
    });
});
</script>
</body>
</html>
