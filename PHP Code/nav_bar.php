<?php
session_start();

// Assuming user is stored in the session as 'username'
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$currentUser = $_SESSION['username'];  // The current logged-in user
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <title>Modern Navigation Bar</title>
    <style>
        /* Basic reset */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html, body {
            height: 100%;
            overflow: hidden;
            font-family: 'Arial', sans-serif;
        }

        body {
            display: flex;
            flex-direction: column;
        }

        /* Navbar Styles */
        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: #3B9C9C;
            padding: 10px 20px;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 60px;
            z-index: 1000;
        }

        .navbar .logo a {
            color: #fff;
            font-size: 24px;
            text-decoration: none;
        }

        .nav-links {
            list-style: none;
            display: flex;
            gap: 20px;
        }

        .nav-links li {
            position: relative;
        }

        .nav-links li a {
            color: #fff;
            text-decoration: none;
            padding: 10px 15px;
            font-size: 18px;
            transition: background 0.3s ease;
        }

        .nav-links li a:hover {
            background-color: rgba(255, 255, 255, 0.2);
            border-radius: 5px;
        }

        /* Dropdown Menu */
        .dropdown-menu {
            display: none;
            position: absolute;
            background-color: #f76c6c;
            top: 100%;
            left: 0;
            width: 200px;
            list-style: none;
            padding: 10px 0;
            border-radius: 5px;
        }

        .dropdown-menu li a {
            display: block;
            padding: 10px 15px;
            font-size: 16px;
            color: #fff;
            text-decoration: none;
            transition: text-decoration 0.3s ease;
        }

        .dropdown-menu li a:hover {
            text-decoration: underline;
        }

        .dropdown:hover .dropdown-menu {
            display: block;
        }

        /* CTA Button */
        .cta-btn a {
            color: white;
            background-color: #f76c6c;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 18px;
            cursor: pointer;
        }

        .cta-btn a:hover {
            background-color: #ff4747;
        }

        /* Settings Dropdown */
        .settings-dropdown {
            display: none;
            background-color: #f76c6c;
            padding: 10px;
            position: absolute;
            top: 60px;
            right: 0;
            width: 200px;
            box-shadow: 0px 5px 10px rgba(0, 0, 0, 0.1);
            border-radius: 5px;
        }

        .settings-dropdown a {
            color: white;
            text-decoration: none;
            padding: 10px;
            display: block;
            font-size: 16px;
        }

        .settings-dropdown a:hover {
            background-color: #ff4747;
            border-radius: 5px;
        }

        .hamburger {
            display: none;
            flex-direction: column;
            gap: 5px;
            cursor: pointer;
        }

        .hamburger span {
            width: 25px;
            height: 3px;
            background-color: white;
        }

        @media (max-width: 768px) {
            .nav-links {
                display: none;
                flex-direction: column;
                gap: 10px;
                width: 100%;
                background-color: #333;
                position: absolute;
                top: 60px;
                left: 0;
                padding: 20px;
                box-shadow: 0px 5px 10px rgba(0, 0, 0, 0.1);
            }

            .nav-links li a {
                font-size: 20px;
                padding: 15px;
                text-align: center;
                width: 100%;
            }

            .hamburger {
                display: flex;
            }

            .nav-links.active {
                display: flex;
            }
        }

        /* Iframe container */
        .iframe-container {
            flex: 1;
            margin-top: 60px; /* height of navbar */
            overflow: auto;
        }

        iframe {
            width: 100%;
            height: 100%;
            border: none;
        }
    </style>
</head>
<body>
    <header>
        <nav class="navbar">
            <div class="logo">
                <a href="#">
                    <i class="fas fa-boxes-stacked" style="margin-right: 8px; color: #f76c6c;"></i>
                    Asset Tracking System
                </a>
            </div>
            <ul class="nav-links">
                <li><a href="dashboard.php" target="iframe">Home</a></li>
                <li class="dropdown">
                    <a href="javascript:void(0)">Assets</a>
                    <ul class="dropdown-menu">
                        <li><a href="asset_registration_V2.php" target="iframe"> + Asset Registration</a></li>
                        <li><a href="Final_Test_B_1.php" target="iframe"> ~ Asset Transaction</a></li>
						<li><a href="add_seat_number.php" target="iframe"> + ADD Seat</a></li>
						<li><a href="assign_seat_to_user.php" target="iframe"> + Assign Seat to user</a></li>
                    </ul>
                </li>
                <li class="dropdown">
                    <a href="javascript:void(0)">Keys</a>
                    <ul class="dropdown-menu">
                        <li><a href="windows.php" target="iframe"> + Windows Key</a></li>
                        <li><a href="office.php" target="iframe"> + Office Key</a></li>
                        <li><a href="anti_defection.php" target="iframe"> + Anti-Defection Key</a></li>
                        <li><a href="registry_key.php" target="iframe"> + Registry Key</a></li>
						<li><a href="anti_defection.php" target="iframe"> + Anti-Defection Key</a></li>
                        <li><a href="Test_B_2.php" target="iframe"> + Amendments of Key</a></li>
                    </ul>
                </li>
                <li class="dropdown">
                    <a href="javascript:void(0)">User</a>
                    <ul class="dropdown-menu">
                        <li><a href="user.php" target="iframe"> + Add User</a></li>
                        <li><a href="User_B.php" target="iframe">User Report</a></li>
                    </ul>
                </li>
				<li class="dropdown">
                    <a href="javascript:void(0)">Report</a>
                    <ul class="dropdown-menu">
                        <li><a href="Test_B.php" target="iframe">Asset Registration Report</a></li>
                        <li><a href="Windows_Test_B.php" target="iframe">Windows Key Report</a></li>
						<li><a href="office_Test_B.php" target="iframe">Office Key Report</a></li>
						<li><a href="anti_Test_B.php" target="iframe">Anti-Defection Key Report</a></li>
						<li><a href="registry_Test_B.php" target="iframe">Registry Key Report</a></li>
						<li><a href="Assign_Test_B_1.php" target="iframe">Assign Asset Report</a></li>
                    </ul>
                </li>
                <li><a href="#"></a></li>	
            </ul>
            <div class="cta-btn">
                <a href="javascript:void(0);" id="settingsBtn">Setting</a>
            </div>
            <div class="hamburger" id="hamburger">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </nav>
    </header>

    <!-- Settings Dropdown -->
    <div class="settings-dropdown" id="settingsDropdown">
        <p>Current User: <strong><?php echo htmlspecialchars($currentUser); ?></strong></p>
        <a href="logout.php">Log Out</a>
    </div>

    <!-- Scrollable Iframe Content -->
    <div class="iframe-container">
        <iframe name="iframe" src="dashboard.php"></iframe>
    </div>

    <script>
        document.getElementById('hamburger').addEventListener('click', () => {
            document.querySelector('.nav-links').classList.toggle('active');
        });

        // Toggle Settings Dropdown
        document.getElementById('settingsBtn').addEventListener('click', () => {
            const settingsDropdown = document.getElementById('settingsDropdown');
            settingsDropdown.style.display = settingsDropdown.style.display === 'block' ? 'none' : 'block';
        });
    </script>
</body>
</html>
