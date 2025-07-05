<?php
// DB connection
$conn = new mysqli("localhost", "root", "", "track");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle asset return
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'], $_POST['asset_id'])) {
    $user_id = $_POST['user_id'];
    $asset_id = $_POST['asset_id'];

    // Get asset_type_id from asset_registration
    $stmt = $conn->prepare("SELECT asset_type_id FROM asset_registration WHERE asset_id = ?");
    $stmt->bind_param("s", $asset_id);
    $stmt->execute();
    $stmt->bind_result($asset_type_id);
    $stmt->fetch();
    $stmt->close();

    // Insert into asset_transactions (return)
    $stmt = $conn->prepare("INSERT INTO asset_transactions (transaction_type, asset_type_id, asset_id, user_id, transaction_date) VALUES ('return', ?, ?, ?, NOW())");
    $stmt->bind_param("isi", $asset_type_id, $asset_id, $user_id);
    $stmt->execute();
    $stmt->close();

    // Update asset status
    $stmt = $conn->prepare("UPDATE asset_registration SET status = 'Free' WHERE asset_id = ?");
    $stmt->bind_param("s", $asset_id);
    $stmt->execute();
    $stmt->close();

    echo "<script>alert('Asset returned successfully!'); window.location.href='';</script>";
    exit;
}

// Fetch all users
$users = $conn->query("SELECT user_id, full_name FROM users WHERE status = 'Active'");

// If user is selected, fetch their issued assets
$assets = [];
if (isset($_GET['user_id'])) {
    $user_id = $_GET['user_id'];
    $sql = "SELECT ar.asset_id, ar.asset_name FROM asset_registration ar
            JOIN asset_transactions atx ON ar.asset_id = atx.asset_id
            WHERE atx.user_id = $user_id AND atx.transaction_type = 'issue' AND ar.status != 'Free'";
    $assets = $conn->query($sql);
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Return Asset</title>
</head>
<body>
<h2>Return Asset Form</h2>
<form method="get">
    <label for="user_id">Select User:</label>
    <select name="user_id" onchange="this.form.submit()">
        <option value="">--Select--</option>
        <?php while($row = $users->fetch_assoc()): ?>
            <option value="<?= $row['user_id'] ?>" <?= isset($_GET['user_id']) && $_GET['user_id'] == $row['user_id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($row['full_name']) ?>
            </option>
        <?php endwhile; ?>
    </select>
</form>

<?php if (!empty($_GET['user_id']) && $assets->num_rows > 0): ?>
    <form method="post">
        <input type="hidden" name="user_id" value="<?= $_GET['user_id'] ?>">
        <label for="asset_id">Select Asset:</label>
        <select name="asset_id">
            <?php while($asset = $assets->fetch_assoc()): ?>
                <option value="<?= $asset['asset_id'] ?>">
                    <?= htmlspecialchars($asset['asset_name']) ?> (<?= $asset['asset_id'] ?>)
                </option>
            <?php endwhile; ?>
        </select>
        <button type="submit">Return</button>
    </form>
<?php elseif (isset($_GET['user_id'])): ?>
    <p>No assets issued to this user.</p>
<?php endif; ?>

</body>
</html>
