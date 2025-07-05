<?php
include('db.php');

// Retrieve selected columns and filters from the form
$columns = $_POST['columns'] ?? [];
$status = $_GET['status'] ?? '';
$role = $_GET['role'] ?? '';
$from_date = $_GET['from_date'] ?? '';
$to_date = $_GET['to_date'] ?? '';

// SQL query to filter users based on selected filters
$query = "SELECT * FROM users WHERE 1=1";

if ($status !== '') {
    $query .= " AND status = '" . $conn->real_escape_string($status) . "'";
}

if ($role !== '') {
    $query .= " AND role = '" . $conn->real_escape_string($role) . "'";
}

if ($from_date !== '') {
    $query .= " AND created_at >= '" . $conn->real_escape_string($from_date) . "'";
}

if ($to_date !== '') {
    $query .= " AND created_at <= '" . $conn->real_escape_string($to_date) . "'";
}

// Execute the query
$result = $conn->query($query);

// Set headers for CSV file download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="user_report.csv"');

// Open the output stream
$output = fopen('php://output', 'w');

// Write the header row with selected columns
$header = [];
if (in_array("user_id", $columns)) $header[] = "User ID";
if (in_array("full_name", $columns)) $header[] = "Full Name";
if (in_array("email", $columns)) $header[] = "Email";
if (in_array("phone", $columns)) $header[] = "Phone";
if (in_array("role", $columns)) $header[] = "Role";
if (in_array("status", $columns)) $header[] = "Status";
if (in_array("created_at", $columns)) $header[] = "Created At";
fputcsv($output, $header);

// Write the data rows
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $data = [];
        if (in_array("user_id", $columns)) $data[] = $row['user_id'];
        if (in_array("full_name", $columns)) $data[] = $row['full_name'];
        if (in_array("email", $columns)) $data[] = $row['email'];
        if (in_array("phone", $columns)) $data[] = $row['phone'];
        if (in_array("role", $columns)) $data[] = $row['role'];
        if (in_array("status", $columns)) $data[] = $row['status'];
        if (in_array("created_at", $columns)) $data[] = $row['created_at'];
        fputcsv($output, $data);
    }
}

// Close the output stream
fclose($output);
$conn->close();
exit;
