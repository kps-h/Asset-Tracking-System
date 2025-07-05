<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}
include('db.php');

// AJAX: Check for duplicate key
if (isset($_POST['check_duplicate_key'])) {
    $key = $_POST['key'];

    $stmt = $conn->prepare("SELECT COUNT(*) FROM windows_table WHERE `key` = ?");
    $stmt->bind_param("s", $key);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();

    echo ($count > 0) ? "duplicate" : "ok";
    exit;
}

// Flag to trigger toast after successful submission
$showToast = false;

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['key'])) {
    $key = $_POST['key'];
    $vendor = $_POST['vendor'];
    $original_or_pirated = $_POST['original_or_pirated'];
    $purchase_date = $_POST['purchase_date'];
    $validity = $_POST['validity'];
    $remarks = $_POST['remarks'];

    $sql = "INSERT INTO windows_table (`key`, vendor, original_or_pirated, purchase_date, validity, remarks) 
            VALUES (?, ?, ?, ?, ?, ?)";

    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("ssssss", $key, $vendor, $original_or_pirated, $purchase_date, $validity, $remarks);
        if ($stmt->execute()) {
            $showToast = true;
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Window Information Form</title>
    <link href="bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
	body {
    background-color: #f2f7f7;
}
        .custom-toast {
            position: fixed;
            top: 490px;
            left: 50%;
            transform: translateX(-50%);
            min-width: 350px;
            background-color: #198754;
            color: white;
            border-radius: 10px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
            text-align: center;
            font-size: 1rem;
            font-weight: 500;
            padding: 15px 20px;
            z-index: 9999;
            opacity: 1;
            transition: opacity 1s ease-out;
        }
        .custom-toast.fade-out {
            opacity: 0;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <h2 class="text-center mb-4">Windows Key</h2>
        <form action="" method="POST" id="windowsForm" novalidate>
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
                    <label for="original_or_pirated" class="form-label">Original or Pirated</label>
                    <select class="form-select" id="original_or_pirated" name="original_or_pirated" >
                        <option value="">Select...</option>
                        <option value="Original">Original</option>
                        <option value="Pirated">Pirated</option>
                    </select>
                    <div class="invalid-feedback">Please select Original or Pirated.</div>
                </div>

                <div class="col-md-6 mb-3">
                    <label for="purchase_date" class="form-label">Purchase Date</label>
                    <input type="date" class="form-control" id="purchase_date" name="purchase_date" >
                    <div class="invalid-feedback">Please provide a purchase date.</div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="validity" class="form-label">Validity</label>
                    <input type="date" class="form-control" id="validity" name="validity" >
                    <div class="invalid-feedback">Please provide a validity date.</div>
                </div>

                <div class="col-md-6 mb-3">
                    <label for="remarks" class="form-label">Remarks</label>
                    <textarea class="form-control" id="remarks" name="remarks" rows="3"></textarea>
                </div>
            </div>

            <div class="text-center">
                <button type="button" class="btn btn-primary" id="submitBtn">Submit</button>
            </div>
        </form>
    </div>

    <!-- Confirmation Modal -->
    <div class="modal fade" id="confirmationModal" tabindex="-1" aria-labelledby="confirmationModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmationModalLabel">Confirm Submission</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to submit the form?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" form="windowsForm">Submit</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Success Toast -->
    <?php if ($showToast): ?>
        <div class="custom-toast show" role="alert" aria-live="assertive" aria-atomic="true" id="successToast">
            ✅ New Windows Key is created.
        </div>
    <?php endif; ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        let isKeyDuplicate = false;

        // Check for duplicate key on blur
        document.getElementById('key').addEventListener('blur', function () {
            const keyValue = this.value;
            if (!keyValue) return;

            $.post('', { check_duplicate_key: true, key: keyValue }, function (response) {
                const keyInput = document.getElementById('key');
                const errorBox = document.getElementById('error-key');

                if (response.trim() === "duplicate") {
                    isKeyDuplicate = true;
                    keyInput.classList.add('is-invalid');
                    errorBox.textContent = "Duplicate key is not allowed.";
                } else {
                    isKeyDuplicate = false;
                    keyInput.classList.remove('is-invalid');
                    errorBox.textContent = "Please provide a key.";
                }
            });
        });

        // Handle submit click
        document.getElementById('submitBtn').addEventListener('click', function () {
            const form = document.getElementById('windowsForm');
            if (form.checkValidity() === false || isKeyDuplicate) {
                form.classList.add('was-validated');
                if (isKeyDuplicate) {
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

        // Fade out toast
        window.addEventListener('DOMContentLoaded', function () {
            const toastEl = document.getElementById('successToast');
            if (toastEl) {
                setTimeout(() => {
                    toastEl.classList.add('fade-out');
                    setTimeout(() => toastEl.classList.remove('show'), 1000);
                }, 3000);
            }
        });
    </script>
</body>
</html>
