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
$query = "SELECT r.*, d.full_name as driver_name, d.driver_id, rt.type_name 
          FROM rides r 
          LEFT JOIN drivers d ON r.driver_id = d.driver_id 
          LEFT JOIN ride_types rt ON r.ride_type_id = rt.type_id 
          WHERE r.ride_id = $ride_id AND r.user_id = $user_id";
$result = $conn->query($query);

if ($result->num_rows === 0) {
    redirect('dashboard.php');
}

$ride = $result->fetch_assoc();

// Check if review already exists
$review_query = "SELECT * FROM reviews WHERE ride_id = $ride_id AND user_id = $user_id";
$review_result = $conn->query($review_query);
$review_exists = ($review_result->num_rows > 0);

// Process review form
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$review_exists) {
    $rating = sanitize($conn, $_POST['rating']);
    $comment = sanitize($conn, $_POST['comment']);
    $driver_id = $ride['driver_id'];
    
    if (empty($rating)) {
        $error = 'Please select a rating';
    } elseif ($rating < 1 || $rating > 5) {
        $error = 'Rating must be between 1 and 5';
    } else {
        // Insert review
        $query = "INSERT INTO reviews (ride_id, user_id, driver_id, rating, comment) 
                  VALUES ($ride_id, $user_id, $driver_id, $rating, '$comment')";
        
        if ($conn->query($query)) {
            // Update driver's average rating
            $update_rating_query = "UPDATE drivers SET rating = (
                SELECT AVG(rating) FROM reviews WHERE driver_id = $driver_id
            ) WHERE driver_id = $driver_id";
            $conn->query($update_rating_query);
            
            $success = 'Thank you for your review!';
            $review_exists = true;
            
            echo "<script>
                setTimeout(function() {
                    window.location.href = 'dashboard.php';
                }, 2000);
            </script>";
        } else {
            $error = 'Failed to submit review: ' . $conn->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rate Your Ride - Yango</title>
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
        
        .review-container {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            padding: 30px;
            max-width: 600px;
            margin: 0 auto;
        }
        
        .ride-summary {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }
        
        .ride-summary h2 {
            color: var(--dark-color);
            margin-bottom: 15px;
        }
        
        .summary-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        
        .summary-label {
            color: #777;
        }
        
        .summary-value {
            font-weight: 500;
        }
        
        .driver-info {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
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
        
        .driver-details h3 {
            margin-bottom: 5px;
            color: var(--dark-color);
        }
        
        .driver-details p {
            color: #777;
        }
        
        .rating-form {
            margin-top: 20px;
        }
        
        .rating-form h2 {
            color: var(--dark-color);
            margin-bottom: 15px;
        }
        
        .star-rating {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .star {
            font-size: 30px;
            cursor: pointer;
            color: #ddd;
            transition: color 0.3s;
        }
        
        .star:hover, .star.active {
            color: var(--secondary-color);
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
        
        textarea.form-control {
            min-height: 120px;
            resize: vertical;
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
        
        .review-submitted {
            text-align: center;
            padding: 20px 0;
        }
        
        .review-submitted .icon {
            font-size: 60px;
            color: var(--success-color);
            margin-bottom: 20px;
        }
        
        .review-submitted h2 {
            color: var(--dark-color);
            margin-bottom: 10px;
        }
        
        .review-submitted p {
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
                <h1>Rate Your Ride</h1>
                <p>Share your experience and help us improve our service</p>
            </div>
            
            <div class="review-container">
                <?php if ($error): ?>
                    <div class="error-message"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="success-message"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <div class="ride-summary">
                    <h2>Ride Summary</h2>
                    
                    <div class="summary-item">
                        <div class="summary-label">Ride ID</div>
                        <div class="summary-value">#<?php echo $ride['ride_id']; ?></div>
                    </div>
                    
                    <div class="summary-item">
                        <div class="summary-label">Date</div>
                        <div class="summary-value"><?php echo date('M d, Y h:i A', strtotime($ride['created_at'])); ?></div>
                    </div>
                    
                    <div class="summary-item">
                        <div class="summary-label">From</div>
                        <div class="summary-value"><?php echo $ride['pickup_location']; ?></div>
                    </div>
                    
                    <div class="summary-item">
                        <div class="summary-label">To</div>
                        <div class="summary-value"><?php echo $ride['dropoff_location']; ?></div>
                    </div>
                    
                    <div class="summary-item">
                        <div class="summary-label">Distance</div>
                        <div class="summary-value"><?php echo $ride['distance']; ?> km</div>
                    </div>
                    
                    <div class="summary-item">
                        <div class="summary-label">Fare</div>
                        <div class="summary-value">₹<?php echo $ride['final_fare'] ? $ride['final_fare'] : $ride['estimated_fare']; ?></div>
                    </div>
                </div>
                
                <div class="driver-info">
                    <div class="driver-avatar"><?php echo substr($ride['driver_name'], 0, 1); ?></div>
                    <div class="driver-details">
                        <h3><?php echo $ride['driver_name']; ?></h3>
                        <p>Your driver for this trip</p>
                    </div>
                </div>
                
                <?php if ($review_exists): ?>
                    <div class="review-submitted">
                        <div class="icon">✅</div>
                        <h2>Thank You!</h2>
                        <p>You have already submitted a review for this ride.</p>
                        <a href="dashboard.php" class="btn">Back to Dashboard</a>
                    </div>
                <?php else: ?>
                    <form method="POST" action="" class="rating-form">
                        <h2>Rate Your Experience</h2>
                        
                        <div class="star-rating">
                            <div class="star" data-rating="1">★</div>
                            <div class="star" data-rating="2">★</div>
                            <div class="star" data-rating="3">★</div>
                            <div class="star" data-rating="4">★</div>
                            <div class="star" data-rating="5">★</div>
                        </div>
                        
                        <input type="hidden" name="rating" id="rating-value" value="">
                        
                        <div class="form-group">
                            <label for="comment">Additional Comments (Optional)</label>
                            <textarea id="comment" name="comment" class="form-control" placeholder="Share your experience with the driver and the ride"></textarea>
                        </div>
                        
                        <button type="submit" class="btn">Submit Review</button>
                    </form>
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
            // Star rating functionality
            const stars = document.querySelectorAll('.star');
            const ratingInput = document.getElementById('rating-value');
            
            stars.forEach(star => {
                star.addEventListener('click', function() {
                    const rating = this.dataset.rating;
                    ratingInput.value = rating;
                    
                    // Update star colors
                    stars.forEach(s => {
                        if (s.dataset.rating <= rating) {
                            s.classList.add('active');
                        } else {
                            s.classList.remove('active');
                        }
                    });
                });
            });
        });
    </script>
</body>
</html>
