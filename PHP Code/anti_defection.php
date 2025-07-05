<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}
include('db.php');

// AJAX: Check for duplicate Anti-Defection key
if (isset($_POST['check_duplicate_key'])) {
    $key = $_POST['key'];
    $stmt = $conn->prepare("SELECT COUNT(*) FROM anti_defection WHERE `key` = ?");
    $stmt->bind_param("s", $key);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();

    echo ($count > 0) ? "duplicate" : "ok";
    exit;
}

// Handle AJAX submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['key'])) {
    $key = $_POST['key'];
    $vendor = $_POST['vendor'];
    $purchase_date = $_POST['purchase_date'];
    $validity = $_POST['validity'];
    $remarks = $_POST['remarks'];

    $sql = "INSERT INTO anti_defection (`key`, vendor, purchase_date, validity, remarks) 
            VALUES (?, ?, ?, ?, ?)";

    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("sssss", $key, $vendor, $purchase_date, $validity, $remarks);
        $stmt->execute();
        $stmt->close();
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Anti-Defection Form</title>
    <link href="bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
	body {
    background-color: #f2f7f7;
}
        #popup {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color: #198754;
            color: white;
            padding: 20px 30px;
            border-radius: 10px;
            font-size: 18px;
            font-weight: 500;
            box-shadow: 0 8px 20px rgba(0,0,0,0.2);
            z-index: 9999;
            opacity: 0;
            transition: opacity 0.5s ease;
            text-align: center;
            min-width: 300px;
        }
    </style>
</head>
<body>
<div class="container mt-5">
    <h2 class="text-center mb-4">Anti-Defection Form</h2>
    <form method="POST" id="antiDefectionForm" novalidate>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="key" class="form-label">Key</label>
                <input type="text" class="form-control" id="key" name="key" required>
                <div class="invalid-feedback" id="error-key">Please provide a key.</div>
            </div>
            <div class="col-md-6 mb-3">
                <label for="vendor" class="form-label">Vendor</label>
                <input type="text" class="form-control" id="vendor" name="vendor" >
                <div class="invalid-feedback">Please provide a vendor name.</div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="purchase_date" class="form-label">Purchase Date</label>
                <input type="date" class="form-control" id="purchase_date" name="purchase_date" >
                <div class="invalid-feedback">Please provide a purchase date.</div>
            </div>
            <div class="col-md-6 mb-3">
                <label for="validity" class="form-label">Validity</label>
                <input type="date" class="form-control" id="validity" name="validity" >
                <div class="invalid-feedback">Please provide a validity date.</div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12 mb-3">
                <label for="remarks" class="form-label">Remarks</label>
                <textarea class="form-control" id="remarks" name="remarks" rows="3"></textarea>
            </div>
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
                Are you sure you want to submit this Anti-Defection entry?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="confirmSubmit">Submit</button>
            </div>
        </div>
    </div>
</div>

<!-- JS Dependencies -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<script>
    let isDuplicateKey = false;

    // Real-time duplicate check
    document.getElementById('key').addEventListener('blur', function () {
        const keyValue = this.value;
        if (!keyValue) return;

        fetch('', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                check_duplicate_key: true,
                key: keyValue
            })
        })
        .then(res => res.text())
        .then(response => {
            const keyInput = document.getElementById('key');
            const errorBox = document.getElementById('error-key');

            if (response.trim() === "duplicate") {
                isDuplicateKey = true;
                keyInput.classList.add('is-invalid');
                errorBox.textContent = "Duplicate key is not allowed.";
            } else {
                isDuplicateKey = false;
                keyInput.classList.remove('is-invalid');
                errorBox.textContent = "Please provide a key.";
            }
        });
    });

    // Validate and show confirmation modal
    document.getElementById('submitBtn').addEventListener('click', () => {
        const form = document.getElementById('antiDefectionForm');
        if (!form.checkValidity() || isDuplicateKey) {
            form.classList.add('was-validated');
            if (isDuplicateKey) {
                const keyInput = document.getElementById('key');
                const errorBox = document.getElementById('error-key');
                keyInput.classList.add('is-invalid');
                errorBox.textContent = "Duplicate key is not allowed.";
            }
            return;
        }

        const modal = new bootstrap.Modal(document.getElementById('confirmationModal'));
        modal.show();
    });

    // On confirm, submit via AJAX
    document.getElementById('confirmSubmit').addEventListener('click', () => {
        const form = document.getElementById('antiDefectionForm');
        const formData = new FormData(form);

        fetch('', {
            method: 'POST',
            body: formData
        })
        .then(res => res.ok ? res.text() : Promise.reject())
        .then(() => {
            const modal = bootstrap.Modal.getInstance(document.getElementById('confirmationModal'));
            modal.hide();
            form.reset();
            form.classList.remove('was-validated');
            showPopup("✅ <strong>Anti-Defection Key</strong> added successfully!");
        })
        .catch(() => {
            showPopup("❌ Something went wrong!", 'error');
        });
    });

    // Show popup
    function showPopup(message, type = 'success') {
        const popup = document.getElementById("popup");
        popup.innerHTML = message;
        popup.style.backgroundColor = type === 'error' ? '#dc3545' : '#198754';
        popup.style.display = "block";
        popup.style.opacity = "1";

        setTimeout(() => popup.style.opacity = "0", 2500);
        setTimeout(() => popup.style.display = "none", 3000);
    }
</script>
</body>
</html>
