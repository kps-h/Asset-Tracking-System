<?php	
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}
include('db.php');

// Fetch dropdown data
$windows_keys = $conn->query("SELECT id, `key` FROM windows_table");
$office_keys = $conn->query("SELECT id, `key` FROM office_keys");
$anti_defection_keys = $conn->query("SELECT id, `key` FROM anti_defection");
$registry_keys = $conn->query("SELECT id, `key` FROM registry_keys");
$asset_types_result = $conn->query("SELECT * FROM asset_types");

// AJAX: Add new asset type
if (isset($_POST['asset_type_to_add'])) {
    $new_asset_type = $_POST['asset_type_to_add'];
    $sql = "INSERT INTO asset_types (asset_type) VALUES (?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $new_asset_type);
    if ($stmt->execute()) {
        echo json_encode(['id' => $stmt->insert_id, 'name' => $new_asset_type]);
    } else {
        echo json_encode(['error' => $stmt->error]);
    }
    exit;
}

// AJAX: Check for duplicates
if (isset($_POST['check_duplicate'])) {
    $field = $_POST['field'];
    $value = $_POST['value'];

    $stmt = null;

    switch ($field) {
        case 'asset_id':
            $stmt = $conn->prepare("SELECT COUNT(*) FROM asset_registration WHERE asset_id = ?");
            break;
        case 'windows_key':
            $stmt = $conn->prepare("SELECT COUNT(*) FROM asset_registration WHERE windows_key = ?");
            break;
        case 'anti_defection_key':
            $stmt = $conn->prepare("SELECT COUNT(*) FROM asset_registration WHERE anti_defection_key = ?");
            break;
    }

    if ($stmt) {
        $stmt->bind_param("s", $value);
        $stmt->execute();
        $stmt->bind_result($count);
        $stmt->fetch();
        echo ($count > 0) ? "duplicate" : "ok";
        $stmt->close();
    }
    exit;
}

// AJAX: Register asset
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['asset_id'])) {
    $asset_id = $_POST['asset_id'];
    $asset_type_id = $_POST['asset_type'];

    // Optional fields
    $vendor = $_POST['vendor'] ?? '';
    $purchase_date = $_POST['purchase_date'] ?? '';
    $windows_key = $_POST['windows_key'] ?? '';
    $office_key = $_POST['office_key'] ?? '';
    $anti_defection_key = $_POST['anti_defection_key'] ?? '';
    $registry_key = $_POST['registry_key'] ?? '';
    $remarks = $_POST['remarks'] ?? '';

    $stmt = $conn->prepare("SELECT asset_type FROM asset_types WHERE id = ?");
    $stmt->bind_param("i", $asset_type_id);
    $stmt->execute();
    $stmt->bind_result($asset_type);
    $stmt->fetch();
    $stmt->close();

    $sql = "INSERT INTO asset_registration 
        (asset_id, asset_type, vendor, purchase_date, windows_key, office_key, anti_defection_key, registry_key, remarks)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssssss", $asset_id, $asset_type, $vendor, $purchase_date, $windows_key, $office_key, $anti_defection_key, $registry_key, $remarks);

    if ($stmt->execute()) {
        echo "success";
    } else {
        echo "error";
    }
    $stmt->close();
    exit;
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Asset Registration</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
	body {
    background-color: #f2f7f7;
}
        #popup {
            display: none;
            position: fixed;
            top: 70%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color: #28a745;
            color: white;
            padding: 20px 30px;
            border-radius: 8px;
            font-size: 18px;
            box-shadow: 0 0 20px rgba(0,0,0,0.3);
            z-index: 9999;
            transition: opacity 0.5s ease;
            opacity: 0;
            min-width: 300px;
            text-align: center;
        }
		#submitBtn {
            background-color: #f76c6c !important;
            border-color: #f76c6c !important;
            color: #fff !important;
        }
		#submitBtn:hover {
    background-color: #C11B17 !important;
    border-color: #C11B17 !important;
}
    </style>
</head>
<body>
<div class="container mt-5">
    <h2 class="text-center mb-4">Asset Registration Form</h2>
    <form id="asset-form" novalidate>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Asset ID</label>
                <input type="text" class="form-control" name="asset_id" required>
                <div class="invalid-feedback" id="error-asset_id"></div>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Vendor</label>
                <input type="text" class="form-control" name="vendor" >
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Asset Type</label>
                <select class="form-select" name="asset_type" id="asset_type" required>
                    <option value="">Select Asset Type</option>
                    <?php while($row = $asset_types_result->fetch_assoc()) { ?>
                        <option value="<?= $row['id'] ?>"><?= $row['asset_type'] ?></option>
                    <?php } ?>
                    <option value="add_new">Add + Asset Type</option>
                </select>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Purchase Date</label>
                <input type="date" class="form-control" name="purchase_date" >
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Windows Key</label>
                <select class="form-select" name="windows_key" >
                    <option value="">Select</option>
                    <?php while($row = $windows_keys->fetch_assoc()) { ?>
                        <option value="<?= $row['id'] ?>"><?= $row['key'] ?></option>
                    <?php } ?>
                </select>
                <div class="invalid-feedback" id="error-windows_key"></div>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Office Key</label>
                <select class="form-select" name="office_key" >
                    <option value="">Select</option>
                    <?php while($row = $office_keys->fetch_assoc()) { ?>
                        <option value="<?= $row['id'] ?>"><?= $row['key'] ?></option>
                    <?php } ?>
                </select>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Anti Defection Key</label>
                <select class="form-select" name="anti_defection_key" >
                    <option value="">Select</option>
                    <?php while($row = $anti_defection_keys->fetch_assoc()) { ?>
                        <option value="<?= $row['id'] ?>"><?= $row['key'] ?></option>
                    <?php } ?>
                </select>
                <div class="invalid-feedback" id="error-anti_defection_key"></div>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Registry Key</label>
                <select class="form-select" name="registry_key" >
                    <option value="">Select</option>
                    <?php while($row = $registry_keys->fetch_assoc()) { ?>
                        <option value="<?= $row['id'] ?>"><?= $row['key'] ?></option>
                    <?php } ?>
                </select>
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label">Remarks</label>
            <textarea class="form-control" name="remarks" rows="3"></textarea>
        </div>

        <div class="text-center">
            <button type="button" class="btn btn-primary" id="submitBtn">Submit</button>
        </div>
    </form>
</div>

<!-- Popup -->
<div id="popup"></div>

<!-- Confirmation Modal -->
<div class="modal fade" id="confirmationModal" tabindex="-1" aria-labelledby="confirmationModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Submission</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to register this asset?
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-primary" id="confirmSubmit">Confirm</button>
            </div>
        </div>
    </div>
</div>

<!-- JS -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<script>
    let isDuplicate = {
        asset_id: false,
        windows_key: false,
        anti_defection_key: false
    };

    function checkDuplicate(field, value) {
        if (!value) return;

        $.post('', { check_duplicate: true, field, value }, function (response) {
            const input = $(`[name="${field}"]`);
            const errorBox = $(`#error-${field}`);

            if (response.trim() === "duplicate") {
                isDuplicate[field] = true;
                input.addClass('is-invalid');
                errorBox.text(`Duplicate ${field.replaceAll('_', ' ')} not allowed.`);
            } else {
                isDuplicate[field] = false;
                input.removeClass('is-invalid');
                errorBox.text('');
            }
        });
    }

    $(() => {
        $('[name="asset_id"]').on('blur', function () {
            checkDuplicate('asset_id', this.value);
        });
        $('[name="windows_key"]').on('change', function () {
            checkDuplicate('windows_key', this.value);
        });
        $('[name="anti_defection_key"]').on('change', function () {
            checkDuplicate('anti_defection_key', this.value);
        });
    });

    $('#asset_type').on('change', function () {
        if ($(this).val() === 'add_new') {
            const newType = prompt("Enter new Asset Type:");
            if (newType) {
                $.post('', { asset_type_to_add: newType }, function (response) {
                    try {
                        const res = JSON.parse(response);
                        if (res.id && res.name) {
                            const newOption = new Option(res.name, res.id, false, true);
                            $('#asset_type').append(newOption).val(res.id);
                            showPopup("Asset Type <strong>" + res.name + "</strong> created!");
                        } else {
                            alert("Failed to add asset type.");
                        }
                    } catch (e) {
                        alert("Invalid server response.");
                    }
                });
            } else {
                $(this).val('');
            }
        }
    });

    $('#submitBtn').on('click', function () {
        const form = document.getElementById('asset-form');
        if (!form.checkValidity()) {
            form.classList.add('was-validated');
        } else {
            const modal = new bootstrap.Modal(document.getElementById('confirmationModal'));
            modal.show();
        }
    });

    $('#confirmSubmit').on('click', function () {
        if (isDuplicate.asset_id || isDuplicate.windows_key || isDuplicate.anti_defection_key) {
            showPopup("❌ Cannot submit. Please fix duplicate entries first!", 'error');
            return;
        }

        const form = $('#asset-form')[0];
        const formData = new FormData(form);

        fetch('', {
            method: 'POST',
            body: formData
        })
        .then(res => res.text())
        .then(response => {
    const modal = bootstrap.Modal.getInstance(document.getElementById('confirmationModal'));
    modal.hide();
    form.reset();
    form.classList.remove('was-validated');

    console.log('Server response:', response); // Add this line

    if (response.includes("success")) {
        showPopup("✅ <strong>Asset</strong> registered successfully!");
    } else {
        showPopup(`❌ Failed to register asset! <br>${response}`, 'error'); // Show actual error
    }
})

        .catch(() => {
            showPopup("❌ An unexpected error occurred!", 'error');
        });
    });

    function showPopup(message, type = 'success') {
        const popup = document.getElementById("popup");
        popup.innerHTML = message;
        popup.style.backgroundColor = (type === 'error') ? '#dc3545' : '#28a745';
        popup.style.display = "block";
        popup.style.opacity = "1";
        setTimeout(() => popup.style.opacity = "0", 2500);
        setTimeout(() => popup.style.display = "none", 3000);
    }
</script>
</body>
</html>
