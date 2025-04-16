<?php
require_once 'db.php';

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    redirect('dashboard.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yango - Book Your Ride</title>
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
        
        .hero {
            height: 100vh;
            display: flex;
            align-items: center;
            background: linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7)), url('https://images.unsplash.com/photo-1449965408869-eaa3f722e40d?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80');
            background-size: cover;
            background-position: center;
            color: white;
            text-align: center;
            padding-top: 60px;
        }
        
        .hero-content {
            max-width: 800px;
            margin: 0 auto;
        }
        
        .hero h1 {
            font-size: 3.5rem;
            margin-bottom: 20px;
        }
        
        .hero p {
            font-size: 1.2rem;
            margin-bottom: 30px;
        }
        
        .booking-form {
            background-color: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            max-width: 500px;
            margin: 40px auto;
            color: var(--dark-color);
        }
        
        .booking-form h2 {
            text-align: center;
            margin-bottom: 20px;
            color: var(--primary-color);
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
            justify-content: space-between;
            margin-bottom: 20px;
        }
        
        .ride-type {
            text-align: center;
            padding: 10px;
            border: 2px solid #ddd;
            border-radius: 5px;
            cursor: pointer;
            width: 23%;
            transition: all 0.3s;
        }
        
        .ride-type:hover, .ride-type.active {
            border-color: var(--primary-color);
            background-color: rgba(255, 87, 34, 0.1);
        }
        
        .ride-type img {
            width: 40px;
            height: 40px;
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
        
        .features {
            padding: 80px 0;
            background-color: white;
        }
        
        .section-title {
            text-align: center;
            margin-bottom: 50px;
        }
        
        .section-title h2 {
            font-size: 2.5rem;
            color: var(--primary-color);
            margin-bottom: 15px;
        }
        
        .section-title p {
            color: #777;
            max-width: 700px;
            margin: 0 auto;
        }
        
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
        }
        
        .feature-card {
            background-color: #f9f9f9;
            padding: 30px;
            border-radius: 10px;
            text-align: center;
            transition: transform 0.3s;
        }
        
        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }
        
        .feature-icon {
            font-size: 50px;
            color: var(--primary-color);
            margin-bottom: 20px;
        }
        
        .feature-card h3 {
            margin-bottom: 15px;
            color: var(--dark-color);
        }
        
        .how-it-works {
            padding: 80px 0;
            background-color: #f8f9fa;
        }
        
        .steps {
            display: flex;
            justify-content: space-between;
            margin-top: 50px;
        }
        
        .step {
            text-align: center;
            width: 30%;
            position: relative;
        }
        
        .step-number {
            width: 60px;
            height: 60px;
            background-color: var(--primary-color);
            color: white;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 24px;
            font-weight: bold;
            margin: 0 auto 20px;
        }
        
        .step h3 {
            margin-bottom: 15px;
            color: var(--dark-color);
        }
        
        .step:not(:last-child)::after {
            content: '';
            position: absolute;
            top: 30px;
            right: -15%;
            width: 30%;
            height: 2px;
            background-color: var(--primary-color);
        }
        
        .testimonials {
            padding: 80px 0;
            background-color: white;
        }
        
        .testimonial-slider {
            max-width: 800px;
            margin: 0 auto;
            overflow: hidden;
            position: relative;
        }
        
        .testimonial-slide {
            text-align: center;
            padding: 30px;
        }
        
        .testimonial-text {
            font-size: 18px;
            font-style: italic;
            margin-bottom: 20px;
            color: #555;
        }
        
        .testimonial-author {
            font-weight: bold;
            color: var(--primary-color);
        }
        
        .download-app {
            padding: 80px 0;
            background: linear-gradient(to right, var(--primary-color), var(--secondary-color));
            color: white;
            text-align: center;
        }
        
        .app-buttons {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 30px;
        }
        
        .app-btn {
            display: flex;
            align-items: center;
            background-color: white;
            color: var(--dark-color);
            padding: 12px 25px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 500;
            transition: transform 0.3s;
        }
        
        .app-btn:hover {
            transform: translateY(-5px);
        }
        
        .app-btn i {
            font-size: 30px;
            margin-right: 10px;
        }
        
        footer {
            background-color: var(--dark-color);
            color: white;
            padding: 50px 0 20px;
        }
        
        .footer-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 30px;
            margin-bottom: 30px;
        }
        
        .footer-column h3 {
            font-size: 18px;
            margin-bottom: 20px;
            color: var(--primary-color);
        }
        
        .footer-links {
            list-style: none;
        }
        
        .footer-links li {
            margin-bottom: 10px;
        }
        
        .footer-links a {
            color: #ddd;
            text-decoration: none;
            transition: color 0.3s;
        }
        
        .footer-links a:hover {
            color: var(--primary-color);
        }
        
        .social-links {
            display: flex;
            gap: 15px;
        }
        
        .social-links a {
            color: white;
            background-color: rgba(255, 255, 255, 0.1);
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            transition: background-color 0.3s;
        }
        
        .social-links a:hover {
            background-color: var(--primary-color);
        }
        
        .footer-bottom {
            text-align: center;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        @media (max-width: 768px) {
            .navbar {
                flex-direction: column;
            }
            
            .nav-links {
                margin-top: 15px;
            }
            
            .hero h1 {
                font-size: 2.5rem;
            }
            
            .steps {
                flex-direction: column;
                align-items: center;
            }
            
            .step {
                width: 100%;
                margin-bottom: 40px;
            }
            
            .step:not(:last-child)::after {
                display: none;
            }
            
            .ride-types {
                flex-wrap: wrap;
            }
            
            .ride-type {
                width: 48%;
                margin-bottom: 10px;
            }
        }
        
        /* Icons */
        .icon {
            display: inline-block;
            width: 1em;
            height: 1em;
            stroke-width: 0;
            stroke: currentColor;
            fill: currentColor;
            font-size: 24px;
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <nav class="navbar">
                <a href="index.php" class="logo">Yan<span>go</span></a>
                <ul class="nav-links">
                    <li><a href="#features">Features</a></li>
                    <li><a href="#how-it-works">How It Works</a></li>
                    <li><a href="#testimonials">Testimonials</a></li>
                    <li><a href="login.php" class="btn btn-outline">Login</a></li>
                    <li><a href="register.php" class="btn">Sign Up</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <section class="hero">
        <div class="container">
            <div class="hero-content">
                <h1>Your Ride, Your Way</h1>
                <p>Book a ride in minutes and travel with comfort and safety. Yango is your reliable transportation partner.</p>
                <a href="register.php" class="btn">Get Started</a>
            </div>
            
            <div class="booking-form">
                <h2>Book Your Ride</h2>
                <form id="ride-booking-form">
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
                    
                    <button type="button" id="estimate-btn" class="btn">Estimate Fare</button>
                    <button type="button" id="book-btn" class="btn" style="display: none;">Book Now</button>
                </form>
            </div>
        </div>
    </section>

    <section class="features" id="features">
        <div class="container">
            <div class="section-title">
                <h2>Why Choose Yango</h2>
                <p>Experience the best ride-hailing service with features designed for your comfort and convenience.</p>
            </div>
            
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">💰</div>
                    <h3>Affordable Prices</h3>
                    <p>Enjoy competitive rates and transparent pricing with no hidden charges.</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">⏱️</div>
                    <h3>Quick Pickups</h3>
                    <p>Get picked up within minutes of booking your ride.</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">🛡️</div>
                    <h3>Safe Rides</h3>
                    <p>All our drivers are verified and trained to ensure your safety.</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">🌟</div>
                    <h3>Quality Service</h3>
                    <p>Enjoy a comfortable ride with our well-maintained vehicles.</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">📱</div>
                    <h3>Easy Booking</h3>
                    <p>Book your ride with just a few taps on your smartphone.</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">🔍</div>
                    <h3>Ride Tracking</h3>
                    <p>Track your ride in real-time and share your trip details with loved ones.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="how-it-works" id="how-it-works">
        <div class="container">
            <div class="section-title">
                <h2>How It Works</h2>
                <p>Booking a ride with Yango is simple and straightforward.</p>
            </div>
            
            <div class="steps">
                <div class="step">
                    <div class="step-number">1</div>
                    <h3>Enter Location</h3>
                    <p>Enter your pickup and dropoff locations to find available rides.</p>
                </div>
                
                <div class="step">
                    <div class="step-number">2</div>
                    <h3>Choose Ride</h3>
                    <p>Select your preferred ride type based on your needs and budget.</p>
                </div>
                
                <div class="step">
                    <div class="step-number">3</div>
                    <h3>Enjoy the Ride</h3>
                    <p>Confirm your booking, wait for your driver, and enjoy your journey.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="testimonials" id="testimonials">
        <div class="container">
            <div class="section-title">
                <h2>What Our Customers Say</h2>
                <p>Don't just take our word for it. Here's what our customers have to say about their Yango experience.</p>
            </div>
            
            <div class="testimonial-slider">
                <div class="testimonial-slide">
                    <p class="testimonial-text">"I've been using Yango for my daily commute for the past 6 months, and I couldn't be happier. The drivers are professional, and the rides are always comfortable."</p>
                    <p class="testimonial-author">- Rahul Sharma</p>
                </div>
            </div>
        </div>
    </section>

    <section class="download-app">
        <div class="container">
            <h2>Download Our App</h2>
            <p>Get the full Yango experience by downloading our mobile app.</p>
            
            <div class="app-buttons">
                <a href="#" class="app-btn">
                    <span class="icon">📱</span>
                    <span>
                        <small>Download on the</small>
                        <strong>App Store</strong>
                    </span>
                </a>
                
                <a href="#" class="app-btn">
                    <span class="icon">📱</span>
                    <span>
                        <small>Get it on</small>
                        <strong>Google Play</strong>
                    </span>
                </a>
            </div>
        </div>
    </section>

    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-column">
                    <h3>Yango</h3>
                    <p>Your reliable ride-hailing partner for safe and comfortable journeys. Experience the best service in town.</p>
                    
                    <div class="social-links">
                        <a href="#"><span class="icon">📱</span></a>
                        <a href="#"><span class="icon">💻</span></a>
                        <a href="#"><span class="icon">📷</span></a>
                        <a href="#"><span class="icon">📝</span></a>
                    </div>
                </div>
                
                <div class="footer-column">
                    <h3>Quick Links</h3>
                    <ul class="footer-links">
                        <li><a href="#">About Us</a></li>
                        <li><a href="#">Services</a></li>
                        <li><a href="#">Careers</a></li>
                        <li><a href="#">Blog</a></li>
                        <li><a href="#">Contact Us</a></li>
                    </ul>
                </div>
                
                <div class="footer-column">
                    <h3>Services</h3>
                    <ul class="footer-links">
                        <li><a href="#">City Rides</a></li>
                        <li><a href="#">Airport Transfers</a></li>
                        <li><a href="#">Intercity Travel</a></li>
                        <li><a href="#">Corporate Services</a></li>
                        <li><a href="#">Package Delivery</a></li>
                    </ul>
                </div>
                
                <div class="footer-column">
                    <h3>Contact Us</h3>
                    <ul class="footer-links">
                        <li>Email: info@yango.com</li>
                        <li>Phone: +1 234 567 890</li>
                        <li>Address: 123 Main Street, City</li>
                    </ul>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; 2023 Yango. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script>
        // JavaScript for ride booking functionality
        document.addEventListener('DOMContentLoaded', function() {
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
                // In a real app, this would save the booking to the database
                // For now, redirect to login if not logged in
                window.location.href = 'login.php';
            });
            
            // Smooth scrolling for navigation links
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    const targetId = this.getAttribute('href');
                    if (targetId === '#') return;
                    
                    const targetElement = document.querySelector(targetId);
                    if (targetElement) {
                        window.scrollTo({
                            top: targetElement.offsetTop - 70,
                            behavior: 'smooth'
                        });
                    }
                });
            });
        });
    </script>
</body>
</html>
