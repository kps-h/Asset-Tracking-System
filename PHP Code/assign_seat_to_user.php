<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = "localhost";
$db = "track";
$user = "root";
$pass = "";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$message = '';

// ✅ Unassign seat - must go OUTSIDE the POST block
if (isset($_GET['unassign_user_id'])) {
    $unassign_user_id = intval($_GET['unassign_user_id']);
    $stmt = $conn->prepare("UPDATE users SET seat_id = NULL WHERE user_id = ?");
    $stmt->bind_param("i", $unassign_user_id);

    if ($stmt->execute()) {
        $message = urlencode("✅ Seat unassigned successfully!");
    } else {
        $message = urlencode("❌ Error: " . $stmt->error);
    }

    $stmt->close();

    header("Location: " . $_SERVER['PHP_SELF'] . "?message=" . $message);
    exit();
}

// Display message from GET
if (isset($_GET['message'])) {
    $message = "<div class='success-message'>" . htmlspecialchars($_GET['message']) . "</div>";
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['edit_seat'])) {
        $edit_user_id = $_POST['edit_user_id'];
        $new_seat_id = $_POST['new_seat_id'];

        $stmt = $conn->prepare("UPDATE users SET seat_id = ? WHERE user_id = ?");
        $stmt->bind_param("ii", $new_seat_id, $edit_user_id);

        if ($stmt->execute()) {
            $message = "<div class='success-message'>✅ Seat updated successfully!</div>";
        } else {
            $message = "<div class='error-message'>❌ Error: " . $stmt->error . "</div>";
        }

        $stmt->close();
    } else {
        $seat_id = $_POST['seat_id'];
        $user_id = $_POST['user_id'];

        $stmt = $conn->prepare("UPDATE users SET seat_id = ? WHERE user_id = ?");
        $stmt->bind_param("ii", $seat_id, $user_id);

        if ($stmt->execute()) {
            $message = "<div class='success-message'>✅ Seat assigned successfully!</div>";
        } else {
            $message = "<div class='error-message'>❌ Error: " . $stmt->error . "</div>";
        }

        $stmt->close();
    }
}

$seat_result = $conn->query("SELECT id, seat_number FROM seat_number");
$user_result = $conn->query("SELECT user_id, full_name FROM users");
$report_result = $conn->query("
    SELECT u.user_id, u.full_name, s.seat_number, s.id AS seat_id
    FROM users u
    LEFT JOIN seat_number s ON u.seat_id = s.id
    ORDER BY CAST(s.seat_number AS UNSIGNED) ASC, u.full_name ASC
");

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Seat Assignment</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        <style>
        body {
            margin: 0;
            font-family: 'Segoe UI', Tahoma, sans-serif;
            background-color: #f5f6fa;
        }

        .container {
            display: flex;
            height: 100vh;
            padding: 120px;
			padding-left: 400px;
            box-sizing: border-box;
			
        }

        .form-section {
            flex: 0 0 35%;
            background-color: #ffffff;
            padding: 30px;
			top-padding: 20px;
            margin-right: 20px;
			max-height: 300px;
            border-radius: 10px;
            box-shadow: 0 0 12px rgba(0, 0, 0, 0.08);
        }

        .report-section {
            flex: 1;
            background-color: #ffffff;
            padding: 30px;
			max-height: 800px;
			max-width: 500px;
            overflow-y: auto;
            border-radius: 10px;
            box-shadow: 0 0 12px rgba(0, 0, 0, 0.08);
        }

        h2 {
            margin-top: 0;
            color: #2c3e50;
        }

        label {
            display: block;
            margin-top: 15px;
            font-weight: bold;
            color: #34495e;
        }

        select, button {
            width: 100%;
            padding: 10px;
            margin-top: 8px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 15px;
        }

        button {
            background-color: #f76c6c;
            color: #fff;
            font-weight: bold;
            margin-top: 20px;
            cursor: pointer;
            border: none;
            transition: background 0.3s ease;
        }

        button:hover {
            background-color: #C11B17;
        }

        .success-message,
        .error-message {
            margin-top: 15px;
            padding: 12px;
            border-radius: 5px;
            text-align: center;
            font-weight: bold;
        }

        .success-message {
            background-color: #2ecc71;
            color: white;
        }

        .error-message {
            background-color: #e74c3c;
            color: white;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            border-radius: 8px;
            overflow: hidden;
        }

        th, td {
            padding: 12px;
            border-bottom: 1px solid #eee;
            text-align: left;
        }

        th {
            background-color: #f76c6c;
            color: #fff;
        }

        td {
            color: #2c3e50;
        }

        @media (max-width: 900px) {
            .container {
                flex-direction: column;
                height: auto;
            }

            .form-section, .report-section {
                width: 100%;
                margin-right: 0;
                margin-bottom: 20px;
            }
        }
		.search-bar {
    margin-bottom: 15px;
}

.search-bar input {
    width: 100%;
	max-width: 480px;
    padding: 10px;
    font-size: 14px;
    border: 1px solid #ccc;
    border-radius: 6px;
}

.options-cell {
    position: relative;
    width: 40px;
    text-align: center;
}

.dots {
    cursor: pointer;
    font-size: 20px;
    user-select: none;
}

.dropdown-content {
    display: none;
    position: absolute;
    right: 0;
    top: 24px;
    background-color: white;
    border: 1px solid #ccc;
    z-index: 1;
    border-radius: 4px;
    min-width: 100px;
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.dropdown-content a {
    padding: 8px 12px;
    display: block;
    text-decoration: none;
    color: #333;
}

.dropdown-content a:hover {
    background-color: #f1f1f1;
}
    </style>
    <script>
        function filterTable() {
            const input = document.getElementById("searchInput");
            const filter = input.value.toLowerCase();
            const rows = document.querySelectorAll("#reportTable tbody tr");

            rows.forEach(row => {
                const name = row.querySelector("td").textContent.toLowerCase();
                row.style.display = name.includes(filter) ? "" : "none";
            });
        }

        function toggleMenu(id) {
            document.querySelectorAll('.dropdown-content').forEach(menu => menu.style.display = 'none');
            const menu = document.getElementById('dropdown-' + id);
            menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
        }

        function openEditModal(userId) {
            document.getElementById('editUserId').value = userId;
            document.getElementById('editModal').style.display = 'block';
        }

        function closeModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        window.onclick = function(event) {
            const modal = document.getElementById('editModal');
            if (event.target == modal) {
                closeModal();
            } else if (!event.target.matches('.dots')) {
                document.querySelectorAll('.dropdown-content').forEach(menu => menu.style.display = 'none');
            }
        }
    </script>
</head>
<body>

<div class="container">
    <div class="form-section">
        <h2>Assign Seat</h2>
        <form method="POST" action="">
            <label for="seat_id">Select Seat:</label>
            <select name="seat_id" required>
                <option value="">-- Select Seat --</option>
                <?php
                $seat_result->data_seek(0);
                while ($row = $seat_result->fetch_assoc()) {
                    echo '<option value="' . $row['id'] . '">' . htmlspecialchars($row['seat_number']) . '</option>';
                }
                ?>
            </select>

            <label for="user_id">Select User:</label>
            <select name="user_id" required>
                <option value="">-- Select User --</option>
                <?php
                $user_result->data_seek(0);
                while ($row = $user_result->fetch_assoc()) {
                    echo '<option value="' . $row['user_id'] . '">' . htmlspecialchars($row['full_name']) . '</option>';
                }
                ?>
            </select>

            <button type="submit">Assign Seat</button>
            <?php if (!empty($message)) echo $message; ?>
        </form>
    </div>

    <div class="report-section">
        <h2>Seat Assignment Report</h2>
        <div class="search-bar">
            <input type="text" id="searchInput" onkeyup="filterTable()" placeholder="🔍 Search user...">
        </div>
        <table id="reportTable">
            <thead>
            <tr>
                <th>User</th>
                <th>Seat</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            <?php while ($row = $report_result->fetch_assoc()) { ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                    <td><?php echo $row['seat_number'] ? htmlspecialchars($row['seat_number']) : '<em>Unassigned</em>'; ?></td>
                    <td class="options-cell">
                        <span class="dots" onclick="toggleMenu('<?php echo $row['user_id']; ?>')">⋮</span>
                        <div id="dropdown-<?php echo $row['user_id']; ?>" class="dropdown-content">
                            <a href="javascript:void(0);" onclick="openEditModal('<?php echo $row['user_id']; ?>')">Edit</a>
                            <a href="?unassign_user_id=<?php echo $row['user_id']; ?>" onclick="return confirm('Are you sure you want to unassign this seat?')">Unassign</a>

                        </div>
                    </td>
                </tr>
            <?php } ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal -->
<div id="editModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:999;">
    <div style="background:#fff; padding:20px; width:400px; margin:100px auto; border-radius:8px; position:relative;">
        <h3>Edit Seat Assignment</h3>
        <form method="POST" action="">
            <input type="hidden" name="edit_user_id" id="editUserId">
            <label for="new_seat_id">Select New Seat:</label>
            <select name="new_seat_id" required>
                <option value="">-- Select Seat --</option>
                <?php
                $seat_result->data_seek(0);
                while ($row = $seat_result->fetch_assoc()) {
                    echo '<option value="' . $row['id'] . '">' . htmlspecialchars($row['seat_number']) . '</option>';
                }
                ?>
            </select>
            <button type="submit" name="edit_seat">Update Seat</button>
            <button type="button" onclick="closeModal()">Cancel</button>
        </form>
    </div>
</div>

</body>
</html>

<?php $conn->close(); ?>
