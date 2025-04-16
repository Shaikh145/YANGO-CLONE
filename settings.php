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

// Process settings update
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    
    if ($action === 'notifications') {
        // Update notification settings
        $email_notifications = isset($_POST['email_notifications']) ? 1 : 0;
        $sms_notifications = isset($_POST['sms_notifications']) ? 1 : 0;
        $promo_notifications = isset($_POST['promo_notifications']) ? 1 : 0;
        
        $update_query = "UPDATE users SET 
                        email_notifications = $email_notifications,
                        sms_notifications = $sms_notifications,
                        promo_notifications = $promo_notifications
                        WHERE user_id = $user_id";
        
        if ($conn->query($update_query)) {
            $success = 'Notification settings updated successfully';
            
            // Refresh user data
            $result = $conn->query("SELECT * FROM users WHERE user_id = $user_id");
            $user = $result->fetch_assoc();
        } else {
            $error = 'Failed to update notification settings: ' . $conn->error;
        }
    } elseif ($action === 'payment') {
        // Update payment settings
        $default_payment = sanitize($conn, $_POST['default_payment']);
        
        $update_query = "UPDATE users SET default_payment = '$default_payment' WHERE user_id = $user_id";
        
        if ($conn->query($update_query)) {
            $success = 'Payment settings updated successfully';
            
            // Refresh user data
            $result = $conn->query("SELECT * FROM users WHERE user_id = $user_id");
            $user = $result->fetch_assoc();
        } else {
            $error = 'Failed to update payment settings: ' . $conn->error;
        }
    } elseif ($action === 'privacy') {
        // Update privacy settings
        $share_location = isset($_POST['share_location']) ? 1 : 0;
        $share_ride_info = isset($_POST['share_ride_info']) ? 1 : 0;
        
        $update_query = "UPDATE users SET 
                        share_location = $share_location,
                        share_ride_info = $share_ride_info
                        WHERE user_id = $user_id";
        
        if ($conn->query($update_query)) {
            $success = 'Privacy settings updated successfully';
            
            // Refresh user data
            $result = $conn->query("SELECT * FROM users WHERE user_id = $user_id");
            $user = $result->fetch_assoc();
        } else {
            $error = 'Failed to update privacy settings: ' . $conn->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Settings - Yango</title>
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
        
        .settings-container {
            display: grid;
            grid-template-columns: 1fr 3fr;
            gap: 30px;
        }
        
        .settings-sidebar {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            padding: 20px;
        }
        
        .settings-nav {
            list-style: none;
        }
        
        .settings-nav li {
            margin-bottom: 10px;
        }
        
        .settings-nav a {
            display: block;
            padding: 12px 15px;
            text-decoration: none;
            color: var(--dark-color);
            border-radius: 5px;
            transition: all 0.3s;
        }
        
        .settings-nav a:hover, .settings-nav a.active {
            background-color: var(--primary-color);
            color: white;
        }
        
        .settings-content {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            padding: 30px;
        }
        
        .settings-section {
            display: none;
        }
        
        .settings-section.active {
            display: block;
        }
        
        .settings-section h2 {
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
        
        .checkbox-group {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .checkbox-group input[type="checkbox"] {
            margin-right: 10px;
            width: 18px;
            height: 18px;
        }
        
        .radio-group {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .radio-group input[type="radio"] {
            margin-right: 10px;
            width: 18px;
            height: 18px;
        }
        
        .payment-methods {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .payment-method {
            border: 2px solid #ddd;
            border-radius: 5px;
            padding: 15px;
            cursor: pointer;
            transition: all 0.3s;
            flex: 1;
            text-align: center;
        }
        
        .payment-method:hover, .payment-method.active {
            border-color: var(--primary-color);
            background-color: rgba(255, 87, 34, 0.05);
        }
        
        .payment-icon {
            font-size: 30px;
            margin-bottom: 10px;
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
        
        .btn-danger {
            background-color: var(--error-color);
        }
        
        .btn-danger:hover {
            background-color: #bd2130;
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
        
        .danger-zone {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        
        .danger-zone h3 {
            color: var(--error-color);
            margin-bottom: 15px;
        }
        
        .danger-zone p {
            color: #777;
            margin-bottom: 20px;
        }
        
        footer {
            background-color: var(--dark-color);
            color: white;
            padding: 20px 0;
            text-align: center;
            margin-top: 50px;
        }
        
        @media (max-width: 992px) {
            .settings-container {
                grid-template-columns: 1fr;
            }
            
            .settings-sidebar {
                margin-bottom: 20px;
            }
            
            .settings-nav {
                display: flex;
                flex-wrap: wrap;
                gap: 10px;
            }
            
            .settings-nav li {
                margin-bottom: 0;
            }
        }
        
        @media (max-width: 768px) {
            .navbar {
                flex-direction: column;
            }
            
            .nav-links {
                margin-top: 15px;
            }
            
            .payment-methods {
                flex-direction: column;
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
                <h1>Account Settings</h1>
                <p>Manage your account preferences and settings</p>
            </div>
            
            <?php if ($error): ?>
                <div class="error-message"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="success-message"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <div class="settings-container">
                <div class="settings-sidebar">
                    <ul class="settings-nav">
                        <li><a href="#notifications" class="active">Notifications</a></li>
                        <li><a href="#payment">Payment Methods</a></li>
                        <li><a href="#privacy">Privacy</a></li>
                        <li><a href="#security">Security</a></li>
                        <li><a href="#account">Account</a></li>
                    </ul>
                </div>
                
                <div class="settings-content">
                    <div id="notifications-section" class="settings-section active">
                        <h2>Notification Settings</h2>
                        <p style="margin-bottom: 20px; color: #777;">Control how you receive notifications from Yango</p>
                        
                        <form method="POST" action="">
                            <input type="hidden" name="action" value="notifications">
                            
                            <div class="checkbox-group">
                                <input type="checkbox" id="email_notifications" name="email_notifications" <?php echo isset($user['email_notifications']) && $user['email_notifications'] ? 'checked' : ''; ?>>
                                <label for="email_notifications">Email Notifications</label>
                            </div>
                            
                            <div class="checkbox-group">
                                <input type="checkbox" id="sms_notifications" name="sms_notifications" <?php echo isset($user['sms_notifications']) && $user['sms_notifications'] ? 'checked' : ''; ?>>
                                <label for="sms_notifications">SMS Notifications</label>
                            </div>
                            
                            <div class="checkbox-group">
                                <input type="checkbox" id="promo_notifications" name="promo_notifications" <?php echo isset($user['promo_notifications']) && $user['promo_notifications'] ? 'checked' : ''; ?>>
                                <label for="promo_notifications">Promotional Notifications</label>
                            </div>
                            
                            <button type="submit" class="btn">Save Changes</button>
                        </form>
                    </div>
                    
                    <div id="payment-section" class="settings-section">
                        <h2>Payment Methods</h2>
                        <p style="margin-bottom: 20px; color: #777;">Manage your payment methods and preferences</p>
                        
                        <form method="POST" action="">
                            <input type="hidden" name="action" value="payment">
                            
                            <div class="form-group">
                                <label>Default Payment Method</label>
                                <div class="payment-methods">
                                    <div class="payment-method <?php echo (!isset($user['default_payment']) || $user['default_payment'] === 'cash') ? 'active' : ''; ?>" data-method="cash">
                                        <div class="payment-icon">💵</div>
                                        <div>Cash</div>
                                    </div>
                                    
                                    <div class="payment-method <?php echo (isset($user['default_payment']) && $user['default_payment'] === 'card') ? 'active' : ''; ?>" data-method="card">
                                        <div class="payment-icon">💳</div>
                                        <div>Card</div>
                                    </div>
                                    
                                    <div class="payment-method <?php echo (isset($user['default_payment']) && $user['default_payment'] === 'wallet') ? 'active' : ''; ?>" data-method="wallet">
                                        <div class="payment-icon">👛</div>
                                        <div>Wallet</div>
                                    </div>
                                </div>
                                <input type="hidden" name="default_payment" id="default_payment" value="<?php echo isset($user['default_payment']) ? $user['default_payment'] : 'cash'; ?>">
                            </div>
                            
                            <button type="submit" class="btn">Save Changes</button>
                        </form>
                    </div>
                    
                    <div id="privacy-section" class="settings-section">
                        <h2>Privacy Settings</h2>
                        <p style="margin-bottom: 20px; color: #777;">Control your privacy preferences</p>
                        
                        <form method="POST" action="">
                            <input type="hidden" name="action" value="privacy">
                            
                            <div class="checkbox-group">
                                <input type="checkbox" id="share_location" name="share_location" <?php echo isset($user['share_location']) && $user['share_location'] ? 'checked' : ''; ?>>
                                <label for="share_location">Share my location with driver</label>
                            </div>
                            
                            <div class="checkbox-group">
                                <input type="checkbox" id="share_ride_info" name="share_ride_info" <?php echo isset($user['share_ride_info']) && $user['share_ride_info'] ? 'checked' : ''; ?>>
                                <label for="share_ride_info">Allow sharing ride information with friends</label>
                            </div>
                            
                            <button type="submit" class="btn">Save Changes</button>
                        </form>
                    </div>
                    
                    <div id="security-section" class="settings-section">
                        <h2>Security Settings</h2>
                        <p style="margin-bottom: 20px; color: #777;">Manage your account security</p>
                        
                        <div class="form-group">
                            <label>Change Password</label>
                            <p style="color: #777; margin-bottom: 10px;">To change your password, please visit the <a href="profile.php" style="color: var(--primary-color);">Profile</a> page.</p>
                        </div>
                        
                        <div class="form-group">
                            <label>Two-Factor Authentication</label>
                            <p style="color: #777; margin-bottom: 10px;">Enhance your account security by enabling two-factor authentication.</p>
                            <button class="btn">Enable 2FA</button>
                        </div>
                    </div>
                    
                    <div id="account-section" class="settings-section">
                        <h2>Account Settings</h2>
                        <p style="margin-bottom: 20px; color: #777;">Manage your account preferences</p>
                        
                        <div class="form-group">
                            <label>Language</label>
                            <select class="form-control">
                                <option value="en">English</option>
                                <option value="es">Spanish</option>
                                <option value="fr">French</option>
                                <option value="de">German</option>
                                <option value="hi">Hindi</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Currency</label>
                            <select class="form-control">
                                <option value="inr">Indian Rupee (₹)</option>
                                <option value="usd">US Dollar ($)</option>
                                <option value="eur">Euro (€)</option>
                                <option value="gbp">British Pound (£)</option>
                            </select>
                        </div>
                        
                        <button class="btn">Save Changes</button>
                        
                        <div class="danger-zone">
                            <h3>Danger Zone</h3>
                            <p>Once you delete your account, there is no going back. Please be certain.</p>
                            <button class="btn btn-danger">Delete Account</button>
                        </div>
                    </div>
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
            
            // Settings navigation
            const navLinks = document.querySelectorAll('.settings-nav a');
            const sections = document.querySelectorAll('.settings-section');
            
            navLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    // Update active link
                    navLinks.forEach(l => l.classList.remove('active'));
                    this.classList.add('active');
                    
                    // Show corresponding section
                    const targetId = this.getAttribute('href').substring(1);
                    sections.forEach(section => {
                        section.classList.remove('active');
                        if (section.id === targetId + '-section') {
                            section.classList.add('active');
                        }
                    });
                });
            });
            
            // Payment method selection
            const paymentMethods = document.querySelectorAll('.payment-method');
            const defaultPaymentInput = document.getElementById('default_payment');
            
            paymentMethods.forEach(method => {
                method.addEventListener('click', function() {
                    paymentMethods.forEach(m => m.classList.remove('active'));
                    this.classList.add('active');
                    defaultPaymentInput.value = this.dataset.method;
                });
            });
            
            // Delete account confirmation
            const deleteAccountBtn = document.querySelector('.btn-danger');
            if (deleteAccountBtn) {
                deleteAccountBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    if (confirm('Are you sure you want to delete your account? This action cannot be undone.')) {
                        alert('Account deletion request submitted. An email has been sent to confirm this action.');
                    }
                });
            }
        });
    </script>
</body>
</html>
