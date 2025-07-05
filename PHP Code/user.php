<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}
// Include database connection
include('db.php');

// Handle AJAX form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['full_name'])) {
    // Sanitize and capture form data
    $full_name = mysqli_real_escape_string($conn, $_POST['full_name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $role = mysqli_real_escape_string($conn, $_POST['role']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);

    // Prepare SQL query
    $sql = "INSERT INTO users (full_name, email, phone, role, status) 
            VALUES (?, ?, ?, ?, ?)";

    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("sssss", $full_name, $email, $phone, $role, $status);

        if ($stmt->execute()) {
            echo "<div class='alert alert-success'>User registered successfully!</div>";
        } else {
            echo "<div class='alert alert-danger'>Error: " . $stmt->error . "</div>";
        }

        $stmt->close();
    }
    exit; // prevent HTML output
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Registration</title>
    <link href="bootstrap.min.css" rel="stylesheet">

    <style>
	body {
    background-color: #f2f7f7;
}
        #popup {
            display: none;
            position: fixed;
            top: 45%;
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
        <h2 class="text-center mb-4">User Form</h2>

        <!-- User Registration Form -->
        <form method="POST" id="registrationForm" novalidate>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="full_name" class="form-label">Full Name</label>
                    <input type="text" class="form-control" id="full_name" name="full_name" required>
                    <div class="invalid-feedback">Please provide your full name.</div>
                </div>

                <div class="col-md-6 mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" >
                    <div class="invalid-feedback">Please provide a valid email address.</div>
                </div>

                <div class="col-md-6 mb-3">
                    <label for="phone" class="form-label">Phone</label>
                    <input type="text" class="form-control" id="phone" name="phone" >
                    <div class="invalid-feedback">Please provide a valid phone number.</div>
                </div>

                <div class="col-md-6 mb-3">
                    <label for="role" class="form-label">Role</label>
                    <input type="text" class="form-control" id="role" name="role" >
                    <div class="invalid-feedback">Please provide a role.</div>
                </div>

                <div class="col-md-6 mb-3">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="Active">Active</option>
                        <option value="Inactive">Inactive</option>
						<option value="Sell">Sell</option>
						<option value="Dispose">Dispose</option>
                    </select>
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
                    <h5 class="modal-title" id="confirmationModalLabel">Confirm Registration</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to submit the registration form?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="confirmSubmit">Submit</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Popup -->
    <div id="popup"></div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Trigger confirmation modal
        document.getElementById('submitBtn').addEventListener('click', () => {
            const form = document.getElementById('registrationForm');
            if (!form.checkValidity()) {
                form.classList.add('was-validated');
            } else {
                const modal = new bootstrap.Modal(document.getElementById('confirmationModal'));
                modal.show();
            }
        });

        // Confirm submission using AJAX
        document.getElementById('confirmSubmit').addEventListener('click', () => {
            const form = document.getElementById('registrationForm');
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

                if (response.includes("alert-success")) {
                    showPopup("✅ <strong>User</strong> registered successfully!");
                } else {
                    showPopup("❌ Failed to register user!", 'error');
                }
            })
            .catch(() => {
                showPopup("❌ Something went wrong!", 'error');
            });
        });

        // Popup display function
        function showPopup(message, type = 'success') {
            const popup = document.getElementById("popup");
            popup.innerHTML = message;
            popup.style.backgroundColor = (type === 'error') ? '#dc3545' : '#198754';
            popup.style.display = "block";
            popup.style.opacity = "1";

            setTimeout(() => popup.style.opacity = "0", 2500);
            setTimeout(() => popup.style.display = "none", 3000);
        }
    </script>
</body>
</html>
