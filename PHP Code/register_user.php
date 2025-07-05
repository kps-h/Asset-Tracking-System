<?php
require 'session_check.php';  // Checks login
require 'db.php';             // DB connection

// Only allow admins to access
if ($_SESSION['role'] !== 'admin') {
    die("Access denied. Only admins can add users.");
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $fullname = trim($_POST['fullname']);
    $role = $_POST['role'];

    // Check if username already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);

    if ($stmt->rowCount() > 0) {
        $error = "Username already exists.";
    } else {
        $hashed = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("INSERT INTO users (username, password, full_name, role) VALUES (?, ?, ?, ?)");
        if ($stmt->execute([$username, $hashed, $fullname, $role])) {
            $success = "User added successfully!";
        } else {
            $error = "Something went wrong while saving the user.";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Register New User</title>
    <style>
        body { font-family: sans-serif; background: #f5f5f5; padding: 40px; }
        .form-container {
            max-width: 400px; margin: auto; background: white; padding: 20px; border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        input, select, button {
            width: 100%; padding: 10px; margin-bottom: 15px;
            border-radius: 5px; border: 1px solid #ccc;
        }
        .success { color: green; margin-bottom: 15px; }
        .error { color: red; margin-bottom: 15px; }
    </style>
</head>
<body>

<div class="form-container">
    <h2>Add New User</h2>

    <?php if ($success): ?><div class="success"><?= $success ?></div><?php endif; ?>
    <?php if ($error): ?><div class="error"><?= $error ?></div><?php endif; ?>

    <form method="POST">
        <input type="text" name="fullname" placeholder="Full Name" required>
        <input type="text" name="username" placeholder="Username" required>
        <input type="password" name="password" placeholder="Password" required>
        <select name="role" required>
            <option value="">Select Role</option>
            <option value="user">User</option>
            <option value="admin">Admin</option>
        </select>
        <button type="submit">Add User</button>
    </form>
</div>

</body>
</html>
