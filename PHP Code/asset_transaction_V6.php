<?php
session_start();
$conn = new mysqli("localhost", "root", "", "track");
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>User-Wise Transaction History</title>
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">
  <style>
    body {
      font-family: Arial, sans-serif;
      background: #f4f4f4;
      padding: 30px;
    }
    .recent-transactions {
      background: white;
      padding: 20px;
      border-radius: 10px;
      max-width: 1100px;
      margin: 0 auto;
      box-shadow: 0 0 10px rgba(0,0,0,0.1);
    }
    h2 {
      text-align: center;
      margin-bottom: 25px;
    }
    table.dataTable tbody td {
      vertical-align: top;
    }
  </style>
</head>
<body>

<div class="recent-transactions">
  <h2>User-wise Transaction History</h2>
  <table id="userTransactionTable" class="display nowrap" style="width:100%">
    <thead>
      <tr>
        <th>User</th>
        <th>Issued Assets</th>
        <th>Returned Assets</th>
      </tr>
    </thead>
    <tbody>
      <?php
      // Fetch users with transactions
      $usersWithTransactions = $conn->query("
        SELECT u.user_id, u.full_name
        FROM users u
        JOIN asset_transactions t ON t.user_id = u.user_id
        GROUP BY u.user_id
      ");

      while ($user = $usersWithTransactions->fetch_assoc()) {
          $userId = $user['user_id'];
          $fullName = $user['full_name'];

          // Fetch issued assets
          $issuedResult = $conn->query("SELECT asset_id FROM asset_transactions WHERE user_id = $userId AND transaction_type = 'issue'");
          $issuedAssets = [];
          while ($row = $issuedResult->fetch_assoc()) {
              $issuedAssets[] = $row['asset_id'];
          }

          // Fetch returned assets
          $returnedResult = $conn->query("SELECT asset_id FROM asset_transactions WHERE user_id = $userId AND transaction_type = 'return'");
          $returnedAssets = [];
          while ($row = $returnedResult->fetch_assoc()) {
              $returnedAssets[] = $row['asset_id'];
          }

          echo "<tr>
                  <td>{$fullName}</td>
                  <td>" . implode(', ', $issuedAssets) . "</td>
                  <td>" . implode(', ', $returnedAssets) . "</td>
                </tr>";
      }
      ?>
    </tbody>
  </table>
</div>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>

<script>
  $(document).ready(function () {
    $('#userTransactionTable').DataTable({
      dom: 'Bfrtip',
      buttons: ['csvHtml5', 'excelHtml5', 'print'],
      pageLength: 10,
      responsive: true
    });
  });
</script>

</body>
</html>
