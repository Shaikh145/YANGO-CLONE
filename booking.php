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

// Get ride types
$query = "SELECT * FROM ride_types";
$ride_types_result = $conn->query($query);
$ride_types = [];
while ($type = $ride_types_result->fetch_assoc()) {
    $ride_types[] = $type;
}

// Get parameters from URL if available
$pickup = isset($_GET['pickup']) ? $_GET['pickup'] : '';
$dropoff = isset($_GET['dropoff']) ? $_GET['dropoff'] : '';
$selected_type = isset($_GET['type']) ? $_GET['type'] : '';

// Process booking form
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pickup = sanitize($conn, $_POST['pickup']);
    $dropoff = sanitize($conn, $_POST['dropoff']);
    $ride_type_id = sanitize($conn, $_POST['ride_type_id']);
    $distance = sanitize($conn, $_POST['distance']);
    $estimated_fare = sanitize($conn, $_POST['estimated_fare']);
    
    // Generate random coordinates (in a real app, these would come from geocoding API)
    $pickup_lat = 40.7128 + (rand(-1000, 1000) / 10000);
    $pickup_lng = -74.0060 + (rand(-1000, 1000) / 10000);
    $dropoff_lat = 40.7128 + (rand(-1000, 1000) / 10000);
    $dropoff_lng = -74.0060 + (rand(-1000, 1000) / 10000);
    
    // Insert ride into database
    $query = "INSERT INTO rides (user_id, ride_type_id, pickup_location, pickup_latitude, pickup_longitude, 
              dropoff_location, dropoff_latitude, dropoff_longitude, distance, estimated_fare) 
              VALUES ($user_id, $ride_type_id, '$pickup', $pickup_lat, $pickup_lng, 
              '$dropoff', $dropoff_lat, $dropoff_lng, $distance, $estimated_fare)";
    
    if ($conn->query($query)) {
        $ride_id = $conn->insert_id;
        
        // Simulate driver assignment (in a real app, this would be more complex)
        $driver_query = "SELECT * FROM drivers WHERE status = 'available' ORDER BY RAND() LIMIT 1";
        $driver_result = $conn->query($driver_query);
        
        if ($driver_result->num_rows > 0) {
            $driver = $driver_result->fetch_assoc();
            $driver_id = $driver['driver_id'];
            
            // Assign driver to ride
            $update_query = "UPDATE rides SET driver_id = $driver_id, status = 'accepted', accepted_at = NOW() WHERE ride_id = $ride_id";
            $conn->query($update_query);
            
            // Update driver status
            $update_driver_query = "UPDATE drivers SET status = 'busy' WHERE driver_id = $driver_id";
            $conn->query($update_driver_query);
            
            $success = "Ride booked successfully! Your driver is on the way.";
            echo "<script>
                setTimeout(function() {
                    window.location.href = 'tracking.php?ride_id=$ride_id';
                }, 2000);
            </script>";
        } else {
            $success = "Ride booked successfully! Looking for a driver...";
            echo "<script>
                setTimeout(function() {
                    window.location.href = 'dashboard.php';
                }, 2000);
            </script>";
        }
    } else {
        $error = "Booking failed: " . $conn->error;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book a Ride - Yango</title>
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
        
        .booking-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }
        
        .booking-form {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            padding: 30px;
        }
        
        .booking-form h2 {
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
        
        .ride-types-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .ride-type-card {
            border: 2px solid #ddd;
            border-radius: 10px;
            padding: 15px;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .ride-type-card:hover, .ride-type-card.active {
            border-color: var(--primary-color);
            background-color: rgba(255, 87, 34, 0.05);
        }
        
        .ride-type-icon {
            font-size: 30px;
            color: var(--primary-color);
        }
        
        .ride-type-info h3 {
            margin-bottom: 5px;
            color: var(--dark-color);
        }
        
        .ride-type-info p {
            color: #777;
            font-size: 0.9rem;
        }
        
        .fare-estimate {
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: none;
        }
        
        .fare-estimate h3 {
            color: var(--primary-color);
            margin-bottom: 15px;
            text-align: center;
        }
        
        .fare-details {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px dashed #ddd;
        }
        
        .fare-details:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        
        .fare-label {
            color: #777;
        }
        
        .fare-value {
            font-weight: 500;
        }
        
        .total-fare {
            font-size: 1.2rem;
            color: var(--primary-color);
            font-weight: bold;
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
        
        .payment-methods {
            margin-bottom: 20px;
        }
        
        .payment-methods h3 {
            margin-bottom: 15px;
            color: var(--dark-color);
        }
        
        .payment-options {
            display: flex;
            gap: 15px;
        }
        
        .payment-option {
            border: 2px solid #ddd;
            border-radius: 5px;
            padding: 10px 15px;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .payment-option:hover, .payment-option.active {
            border-color: var(--primary-color);
            background-color: rgba(255, 87, 34, 0.05);
        }
        
        .payment-icon {
            font-size: 20px;
            color: var(--primary-color);
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
            width: 100%;
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
            .booking-container {
                grid-template-columns: 1fr;
            }
            
            .map-container {
                order: -1;
            }
        }
        
        @media (max-width: 768px) {
            .navbar {
                flex-direction: column;
            }
            
            .nav-links {
                margin-top: 15px;
            }
            
            .ride-types-grid {
                grid-template-columns: 1fr;
            }
            
            .payment-options {
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
                <h1>Book a Ride</h1>
                <p>Enter your pickup and dropoff locations to get started</p>
            </div>
            
            <?php if ($error): ?>
                <div class="error-message"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="success-message"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <div class="booking-container">
                <div class="booking-form">
                    <h2>Ride Details</h2>
                    
                    <form method="POST" action="" id="booking-form">
                        <div class="form-group">
                            <label for="pickup">Pickup Location</label>
                            <input type="text" id="pickup" name="pickup" class="form-control" placeholder="Enter pickup location" value="<?php echo $pickup; ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="dropoff">Dropoff Location</label>
                            <input type="text" id="dropoff" name="dropoff" class="form-control" placeholder="Enter destination" value="<?php echo $dropoff; ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Select Ride Type</label>
                            <div class="ride-types-grid">
                                <?php foreach ($ride_types as $type): ?>
                                    <div class="ride-type-card <?php echo ($selected_type == $type['type_id']) ? 'active' : ''; ?>" data-type-id="<?php echo $type['type_id']; ?>" data-base-fare="<?php echo $type['base_fare']; ?>" data-per-km="<?php echo $type['per_km_rate']; ?>" data-per-min="<?php echo $type['per_minute_rate']; ?>">
                                        <div class="ride-type-icon">
                                            <?php 
                                            $icon = '🚗';
                                            if ($type['type_name'] == 'Comfort') $icon = '🚙';
                                            if ($type['type_name'] == 'Premium') $icon = '🚘';
                                            if ($type['type_name'] == 'XL') $icon = '🚐';
                                            echo $icon;
                                            ?>
                                        </div>
                                        <div class="ride-type-info">
                                            <h3><?php echo $type['type_name']; ?></h3>
                                            <p>Base fare: ₹<?php echo $type['base_fare']; ?></p>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <input type="hidden" id="ride_type_id" name="ride_type_id" value="<?php echo $selected_type; ?>">
                        <input type="hidden" id="distance" name="distance" value="">
                        <input type="hidden" id="estimated_fare" name="estimated_fare" value="">
                        
                        <div class="fare-estimate" id="fare-estimate">
                            <h3>Fare Estimate</h3>
                            
                            <div class="fare-details">
                                <div class="fare-label">Base Fare</div>
                                <div class="fare-value" id="base-fare">₹0.00</div>
                            </div>
                            
                            <div class="fare-details">
                                <div class="fare-label">Distance Charge</div>
                                <div class="fare-value" id="distance-charge">₹0.00</div>
                            </div>
                            
                            <div class="fare-details">
                                <div class="fare-label">Time Charge</div>
                                <div class="fare-value" id="time-charge">₹0.00</div>
                            </div>
                            
                            <div class="fare-details">
                                <div class="fare-label">Total Distance</div>
                                <div class="fare-value" id="total-distance">0 km</div>
                            </div>
                            
                            <div class="fare-details">
                                <div class="fare-label">Estimated Time</div>
                                <div class="fare-value" id="estimated-time">0 min</div>
                            </div>
                            
                            <div class="fare-details">
                                <div class="fare-label total-fare">Total Fare</div>
                                <div class="fare-value total-fare" id="total-fare">₹0.00</div>
                            </div>
                        </div>
                        
                        <div class="payment-methods">
                            <h3>Payment Method</h3>
                            <div class="payment-options">
                                <div class="payment-option active" data-method="cash">
                                    <div class="payment-icon">💵</div>
                                    <div>Cash</div>
                                </div>
                                <div class="payment-option" data-method="card">
                                    <div class="payment-icon">💳</div>
                                    <div>Card</div>
                                </div>
                                <div class="payment-option" data-method="wallet">
                                    <div class="payment-icon">👛</div>
                                    <div>Wallet</div>
                                </div>
                            </div>
                        </div>
                        
                        <button type="button" id="estimate-btn" class="btn">Estimate Fare</button>
                        <button type="submit" id="book-btn" class="btn" style="display: none;">Book Now</button>
                    </form>
                </div>
                
                <div class="map-container">
                    <h2>Route Preview</h2>
                    
                    <div class="map">
                        <div>Map will be displayed here</div>
                    </div>
                    
                    <div class="route-info">
                        <h3>Route Information</h3>
                        <p>Enter your pickup and dropoff locations to see the route on the map.</p>
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
            
            // Ride booking functionality
            const rideTypeCards = document.querySelectorAll('.ride-type-card');
            const estimateBtn = document.getElementById('estimate-btn');
            const bookBtn = document.getElementById('book-btn');
            const fareEstimate = document.getElementById('fare-estimate');
            const rideTypeIdInput = document.getElementById('ride_type_id');
            const distanceInput = document.getElementById('distance');
            const estimatedFareInput = document.getElementById('estimated_fare');
            
            // Payment method selection
            const paymentOptions = document.querySelectorAll('.payment-option');
            
            paymentOptions.forEach(option => {
                option.addEventListener('click', function() {
                    paymentOptions.forEach(opt => opt.classList.remove('active'));
                    this.classList.add('active');
                });
            });
            
            // Select ride type
            let selectedRideType = null;
            
            // Check if a ride type is already selected from URL parameters
            const preSelectedType = '<?php echo $selected_type; ?>';
            if (preSelectedType) {
                const preSelectedCard = document.querySelector(`.ride-type-card[data-type-id="${preSelectedType}"]`);
                if (preSelectedCard) {
                    preSelectedCard.classList.add('active');
                    selectedRideType = {
                        id: preSelectedCard.dataset.typeId,
                        baseFare: parseFloat(preSelectedCard.dataset.baseFare),
                        perKm: parseFloat(preSelectedCard.dataset.perKm),
                        perMin: parseFloat(preSelectedCard.dataset.perMin)
                    };
                    rideTypeIdInput.value = selectedRideType.id;
                }
            }
            
            rideTypeCards.forEach(card => {
                card.addEventListener('click', function() {
                    rideTypeCards.forEach(c => c.classList.remove('active'));
                    this.classList.add('active');
                    selectedRideType = {
                        id: this.dataset.typeId,
                        baseFare: parseFloat(this.dataset.baseFare),
                        perKm: parseFloat(this.dataset.perKm),
                        perMin: parseFloat(this.dataset.perMin)
                    };
                    rideTypeIdInput.value = selectedRideType.id;
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
                
                // Calculate fare components
                const baseFare = selectedRideType.baseFare;
                const distanceCharge = selectedRideType.perKm * distance;
                const timeCharge = selectedRideType.perMin * time;
                
                // Calculate total fare
                const totalFare = baseFare + distanceCharge + timeCharge;
                
                // Update hidden inputs
                distanceInput.value = distance;
                estimatedFareInput.value = totalFare.toFixed(2);
                
                // Display fare estimate
                document.getElementById('base-fare').textContent = '₹' + baseFare.toFixed(2);
                document.getElementById('distance-charge').textContent = '₹' + distanceCharge.toFixed(2);
                document.getElementById('time-charge').textContent = '₹' + timeCharge.toFixed(2);
                document.getElementById('total-distance').textContent = distance + ' km';
                document.getElementById('estimated-time').textContent = time + ' min';
                document.getElementById('total-fare').textContent = '₹' + totalFare.toFixed(2);
                
                fareEstimate.style.display = 'block';
                
                // Show book button
                bookBtn.style.display = 'block';
                estimateBtn.style.display = 'none';
                
                // Update route info
                document.querySelector('.route-info').innerHTML = `
                    <h3>Route Information</h3>
                    <p><strong>From:</strong> ${pickup}</p>
                    <p><strong>To:</strong> ${dropoff}</p>
                    <p><strong>Distance:</strong> ${distance} km</p>
                    <p><strong>Estimated Time:</strong> ${time} min</p>
                `;
                
                // Simulate map display (in a real app, this would show an actual map)
                document.querySelector('.map').innerHTML = `
                    <div style="text-align: center;">
                        <div style="font-size: 50px; margin-bottom: 20px;">🗺️</div>
                        <div style="font-weight: bold; margin-bottom: 10px;">Route from ${pickup} to ${dropoff}</div>
                        <div>Distance: ${distance} km | Time: ${time} min</div>
                    </div>
                `;
            });
            
            // If parameters are present in URL, trigger estimate automatically
            if (pickup && dropoff && selectedRideType) {
                estimateBtn.click();
            }
        });
    </script>
</body>
</html>
