<?php
session_start();

$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

// DB connection (MySQLi)
$conn = mysqli_connect("localhost", "root", " ", "track");

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

$sql = "SELECT * FROM login_users WHERE username = '$username' AND password = '$password'";
$result = mysqli_query($conn, $sql);

if (mysqli_num_rows($result) == 1) {
    $_SESSION['username'] = $username;
    header("Location: nav_bar.php");
    exit();
} else {
    $_SESSION['error'] = "Invalid username or password.";
    header("Location: login.php");
    exit();
}
if (isset($_POST['remember'])) {
    setcookie('remember_user', $username, time() + (86400 * 30), "/"); // 30 days
} else {
    setcookie('remember_user', '', time() - 3600, "/"); // Clear it
}

?>
