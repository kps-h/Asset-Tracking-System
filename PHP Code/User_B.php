<?php
// Database connection
$conn = new mysqli("localhost", "username", "password", "track");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch user data
$sql = "SELECT * FROM users";
$result = $conn->query($sql);

// Handle the delete action
if (isset($_POST['delete'])) {
    $user_id = $_POST['user_id'];
    $delete_sql = "DELETE FROM users WHERE user_id = ?";
    $stmt = $conn->prepare($delete_sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();
    header("Location: ".$_SERVER['PHP_SELF']."?deleted=1"); // Refresh the page to reflect the changes
    exit;
}

// Handle the edit action
if (isset($_POST['edit'])) {
    $user_id = $_POST['user_id'];
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $role = $_POST['role'];
    $status = $_POST['status'];

    $update_sql = "UPDATE users SET full_name = ?, email = ?, phone = ?, role = ?, status = ? WHERE user_id = ?";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("sssssi", $full_name, $email, $phone, $role, $status, $user_id);
    $stmt->execute();
    $stmt->close();
    header("Location: ".$_SERVER['PHP_SELF']); // Refresh the page to reflect the changes
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Report</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css" rel="stylesheet">
</head>
<style>
    thead.custom-red-header th {
        background-color: #f76c6c !important;
        color: white !important;
    }
</style>
<body class="bg-light">
<div class="container py-5">
<?php if (isset($_GET['deleted']) && $_GET['deleted'] == 1): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        User is deleted.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

    <h2 class="mb-4">User Report</h2>
    <table id="userTable" class="table table-bordered table-striped">
        <thead class="custom-red-header">
            <tr>
                <th>User ID</th>
                <th>Full Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Role</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($row['user_id']) ?></td>
                <td><?= htmlspecialchars($row['full_name']) ?></td>
                <td><?= htmlspecialchars($row['email']) ?></td>
                <td><?= ($row['phone'] == '0' || $row['phone'] == '' || $row['phone'] === null) ? '' : htmlspecialchars($row['phone']) ?></td>
                <td><?= htmlspecialchars($row['role']) ?></td>
                <td><?= htmlspecialchars($row['status']) ?></td>
                
                <td>
                    <!-- Edit Button -->
                    <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editModal" 
                            data-user_id="<?= $row['user_id'] ?>" 
                            data-full_name="<?= $row['full_name'] ?>" 
                            data-email="<?= $row['email'] ?>" 
                            data-phone="<?= $row['phone'] ?>" 
                            data-role="<?= $row['role'] ?>" 
                            data-status="<?= $row['status'] ?>">Edit</button>

                    <!-- Delete Button -->
                    <form action="<?= $_SERVER['PHP_SELF'] ?>" method="POST" class="delete-form d-inline">
                        <input type="hidden" name="user_id" value="<?= $row['user_id'] ?>">
                        <button type="submit" name="delete" class="btn btn-danger btn-sm">Delete</button>
                    </form>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editModalLabel">Edit User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="<?= $_SERVER['PHP_SELF'] ?>" method="POST">
                    <input type="hidden" name="user_id" id="editUserId">
                    <div class="mb-3">
                        <label for="editFullName" class="form-label">Full Name</label>
                        <input type="text" class="form-control" name="full_name" id="editFullName" required>
                    </div>
                    <div class="mb-3">
                        <label for="editEmail" class="form-label">Email</label>
                        <input type="email" class="form-control" name="email" id="editEmail">
                    </div>
                    <div class="mb-3">
                        <label for="editPhone" class="form-label">Phone</label>
                        <input type="text" class="form-control" name="phone" id="editPhone">
                    </div>
                    <div class="mb-3">
                        <label for="editRole" class="form-label">Role</label>
                        <input type="text" class="form-control" name="role" id="editRole">
                    </div>
                    <div class="mb-3">
                        <label for="editStatus" class="form-label">Status</label>
                        <input type="text" class="form-control" name="status" id="editStatus" required>
                    </div>
                    <button type="submit" name="edit" class="btn btn-primary">Save changes</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- JS includes -->
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>

<script>
$(document).ready(function () {
    $('#userTable').DataTable({
        dom: 'Bfrtip',
        buttons: ['csv', 'excel'],
        pageLength: 10,
        order: [[0, 'asc']],
        columnDefs: [
            { targets: 3, searchable: true } // phone search enabled
        ]
    });

    // Fill the edit modal with data
    $('#editModal').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget);
        var userId = button.data('user_id');
        var fullName = button.data('full_name');
        var email = button.data('email');
        var phone = button.data('phone');
        var role = button.data('role');
        var status = button.data('status');

        $('#editUserId').val(userId);
        $('#editFullName').val(fullName);
        $('#editEmail').val(email);
        $('#editPhone').val(phone);
        $('#editRole').val(role);
        $('#editStatus').val(status);
    });
});
// Confirm before deleting
$('.delete-form').on('submit', function (e) {
    if (!confirm("Are you sure you want to delete this user?")) {
        e.preventDefault();
    }
});
</script>
</body>
</html>
