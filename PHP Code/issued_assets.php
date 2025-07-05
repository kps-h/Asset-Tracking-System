<?php
// Start session
session_start();

// Check if the user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// Connect to the database
$conn = mysqli_connect("localhost", "root", "", "track");

// Fetch asset data along with related key data from the database
$query = "
    SELECT ar.id, ar.asset_id, ar.asset_type, ar.vendor, ar.purchase_date, ar.remarks, ar.status, ar.created_at,
           wt.key AS windows_key, wt.original_or_pirated AS windows_status, wt.validity AS windows_validity,
           ot.key AS office_key, ot.original_or_pirated AS office_status, ot.validity AS office_validity,
           at.key AS anti_defection_key, at.validity AS anti_defection_validity,
           rt.key AS registry_key
    FROM asset_registration ar
    JOIN (
        SELECT asset_id, MAX(transaction_date) AS latest_date
        FROM asset_transactions
        WHERE transaction_type = 'issue'
        GROUP BY asset_id
    ) latest_issue ON ar.asset_id = latest_issue.asset_id
    JOIN asset_transactions atx ON atx.asset_id = latest_issue.asset_id AND atx.transaction_date = latest_issue.latest_date
    JOIN users u ON u.user_id = atx.user_id
    LEFT JOIN windows_table wt ON wt.id = ar.windows_key
    LEFT JOIN office_keys ot ON ot.id = ar.office_key
    LEFT JOIN anti_defection at ON at.id = ar.anti_defection_key
    LEFT JOIN registry_keys rt ON rt.id = ar.registry_key
    WHERE ar.status = 'issued'
      AND u.status NOT IN ('Sell', 'Dispose')
    ORDER BY ar.created_at DESC
";


// Execute the query
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Asset Registration Report</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- DataTables CSS & JS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.5/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css">
    
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.5/js/dataTables.bootstrap5.min.js"></script>

    <!-- DataTables Export Buttons -->
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>

</head>
<body>
    <div class="container my-5">      
        <div class="table-responsive">
            <table id="assetTable" class="table table-striped table-bordered">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Asset ID</th>
                        <th>Type</th>
                        <th>Vendor</th>
                        <th>Purchase Date</th>
                        <th>Windows Key</th>
                        <th>Office Key</th>
                        <th>Anti-Defection Key</th>
                        <th>Registry Key</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <td><?= $row['id'] ?></td>
                            <td><?= htmlspecialchars($row['asset_id']) ?></td>
                            <td><?= htmlspecialchars($row['asset_type']) ?></td>
                            <td><?= htmlspecialchars($row['vendor']) ?></td>
                            <!-- If purchase_date is empty or NULL, leave it blank -->
                            <td><?= $row['purchase_date'] ? $row['purchase_date'] : '' ?></td>
                            <!-- If windows_key is empty or NULL, leave it blank -->
                            <td><?= $row['windows_key'] ? htmlspecialchars($row['windows_key']) : '' ?></td>
                            <!-- If office_key is empty or NULL, leave it blank -->
                            <td><?= $row['office_key'] ? htmlspecialchars($row['office_key']) : '' ?></td>
                            <!-- If anti_defection_key is empty or NULL, leave it blank -->
                            <td><?= $row['anti_defection_key'] ? htmlspecialchars($row['anti_defection_key']) : '' ?></td>
                            <!-- If registry_key is empty or NULL, leave it blank -->
                            <td><?= $row['registry_key'] ? htmlspecialchars($row['registry_key']) : '' ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Initialize DataTable -->
    <script>
    $(document).ready(function() {
        $('#assetTable').DataTable({
            dom: 'Bfrtip',
            buttons: [
                'copyHtml5', 'excelHtml5', 'csvHtml5', 'pdfHtml5'
            ],
            pageLength: 10,
            lengthMenu: [ [10, 25, 50, -1], [10, 25, 50, "All"] ],
            responsive: true
        });
    });
    </script>

</body>
</html>
