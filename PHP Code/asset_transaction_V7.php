<?php
$conn = new mysqli("localhost", "root", "", "track");

// Pagination settings
$limit = 10;  // Number of records per page
$page = isset($_GET['page']) ? $_GET['page'] : 1;
$offset = ($page - 1) * $limit;
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Asset Transaction Report</title>
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500&display=swap" rel="stylesheet">
  <style>
    body {
      font-family: 'Roboto', sans-serif;
      background: #f8f9fa;
      margin: 0;
      padding: 40px;
      color: #333;
    }

    h2 {
      text-align: center;
      font-size: 32px;
      margin-bottom: 40px;
      color: #444;
    }

    .container {
      background: #fff;
      padding: 20px;
      border-radius: 10px;
      box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
      overflow: hidden;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 20px;
    }

    table, th, td {
      border: 1px solid #ddd;
    }

    th, td {
      padding: 12px;
      text-align: left;
      vertical-align: middle;
    }

    th {
      background-color: #f8f9fa;
      font-weight: bold;
    }

    .issued {
      color: #2e7d32;
      background-color: #e0f7e9;
      padding: 5px 10px;
      border-radius: 5px;
      display: inline-block;
      max-width: 150px;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
    }

    .returned {
      color: #d32f2f;
      background-color: #f8d7da;
      padding: 5px 10px;
      border-radius: 5px;
      display: inline-block;
      max-width: 150px;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
    }

    .transaction-flow {
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
    }

    .asset-box {
      display: inline-block;
      max-width: 150px;
      margin-right: 10px;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .asset-box span {
      font-size: 14px;
    }

    .asset-box .date {
      display: block;
      font-size: 12px;
      margin-top: 4px;
      color: #777;
    }

    .page-nav {
      text-align: center;
      margin-top: 20px;
    }

    .page-nav a {
      margin: 0 5px;
      padding: 5px 10px;
      background-color: #007bff;
      color: white;
      text-decoration: none;
      border-radius: 5px;
    }

    .page-nav a:hover {
      background-color: #0056b3;
    }

    .export-btn {
      display: inline-block;
      background-color: #28a745;
      color: white;
      padding: 8px 16px;
      border-radius: 5px;
      text-decoration: none;
      margin: 20px 0;
      font-weight: bold;
      position: sticky;
      top: 0;
      left: 50%;
      transform: translateX(-50%);
    }

    .export-btn:hover {
      background-color: #218838;
    }

  </style>
</head>
<body>

<h2>Asset Transaction Report</h2>

<div class="container">

  <a href="export_report.php" class="export-btn">Export to CSV</a>

  <table>
    <thead>
      <tr>
        <th>User Name</th>
        <th>Transaction Flow</th>
      </tr>
    </thead>
    <tbody>
      <?php
      $users_query = $conn->query("
        SELECT u.user_id, u.full_name 
        FROM users u 
        JOIN asset_transactions t ON t.user_id = u.user_id 
        GROUP BY u.user_id 
        LIMIT $limit OFFSET $offset
      ");

      while ($user = $users_query->fetch_assoc()) {
        $user_id = $user['user_id'];
        $full_name = $user['full_name'];

        $transactions = $conn->query("
          SELECT asset_id, transaction_type, transaction_date 
          FROM asset_transactions 
          WHERE user_id = $user_id 
          ORDER BY transaction_date ASC
        ");

        $transaction_flow = [];

        while ($t = $transactions->fetch_assoc()) {
          $date = date('d M Y', strtotime($t['transaction_date']));
          if ($t['transaction_type'] === 'issue') {
            $transaction_flow[] = "
              <div class='asset-box'>
                <span class='issued'>{$t['asset_id']}</span>
                <span class='date'>{$date}</span>
              </div>";
          } elseif ($t['transaction_type'] === 'return') {
            $transaction_flow[] = "
              <div class='asset-box'>
                <span class='returned'>{$t['asset_id']}</span>
                <span class='date'>{$date}</span>
              </div>";
          }
        }

        // Combine issued and returned assets into rows for the report
        $flow_str = "<div class='transaction-flow'>" . implode("", $transaction_flow) . "</div>";

        echo "
          <tr>
            <td>{$full_name}</td>
            <td>{$flow_str}</td>
          </tr>
        ";
      }
      ?>
    </tbody>
  </table>

  <div class="page-nav">
    <?php
    // Pagination logic
    $total_users_query = $conn->query("SELECT COUNT(DISTINCT user_id) AS total_users FROM asset_transactions");
    $total_users = $total_users_query->fetch_assoc()['total_users'];
    $total_pages = ceil($total_users / $limit);

    // Pagination links
    echo "<a href='?page=1'>&laquo; First</a>";
    echo "<a href='?page=" . ($page > 1 ? $page - 1 : 1) . "'>Prev</a>";
    echo "<a href='?page=" . ($page < $total_pages ? $page + 1 : $total_pages) . "'>Next</a>";
    echo "<a href='?page=$total_pages'>Last &raquo;</a>";
    ?>
  </div>

</div>

</body>
</html>
