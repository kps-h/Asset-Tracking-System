<?php
$conn = new mysqli("localhost", "root", "", "track");

// Set headers for CSV download
header("Content-Type: text/csv");
header("Content-Disposition: attachment; filename=asset_report.csv");

// Open output stream
$output = fopen("php://output", "w");

// CSV column headers
fputcsv($output, [
    'Username',
    'Asset ID',
    'Asset Type',
    'Windows Key',
    'Office Key',
    'Anti-Defection Key',
    'Registry Key',
    'Status',
    'Date'
]);

// Query with JOINs to fetch actual key values
$result = $conn->query("
  SELECT 
    u.full_name AS user_name,
    ar.asset_id,
    ar.asset_type,
    wt.key AS windows_key,
    ot.key AS office_key,
    ad.key AS anti_defection_key,
    rk.key AS registry_key,
    atx.transaction_type,
    atx.transaction_date
  FROM asset_transactions atx
  JOIN users u ON u.user_id = atx.user_id
  JOIN asset_registration ar ON ar.asset_id = atx.asset_id
  LEFT JOIN windows_table wt ON ar.windows_key = wt.id
  LEFT JOIN office_keys ot ON ar.office_key = ot.id
  LEFT JOIN anti_defection ad ON ad.id = ar.anti_defection_key
  LEFT JOIN registry_keys rk ON rk.id = ar.registry_key
  ORDER BY atx.transaction_date DESC
");

// Write each row to CSV
while ($row = $result->fetch_assoc()) {
    fputcsv($output, [
        $row['user_name'],
        $row['asset_id'],
        $row['asset_type'],
        $row['windows_key'] ?? '',
        $row['office_key'] ?? '',
        $row['anti_defection_key'] ?? '',
        $row['registry_key'] ?? '',
        ucfirst($row['transaction_type']),
        date('d M Y, h:i A', strtotime($row['transaction_date']))
    ]);
}

fclose($output);
exit();
