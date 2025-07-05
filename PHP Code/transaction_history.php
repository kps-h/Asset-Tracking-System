<?php
$conn = new mysqli("localhost", "root", "", "track");

$query = "
    SELECT atx.transaction_id, atx.transaction_type, atx.asset_id, ar.asset_type, 
           u.full_name, atx.transaction_date
    FROM asset_transactions atx
    JOIN users u ON atx.user_id = u.user_id
    JOIN asset_registration ar ON atx.asset_id = ar.asset_id
    ORDER BY atx.transaction_date DESC
";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Transaction History</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <style>
    body {
      font-family: 'Segoe UI', sans-serif;
      background: #f4f6f8;
      padding: 40px 20px;
    }

    h2 {
      text-align: center;
      margin-bottom: 40px;
      font-size: 28px;
    }

    .container {
      max-width: 1000px;
      margin: 0 auto;
      background: #fff;
      border-radius: 10px;
      padding: 20px;
      box-shadow: 0 0 12px rgba(0,0,0,0.1);
    }

    table {
      width: 100%;
      border-collapse: collapse;
    }

    th, td {
      padding: 14px 12px;
      text-align: left;
      border-bottom: 1px solid #ddd;
    }

    th {
      background-color: #007bff;
      color: white;
    }

    tr:hover {
      background-color: #f1f1f1;
    }

    .issue {
      color: #28a745;
      font-weight: bold;
    }

    .return {
      color: #dc3545;
      font-weight: bold;
    }

    @media (max-width: 768px) {
      table, thead, tbody, th, td, tr {
        display: block;
      }

      thead tr {
        display: none;
      }

      td {
        padding-left: 50%;
        position: relative;
        border: none;
        border-bottom: 1px solid #ddd;
      }

      td:before {
        position: absolute;
        left: 16px;
        top: 14px;
        white-space: nowrap;
        font-weight: bold;
      }

      td:nth-of-type(1):before { content: "ID"; }
      td:nth-of-type(2):before { content: "Type"; }
      td:nth-of-type(3):before { content: "Asset"; }
      td:nth-of-type(4):before { content: "Asset Type"; }
      td:nth-of-type(5):before { content: "User"; }
      td:nth-of-type(6):before { content: "Date"; }
    }

    .back-link {
      text-align: center;
      margin-top: 20px;
    }

    .back-link a {
      text-decoration: none;
      color: #007bff;
      font-weight: bold;
    }

    .back-link a:hover {
      text-decoration: underline;
    }
  </style>
</head>
<body>

  <h2>Asset Transaction History</h2>

  <div class="container">
    <table>
      <thead>
        <tr>
          <th>ID</th>
          <th>Type</th>
          <th>Asset ID</th>
          <th>Asset Type</th>
          <th>User</th>
          <th>Date</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($result->num_rows > 0): ?>
          <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
              <td><?= $row['transaction_id'] ?></td>
              <td class="<?= $row['transaction_type'] ?>"><?= ucfirst($row['transaction_type']) ?></td>
              <td><?= $row['asset_id'] ?></td>
              <td><?= $row['asset_type'] ?></td>
              <td><?= $row['full_name'] ?></td>
              <td><?= date("d M Y, h:i A", strtotime($row['transaction_date'])) ?></td>
            </tr>
          <?php endwhile; ?>
        <?php else: ?>
          <tr>
            <td colspan="6" style="text-align: center;">No transactions found.</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <div class="back-link">
    <a href="asset_transactions.php">&larr; Back to Asset Form</a>
  </div>

</body>
</html>
