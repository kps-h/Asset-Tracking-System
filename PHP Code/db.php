<?php
$servername = "localhost";  // Database server
$username = "root";         // Your MySQL username
$password = "";             // Your MySQL password
$dbname = "track";  // Your database name

// Create a connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
