<?php
$conn = new mysqli("localhost", "root", "", "track");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$sql = "SELECT * FROM asset_registration ORDER BY id DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Asset Report</title>
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 8px 10px;
            border: 1px solid #ccc;
            text-align: left;
        }
        th {
            background-color: #333;
            color: white;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .issued {
            color: red;
            font-weight: bold;
        }
        .free {
            color: green;
            font-weight: bold;
        }

        .modal, .confirm-modal {
            display: none;
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background-color: rgba(0,0,0,0.5);
            justify-content: center;
            align-items: center;
        }

        .modal-content, .confirm-content {
            background: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
        }

        .modal-content button, .confirm-content button {
            margin: 10px;
            padding: 8px 16px;
            cursor: pointer;
        }

        a.asset-link {
            color: blue;
            text-decoration: underline;
            cursor: pointer;
        }
    </style>
</head>
<body>

<h2>Asset Report</h2>

<table>
    <tr>
        <th>ID</th>
        <th>Asset ID</th>
        <th>Asset Type</th>
        <th>Vendor</th>
        <th>Purchase Date</th>
        <th>Windows Key</th>
        <th>Office Key</th>
        <th>Anti-Defection Key</th>
        <th>Registry Key</th>
        <th>Remarks</th>
        <th>Status</th>
        <th>Created At</th>
    </tr>
    <?php
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>{$row["id"]}</td>";
            echo "<td><a class='asset-link' data-id='{$row["id"]}'>{$row["asset_id"]}</a></td>";
            echo "<td>{$row["asset_type"]}</td>";
            echo "<td>{$row["vendor"]}</td>";
            echo "<td>{$row["purchase_date"]}</td>";
            echo "<td>{$row["windows_key"]}</td>";
            echo "<td>{$row["office_key"]}</td>";
            echo "<td>{$row["anti_defection_key"]}</td>";
            echo "<td>{$row["registry_key"]}</td>";
            echo "<td>{$row["remarks"]}</td>";
            echo "<td class='{$row["status"]}'>". ucfirst($row["status"]) ."</td>";
            echo "<td>{$row["created_at"]}</td>";
            echo "</tr>";
        }
    } else {
        echo "<tr><td colspan='12'>No assets found</td></tr>";
    }
    ?>
</table>

<!-- Main Modal -->
<div class="modal" id="mainModal">
    <div class="modal-content">
        <p>What do you want to do?</p>
        <button id="editBtn">Edit</button>
        <button id="deleteBtn">Delete</button>
    </div>
</div>

<!-- Confirm Delete Modal -->
<div class="confirm-modal" id="confirmModal">
    <div class="confirm-content">
        <p>Are you sure you want to delete?</p>
        <button id="cancelBtn">Cancel</button>
        <button id="yesBtn">Yes</button>
    </div>
</div>

<script>
    let selectedId = null;

    document.querySelectorAll('.asset-link').forEach(link => {
        link.addEventListener('click', function() {
            selectedId = this.getAttribute('data-id');
            document.getElementById('mainModal').style.display = 'flex';
        });
    });

    document.getElementById('editBtn').addEventListener('click', function() {
        window.location.href = 'asset_registration_V3.php?id=' + selectedId;
    });

    document.getElementById('deleteBtn').addEventListener('click', function() {
        document.getElementById('mainModal').style.display = 'none';
        document.getElementById('confirmModal').style.display = 'flex';
    });

    document.getElementById('cancelBtn').addEventListener('click', function() {
        document.getElementById('confirmModal').style.display = 'none';
    });

    document.getElementById('yesBtn').addEventListener('click', function() {
        window.location.href = 'delete_asset_V4.php?id=' + selectedId;
    });

    // Close modals on outside click
    window.onclick = function(e) {
        if (e.target.classList.contains('modal')) {
            document.getElementById('mainModal').style.display = 'none';
        }
        if (e.target.classList.contains('confirm-modal')) {
            document.getElementById('confirmModal').style.display = 'none';
        }
    };
</script>

</body>
</html>

<?php $conn->close(); ?>
