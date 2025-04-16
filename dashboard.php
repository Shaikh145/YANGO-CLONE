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

// Get user's ride history
$query = "SELECT r.*, d.full_name as driver_name, rt.type_name 
          FROM rides r 
          LEFT JOIN drivers d ON r.driver_id = d.driver_id 
          LEFT JOIN ride_types rt ON r.ride_type_id = rt.type_id 
          WHERE r.user_id = $user_id 
          ORDER BY r.created_at DESC";
$rides_result = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Yango</title>
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
            --info-color: #17a2b8;
            --warning-color: #ffc107;
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
        
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            font-weight: 500;
            transition: background-color 0.3s;
        }
        
        .btn:hover {
            background-color: var(--secondary-color);
        }
        
        .btn-outline {
            background-color: transparent;
            border: 2px solid var(--primary-color);
            color: var(--primary-color);
        }
        
        .btn-outline:hover {
            background-color: var(--primary-color);
            color: white;
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
        
        .dashboard-header {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            padding: 30px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .welcome-message h1 {
            color: var(--dark-color);
            margin-bottom: 10px;
        }
        
        .welcome-message p {
            color: #777;
        }
        
        .dashboard-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            padding: 20px;
            text-align: center;
        }
        
        .stat-card h3 {
            color: var(--dark-color);
            margin-bottom: 10px;
        }
        
        .stat-card .stat-value {
            font-size: 2rem;
            font-weight: bold;
            color: var(--primary-color);
            margin-bottom: 10px;
        }
        
        .stat-card .stat-label {
            color: #777;
        }
        
        .dashboard-content {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
        }
        
        .ride-history {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            padding: 30px;
        }
        
        .ride-history h2 {
            color: var(--dark-color);
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .ride-list {
            max-height: 500px;
            overflow-y: auto;
        }
        
        .ride-card {
            border: 1px solid #eee;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 15px;
        }
        
        .ride-card:last-child {
            margin-bottom: 0;
        }
        
        .ride-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        
        .ride-date {
            color: #777;
            font-size: 0.9rem;
        }
        
        .ride-status {
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .status-pending {
            background-color: rgba(255, 193, 7, 0.1);
            color: var(--warning-color);
        }
        
        .status-accepted {
            background-color: rgba(23, 162, 184, 0.1);
            color: var(--info-color);
        }
        
        .status-in_progress {
            background-color: rgba(0, 123, 255, 0.1);
            color: #007bff;
        }
        
        .status-completed {
            background-color: rgba(40, 167, 69, 0.1);
            color: var(--success-color);
        }
        
        .status-cancelled {
            background-color: rgba(220, 53, 69, 0.1);
            color: var(--error-color);
        }
        
        .ride-details {
            margin-bottom: 10px;
        }
        
        .ride-route {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .location-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background-color: var(--primary-color);
            margin-right: 10px;
        }
        
        .location-line {
            width: 1px;
            height: 30px;
            background-color: #ddd;
            margin: 5px 0 5px 5px;
        }
        
        .ride-meta {
            display: flex;
            justify-content: space-between;
            color: #777;
            font-size: 0.9rem;
        }
        
        .ride-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 10px;
        }
        
        .ride-actions button {
            padding: 5px 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9rem;
        }
        
        .book-ride {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            padding: 30px;
        }
        
        .book-ride h2 {
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
        
        .ride-types {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .ride-type {
            text-align: center;
            padding: 10px;
            border: 2px solid #ddd;
            border-radius: 5px;
            cursor: pointer;
            width: calc(50% - 5px);
            transition: all 0.3s;
        }
        
        .ride-type:hover, .ride-type.active {
            border-color: var(--primary-color);
            background-color: rgba(255, 87, 34, 0.1);
        }
        
        .ride-type .icon {
            font-size: 24px;
            margin-bottom: 5px;
        }
        
        .fare-estimate {
            background-color: #f9f9f9;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
            display: none;
        }
        
        .fare-estimate h3 {
            margin-bottom: 10px;
            color: var(--primary-color);
        }
        
        .no-rides {
            text-align: center;
            padding: 30px;
            color: #777;
        }
        
        .no-rides .icon {
            font-size: 50px;
            color: #ddd;
            margin-bottom: 15px;
        }
        
        footer {
            background-color: var(--dark-color);
            color: white;
            padding: 20px 0;
            text-align: center;
            margin-top: 50px;
        }
        
        @media (max-width: 992px) {
            .dashboard-content {
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
            
            .dashboard-header {
                flex-direction: column;
                text-align: center;
            }
            
            .welcome-message {
                margin-bottom: 20px;
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
            <div class="dashboard-header">
                <div class="welcome-message">
                    <h1>Welcome, <?php echo $user['full_name']; ?>!</h1>
                    <p>Here's an overview of your Yango activity</p>
                </div>
                <a href="booking.php" class="btn">Book a Ride</a>
            </div>
            
            <div class="dashboard-stats">
                <div class="stat-card">
                    <h3>Total Rides</h3>
                    <div class="stat-value"><?php echo $rides_result->num_rows; ?></div>
                    <div class="stat-label">Rides taken</div>
                </div>
                
                <div class="stat-card">
                    <h3>Completed Rides</h3>
                    <?php
                    $completed_query = "SELECT COUNT(*) as count FROM rides WHERE user_id = $user_id AND status = 'completed'";
                    $completed_result = $conn->query($completed_query);
                    $completed_count = $completed_result->fetch_assoc()['count'];
                    ?>
                    <div class="stat-value"><?php echo $completed_count; ?></div>
                    <div class="stat-label">Successfully completed</div>
                </div>
                
                <div class="stat-card">
                    <h3>Active Rides</h3>
                    <?php
                    $active_query = "SELECT COUNT(*) as count FROM rides WHERE user_id = $user_id AND (status = 'accepted' OR status = 'in_progress')";
                    $active_result = $conn->query($active_query);
                    $active_count = $active_result->fetch_assoc()['count'];
                    ?>
                    <div class="stat-value"><?php echo $active_count; ?></div>
                    <div class="stat-label">Currently active</div>
                </div>
            </div>
            
            <div class="dashboard-content">
                <div class="ride-history">
                    <h2>Recent Rides</h2>
                    
                    <div class="ride-list">
                        <?php if ($rides_result->num_rows > 0): ?>
                            <?php while ($ride = $rides_result->fetch_assoc()): ?>
                                <div class="ride-card">
                                    <div class="ride-header">
                                        <h3><?php echo $ride['type_name']; ?> Ride</h3>
                                        <div class="ride-date"><?php echo date('M d, Y h:i A', strtotime($ride['created_at'])); ?></div>
                                    </div>
                                    
                                    <div class="ride-status status-<?php echo $ride['status']; ?>">
                                        <?php 
                                        $status = ucfirst(str_replace('_', ' ', $ride['status']));
                                        echo $status; 
                                        ?>
                                    </div>
                                    
                                    <div class="ride-details">
                                        <div class="ride-route">
                                            <div class="location-dot"></div>
                                            <div><?php echo $ride['pickup_location']; ?></div>
                                        </div>
                                        
                                        <div class="location-line"></div>
                                        
                                        <div class="ride-route">
                                            <div class="location-dot"></div>
                                            <div><?php echo $ride['dropoff_location']; ?></div>
                                        </div>
                                    </div>
                                    
                                    <div class="ride-meta">
                                        <div>Distance: <?php echo $ride['distance']; ?> km</div>
                                        <div>Fare: ₹<?php echo $ride['final_fare'] ? $ride['final_fare'] : $ride['estimated_fare']; ?></div>
                                        <?php if ($ride['driver_name']): ?>
                                            <div>Driver: <?php echo $ride['driver_name']; ?></div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <?php if ($ride['status'] === 'in_progress'): ?>
                                        <div class="ride-actions">
                                            <a href="tracking.php?ride_id=<?php echo $ride['ride_id']; ?>" class="btn btn-outline">Track Ride</a>
                                        </div>
                                    <?php elseif ($ride['status'] === 'completed'): ?>
                                        <div class="ride-actions">
                                            <a href="review.php?ride_id=<?php echo $ride['ride_id']; ?>" class="btn btn-outline">Leave Review</a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="no-rides">
                                <div class="icon">🚗</div>
                                <h3>No rides yet</h3>
                                <p>Book your first ride to get started!</p>
                                <a href="booking.php" class="btn" style="margin-top: 15px;">Book a Ride</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="book-ride">
                    <h2>Quick Booking</h2>
                    
                    <form id="quick-booking-form">
                        <div class="form-group">
                            <label for="pickup">Pickup Location</label>
                            <input type="text" id="pickup" class="form-control" placeholder="Enter pickup location" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="dropoff">Dropoff Location</label>
                            <input type="text" id="dropoff" class="form-control" placeholder="Enter destination" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Select Ride Type</label>
                            <div class="ride-types">
                                <div class="ride-type" data-type="1" data-base="50" data-per-km="10">
                                    <div class="icon">🚗</div>
                                    <div>Economy</div>
                                </div>
                                <div class="ride-type" data-type="2" data-base="80" data-per-km="15">
                                    <div class="icon">🚙</div>
                                    <div>Comfort</div>
                                </div>
                                <div class="ride-type" data-type="3" data-base="120" data-per-km="25">
                                    <div class="icon">🚘</div>
                                    <div>Premium</div>
                                </div>
                                <div class="ride-type" data-type="4" data-base="100" data-per-km="20">
                                    <div class="icon">🚐</div>
                                    <div>XL</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="fare-estimate" id="fare-estimate">
                            <h3>Estimated Fare</h3>
                            <p id="estimated-fare">₹0.00</p>
                            <p id="estimated-distance">0 km</p>
                            <p id="estimated-time">0 min</p>
                        </div>
                        
                        <button type="button" id="estimate-btn" class="btn" style="width: 100%; margin-bottom: 10px;">Estimate Fare</button>
                        <button type="button" id="book-btn" class="btn" style="width: 100%; display: none;">Book Now</button>
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
        // Toggle user dropdown menu
        document.addEventListener('DOMContentLoaded', function() {
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
            
            // Ride booking functionality
            const rideTypes = document.querySelectorAll('.ride-type');
            const estimateBtn = document.getElementById('estimate-btn');
            const bookBtn = document.getElementById('book-btn');
            const fareEstimate = document.getElementById('fare-estimate');
            const estimatedFare = document.getElementById('estimated-fare');
            const estimatedDistance = document.getElementById('estimated-distance');
            const estimatedTime = document.getElementById('estimated-time');
            
            let selectedRideType = null;
            
            // Select ride type
            rideTypes.forEach(type => {
                type.addEventListener('click', function() {
                    rideTypes.forEach(t => t.classList.remove('active'));
                    this.classList.add('active');
                    selectedRideType = {
                        id: this.dataset.type,
                        baseFare: parseFloat(this.dataset.base),
                        perKmRate: parseFloat(this.dataset.perKm)
                    };
                });
            });
            
            // Estimate fare
            estimateBtn.addEventListener('click', function() {
                const pickup = document.getElementById('pickup').value;
                const dropoff = document.getElementById('dropoff').value;
                
                if (!pickup || !dropoff) {
                    alert('Please enter pickup and dropoff locations');
                    return;
                }
                
                if (!selectedRideType) {
                    alert('Please select a ride type');
                    return;
                }
                
                // Simulate distance calculation (in a real app, this would use Google Maps API or similar)
                const distance = Math.floor(Math.random() * 20) + 1; // Random distance between 1-20 km
                const time = Math.floor(distance * 2) + 5; // Estimated time based on distance
                
                // Calculate fare
                const fare = selectedRideType.baseFare + (selectedRideType.perKmRate * distance);
                
                // Display fare estimate
                estimatedFare.textContent = '₹' + fare.toFixed(2);
                estimatedDistance.textContent = distance + ' km';
                estimatedTime.textContent = time + ' min';
                fareEstimate.style.display = 'block';
                
                // Show book button
                bookBtn.style.display = 'block';
                estimateBtn.style.display = 'none';
            });
            
            // Book ride
            bookBtn.addEventListener('click', function() {
                const pickup = document.getElementById('pickup').value;
                const dropoff = document.getElementById('dropoff').value;
                
                // Redirect to booking page with parameters
                window.location.href = `booking.php?pickup=${encodeURIComponent(pickup)}&dropoff=${encodeURIComponent(dropoff)}&type=${selectedRideType.id}`;
            });
        });
    </script>
</body>
</html>
