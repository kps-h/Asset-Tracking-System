<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "track";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialize filter variable
$filter = isset($_POST['userFilter']) ? $_POST['userFilter'] : '';

// Build SQL with subquery to get the latest issued transaction per asset
$sql = "
    SELECT 
    u.full_name,
    sn.seat_number,
    ar.asset_id, 
    ar.asset_type, 
    wt.key AS windows_key,
    wt.vendor AS windows_vendor,
    ok.key AS office_key,
    ok.vendor AS office_vendor,
    ad.key AS anti_defection_key,
    rk.key AS registry_key
FROM asset_transactions at
INNER JOIN (
    SELECT asset_id, MAX(transaction_date) AS latest_time
    FROM asset_transactions
    WHERE transaction_type = 'issue'
    GROUP BY asset_id
) latest ON at.asset_id = latest.asset_id AND at.transaction_date = latest.latest_time
JOIN asset_registration ar ON at.asset_id = ar.asset_id
JOIN users u ON at.user_id = u.user_id
LEFT JOIN seat_number sn ON u.seat_id = sn.id
LEFT JOIN windows_table wt ON ar.windows_key = wt.id
LEFT JOIN office_keys ok ON ar.office_key = ok.id
LEFT JOIN anti_defection ad ON ar.anti_defection_key = ad.id
LEFT JOIN registry_keys rk ON ar.registry_key = rk.id
WHERE ar.status = 'issued'
  AND u.status NOT IN ('Sell', 'Dispose')
";

if ($filter) {
    $sql .= " AND u.full_name = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $filter);
} else {
    $stmt = $conn->prepare($sql);
}

if (!$stmt) {
    die("SQL Prepare Error: " . $conn->error);
}

$stmt->execute();
$result = $stmt->get_result();

// Fetch users for dropdown filter
$userSql = "SELECT DISTINCT u.full_name 
            FROM asset_transactions at
            JOIN users u ON at.user_id = u.user_id
            JOIN asset_registration ar ON at.asset_id = ar.asset_id
            WHERE ar.status = 'issued'
              AND u.status NOT IN ('Sell', 'Dispose')";

$userResult = $conn->query($userSql);

// Grouping the assets by users
$assetsGroupedBySeat = [];

while ($row = $result->fetch_assoc()) {
    $seatNumber = $row['seat_number'];
    $fullName = $row['full_name'];
    $groupKey = "Seat No: $seatNumber ($fullName)";

    if (!isset($assetsGroupedBySeat[$groupKey])) {
        $assetsGroupedBySeat[$groupKey] = [];
    }

    $assetsGroupedBySeat[$groupKey][] = $row;
}
uksort($assetsGroupedBySeat, function ($a, $b) {
    preg_match('/Seat No: (\d+)/', $a, $matchesA);
    preg_match('/Seat No: (\d+)/', $b, $matchesB);
    $seatA = isset($matchesA[1]) ? (int)$matchesA[1] : PHP_INT_MAX;
    $seatB = isset($matchesB[1]) ? (int)$matchesB[1] : PHP_INT_MAX;
    return $seatA <=> $seatB;
});

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assigned Assets Report</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.1/css/jquery.dataTables.min.css">
	<!-- DataTables CSS -->
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.1/css/jquery.dataTables.min.css">

<!-- DataTables Buttons CSS -->
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/buttons/2.3.3/css/buttons.dataTables.min.css">

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- DataTables JS -->
<script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.13.1/js/jquery.dataTables.min.js"></script>

<!-- DataTables Buttons JS -->
<script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/buttons/2.3.3/js/dataTables.buttons.min.js"></script>

<!-- JSZip (required for Excel export) -->
<script type="text/javascript" charset="utf8" src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>

<!-- pdfMake (required for PDF export) -->
<script type="text/javascript" charset="utf8" src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script type="text/javascript" charset="utf8" src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>

<!-- Buttons HTML5 export (CSV, Excel, PDF) -->
<script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/buttons/2.3.3/js/buttons.html5.min.js"></script>

<!-- Buttons Print -->
<script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/buttons/2.3.3/js/buttons.print.min.js"></script>

    <style>
        body {
            background-color: #f4f6f9;
            font-family: 'Arial', sans-serif;
        }

        .container {
            background-color: #ffffff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        h1 {
            color: #f76c6c;
            font-size: 2.5rem;
            margin-bottom: 30px;
        }

        .filter-form {
            background-color: #fafafa;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }

        .form-label {
            font-weight: bold;
            color: #333;
        }

        .btn-primary {
            background-color: #4e73df;
            border-color: #4e73df;
        }

        .btn-primary:hover {
            background-color: #357abd;
            border-color: #357abd;
        }

        .btn-secondary {
            background-color: #4e73df;
            color: white;
            border: 1px solid #ccc;
        }

        .btn-secondary:hover {
            background-color: #357abd;
        }

        .user-group {
            margin-top: 45px;
            border-top: 2px solid #4e73df;
            padding-top: 20px;
        }

        .user-title {
            background-color: #f76c6c;
            color: white;
            padding: 15px;
            font-size: 18px;
            font-weight: bold;
            border-radius: 8px;
        }

        .table th {
            background-color: #f1f1f1;
            color: #333;
        }

        .table-striped tbody tr:nth-child(odd) {
            background-color: #f9f9f9;
        }

        .table-bordered td,
        .table-bordered th {
            border: 1px solid #ddd;
        }

        .asset-row:hover {
            background-color: #f2f8ff;
        }

        @media print {
            body * {
                visibility: hidden;
            }
            .printable-area, .printable-area * {
                visibility: visible;
            }
            .printable-area {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
                margin: 0;
            }
        }
    </style>
</head>
<body>

<div class="container mt-4">
    <!-- Filter Form -->
    <form method="POST" class="mb-4">
        <div class="row">
            <div class="col-md-3">
                <label for="userFilter" class="form-label">Filter by User:</label>
                <select class="form-select" id="userFilter" name="userFilter" onchange="this.form.submit()">
                    <option value="">All Users</option>
                    <?php while ($user = $userResult->fetch_assoc()): ?>
                        <option value="<?= $user['full_name'] ?>" <?= $user['full_name'] == $filter ? 'selected' : '' ?>>
                            <?= $user['full_name'] ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
			
        </div>
    </form>
    <div class="text-end mb-3">
    <button class="btn btn-secondary" onclick="window.print()">🖨️ Print Report</button>
</div>
<div class="printable-area">
    <!-- Grouped Assets by Assigned User -->
	<h1 class="text-center mb-4">Issued Assets Report</h1>
    <div class="user-group">
        <?php foreach ($assetsGroupedBySeat as $seatGroup => $assets): ?>
          <div class="user-title"><?= htmlspecialchars($seatGroup) ?></div>
            <table class="table table-bordered table-striped" id="assetsTable_<?= $userName ?>">
                <thead>
                    <tr>
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
                    <?php foreach ($assets as $asset): ?>
                        <tr>
                            <td><?= htmlspecialchars($asset['asset_id']) ?></td>
                            <td><?= htmlspecialchars($asset['asset_type']) ?></td>
                            <td><?= htmlspecialchars($asset['windows_key']) ?></td>
                            <td><?= htmlspecialchars($asset['windows_vendor']) ?></td>
                            <td><?= htmlspecialchars($asset['office_key']) ?></td>
                            <td><?= htmlspecialchars($asset['office_vendor']) ?></td>
                            <td><?= htmlspecialchars($asset['anti_defection_key']) ?></td>
                            <td><?= htmlspecialchars($asset['registry_key']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endforeach; ?>
    </div>
</div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.1/js/jquery.dataTables.min.js"></script>
<script>
    $(document).ready(function() {
    var table = $('#assetsTable').DataTable({
        dom: 'Bfrtip', // Position of the buttons
        buttons: [
            {
                extend: 'csv',
                text: 'Export CSV',
                exportOptions: {
                    modifier: {
                        search: 'applied' // Only export filtered data
                    }
                }
            },
            {
                extend: 'pdf',
                text: 'Export PDF',
                exportOptions: {
                    modifier: {
                        search: 'applied' // Only export filtered data
                    }
                }
            },
            {
                extend: 'print',
                text: 'Print',
                exportOptions: {
                    modifier: {
                        search: 'applied' // Only print filtered data
                    }
                }
            }
        ]
    });
});
</script>

</body>
</html>

<?php $conn->close(); ?>
