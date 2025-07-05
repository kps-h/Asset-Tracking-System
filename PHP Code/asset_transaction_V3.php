<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "track";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch all users
$usersQuery = "SELECT user_id, full_name FROM users WHERE status = 'Active'";
$usersResult = $conn->query($usersQuery);

// Handle form submission for returning assets
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['return_assets'])) {
    $userId = $_POST['user_id'];
    $returnedAssets = $_POST['returned_assets'];

    // Begin a transaction
    $conn->begin_transaction();

    try {
        // Process each returned asset
        foreach ($returnedAssets as $assetId) {
            // Update asset status to 'free'
            $updateAssetQuery = "UPDATE asset_registration SET status = 'free' WHERE asset_id = '$assetId' AND status = 'issued'";
            $conn->query($updateAssetQuery);

            // Record the return transaction
            $transactionQuery = "INSERT INTO asset_transactions (transaction_type, asset_id, user_id, transaction_date) 
                                 VALUES ('return', '$assetId', '$userId', NOW())";
            $conn->query($transactionQuery);
        }

        // Commit the transaction
        $conn->commit();
        echo "Assets returned successfully!";
    } catch (Exception $e) {
        // Rollback the transaction on error
        $conn->rollback();
        echo "Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Asset Return</title>
    <style>
        .box {
            width: 300px;
            height: 200px;
            border: 1px solid #ccc;
            margin: 10px;
            padding: 10px;
            overflow-y: auto;
        }
        .drag-item {
            padding: 5px;
            margin: 5px;
            border: 1px solid #ddd;
            cursor: move;
        }
    </style>
</head>
<body>
    <h2>Return Issued Assets</h2>

    <form method="POST" action="">
        <label for="user_id">Select User:</label>
        <select name="user_id" id="user_id" onchange="loadIssuedAssets()">
            <option value="">--Select User--</option>
            <?php while ($row = $usersResult->fetch_assoc()) { ?>
                <option value="<?php echo $row['user_id']; ?>"><?php echo $row['full_name']; ?></option>
            <?php } ?>
        </select>

        <br><br>

        <label for="issued_assets">Issued Assets:</label>
        <select id="issued_assets" multiple size="6">
            <!-- Dynamic options will be loaded based on selected user -->
        </select>

        <br><br>

        <label for="returned_assets">Returned Assets:</label>
        <div id="returned_assets_box" class="box" ondrop="drop(event)" ondragover="allowDrop(event)">
            <!-- Dragged assets will be added here -->
        </div>

        <br><br>
        
        <input type="hidden" name="returned_assets" id="returned_assets_input">
        <button type="submit" name="return_assets">Return Assets</button>
    </form>

    <script>
        // Function to load issued assets based on selected user
        function loadIssuedAssets() {
            var userId = document.getElementById('user_id').value;
            var issuedAssetsSelect = document.getElementById('issued_assets');
            
            // Clear previous options
            issuedAssetsSelect.innerHTML = '';

            if (userId) {
                // Fetch issued assets for the selected user via AJAX
                var xhr = new XMLHttpRequest();
                xhr.open('GET', 'fetch_assets_V3.php?user_id=' + userId, true);
                xhr.onload = function() {
                    if (xhr.status == 200) {
                        var assets = JSON.parse(xhr.responseText);
                        assets.forEach(function(asset) {
                            var option = document.createElement('option');
                            option.value = asset.asset_id;
                            option.textContent = asset.asset_id + ' (' + asset.asset_type + ')';
                            issuedAssetsSelect.appendChild(option);
                        });
                    }
                };
                xhr.send();
            }
        }

        // Allow dragging over the returned assets box
        function allowDrop(event) {
            event.preventDefault();
        }

        // Handle drag event (store dragged asset ID)
        function drag(event) {
            event.dataTransfer.setData('text', event.target.id);
        }

        // Handle drop event (add the dragged asset to the returned box)
        function drop(event) {
            event.preventDefault();
            var data = event.dataTransfer.getData('text');
            var assetId = document.getElementById(data).textContent;
            
            var returnedBox = document.getElementById('returned_assets_box');
            var div = document.createElement('div');
            div.classList.add('drag-item');
            div.textContent = assetId;
            div.setAttribute('draggable', true);
            div.setAttribute('ondragstart', 'drag(event)');
            
            returnedBox.appendChild(div);

            updateReturnedAssets();
        }

        // Update the hidden input with the returned assets
        function updateReturnedAssets() {
            var returnedAssets = [];
            var returnedBox = document.getElementById('returned_assets_box');
            var items = returnedBox.getElementsByClassName('drag-item');
            
            for (var i = 0; i < items.length; i++) {
                returnedAssets.push(items[i].textContent.split(' ')[0]); // Extract asset_id
            }

            document.getElementById('returned_assets_input').value = returnedAssets.join(',');
        }
    </script>

</body>
</html>

