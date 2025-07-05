<?php
$conn = mysqli_connect("localhost", "root", "", "track");
$filter = $_GET['filter'] ?? 'year';

switch ($filter) {
    case 'day':
        $query = "
            SELECT DATE(transaction_date) as label,
                SUM(CASE WHEN transaction_type = 'issue' THEN 1 ELSE 0 END) as issued,
                SUM(CASE WHEN transaction_type = 'return' THEN 1 ELSE 0 END) as returned
            FROM asset_transactions
            WHERE transaction_date >= CURDATE() - INTERVAL 7 DAY
            GROUP BY DATE(transaction_date)
        ";
        break;

    case 'week':
        $query = "
            SELECT WEEK(transaction_date) as label,
                SUM(CASE WHEN transaction_type = 'issue' THEN 1 ELSE 0 END) as issued,
                SUM(CASE WHEN transaction_type = 'return' THEN 1 ELSE 0 END) as returned
            FROM asset_transactions
            WHERE transaction_date >= CURDATE() - INTERVAL 2 MONTH
            GROUP BY WEEK(transaction_date)
        ";
        break;

    case 'month':
        $query = "
            SELECT MONTH(transaction_date) as label,
                SUM(CASE WHEN transaction_type = 'issue' THEN 1 ELSE 0 END) as issued,
                SUM(CASE WHEN transaction_type = 'return' THEN 1 ELSE 0 END) as returned
            FROM asset_transactions
            WHERE YEAR(transaction_date) = YEAR(CURDATE())
            GROUP BY MONTH(transaction_date)
        ";
        break;

    case 'year':
    default:
        $query = "
            SELECT YEAR(transaction_date) as label,
                SUM(CASE WHEN transaction_type = 'issue' THEN 1 ELSE 0 END) as issued,
                SUM(CASE WHEN transaction_type = 'return' THEN 1 ELSE 0 END) as returned
            FROM asset_transactions
            GROUP BY YEAR(transaction_date)
        ";
        break;
}

$result = mysqli_query($conn, $query);

$labels = $issued = $returned = [];

while ($row = mysqli_fetch_assoc($result)) {
    $labels[] = $row['label'];
    $issued[] = $row['issued'];
    $returned[] = $row['returned'];
}

echo json_encode([
    'labels' => $labels,
    'issued' => $issued,
    'returned' => $returned
]);
