<?php
session_start();
$error = $_SESSION['error'] ?? '';
unset($_SESSION['error']);
$remembered_user = $_COOKIE['remember_user'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login | Asset Management</title>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 0;
            background: #3B9C9C;
            font-family: 'Segoe UI', sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .login-container {
            background: #fff;
            padding: 40px;
            border-radius: 16px;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .login-container h2 {
            margin-bottom: 30px;
            color: #3B9C9C;
            text-align: center;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-size: 14px;
            margin-bottom: 8px;
            color: #333;
        }

        .form-group input[type="text"],
        .form-group input[type="password"] {
            width: 100%;
            padding: 12px;
            font-size: 15px;
            border: 1px solid #ccc;
            border-radius: 8px;
            transition: 0.2s border-color ease;
        }

        .form-group input:focus {
            border-color: #3B9C9C;
            outline: none;
        }

        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 13px;
            margin-top: -10px;
            margin-bottom: 20px;
        }

        .form-options label {
            color: #666;
        }

        .login-btn {
            background: #f76c6c;
            color: white;
            border: none;
            padding: 12px;
            width: 100%;
            font-size: 16px;
            font-weight: bold;
            border-radius: 8px;
            cursor: pointer;
            transition: background 0.3s ease;
        }

        .login-btn:hover {
            background: #e85c5c;
        }

        .error-message {
            background-color: #f76c6c;
            color: white;
            padding: 10px 15px;
            text-align: center;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        @media (max-width: 500px) {
            .login-container {
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>

<div class="login-container">
    <h2>Login</h2>

    <?php if ($error): ?>
        <div class="error-message"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="auth.php">
        <div class="form-group">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" value="<?= htmlspecialchars($remembered_user) ?>" required>
        </div>

        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password-field" name="password" required>
        </div>

        <div class="form-options">
            <label><input type="checkbox" name="remember"> Remember Me</label>
            <label><input type="checkbox" id="togglePassword"> Show Password</label>
        </div>

        <button type="submit" class="login-btn">Login</button>
    </form>
</div>

<script>
document.getElementById("togglePassword").addEventListener("change", function () {
    var pwdField = document.getElementById("password-field");
    pwdField.type = this.checked ? "text" : "password";
});
</script>

</body>
</html>
