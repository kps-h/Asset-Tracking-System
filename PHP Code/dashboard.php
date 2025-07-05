<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$conn = mysqli_connect("localhost", "root", "", "track");

$issuedAssets = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) AS total
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
"))['total'];

// Free assets (available for use)
$freeAssets = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) as total FROM asset_registration WHERE status = 'free'
"))['total'];

$totalAssets = $issuedAssets + $freeAssets;

$totalUsers = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) as total 
    FROM users 
    WHERE status = 'Active'
"))['total'];

// Chart Data
$chartQuery = mysqli_query($conn, "
    SELECT 
        MONTH(latest_issue.transaction_date) AS month,
        COUNT(DISTINCT latest_issue.asset_id) AS issued
    FROM (
        SELECT asset_id, MAX(transaction_date) AS transaction_date
        FROM asset_transactions
        WHERE transaction_type = 'issue'
        GROUP BY asset_id
    ) latest
    JOIN asset_transactions latest_issue 
        ON latest.asset_id = latest_issue.asset_id 
        AND latest.transaction_date = latest_issue.transaction_date
    JOIN asset_registration ar ON ar.asset_id = latest_issue.asset_id
    WHERE ar.status = 'issued' AND YEAR(latest_issue.transaction_date) = YEAR(CURDATE())
    GROUP BY MONTH(latest_issue.transaction_date)
    ORDER BY MONTH(latest_issue.transaction_date)
");
$returnQuery = mysqli_query($conn, "
    SELECT 
        MONTH(transaction_date) as month,
        COUNT(*) as returned
    FROM asset_transactions
    WHERE transaction_type = 'return' 
      AND YEAR(transaction_date) = YEAR(CURDATE())
    GROUP BY MONTH(transaction_date)
");
$issuedByMonth = [];
while ($row = mysqli_fetch_assoc($chartQuery)) {
    $month = (int)$row['month'];
    $issuedByMonth[$month] = $row['issued'];
}

$returnedByMonth = [];
while ($row = mysqli_fetch_assoc($returnQuery)) {
    $month = (int)$row['month'];
    $returnedByMonth[$month] = $row['returned'];
}
$months = $issuedData = $returnedData = [];
for ($m = 1; $m <= 12; $m++) {
    $months[] = date('M', mktime(0, 0, 0, $m, 10));
    $issuedData[] = $issuedByMonth[$m] ?? 0;
    $returnedData[] = $returnedByMonth[$m] ?? 0;
}

// Recent Transactions (last 5 assets added)
$recent = mysqli_query($conn, "
    SELECT asset_id, asset_type, status, created_at 
    FROM asset_registration 
    ORDER BY created_at DESC 
    LIMIT 10
");

// Asset Type Distribution for Pie Chart
$assetTypeQuery = mysqli_query($conn, "
    SELECT ar.asset_type, COUNT(*) AS count
    FROM asset_registration ar
    LEFT JOIN (
        SELECT at.asset_id, u.status AS user_status
        FROM asset_transactions at
        JOIN (
            SELECT asset_id, MAX(transaction_date) AS latest_date
            FROM asset_transactions
            WHERE transaction_type = 'issue'
            GROUP BY asset_id
        ) latest ON at.asset_id = latest.asset_id AND at.transaction_date = latest.latest_date
        JOIN users u ON at.user_id = u.user_id
        WHERE at.transaction_type = 'issue'
    ) issued_users ON ar.asset_id = issued_users.asset_id
    WHERE 
        (ar.status = 'free') OR 
        (ar.status = 'issued' AND issued_users.user_status NOT IN ('Sell', 'Dispose'))
    GROUP BY ar.asset_type
");

$assetTypes = $assetCounts = [];
while ($row = mysqli_fetch_assoc($assetTypeQuery)) {
    $assetTypes[] = $row['asset_type'];
    $assetCounts[] = $row['count'];
}
// Assets given to users with status 'Sell'
$sellAssets = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) as total 
    FROM asset_registration ar
    JOIN asset_transactions atx ON ar.asset_id = atx.asset_id
    JOIN users u ON atx.user_id = u.user_id
    WHERE u.status = 'Sell' AND atx.transaction_type = 'issue'
"))['total'];
// Assets given to users with status 'Dispose'
$disposeAssets = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) as total 
    FROM asset_registration ar
    JOIN asset_transactions atx ON ar.asset_id = atx.asset_id
    JOIN users u ON atx.user_id = u.user_id
    WHERE u.status = 'Dispose' AND atx.transaction_type = 'issue'
"))['total'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: #f2f7f7;
            margin: 0;
        }
        .header {
            background-color: ;
            color: #f76c6c;
            padding: 20px 30px;
            text-align: center;
            font-size: 27px;
			font-weight: bold;        }
        .container {
            max-width: 1600px;
            margin: 30px auto;
            padding: 0 20px;
        }
        .card-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
        }
        .card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.07);
            transition: 0.3s transform ease;
            text-align: center;
            text-decoration: none;
            color: inherit;
        }
        .card:hover {
            transform: translateY(-5px);
        }
        .card h3 {
            color: #3B9C9C;
            margin-bottom: 10px;
        }
        .card p {
            font-size: 24px;
            font-weight: bold;
            color: #f76c6c;
            margin: 0;
        }
        .section {
            margin-top: 50px;
        }
        .logout-btn {
            display: inline-block;
            margin-top: 40px;
            padding: 10px 20px;
            background: #f76c6c;
            color: white;
            text-decoration: none;
            border-radius: 6px;
        }
        .logout-btn:hover {
            background: #e65c5c;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: white;
            box-shadow: 0 0 5px rgba(0,0,0,0.05);
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        th {
            background-color: #3B9C9C;
            color: white;
        }
		.horizontal-wrap {
    display: flex;
    gap: 30px;
    align-items: flex-start;
    flex-wrap: nowrap; /* Don't wrap by default */
}

@media (max-width: 900px) {
    .horizontal-wrap {
        flex-wrap: wrap; /* Wrap only on smaller screens */
    }
}
}
    </style>
</head>
<body>
    <div class="header">Asset Management Dashboard </div>

    <div class="container">
        <div class="card-grid">
            <a href="view_assets.php" class="card">
                <h3>Total Assets</h3>
                <p><?= $totalAssets ?></p>
            </a>
          <a href="issued_assets.php" class="card">
            <h3>Issued Assets</h3>
            <p><?= $issuedAssets ?></p>
           </a>
			<a href="free_assets.php" class="card">
                <h3>Free Assets</h3>
                <p><?= $freeAssets ?></p>
            </a>
		    <a href="sell_assets.php" class="card">
                <h3>Sold Assets</h3>
                <p><?= $sellAssets ?></p>
            </a>
			<a href="dispose_assets.php" class="card">
                <h3>Disposed Assets</h3>
                <p><?= $disposeAssets ?></p>
            </a>
            <a href="users.php" class="card">
                <h3>Active Users</h3>
                <p><?= $totalUsers ?></p>
            </a>
        </div>

        <div class="section horizontal-wrap">
    <!-- Recent Transactions Table -->
    <div style="flex: 1 1 65%; min-width: 360px;">
        <h3>Recent Transactions</h3>
        <table>
            <thead>
                <tr>
                    <th>Asset ID</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th>Created At</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = mysqli_fetch_assoc($recent)): ?>
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

    <!-- Pie Chart for Asset Type Distribution -->
    <div style="flex: 1 1 30%; min-width: 280px; background: white; padding: 20px; border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.05);">
        <h3 style="text-align:center;">Assets by Type</h3>
        <canvas id="assetPieChart" width="250" height="250"></canvas>
    </div>
</div>

        <div class="section">
            <h3>Monthly Issued vs Returned</h3>
            <canvas id="assetChart" height="100"></canvas>
        </div>
    </div>

    <script>
    const ctx = document.getElementById('assetChart');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?= json_encode($months) ?>,
        datasets: [
            {
                label: 'Currently Issued Assets by Month',
                data: <?= json_encode($issuedData) ?>,
                backgroundColor: '#3B9C9C'
            },
            {
                label: 'Returned Assets by Month',
                data: <?= json_encode($returnedData) ?>,
                backgroundColor: '#f76c6c'
            }
        ]
    },
    options: {
        responsive: true,
        plugins: {
            title: {
                display: true,
                text: 'Monthly Asset Issuance vs Returns'
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: { stepSize: 5 }
            }
        }
    }
});
const pieCtx = document.getElementById('assetPieChart').getContext('2d');
new Chart(pieCtx, {
    type: 'pie',
    data: {
        labels: <?= json_encode($assetTypes) ?>,
        datasets: [{
            data: <?= json_encode($assetCounts) ?>,
            backgroundColor: [
                '#3B9C9C', '#f76c6c', '#ffa500', '#6a5acd', '#20b2aa', '#ff69b4'
            ],
            borderColor: '#fff',
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    }
});
   </script>
</body>
</html>
