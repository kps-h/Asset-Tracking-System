<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$conn = mysqli_connect("localhost", "root", "", "track");

// Fetch assets issued to users with 'Sell' status
$result = mysqli_query($conn, "
    SELECT ar.asset_id, ar.asset_type, atx.transaction_date, u.full_name
    FROM asset_registration ar
    JOIN asset_transactions atx ON ar.asset_id = atx.asset_id
    JOIN users u ON atx.user_id = u.user_id
    WHERE u.status = 'Sell' AND atx.transaction_type = 'issue'
    ORDER BY atx.transaction_date DESC
");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Sell Assets</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: #f2f2f2;
            margin: 0;
            display: flex;
            justify-content: center;
            padding: 40px 20px;
        }
        .container {
            max-width: 1000px;
            width: 100%;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }
        h2 {
            color: #3B9C9C;
            text-align: center;
            margin-bottom: 25px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background: #f76c6c;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Sold Assets</h2>
        <table>
            <thead>
                <tr>
                    <th>Asset ID</th>
                    <th>Asset Type</th>
                    <th>Issued To</th>
                    <th>Transaction Date</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['asset_id']) ?></td>
                        <td><?= htmlspecialchars($row['asset_type']) ?></td>
                        <td><?= htmlspecialchars($row['full_name']) ?></td>
                        <td><?= date("d/m/Y h:i A", strtotime($row['transaction_date'])) ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
