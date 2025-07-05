<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "track";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql = "
    SELECT 
        u.full_name,
        ar.asset_id, 
        ar.asset_type, 
        wt.key AS windows_key,
        wt.vendor AS windows_vendor,
        ok.key AS office_key,
        ok.vendor AS office_vendor,
        ad.key AS anti_defection_key,
        rk.key AS registry_key
    FROM 
        asset_transactions at
    JOIN 
        asset_registration ar ON at.asset_id = ar.asset_id
    JOIN 
        users u ON at.user_id = u.user_id
    LEFT JOIN 
        windows_table wt ON ar.windows_key = wt.id
    LEFT JOIN 
        office_keys ok ON ar.office_key = ok.id
    LEFT JOIN 
        anti_defection ad ON ar.anti_defection_key = ad.id
    LEFT JOIN 
        registry_keys rk ON ar.registry_key = rk.id
    WHERE 
        ar.status = 'issued'
";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Issued Assets Report</title>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.dataTables.min.css">
    <style>
    body {
        font-family: 'Segoe UI', sans-serif;
        background-color: #f5f7fa;
        padding: 40px;
    }

    h2 {
        text-align: center;
        margin-bottom: 30px;
        color: #333;
    }

    .container {
        background: #fff;
        padding: 25px 35px;
        border-radius: 12px;
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.06);
    }

    table.dataTable thead {
        background-color: #f76c6c;
        color: white;
    }

    table.dataTable tbody tr:nth-child(odd) {
        background-color: #f0f4f8;
    }

    table.dataTable tbody tr:nth-child(even) {
        background-color: #ffffff;
    }

    .dt-group-header {
        background-color: #e3f2fd !important;
        font-weight: bold;
        color: #0d47a1;
        border-top: 2px solid #90caf9;
    }

    tr.group-separator td {
        border-bottom: 2px solid #1976d2;
        padding: 0 !important;
        margin: 0 !important;
        height: 1px;
        background-color: #1976d2;
    }
	.group-header {
    background-color: #e3f2fd;
    font-weight: bold;
    color: #0d47a1;
    border-top: 4px solid #bbdefb;
    padding-top: 8px;
}

.group-separator td {
    border-bottom: 2px solid #1976d2;
    padding: 0 !important;
    height: 8px;
    background-color: #1976d2;
}

</style>

</head>
<body>
<div class="container">
    <h2>Issued Assets Report</h2>
    <label for="userFilter">Filter by User:</label>
<select id="userFilter">
    <option value="">All Users</option>
    <?php
    // Create a new result set with distinct user names
    $userSql = "SELECT DISTINCT u.full_name FROM asset_transactions at
                JOIN users u ON at.user_id = u.user_id
                JOIN asset_registration ar ON at.asset_id = ar.asset_id
                WHERE ar.status = 'issued'";
    $userResult = $conn->query($userSql);
    if ($userResult && $userResult->num_rows > 0):
        while ($user = $userResult->fetch_assoc()):
    ?>
        <option value="<?= htmlspecialchars($user['full_name']) ?>"><?= htmlspecialchars($user['full_name']) ?></option>
    <?php endwhile; endif; ?>
</select>
<br><br>
    <table id="assetsTable" class="display nowrap" style="width:100%">
        <thead>
        <tr>
            <th>Username</th>
            <th>Asset ID</th>
            <th>Asset Type</th>
            <th>Windows Key</th>
            <th>Windows Vendor</th>
            <th>Office Key</th>
            <th>Office Vendor</th>
            <th>Anti Defection Key</th>
            <th>Registry Key</th>
        </tr>
        </thead>
        <tbody>
        <?php if ($result && $result->num_rows > 0): ?>
            <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['full_name']) ?></td>
                    <td><?= htmlspecialchars($row['asset_id']) ?></td>
                    <td><?= htmlspecialchars($row['asset_type']) ?></td>
                    <td><?= htmlspecialchars($row['windows_key']) ?></td>
                    <td><?= htmlspecialchars($row['windows_vendor']) ?></td>
                    <td><?= htmlspecialchars($row['office_key']) ?></td>
                    <td><?= htmlspecialchars($row['office_vendor']) ?></td>
                    <td><?= htmlspecialchars($row['anti_defection_key']) ?></td>
                    <td><?= htmlspecialchars($row['registry_key']) ?></td>
                </tr>
            <?php endwhile; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- JS dependencies for DataTables + buttons -->
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.colVis.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
<script>
    $(document).ready(function () {
    $('#assetsTable').DataTable({
        responsive: true,
		pageLength: 20,
        dom: 'Bfrtip',
        rowGroup: {
            dataSrc: 0,
            startRender: function (rows, group) {
                return $('<tr/>')
                    .addClass('group-header')
                    .append('<td colspan="9">' + group + '</td>');
            },
            endRender: function () {
                // This row acts as a visual separator after the group
                return $('<tr/>')
                    .addClass('group-separator')
                    .append('<td colspan="9"></td>');
            }
        },
        buttons: [
            'copy',
            {
                extend: 'csv',
                exportOptions: { columns: ':visible' }
            },
            {
                extend: 'excel',
                exportOptions: { columns: ':visible' }
            },
            {
                extend: 'print',
                exportOptions: { columns: ':visible' }
            },
            'colvis'
        ]
    });

});
// Custom dropdown filter for user
    $('#userFilter').on('change', function () {
        let selectedUser = $(this).val();
        let table = $('#assetsTable').DataTable();
        if (selectedUser) {
            table.column(0).search('^' + selectedUser + '$', true, false).draw(); // exact match
        } else {
            table.column(0).search('').draw(); // reset filter
        }
    });

</script>
</body>
</html>

<?php $conn->close(); ?>
