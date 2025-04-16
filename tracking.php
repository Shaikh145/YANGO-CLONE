<?php
require_once 'db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    redirect('login.php');
}

// Check if ride_id is provided
if (!isset($_GET['ride_id'])) {
    redirect('dashboard.php');
}

$ride_id = sanitize($conn, $_GET['ride_id']);
$user_id = $_SESSION['user_id'];

// Get ride details
$query = "SELECT r.*, d.full_name as driver_name, d.phone as driver_phone, d.vehicle_model, d.vehicle_color, d.vehicle_plate, rt.type_name 
          FROM rides r 
          LEFT JOIN drivers d ON r.driver_id = d.driver_id 
          LEFT JOIN ride_types rt ON r.ride_type_id = rt.type_id 
          WHERE r.ride_id = $ride_id AND r.user_id = $user_id";
$result = $conn->query($query);

if ($result->num_rows === 0) {
    redirect('dashboard.php');
}

$ride = $result->fetch_assoc();

// Simulate ride progress (in a real app, this would come from real-time updates)
$progress = 0;
$status_text = '';

if ($ride['status'] === 'accepted') {
    $progress = 25;
    $status_text = 'Driver is on the way to pickup location';
} elseif ($ride['status'] === 'in_progress') {
    $progress = 75;
    $status_text = 'On the way to destination';
} elseif ($ride['status'] === 'completed') {
    $progress = 100;
    $status_text = 'Ride completed';
} elseif ($ride['status'] === 'cancelled') {
    $progress = 0;
    $status_text = 'Ride cancelled';
}

// Simulate driver's current location (in a real app, this would come from real-time updates)
$driver_lat = $ride['pickup_latitude'] + (($ride['dropoff_latitude'] - $ride['pickup_latitude']) * ($progress / 100));
$driver_lng = $ride['pickup_longitude'] + (($ride['dropoff_longitude'] - $ride['pickup_longitude']) * ($progress / 100));

// Simulate ETA (in a real app, this would be calculated based on real-time data)
$eta_minutes = ceil((100 - $progress) / 25) * 5;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track Your Ride - Yango</title>
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
        
        .tracking-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }
        
        .tracking-info {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            padding: 30px;
        }
        
        .tracking-info h2 {
            color: var(--dark-color);
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .ride-status {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .status-text {
            font-size: 1.2rem;
            font-weight: 500;
            color: var(--primary-color);
            margin-bottom: 15px;
        }
        
        .progress-bar {
            width: 100%;
            height: 10px;
            background-color: #eee;
            border-radius: 5px;
            overflow: hidden;
            margin-bottom: 10px;
        }
        
        .progress {
            height: 100%;
            background-color: var(--primary-color);
            border-radius: 5px;
            transition: width 0.5s;
        }
        
        .progress-labels {
            display: flex;
            justify-content: space-between;
            color: #777;
            font-size: 0.9rem;
        }
        
        .eta {
            text-align: center;
            margin-bottom: 30px;
            padding: 15px;
            background-color: rgba(255, 87, 34, 0.05);
            border-radius: 10px;
        }
        
        .eta-value {
            font-size: 2rem;
            font-weight: bold;
            color: var(--primary-color);
            margin-bottom: 5px;
        }
        
        .eta-label {
            color: #777;
        }
        
        .driver-info {
            margin-bottom: 30px;
        }
        
        .driver-info h3 {
            color: var(--dark-color);
            margin-bottom: 15px;
        }
        
        .driver-card {
            display: flex;
            align-items: center;
            gap: 20px;
            padding: 15px;
            border: 1px solid #eee;
            border-radius: 10px;
        }
        
        .driver-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background-color: var(--primary-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            font-weight: bold;
        }
        
        .driver-details h4 {
            margin-bottom: 5px;
            color: var(--dark-color);
        }
        
        .driver-details p {
            color: #777;
            margin-bottom: 5px;
        }
        
        .driver-contact {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }
        
        .driver-contact a {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            background-color: var(--primary-color);
            color: white;
            border-radius: 50%;
            text-decoration: none;
            transition: background-color 0.3s;
        }
        
        .driver-contact a:hover {
            background-color: var(--secondary-color);
        }
        
        .ride-details {
            margin-bottom: 30px;
        }
        
        .ride-details h3 {
            color: var(--dark-color);
            margin-bottom: 15px;
        }
        
        .detail-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        
        .detail-item:last-child {
            border-bottom: none;
        }
        
        .detail-label {
            color: #777;
        }
        
        .detail-value {
            font-weight: 500;
        }
        
        .ride-actions {
            display: flex;
            gap: 10px;
        }
        
        .map-container {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            padding: 30px;
            height: 100%;
        }
        
        .map-container h2 {
            color: var(--dark-color);
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .map {
            height: 400px;
            background-color: #f5f5f5;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #777;
            font-size: 1.2rem;
            margin-bottom: 20px;
        }
        
        .route-info {
            margin-bottom: 20px;
        }
        
        .route-info h3 {
            color: var(--dark-color);
            margin-bottom: 15px;
        }
        
        .route-locations {
            margin-bottom: 20px;
        }
        
        .location-item {
            display: flex;
            align-items: flex-start;
            margin-bottom: 20px;
        }
        
        .location-icon {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background-color: var(--primary-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            flex-shrink: 0;
        }
        
        .location-icon.pickup {
            background-color: var(--success-color);
        }
        
        .location-icon.dropoff {
            background-color: var(--error-color);
        }
        
        .location-details h4 {
            margin-bottom: 5px;
            color: var(--dark-color);
        }
        
        .location-details p {
            color: #777;
        }
        
        .location-connector {
            width: 2px;
            height: 30px;
            background-color: #ddd;
            margin-left: 15px;
            margin-bottom: 5px;
        }
        
        footer {
            background-color: var(--dark-color);
            color: white;
            padding: 20px 0;
            text-align: center;
            margin-top: 50px;
        }
        
        @media (max-width: 992px) {
            .tracking-container {
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
                    <li><a href="logout.php">Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main>
        <div class="container">
            <div class="page-header">
                <h1>Track Your Ride</h1>
                <p>Follow your ride in real-time</p>
            </div>
            
            <div class="tracking-container">
                <div class="tracking-info">
                    <h2>Ride Status</h2>
                    
                    <div class="ride-status">
                        <div class="status-text"><?php echo $status_text; ?></div>
                        <div class="progress-bar">
                            <div class="progress" style="width: <?php echo $progress; ?>%;"></div>
                        </div>
                        <div class="progress-labels">
                            <span>Accepted</span>
                            <span>Pickup</span>
                            <span>In Progress</span>
                            <span>Completed</span>
                        </div>
                    </div>
                    
                    <?php if ($ride['status'] !== 'completed' && $ride['status'] !== 'cancelled'): ?>
                        <div class="eta">
                            <div class="eta-value"><?php echo $eta_minutes; ?> min</div>
                            <div class="eta-label">Estimated time of arrival</div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($ride['driver_id']): ?>
                        <div class="driver-info">
                            <h3>Driver Information</h3>
                            <div class="driver-card">
                                <div class="driver-avatar"><?php echo substr($ride['driver_name'], 0, 1); ?></div>
                                <div class="driver-details">
                                    <h4><?php echo $ride['driver_name']; ?></h4>
                                    <p><?php echo $ride['vehicle_color'] . ' ' . $ride['vehicle_model']; ?></p>
                                    <p>License Plate: <?php echo $ride['vehicle_plate']; ?></p>
                                    
                                    <div class="driver-contact">
                                        <a href="tel:<?php echo $ride['driver_phone']; ?>" title="Call Driver">📞</a>
                                        <a href="#" title="Message Driver">💬</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <div class="ride-details">
                        <h3>Ride Details</h3>
                        
                        <div class="detail-item">
                            <div class="detail-label">Ride ID</div>
                            <div class="detail-value">#<?php echo $ride['ride_id']; ?></div>
                        </div>
                        
                        <div class="detail-item">
                            <div class="detail-label">Ride Type</div>
                            <div class="detail-value"><?php echo $ride['type_name']; ?></div>
                        </div>
                        
                        <div class="detail-item">
                            <div class="detail-label">Distance</div>
                            <div class="detail-value"><?php echo $ride['distance']; ?> km</div>
                        </div>
                        
                        <div class="detail-item">
                            <div class="detail-label">Fare</div>
                            <div class="detail-value">₹<?php echo $ride['final_fare'] ? $ride['final_fare'] : $ride['estimated_fare']; ?></div>
                        </div>
                        
                        <div class="detail-item">
                            <div class="detail-label">Payment Method</div>
                            <div class="detail-value"><?php echo ucfirst($ride['payment_method']); ?></div>
                        </div>
                        
                        <div class="detail-item">
                            <div class="detail-label">Booked On</div>
                            <div class="detail-value"><?php echo date('M d, Y h:i A', strtotime($ride['created_at'])); ?></div>
                        </div>
                    </div>
                    
                    <?php if ($ride['status'] === 'in_progress'): ?>
                        <div class="ride-actions">
                            <a href="#" class="btn btn-outline" id="cancel-ride">Cancel Ride</a>
                            <a href="#" class="btn" id="share-ride">Share Ride</a>
                        </div>
                    <?php elseif ($ride['status'] === 'completed'): ?>
                        <div class="ride-actions">
                            <a href="review.php?ride_id=<?php echo $ride['ride_id']; ?>" class="btn">Rate Your Ride</a>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="map-container">
                    <h2>Live Tracking</h2>
                    
                    <div class="map" id="map">
                        <div style="text-align: center;">
                            <div style="font-size: 50px; margin-bottom: 20px;">🗺️</div>
                            <div style="font-weight: bold; margin-bottom: 10px;">Live tracking map</div>
                            <div>Driver is <?php echo $progress; ?>% of the way to destination</div>
                        </div>
                    </div>
                    
                    <div class="route-info">
                        <h3>Route Information</h3>
                        
                        <div class="route-locations">
                            <div class="location-item">
                                <div class="location-icon pickup">A</div>
                                <div class="location-details">
                                    <h4>Pickup Location</h4>
                                    <p><?php echo $ride['pickup_location']; ?></p>
                                </div>
                            </div>
                            
                            <div class="location-connector"></div>
                            
                            <div class="location-item">
                                <div class="location-icon dropoff">B</div>
                                <div class="location-details">
                                    <h4>Dropoff Location</h4>
                                    <p><?php echo $ride['dropoff_location']; ?></p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="detail-item">
                            <div class="detail-label">Total Distance</div>
                            <div class="detail-value"><?php echo $ride['distance']; ?> km</div>
                        </div>
                        
                        <div class="detail-item">
                            <div class="detail-label">Estimated Time</div>
                            <div class="detail-value"><?php echo ceil($ride['distance'] * 2); ?> min</div>
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
            // Simulate real-time updates (in a real app, this would use WebSockets or polling)
            let progress = <?php echo $progress; ?>;
            const progressBar = document.querySelector('.progress');
            const statusText = document.querySelector('.status-text');
            const etaValue = document.querySelector('.eta-value');
            
            // Only run the simulation if the ride is not completed or cancelled
            if (progress < 100 && '<?php echo $ride['status']; ?>' !== 'cancelled') {
                const interval = setInterval(function() {
                    // Increment progress
                    progress += 1;
                    
                    // Update progress bar
                    progressBar.style.width = progress + '%';
                    
                    // Update ETA
                    const etaMinutes = Math.ceil((100 - progress) / 25) * 5;
                    if (etaValue) {
                        etaValue.textContent = etaMinutes + ' min';
                    }
                    
                    // Update status text
                    if (progress < 25) {
                        statusText.textContent = 'Driver is on the way to pickup location';
                    } else if (progress < 50) {
                        statusText.textContent = 'Driver has arrived at pickup location';
                    } else if (progress < 100) {
                        statusText.textContent = 'On the way to destination';
                    } else {
                        statusText.textContent = 'Ride completed';
                        clearInterval(interval);
                        
                        // Redirect to review page after a delay
                        setTimeout(function() {
                            window.location.href = 'review.php?ride_id=<?php echo $ride['ride_id']; ?>';
                        }, 3000);
                    }
                    
                    // Update map display
                    document.querySelector('#map div div:last-child').textContent = 'Driver is ' + progress + '% of the way to destination';
                    
                }, 2000); // Update every 2 seconds
            }
            
            // Cancel ride button
            const cancelRideBtn = document.getElementById('cancel-ride');
            if (cancelRideBtn) {
                cancelRideBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    if (confirm('Are you sure you want to cancel this ride?')) {
                        // In a real app, this would send an AJAX request to cancel the ride
                        alert('Ride cancelled successfully');
                        window.location.href = 'dashboard.php';
                    }
                });
            }
            
            // Share ride button
            const shareRideBtn = document.getElementById('share-ride');
            if (shareRideBtn) {
                shareRideBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    // In a real app, this would open a share dialog
                    const shareText = 'I am currently on a Yango ride. Track my journey here: [Ride Tracking Link]';
                    alert('Share this information with your contacts:\n\n' + shareText);
                });
            }
        });
    </script>
</body>
</html>
