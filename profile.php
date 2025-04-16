<?php
require_once 'db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    redirect('login.php');
}

// Get user data
$user_id = $_SESSION['user_id'];
$query = "SELECT * FROM users WHERE user_id = $user_id";
$result = $conn->query($query);
$user = $result->fetch_assoc();

// Process profile update form
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = sanitize($conn, $_POST['full_name']);
    $email = sanitize($conn, $_POST['email']);
    $phone = sanitize($conn, $_POST['phone']);
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate input
    if (empty($full_name) || empty($email) || empty($phone)) {
        $error = 'Please fill in all required fields';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address';
    } else {
        // Check if email already exists (if changed)
        if ($email !== $user['email']) {
            $check_query = "SELECT * FROM users WHERE email = '$email' AND user_id != $user_id";
            $check_result = $conn->query($check_query);
            
            if ($check_result->num_rows > 0) {
                $error = 'Email address already in use';
            }
        }
        
        // If no errors and password change requested
        if (empty($error) && !empty($current_password)) {
            // Verify current password
            if (!verifyPassword($current_password, $user['password'])) {
                $error = 'Current password is incorrect';
            } elseif (empty($new_password) || empty($confirm_password)) {
                $error = 'Please enter and confirm your new password';
            } elseif ($new_password !== $confirm_password) {
                $error = 'New passwords do not match';
            } elseif (strlen($new_password) < 6) {
                $error = 'New password must be at least 6 characters long';
            } else {
                // Hash new password
                $hashed_password = hashPassword($new_password);
                
                // Update user data with new password
                $update_query = "UPDATE users SET full_name = '$full_name', email = '$email', phone = '$phone', password = '$hashed_password' WHERE user_id = $user_id";
            }
        } else {
            // Update user data without changing password
            $update_query = "UPDATE users SET full_name = '$full_name', email = '$email', phone = '$phone' WHERE user_id = $user_id";
        }
        
        // Execute update query if no errors
        if (empty($error)) {
            if ($conn->query($update_query)) {
                $success = 'Profile updated successfully';
                
                // Update session variables
                $_SESSION['full_name'] = $full_name;
                $_SESSION['email'] = $email;
                
                // Refresh user data
                $result = $conn->query("SELECT * FROM users WHERE user_id = $user_id");
                $user = $result->fetch_assoc();
            } else {
                $error = 'Failed to update profile: ' . $conn->error;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Yango</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        :root {
            --primary-color: #ff5722;
            --secondary-color: #ff9800;
            --dark-color: #333;
            --light-color: #f4f4f4;
            --success-color: #28a745;
            --error-color: #dc3545;
        }
        
        body {
            background-color: #f8f9fa;
            color: #333;
            line-height: 1.6;
        }
        
        .container {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 15px;
        }
        
        header {
            background-color: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            position: fixed;
            top: 0;
            width: 100%;
            z-index: 1000;
        }
        
        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
        }
        
        .logo {
            font-size: 28px;
            font-weight: bold;
            color: var(--primary-color);
            text-decoration: none;
        }
        
        .logo span {
            color: var(--secondary-color);
        }
        
        .nav-links {
            display: flex;
            list-style: none;
            align-items: center;
        }
        
        .nav-links li {
            margin-left: 20px;
        }
        
        .nav-links a {
            text-decoration: none;
            color: var(--dark-color);
            font-weight: 500;
            transition: color 0.3s;
        }
        
        .nav-links a:hover {
            color: var(--primary-color);
        }
        
        .user-dropdown {
            position: relative;
            cursor: pointer;
        }
        
        .user-dropdown-toggle {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .user-avatar {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            background-color: var(--primary-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
        
        .user-dropdown-menu {
            position: absolute;
            top: 100%;
            right: 0;
            background-color: white;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            border-radius: 5px;
            min-width: 180px;
            display: none;
            z-index: 1000;
        }
        
        .user-dropdown-menu.show {
            display: block;
        }
        
        .user-dropdown-menu a {
            display: block;
            padding: 10px 15px;
            color: var(--dark-color);
            text-decoration: none;
            transition: background-color 0.3s;
        }
        
        .user-dropdown-menu a:hover {
            background-color: #f5f5f5;
        }
        
        main {
            padding: 100px 0 50px;
        }
        
        .page-header {
            margin-bottom: 30px;
        }
        
        .page-header h1 {
            color: var(--dark-color);
            margin-bottom: 10px;
        }
        
        .page-header p {
            color: #777;
        }
        
        .profile-container {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 30px;
        }
        
        .profile-sidebar {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            padding: 30px;
            text-align: center;
        }
        
        .profile-avatar {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            background-color: var(--primary-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 60px;
            font-weight: bold;
            margin: 0 auto 20px;
        }
        
        .profile-name {
            font-size: 1.5rem;
            font-weight: bold;
            margin-bottom: 5px;
            color: var(--dark-color);
        }
        
        .profile-username {
            color: #777;
            margin-bottom: 20px;
        }
        
        .profile-stats {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .stat-item {
            padding: 15px;
            background-color: #f9f9f9;
            border-radius: 5px;
        }
        
        .stat-value {
            font-size: 1.5rem;
            font-weight: bold;
            color: var(--primary-color);
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: #777;
            font-size: 0.9rem;
        }
        
        .profile-actions {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        
        .profile-actions a {
            padding: 10px;
            background-color: #f5f5f5;
            border-radius: 5px;
            text-decoration: none;
            color: var(--dark-color);
            transition: all 0.3s;
        }
        
        .profile-actions a:hover {
            background-color: var(--primary-color);
            color: white;
        }
        
        .profile-content {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            padding: 30px;
        }
        
        .profile-content h2 {
            color: var(--dark-color);
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }
        
        .form-control {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
        }
        
        .form-row {
            display: flex;
            gap: 15px;
        }
        
        .form-row .form-group {
            flex: 1;
        }
        
        .password-section {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        
        .password-section h3 {
            color: var(--dark-color);
            margin-bottom: 20px;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 25px;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            font-weight: 500;
            transition: background-color 0.3s;
            font-size: 16px;
        }
        
        .btn:hover {
            background-color: var(--secondary-color);
        }
        
        .error-message {
            background-color: rgba(220, 53, 69, 0.1);
            color: var(--error-color);
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .success-message {
            background-color: rgba(40, 167, 69, 0.1);
            color: var(--success-color);
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        footer {
            background-color: var(--dark-color);
            color: white;
            padding: 20px 0;
            text-align: center;
            margin-top: 50px;
        }
        
        @media (max-width: 992px) {
            .profile-container {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 768px) {
            .navbar {
                flex-direction: column;
            }
            
            .nav-links {
                margin-top: 15px;
            }
            
            .form-row {
                flex-direction: column;
                gap: 0;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <nav class="navbar">
                <a href="dashboard.php" class="logo">Yan<span>go</span></a>
                <ul class="nav-links">
                    <li><a href="dashboard.php">Dashboard</a></li>
                    <li><a href="booking.php">Book a Ride</a></li>
                    <li><a href="history.php">Ride History</a></li>
                    <li class="user-dropdown">
                        <div class="user-dropdown-toggle">
                            <div class="user-avatar"><?php echo substr($user['full_name'], 0, 1); ?></div>
                            <span><?php echo $user['username']; ?></span>
                        </div>
                        <div class="user-dropdown-menu">
                            <a href="profile.php">My Profile</a>
                            <a href="settings.php">Settings</a>
                            <a href="logout.php">Logout</a>
                        </div>
                    </li>
                </ul>
            </nav>
        </div>
    </header>

    <main>
        <div class="container">
            <div class="page-header">
                <h1>My Profile</h1>
                <p>View and update your personal information</p>
            </div>
            
            <div class="profile-container">
                <div class="profile-sidebar">
                    <div class="profile-avatar"><?php echo substr($user['full_name'], 0, 1); ?></div>
                    <div class="profile-name"><?php echo $user['full_name']; ?></div>
                    <div class="profile-username">@<?php echo $user['username']; ?></div>
                    
                    <div class="profile-stats">
                        <?php
                        // Get total rides count
                        $rides_query = "SELECT COUNT(*) as count FROM rides WHERE user_id = $user_id";
                        $rides_result = $conn->query($rides_query);
                        $total_rides = $rides_result->fetch_assoc()['count'];
                        
                        // Get completed rides count
                        $completed_query = "SELECT COUNT(*) as count FROM rides WHERE user_id = $user_id AND status = 'completed'";
                        $completed_result = $conn->query($completed_query);
                        $completed_rides = $completed_result->fetch_assoc()['count'];
                        ?>
                        <div class="stat-item">
                            <div class="stat-value"><?php echo $total_rides; ?></div>
                            <div class="stat-label">Total Rides</div>
                        </div>
                        
                        <div class="stat-item">
                            <div class="stat-value"><?php echo $completed_rides; ?></div>
                            <div class="stat-label">Completed</div>
                        </div>
                    </div>
                    
                    <div class="profile-actions">
                        <a href="history.php">View Ride History</a>
                        <a href="settings.php">Account Settings</a>
                        <a href="logout.php">Logout</a>
                    </div>
                </div>
                
                <div class="profile-content">
                    <h2>Edit Profile</h2>
                    
                    <?php if ($error): ?>
                        <div class="error-message"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="success-message"><?php echo $success; ?></div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="full_name">Full Name</label>
                            <input type="text" id="full_name" name="full_name" class="form-control" value="<?php echo $user['full_name']; ?>" required>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="email">Email</label>
                                <input type="email" id="email" name="email" class="form-control" value="<?php echo $user['email']; ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="phone">Phone Number</label>
                                <input type="tel" id="phone" name="phone" class="form-control" value="<?php echo $user['phone']; ?>" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="username">Username</label>
                            <input type="text" id="username" class="form-control" value="<?php echo $user['username']; ?>" disabled>
                            <small style="color: #777;">Username cannot be changed</small>
                        </div>
                        
                        <div class="password-section">
                            <h3>Change Password</h3>
                            <p style="color: #777; margin-bottom: 15px;">Leave these fields empty if you don't want to change your password</p>
                            
                            <div class="form-group">
                                <label for="current_password">Current Password</label>
                                <input type="password" id="current_password" name="current_password" class="form-control" placeholder="Enter your current password">
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="new_password">New Password</label>
                                    <input type="password" id="new_password" name="new_password" class="form-control" placeholder="Enter new password">
                                </div>
                                
                                <div class="form-group">
                                    <label for="confirm_password">Confirm New Password</label>
                                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="Confirm new password">
                                </div>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn">Save Changes</button>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <footer>
        <div class="container">
            <p>&copy; 2023 Yango. All rights reserved.</p>
        </div>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Toggle user dropdown menu
            const userDropdown = document.querySelector('.user-dropdown');
            const dropdownMenu = document.querySelector('.user-dropdown-menu');
            
            userDropdown.addEventListener('click', function(e) {
                dropdownMenu.classList.toggle('show');
                e.stopPropagation();
            });
            
            document.addEventListener('click', function(e) {
                if (!userDropdown.contains(e.target)) {
                    dropdownMenu.classList.remove('show');
                }
            });
        });
    </script>
</body>
</html>
