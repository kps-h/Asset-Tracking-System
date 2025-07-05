<?php
// Database connection
$conn = new mysqli("localhost", "root", " ", "track");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Filter query for status
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$sql = "SELECT * FROM asset_registration";
if ($status_filter) {
    $sql .= " WHERE status = '" . $conn->real_escape_string($status_filter) . "'";
}

$result = $conn->query($sql);

// Close connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Asset Registration Report</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
</head>
<body class="bg-light">
    <div class="container py-4">
        <h3 class="mb-3">Asset Registration Report</h3>

        <!-- Status Filter -->
        <div class="mb-3">
            <label for="statusFilter" class="form-label">Filter by Status</label>
            <select id="statusFilter" class="form-select">
                <option value="">All</option>
                <option value="issued" <?= isset($_GET['status']) && $_GET['status'] == 'issued' ? 'selected' : '' ?>>Issued</option>
                <option value="free" <?= isset($_GET['status']) && $_GET['status'] == 'free' ? 'selected' : '' ?>>Free</option>
            </select>
        </div>

        <table id="assetTable" class="table table-bordered table-hover">
            <thead class="table-primary">
                <tr>
                    <th>ID</th>
                    <th>Asset ID</th>
                    <th>Asset Type</th>
                    <th>Vendor</th>
                    <th>Purchase Date</th>
                    <th>Status</th>
                    <th>Remarks</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= $row['id'] ?></td>
                        <td contenteditable="true"><?= htmlspecialchars($row['asset_id']) ?></td>
                        <td contenteditable="true"><?= htmlspecialchars($row['asset_type']) ?></td>
                        <td contenteditable="true"><?= htmlspecialchars($row['vendor']) ?></td>
                        <td contenteditable="true"><?= htmlspecialchars($row['purchase_date']) ?></td>
                        <td>
                            <span class="badge bg-<?= $row['status'] == 'issued' ? 'danger' : 'success' ?>"><?= ucfirst($row['status']) ?></span>
                        </td>
                        <td contenteditable="true"><?= nl2br(htmlspecialchars($row['remarks'])) ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <script>
        $(document).ready(function () {
            // Initialize DataTables with export options
            $('#assetTable').DataTable({
                dom: 'Bfrtip',
                buttons: [
                    'csv', 'print'
                ],
                paging: true,
                searching: true,
                ordering: true,
                order: [[0, 'asc']],  // Order by ID (default)
            });

            // Filter status dropdown
            $('#statusFilter').on('change', function() {
                const selectedStatus = $(this).val();
                const url = selectedStatus ? `?status=${selectedStatus}` : '';
                window.location.href = url;
            });

            // Inline editing: when a user edits the content of a cell
            $('td[contenteditable="true"]').on('blur', function() {
                const cell = $(this);
                const row = cell.closest('tr');
                const id = row.find('td:first').text();  // Get the ID from the first column
                const column = cell.index();
                const newValue = cell.text();

                // Here, you can make an AJAX call to save the changes in the database
                // Example: Save the updated value via AJAX
                $.ajax({
                    url: 'Test_B.php', // PHP script to handle update
                    method: 'POST',
                    data: {
                        id: id,
                        column: column,
                        value: newValue
                    },
                    success: function(response) {
                        alert('Updated successfully!');
                    },
                    error: function() {
                        alert('Error updating the record');
                    }
                });
            });
        });
    </script>
</body>
</html>
