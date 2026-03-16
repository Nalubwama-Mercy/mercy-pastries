<?php
session_start();
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

// Get current user if logged in
$current_user = null;
if (isset($_SESSION['user_id'])) {
    $query = "SELECT * FROM users WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$_SESSION['user_id']]);
    $current_user = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mercy Pastries – Fresh & Sweet</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --purple: #6f42c1;
            --purple-dark: #5a32a3;
            --blue: #0d6efd;
            --blue-dark: #0b5ed7;
            --gradient: linear-gradient(135deg, var(--purple), var(--blue));
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding-top: 76px;
            color: #333;
        }
        
        /* Navbar */
        .navbar {
            background: linear-gradient(90deg, var(--purple) 0%, var(--blue) 100%) !important;
            padding: 1rem 0;
            box-shadow: 0 2px 20px rgba(0,0,0,0.1);
        }
        
        .navbar-brand {
            font-size: 1.8rem;
            font-weight: 800;
            letter-spacing: 1px;
            color: white !important;
        }
        
        .navbar-brand i {
            font-size: 2rem;
            margin-right: 10px;
        }
        
        .nav-link {
            color: rgba(255,255,255,0.9) !important;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .nav-link:hover {
            color: white !important;
            transform: translateY(-2px);
        }
        
        .btn-mercy {
            background: white;
            color: var(--purple) !important;
            border: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-mercy:hover {
            background: var(--purple-light);
            color: white !important;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        /* Hero Section - WORKING WEB IMAGES */
        .hero-section {
            height: 90vh;
            min-height: 600px;
            position: relative;
            overflow: hidden;
            margin-top: -76px;
        }
        
        .hero-slide {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            transition: opacity 1s ease;
            background-size: cover;
            background-position: center;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: white;
        }
        
        .hero-slide.active {
            opacity: 1;
        }
        
        .hero-slide::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.5));
        }
        
        .hero-content {
            position: relative;
            z-index: 2;
            max-width: 800px;
            padding: 20px;
        }
        
        .hero-content h1 {
            font-size: 4rem;
            font-weight: 800;
            margin-bottom: 20px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
            animation: fadeInUp 1s ease;
        }
        
        .hero-content p {
            font-size: 1.5rem;
            margin-bottom: 30px;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.5);
            animation: fadeInUp 1s ease 0.2s both;
        }
        
        .hero-content .btn {
            animation: fadeInUp 1s ease 0.4s both;
            padding: 15px 40px;
            font-size: 1.2rem;
        }
        
        .hero-indicators {
            position: absolute;
            bottom: 30px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 3;
            display: flex;
            gap: 10px;
        }
        
        .hero-indicator {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: rgba(255,255,255,0.5);
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .hero-indicator.active {
            background: white;
            transform: scale(1.2);
        }
        
        .hero-control {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            width: 50px;
            height: 50px;
            background: rgba(255,255,255,0.3);
            border: none;
            border-radius: 50%;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
            transition: all 0.3s ease;
            z-index: 3;
        }
        
        .hero-control:hover {
            background: rgba(255,255,255,0.5);
            transform: translateY(-50%) scale(1.1);
        }
        
        .hero-control.prev {
            left: 20px;
        }
        
        .hero-control.next {
            right: 20px;
        }
        
        /* Section Title */
        .section-title {
            position: relative;
            padding-bottom: 15px;
            margin-bottom: 30px;
            text-align: center;
            font-size: 2.5rem;
            font-weight: 700;
        }
        
        .section-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 4px;
            background: var(--gradient);
            border-radius: 2px;
        }
        
        /* Product Cards */
        .product-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            height: 100%;
        }
        
        .product-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.2);
        }
        
        .product-image {
            height: 250px;
            overflow: hidden;
        }
        
        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }
        
        .product-card:hover .product-image img {
            transform: scale(1.1);
        }
        
        .product-info {
            padding: 20px;
            text-align: center;
        }
        
        .product-category {
            color: var(--purple);
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .product-info h5 {
            margin: 10px 0;
            font-weight: 600;
        }
        
        .product-price {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--blue);
        }
        
        /* About Section */
        .about-section {
            background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
            padding: 80px 0;
        }
        
        .stat-box {
            text-align: center;
        }
        
        .stat-box h3 {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--purple);
        }
        
        /* Testimonials */
        .testimonial-card {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            height: 100%;
        }
        
        .testimonial-card i {
            font-size: 2rem;
            color: var(--purple);
            opacity: 0.3;
            margin-bottom: 15px;
        }
        
        .testimonial-author {
            display: flex;
            align-items: center;
            margin-top: 20px;
        }
        
        .testimonial-author img {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 15px;
            border: 3px solid var(--purple);
        }
        
        /* Newsletter */
        .newsletter-section {
            background: var(--gradient);
            color: white;
            padding: 80px 0;
            text-align: center;
        }
        
        .newsletter-form {
            max-width: 500px;
            margin: 0 auto;
        }
        
        .newsletter-form .input-group {
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        
        .newsletter-form input {
            border: none;
            padding: 15px 20px;
            font-size: 1rem;
        }
        
        .newsletter-form button {
            padding: 15px 30px;
            font-weight: 600;
            background: white;
            color: var(--purple);
            border: none;
        }
        
        .newsletter-form button:hover {
            background: var(--purple-light);
            color: white;
        }
        
        /* Footer */
        footer {
            background: #1a1a1a;
            color: #999;
            padding: 60px 0 30px;
        }
        
        footer h5 {
            color: white;
            margin-bottom: 20px;
        }
        
        footer a {
            color: #999;
            text-decoration: none;
            transition: color 0.3s ease;
        }
        
        footer a:hover {
            color: var(--purple);
        }
        
        .social-links {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }
        
        .social-links a {
            width: 40px;
            height: 40px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            transition: all 0.3s ease;
        }
        
        .social-links a:hover {
            background: var(--gradient);
            transform: translateY(-3px);
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @media (max-width: 768px) {
            .hero-content h1 {
                font-size: 2.5rem;
            }
            
            .hero-content p {
                font-size: 1.2rem;
            }
            
            .section-title {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark fixed-top">
    <div class="container">
        <a class="navbar-brand" href="index.php">
            <i class="fas fa-cake-candles"></i> Mercy Pastries
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto align-items-center">
                <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
                <li class="nav-item"><a class="nav-link" href="#products">Products</a></li>
                <li class="nav-item"><a class="nav-link" href="services.php">Services</a></li>
                <li class="nav-item"><a class="nav-link" href="#about">About</a></li>
                <li class="nav-item"><a class="nav-link" href="contact.php">Contact</a></li>
                <?php if ($current_user): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle me-1"></i><?php echo htmlspecialchars($current_user['full_name']); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="profile.php">Profile</a></li>
                            <li><a class="dropdown-item" href="orders.php">My Orders</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="login.php">Login</a></li>
                    <li class="nav-item"><a class="nav-link btn btn-mercy btn-sm ms-2 px-4" href="register.php">Sign Up</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<!-- HERO SECTION WITH 100% WORKING WEB IMAGES -->
<section class="hero-section" id="hero">
    <div class="hero-slide active" style="background-image: url('https://images.unsplash.com/photo-1550617931-eb3a88e84519?ixlib=rb-4.0.3&auto=format&fit=crop&w=1350&q=80')">
        <div class="hero-content">
            <h1>Welcome to Mercy Pastries</h1>
            <p class="lead">Freshly baked with love every day</p>
            <a href="#products" class="btn btn-mercy btn-lg">Explore Our Treats</a>
        </div>
    </div>
    
    <div class="hero-slide" style="background-image: url('https://images.unsplash.com/photo-1563729783412-159409d089a0?ixlib=rb-4.0.3&auto=format&fit=crop&w=1350&q=80')">
        <div class="hero-content">
            <h1>Custom Celebration Cakes</h1>
            <p class="lead">Make your special day even sweeter</p>
            <a href="services.php" class="btn btn-mercy btn-lg">Learn More</a>
        </div>
    </div>
    
    <div class="hero-slide" style="background-image: url('https://images.unsplash.com/photo-1612201142855-fa6e8a26d7e4?ixlib=rb-4.0.3&auto=format&fit=crop&w=1350&q=80')">
        <div class="hero-content">
            <h1>Fresh Daily Pastries</h1>
            <p class="lead">Croissants, donuts, muffins & more</p>
            <a href="products.php" class="btn btn-mercy btn-lg">Shop Now</a>
        </div>
    </div>
    
    <div class="hero-indicators">
        <span class="hero-indicator active" onclick="currentSlide(0)"></span>
        <span class="hero-indicator" onclick="currentSlide(1)"></span>
        <span class="hero-indicator" onclick="currentSlide(2)"></span>
    </div>
    
    <button class="hero-control prev" onclick="changeSlide(-1)">❮</button>
    <button class="hero-control next" onclick="changeSlide(1)">❯</button>
</section>

<!-- Products Section -->
<section class="py-5" id="products">
    <div class="container">
        <h2 class="section-title">Our Signature Treats</h2>
        <div class="row g-4">
            <!-- Product 1 -->
            <div class="col-md-4">
                <div class="product-card">
                    <div class="product-image">
                        <img src="https://images.unsplash.com/photo-1578985545062-69928b1d9587?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80" alt="Chocolate Cake">
                    </div>
                    <div class="product-info">
                        <span class="product-category">Cakes</span>
                        <h5>Classic Chocolate Cake</h5>
                        <div class="product-price">UGX 95,000</div>
                    </div>
                </div>
            </div>
            
            <!-- Product 2 -->
            <div class="col-md-4">
                <div class="product-card">
                    <div class="product-image">
                        <img src="https://images.unsplash.com/photo-1555507036-ab1f4038808a?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80" alt="Croissant">
                    </div>
                    <div class="product-info">
                        <span class="product-category">Pastries</span>
                        <h5>Buttery Croissant</h5>
                        <div class="product-price">UGX 9,000</div>
                    </div>
                </div>
            </div>
            
            <!-- Product 3 -->
            <div class="col-md-4">
                <div class="product-card">
                    <div class="product-image">
                        <img src="https://images.unsplash.com/photo-1550617931-eb3a88e84519?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80" alt="Donuts">
                    </div>
                    <div class="product-info">
                        <span class="product-category">Donuts</span>
                        <h5>Assorted Donuts (6pcs)</h5>
                        <div class="product-price">UGX 48,000</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- About Section -->
<section class="about-section" id="about">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6 mb-4 mb-lg-0">
                <img src="https://images.unsplash.com/photo-1550617931-eb3a88e84519?ixlib=rb-4.0.3&auto=format&fit=crop&w=1050&q=80" alt="About Us" class="img-fluid rounded-3 shadow">
            </div>
            <div class="col-lg-6">
                <h2 class="section-title text-start">Our Story</h2>
                <p class="lead mb-4">Founded in 2018, Mercy Pastries began with a simple dream: to bring joy to every occasion through delicious, beautifully crafted baked goods.</p>
                <p>What started as a small home kitchen operation has grown into one of Kampala's most beloved bakeries. We've maintained our commitment to quality, using only the finest ingredients and traditional baking techniques combined with creative innovation.</p>
                <div class="row mt-5">
                    <div class="col-4 text-center">
                        <h3>5+</h3>
                        <p>Years</p>
                    </div>
                    <div class="col-4 text-center">
                        <h3>1000+</h3>
                        <p>Customers</p>
                    </div>
                    <div class="col-4 text-center">
                        <h3>50+</h3>
                        <p>Recipes</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Testimonials -->
<section class="py-5 bg-light">
    <div class="container">
        <h2 class="section-title">What Our Customers Say</h2>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="testimonial-card">
                    <i class="fas fa-quote-left"></i>
                    <p>"The best cakes in Kampala! They made my daughter's birthday cake exactly as I imagined. So beautiful and delicious!"</p>
                    <div class="testimonial-author">
                        <img src="https://randomuser.me/api/portraits/women/44.jpg" alt="Sarah">
                        <div>
                            <h6 class="mb-0">Sarah K.</h6>
                            <small>Happy Customer</small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="testimonial-card">
                    <i class="fas fa-quote-left"></i>
                    <p>"Amazing service and even better pastries. Their croissants are authentic and flaky – just like in Paris!"</p>
                    <div class="testimonial-author">
                        <img src="https://randomuser.me/api/portraits/men/32.jpg" alt="James">
                        <div>
                            <h6 class="mb-0">James M.</h6>
                            <small>Regular Customer</small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="testimonial-card">
                    <i class="fas fa-quote-left"></i>
                    <p>"I ordered cupcakes for my office party and everyone loved them! Great presentation and very fresh."</p>
                    <div class="testimonial-author">
                        <img src="https://randomuser.me/api/portraits/women/68.jpg" alt="Mercy">
                        <div>
                            <h6 class="mb-0">Mercy N.</h6>
                            <small>Event Planner</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Newsletter -->
<section class="newsletter-section">
    <div class="container">
        <h2 class="mb-4">Subscribe to Our Newsletter</h2>
        <p class="lead mb-5">Get updates on new products, special offers, and baking tips!</p>
        <form class="newsletter-form">
            <div class="input-group">
                <input type="email" class="form-control" placeholder="Your Email Address" required>
                <button class="btn" type="submit">Subscribe</button>
            </div>
        </form>
    </div>
</section>

<!-- Footer -->
<footer>
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-4">
                <h5>Mercy Pastries</h5>
                <p>Baked with love in Kampala. Fresh cakes, pastries, and custom orders for every occasion.</p>
                <div class="social-links">
                    <a href="#"><i class="fab fa-facebook-f"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-whatsapp"></i></a>
                    <a href="#"><i class="fab fa-tiktok"></i></a>
                </div>
            </div>
            <div class="col-lg-2">
                <h5>Quick Links</h5>
                <ul class="list-unstyled">
                    <li><a href="index.php">Home</a></li>
                    <li><a href="#products">Products</a></li>
                    <li><a href="services.php">Services</a></li>
                    <li><a href="contact.php">Contact</a></li>
                </ul>
            </div>
            <div class="col-lg-3">
                <h5>Contact Info</h5>
                <ul class="list-unstyled">
                    <li><i class="fas fa-map-marker-alt me-2"></i> Katabi – Entebbe Road, Kampala</li>
                    <li><i class="fas fa-phone me-2"></i> +256 706 083004</li>
                    <li><i class="fas fa-envelope me-2"></i> info@mercypastries.com</li>
                </ul>
            </div>
            <div class="col-lg-3">
                <h5>Business Hours</h5>
                <ul class="list-unstyled">
                    <li>Mon – Sat: 7:00 AM – 8:00 PM</li>
                    <li>Sunday: 8:00 AM – 6:00 PM</li>
                </ul>
            </div>
        </div>
        <hr class="my-4">
        <div class="text-center">
            <p>&copy; 2024 Mercy Pastries. All rights reserved.</p>
        </div>
    </div>
</footer>

<script>
// Hero Slider JavaScript
let slideIndex = 0;
const slides = document.querySelectorAll('.hero-slide');
const indicators = document.querySelectorAll('.hero-indicator');

function showSlide(n) {
    if (n >= slides.length) slideIndex = 0;
    if (n < 0) slideIndex = slides.length - 1;
    
    slides.forEach(slide => slide.classList.remove('active'));
    indicators.forEach(ind => ind.classList.remove('active'));
    
    slides[slideIndex].classList.add('active');
    indicators[slideIndex].classList.add('active');
}

function changeSlide(direction) {
    slideIndex += direction;
    showSlide(slideIndex);
}

function currentSlide(n) {
    slideIndex = n;
    showSlide(slideIndex);
}

// Auto advance slides every 5 seconds
setInterval(() => {
    changeSlide(1);
}, 5000);

// Smooth scrolling for anchor links
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function(e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    });
});

// Navbar scroll effect
window.addEventListener('scroll', function() {
    const navbar = document.querySelector('.navbar');
    if (window.scrollY > 50) {
        navbar.style.padding = '0.5rem 0';
    } else {
        navbar.style.padding = '1rem 0';
    }
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>