<?php
$conn = new mysqli("localhost", "root", "", "track");

// Step 1: Load Asset Details
$asset_id = $_GET['asset_id'] ?? '';
$asset = null;
if ($asset_id) {
    $stmt = $conn->prepare("SELECT * FROM asset_registration WHERE asset_id = ? AND asset_type = 'CPU'");
    $stmt->bind_param("s", $asset_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $asset = $result->fetch_assoc();
}

// Step 2: Handle Form Submission
if ($_SERVER["REQUEST_METHOD"] === "POST" && $asset_id) {
    $windows = $_POST['windows_key'] !== "" ? (int)$_POST['windows_key'] : NULL;
    $office = $_POST['office_key'] !== "" ? (int)$_POST['office_key'] : NULL;
    $anti_def = $_POST['anti_defection_key'] !== "" ? (int)$_POST['anti_defection_key'] : NULL;
    $registry = $_POST['registry_key'] !== "" ? (int)$_POST['registry_key'] : NULL;

    $query = "UPDATE asset_registration SET 
        windows_key = ?, 
        office_key = ?, 
        anti_defection_key = ?, 
        registry_key = ? 
        WHERE asset_id = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("iiiis", $windows, $office, $anti_def, $registry, $asset_id);

    if ($stmt->execute()) {
        echo "<p class='success'>✅ Keys updated successfully.</p>";
    } else {
        echo "<p class='error'>❌ Error updating keys: " . $stmt->error . "</p>";
    }

    $stmt = $conn->prepare("SELECT * FROM asset_registration WHERE asset_id = ? AND asset_type = 'CPU'");
    $stmt->bind_param("s", $asset_id);
    $stmt->execute();
    $asset = $stmt->get_result()->fetch_assoc();
}

$windowsKeys = $conn->query("SELECT id, `key` FROM windows_table");
$officeKeys = $conn->query("SELECT id, `key` FROM office_keys");
$antiDefKeys = $conn->query("SELECT id, `key` FROM anti_defection");
$registryKeys = $conn->query("SELECT id, `key` FROM registry_keys");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Assign Keys</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: #f4f6f9;
            margin: 0;
            padding: 20px;
        }

        h3 {
            color: #333;
        }

        form {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 8px rgba(0, 0, 0, 0.1);
            max-width: 600px;
            margin: 20px auto;
        }

        label {
            display: block;
            margin-top: 15px;
            font-weight: bold;
        }

        select, input[type="text"], button {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 14px;
        }

        select:focus, input:focus {
            border-color: #f76c6c;
            outline: none;
        }

        button {
            background-color: #f76c6c;
            color: white;
            font-weight: bold;
            cursor: pointer;
            margin-top: 20px;
            transition: background-color 0.3s;
        }

        button:hover {
            background-color: #C11B17;
        }

        .success {
            background: #d4edda;
            color: #155724;
            border-left: 5px solid #28a745;
            padding: 10px;
            max-width: 600px;
            margin: 10px auto;
            border-radius: 5px;
        }

        .error {
            background: #f8d7da;
            color: #721c24;
            border-left: 5px solid #dc3545;
            padding: 10px;
            max-width: 600px;
            margin: 10px auto;
            border-radius: 5px;
        }
    </style>
</head>
<body>

<!-- Asset Dropdown Form -->
<form method="GET">
    <label>Select CPU Asset:</label>
    <select name="asset_id" required>
        <option value="">-- Select CPU Asset --</option>
        <?php
        $cpuAssets = $conn->query("SELECT asset_id FROM asset_registration WHERE asset_type = 'CPU'");
        while ($row = $cpuAssets->fetch_assoc()):
        ?>
            <option value="<?= $row['asset_id'] ?>" <?= $row['asset_id'] == $asset_id ? 'selected' : '' ?>>
                <?= $row['asset_id'] ?>
            </option>
        <?php endwhile; ?>
    </select>
    <button type="submit">Load Asset</button>
</form>

<!-- Key Assignment Form -->
<?php if ($asset): ?>
    <form method="POST">
        <h3>Assign Keys to Asset: <?= htmlspecialchars($asset['asset_id']) ?></h3>

        <label>Windows Key:</label>
        <select name="windows_key">
            <option value="">-- Select --</option>
            <?php while ($w = $windowsKeys->fetch_assoc()): ?>
                <option value="<?= $w['id'] ?>" <?= $w['id'] == $asset['windows_key'] ? 'selected' : '' ?>>
                    <?= $w['key'] ?>
                </option>
            <?php endwhile; ?>
        </select>

        <label>Office Key:</label>
        <select name="office_key">
            <option value="">-- Select --</option>
            <?php while ($o = $officeKeys->fetch_assoc()): ?>
                <option value="<?= $o['id'] ?>" <?= $o['id'] == $asset['office_key'] ? 'selected' : '' ?>>
                    <?= $o['key'] ?>
                </option>
            <?php endwhile; ?>
        </select>

        <label>Anti-Defection Key:</label>
        <select name="anti_defection_key">
            <option value="">-- Select --</option>
            <?php while ($a = $antiDefKeys->fetch_assoc()): ?>
                <option value="<?= $a['id'] ?>" <?= $a['id'] == $asset['anti_defection_key'] ? 'selected' : '' ?>>
                    <?= $a['key'] ?>
                </option>
            <?php endwhile; ?>
        </select>

        <label>Registry Key:</label>
        <select name="registry_key">
            <option value="">-- Select --</option>
            <?php while ($r = $registryKeys->fetch_assoc()): ?>
                <option value="<?= $r['id'] ?>" <?= $r['id'] == $asset['registry_key'] ? 'selected' : '' ?>>
                    <?= $r['key'] ?>
                </option>
            <?php endwhile; ?>
        </select>

        <button type="submit">Assign / Update Keys</button>
    </form>
<?php elseif ($asset_id): ?>
    <p class="error">❌ No CPU asset found with ID: <?= htmlspecialchars($asset_id) ?></p>
<?php endif; ?>

</body>
</html>
