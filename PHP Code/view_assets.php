<?php
// Database connection
$conn = new mysqli("localhost", "username", "password", "track");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch asset data
// Fetch filtered asset data for report (matching $totalAssets logic)
$sql = "
    SELECT ar.asset_id, ar.asset_type, ar.status, ar.created_at
    FROM asset_registration ar
    WHERE ar.status = 'free'
    
    UNION

    SELECT ar.asset_id, ar.asset_type, ar.status, ar.created_at
    FROM asset_registration ar
    JOIN (
        SELECT asset_id, MAX(transaction_date) AS latest_date
        FROM asset_transactions
        WHERE transaction_type = 'issue'
        GROUP BY asset_id
    ) latest_issue ON ar.asset_id = latest_issue.asset_id
    JOIN asset_transactions atx ON atx.asset_id = latest_issue.asset_id AND atx.transaction_date = latest_issue.latest_date
    JOIN users u ON u.user_id = atx.user_id
    WHERE ar.status = 'issued'
      AND u.status NOT IN ('Sell', 'Dispose')
";

$result = $conn->query($sql);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Asset Report</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css" rel="stylesheet">
</head>
<style>
    thead.custom-red-header th {
        background-color: #f76c6c !important;
        color: white !important;
    }
</style>
<body class="bg-light">
<div class="container py-5">
    <h2 class="mb-4">Asset Report</h2>
    <table id="assetTable" class="table table-bordered table-striped">
        <thead class="custom-red-header">
            <tr>
                <th>Asset ID</th>
                <th>Asset Type</th>
                <th>Status</th>
                <th>Created At</th>
            </tr>
        </thead>
        <tbody>
        <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($row['asset_id']) ?></td>
                <td><?= htmlspecialchars($row['asset_type']) ?></td>
                <td><?= ucfirst($row['status']) ?></td>
                <td><?= date("d/m/Y h:i A", strtotime($row['created_at'])) ?></td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>

<!-- JS includes -->
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>

<script>
$(document).ready(function () {
    $('#assetTable').DataTable({
        dom: 'Bfrtip',
        buttons: ['csv', 'excel'],
        pageLength: 10,
        order: [[0, 'asc']],
        columnDefs: [
            { targets: 0, searchable: true },  // Asset ID searchable
            { targets: 1, searchable: true }   // Asset Type searchable
        ]
    });
});
</script>
</body>
</html>
