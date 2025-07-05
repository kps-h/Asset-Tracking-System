<?php
// Database connection
$host = "localhost";
$user = "root";
$pass = "";
$db   = "track";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

$message = "";

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST" && !empty($_POST["seat_number"])) {
    $seat_number = trim($_POST["seat_number"]);

    $check = $conn->prepare("SELECT id FROM seat_number WHERE seat_number = ?");
    $check->bind_param("s", $seat_number);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        $message = "<div class='alert alert-warning'>⚠️ Seat number already exists!</div>";
    } else {
        $stmt = $conn->prepare("INSERT INTO seat_number (seat_number) VALUES (?)");
        $stmt->bind_param("s", $seat_number);
        if ($stmt->execute()) {
            $message = "<div class='alert alert-success'>✅ Seat number added successfully!</div>";
        } else {
            $message = "<div class='alert alert-danger'>❌ Error: " . $stmt->error . "</div>";
        }
        $stmt->close();
    }
    $check->close();
}

// Fetch all seat numbers
$seatsResult = $conn->query("SELECT * FROM seat_number ORDER BY id ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Add Seat Number</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background-color: #f0f4f8;
    }
    .card {
      max-width: 500px;
      margin: 40px auto 20px;
      border-radius: 12px;
      box-shadow: 0 8px 16px rgba(0,0,0,0.1);
    }
    .btn-custom {
      background-color: #f76c6c;
      color: #fff;
    }
    .btn-custom:hover {
      background-color: #C11B17;
    }
    .table-container {
      max-width: 600px;
	  padding: 40px;
	  max-height: 630px;
      margin: 0 auto 50px;
	  overflow-y: auto;
    }
  </style>
</head>
<body>

<div class="card p-4">
  <h4 class="text-center mb-3">➕ Add Seat Number</h4>

  <?php if ($message): ?>
    <?= $message ?>
  <?php endif; ?>

  <form method="POST">
    <div class="mb-3">
      <label for="seat_number" class="form-label">Seat Number</label>
      <input type="text" class="form-control" id="seat_number" name="seat_number" placeholder="e.g. A101" required>
    </div>
    <div class="text-end">
      <button type="submit" class="btn btn-custom">Add Seat</button>
    </div>
  </form>
</div>

<!-- Table Displaying Seat Numbers -->
<div class="table-container">
  <h5 class="text-center mb-3">📋 Seat Number List</h5>
  <table class="table table-bordered table-striped">
    <thead class="table-light">
      <tr>
        <th>ID</th>
        <th>Seat Number</th>
      </tr>
    </thead>
    <tbody>
      <?php if ($seatsResult->num_rows > 0): ?>
        <?php while ($row = $seatsResult->fetch_assoc()): ?>
          <tr>
            <td><?= htmlspecialchars($row['id']) ?></td>
            <td><?= htmlspecialchars($row['seat_number']) ?></td>
          </tr>
        <?php endwhile; ?>
      <?php else: ?>
        <tr><td colspan="2" class="text-center">No seat numbers added yet.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

</body>
</html>
