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
    <title>Ride History - Yango</title>
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
        
        .history-container {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            padding: 30px;
        }
        
        .history-filters {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }
        
        .filter-group {
            display: flex;
            gap: 10px;
        }
        
        .filter-btn {
            padding: 8px 15px;
            background-color: #f5f5f5;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .filter-btn:hover, .filter-btn.active {
            background-color: var(--primary-color);
            color: white;
        }
        
        .search-box {
            display: flex;
        }
        
        .search-box input {
            padding: 8px 15px;
            border: 1px solid #ddd;
            border-radius: 5px 0 0 5px;
            width: 250px;
        }
        
        .search-box button {
            padding: 8px 15px;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 0 5px 5px 0;
            cursor: pointer;
        }
        
        .ride-list {
            display: grid;
            gap: 20px;
        }
        
        .ride-card {
            border: 1px solid #eee;
            border-radius: 10px;
            padding: 20px;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .ride-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.05);
        }
        
        .ride-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
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
            margin-bottom: 15px;
        }
        
        .ride-route {
            display: flex;
            align-items: flex-start;
            margin-bottom: 15px;
        }
        
        .location-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background-color: var(--primary-color);
            margin-right: 10px;
            margin-top: 5px;
        }
        
        .location-line {
            width: 2px;
            height: 30px;
            background-color: #ddd;
            margin: 5px 0 5px 5px;
        }
        
        .location-text h4 {
            margin-bottom: 5px;
            color: var(--dark-color);
        }
        
        .location-text p {
            color: #777;
        }
        
        .ride-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 15px;
        }
        
        .meta-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .meta-icon {
            color: var(--primary-color);
            font-size: 1.2rem;
        }
        
        .meta-text {
            color: #777;
        }
        
        .meta-text span {
            font-weight: 500;
            color: var(--dark-color);
        }
        
        .ride-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }
        
        .action-btn {
            padding: 8px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            font-size: 0.9rem;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }
        
        .btn-primary:hover {
            background-color: var(--secondary-color);
        }
        
        .btn-outline {
            background-color: transparent;
            border: 1px solid var(--primary-color);
            color: var(--primary-color);
        }
        
        .btn-outline:hover {
            background-color: var(--primary-color);
            color: white;
        }
        
        .no-rides {
            text-align: center;
            padding: 50px 0;
            color: #777;
        }
        
        .no-rides .icon {
            font-size: 50px;
            color: #ddd;
            margin-bottom: 15px;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 30px;
        }
        
        .pagination a {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            margin: 0 5px;
            border-radius: 5px;
            background-color: #f5f5f5;
            color: var(--dark-color);
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .pagination a:hover, .pagination a.active {
            background-color: var(--primary-color);
            color: white;
        }
        
        footer {
            background-color: var(--dark-color);
            color: white;
            padding: 20px 0;
            text-align: center;
            margin-top: 50px;
        }
        
        @media (max-width: 768px) {
            .navbar {
                flex-direction: column;
            }
            
            .nav-links {
                margin-top: 15px;
            }
            
            .history-filters {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
            }
            
            .search-box {
                width: 100%;
            }
            
            .search-box input {
                flex: 1;
            }
            
            .ride-header {
                flex-direction: column;
                gap: 10px;
            }
            
            .ride-meta {
                flex-direction: column;
                gap: 10px;
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
                <h1>Ride History</h1>
                <p>View all your past and upcoming rides</p>
            </div>
            
            <div class="history-container">
                <div class="history-filters">
                    <div class="filter-group">
                        <button class="filter-btn active" data-filter="all">All</button>
                        <button class="filter-btn" data-filter="completed">Completed</button>
                        <button class="filter-btn" data-filter="in_progress">In Progress</button>
                        <button class="filter-btn" data-filter="cancelled">Cancelled</button>
                    </div>
                    
                    <div class="search-box">
                        <input type="text" placeholder="Search by location or driver">
                        <button>🔍</button>
                    </div>
                </div>
                
                <div class="ride-list">
                    <?php if ($rides_result->num_rows > 0): ?>
                        <?php while ($ride = $rides_result->fetch_assoc()): ?>
                            <div class="ride-card" data-status="<?php echo $ride['status']; ?>">
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
                                        <div class="location-text">
                                            <h4>Pickup Location</h4>
                                            <p><?php echo $ride['pickup_location']; ?></p>
                                        </div>
                                    </div>
                                    
                                    <div class="location-line"></div>
                                    
                                    <div class="ride-route">
                                        <div class="location-dot"></div>
                                        <div class="location-text">
                                            <h4>Dropoff Location</h4>
                                            <p><?php echo $ride['dropoff_location']; ?></p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="ride-meta">
                                    <div class="meta-item">
                                        <div class="meta-icon">📏</div>
                                        <div class="meta-text">Distance: <span><?php echo $ride['distance']; ?> km</span></div>
                                    </div>
                                    
                                    <div class="meta-item">
                                        <div class="meta-icon">💰</div>
                                        <div class="meta-text">Fare: <span>₹<?php echo $ride['final_fare'] ? $ride['final_fare'] : $ride['estimated_fare']; ?></span></div>
                                    </div>
                                    
                                    <?php if ($ride['driver_name']): ?>
                                        <div class="meta-item">
                                            <div class="meta-icon">🧑‍✈️</div>
                                            <div class="meta-text">Driver: <span><?php echo $ride['driver_name']; ?></span></div>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="meta-item">
                                        <div class="meta-icon">💳</div>
                                        <div class="meta-text">Payment: <span><?php echo ucfirst($ride['payment_method']); ?></span></div>
                                    </div>
                                </div>
                                
                                <div class="ride-actions">
                                    <?php if ($ride['status'] === 'in_progress'): ?>
                                        <a href="tracking.php?ride_id=<?php echo $ride['ride_id']; ?>" class="action-btn btn-primary">Track Ride</a>
                                    <?php elseif ($ride['status'] === 'completed'): ?>
                                        <a href="review.php?ride_id=<?php echo $ride['ride_id']; ?>" class="action-btn btn-outline">Leave Review</a>
                                        <a href="#" class="action-btn btn-primary" onclick="showRideDetails(<?php echo $ride['ride_id']; ?>)">View Details</a>
                                    <?php elseif ($ride['status'] === 'accepted'): ?>
                                        <a href="tracking.php?ride_id=<?php echo $ride['ride_id']; ?>" class="action-btn btn-primary">Track Ride</a>
                                        <a href="#" class="action-btn btn-outline" onclick="cancelRide(<?php echo $ride['ride_id']; ?>)">Cancel</a>
                                    <?php elseif ($ride['status'] === 'pending'): ?>
                                        <a href="#" class="action-btn btn-outline" onclick="cancelRide(<?php echo $ride['ride_id']; ?>)">Cancel</a>
                                    <?php else: ?>
                                        <a href="#" class="action-btn btn-primary" onclick="showRideDetails(<?php echo $ride['ride_id']; ?>)">View Details</a>
                                    <?php endif; ?>
                                </div>
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
                
                <?php if ($rides_result->num_rows > 10): ?>
                    <div class="pagination">
                        <a href="#" class="active">1</a>
                        <a href="#">2</a>
                        <a href="#">3</a>
                        <a href="#">&raquo;</a>
                    </div>
                <?php endif; ?>
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
            
            // Filter rides
            const filterButtons = document.querySelectorAll('.filter-btn');
            const rideCards = document.querySelectorAll('.ride-card');
            
            filterButtons.forEach(button => {
                button.addEventListener('click', function() {
                    // Update active button
                    filterButtons.forEach(btn => btn.classList.remove('active'));
                    this.classList.add('active');
                    
                    const filter = this.dataset.filter;
                    
                    // Filter ride cards
                    rideCards.forEach(card => {
                        if (filter === 'all' || card.dataset.status === filter) {
                            card.style.display = 'block';
                        } else {
                            card.style.display = 'none';
                        }
                    });
                });
            });
            
            // Search functionality
            const searchInput = document.querySelector('.search-box input');
            const searchButton = document.querySelector('.search-box button');
            
            function performSearch() {
                const searchTerm = searchInput.value.toLowerCase();
                
                rideCards.forEach(card => {
                    const locations = card.querySelectorAll('.location-text p');
                    const driverName = card.querySelector('.meta-text span:nth-child(1)');
                    
                    let matchFound = false;
                    
                    // Check locations
                    locations.forEach(location => {
                        if (location.textContent.toLowerCase().includes(searchTerm)) {
                            matchFound = true;
                        }
                    });
                    
                    // Check driver name if exists
                    if (driverName && driverName.textContent.toLowerCase().includes(searchTerm)) {
                        matchFound = true;
                    }
                    
                    if (matchFound) {
                        card.style.display = 'block';
                    } else {
                        card.style.display = 'none';
                    }
                });
            }
            
            searchButton.addEventListener('click', performSearch);
            searchInput.addEventListener('keyup', function(e) {
                if (e.key === 'Enter') {
                    performSearch();
                }
            });
        });
        
        // Function to show ride details
        function showRideDetails(rideId) {
            // In a real app, this would open a modal with ride details or redirect to a details page
            alert('Viewing details for ride #' + rideId);
        }
        
        // Function to cancel ride
        function cancelRide(rideId) {
            // In a real app, this would send an AJAX request to cancel the ride
            if (confirm('Are you sure you want to cancel this ride?')) {
                alert('Ride #' + rideId + ' has been cancelled');
                // Reload page to reflect changes
                window.location.reload();
            }
        }
    </script>
</body>
</html>
