<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

$database = new Database();
$db = $database->getConnection();

// Get all services from database
$query = "SELECT * FROM services WHERE is_active = 1 ORDER BY id";
$stmt = $db->prepare($query);
$stmt->execute();
$services = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
    <title>Our Services - Mercy Pastries</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/aos@next/dist/aos.css" />
    <link rel="stylesheet" href="css/style.css">
    <style>
        :root {
            --purple: #6f42c1;
            --purple-dark: #5a32a3;
            --blue: #0d6efd;
            --blue-dark: #0b5ed7;
            --gradient: linear-gradient(135deg, var(--purple), var(--blue));
        }

        .services-hero {
            background: linear-gradient(rgba(111,66,193,0.95), rgba(13,110,253,0.95)),
                        url('https://images.unsplash.com/photo-1550617931-eb3a88e84519?ixlib=rb-4.0.3&auto=format&fit=crop&w=1350&q=80') center/cover;
            color: white;
            padding: 120px 0 100px;
            margin-bottom: 50px;
            position: relative;
            overflow: hidden;
        }

        .services-hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('images/pattern.png') repeat;
            opacity: 0.1;
            animation: slide 20s linear infinite;
        }

        @keyframes slide {
            from { background-position: 0 0; }
            to { background-position: 100% 100%; }
        }

        .services-hero h1 {
            font-size: 4rem;
            font-weight: 800;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
            margin-bottom: 20px;
        }

        .services-hero p {
            font-size: 1.3rem;
            opacity: 0.95;
            max-width: 800px;
            margin: 0 auto;
        }

        .service-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            height: 100%;
            position: relative;
            z-index: 1;
        }

        .service-card:hover {
            transform: translateY(-15px);
            box-shadow: 0 20px 40px rgba(111,66,193,0.2);
        }

        .service-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: var(--gradient);
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }

        .service-card:hover::before {
            transform: scaleX(1);
        }

        .service-image {
            height: 250px;
            overflow: hidden;
            position: relative;
        }

        .service-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .service-card:hover .service-image img {
            transform: scale(1.1);
        }

        .service-image .service-icon {
            position: absolute;
            bottom: -30px;
            right: 20px;
            width: 70px;
            height: 70px;
            background: var(--gradient);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            transition: all 0.3s ease;
            z-index: 2;
        }

        .service-card:hover .service-image .service-icon {
            transform: rotate(360deg) scale(1.1);
        }

        .service-content {
            padding: 30px 25px 25px;
        }

        .service-content h3 {
            font-size: 1.6rem;
            font-weight: 700;
            margin-bottom: 15px;
            color: #333;
        }

        .service-content p {
            color: #666;
            line-height: 1.7;
            margin-bottom: 20px;
        }

        .service-price {
            font-size: 1.4rem;
            font-weight: 700;
            background: var(--gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 20px;
        }

        .service-features {
            list-style: none;
            padding: 0;
            margin-bottom: 25px;
        }

        .service-features li {
            padding: 8px 0;
            border-bottom: 1px dashed #eee;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .service-features li i {
            color: var(--purple);
            font-size: 0.9rem;
        }

        .service-features li:last-child {
            border-bottom: none;
        }

        .btn-service {
            background: white;
            border: 2px solid var(--purple);
            color: var(--purple);
            padding: 12px 30px;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s ease;
            width: 100%;
            position: relative;
            overflow: hidden;
            z-index: 1;
        }

        .btn-service::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: var(--gradient);
            transition: left 0.3s ease;
            z-index: -1;
        }

        .btn-service:hover {
            color: white;
            border-color: transparent;
        }

        .btn-service:hover::before {
            left: 0;
        }

        .process-section {
            background: linear-gradient(135deg, #f5f0ff 0%, #e8f0ff 100%);
            padding: 80px 0;
            margin-top: 50px;
        }

        .process-step {
            text-align: center;
            padding: 30px;
            position: relative;
        }

        .process-step:not(:last-child)::after {
            content: '\f054';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            position: absolute;
            right: -20px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--purple);
            font-size: 1.5rem;
            opacity: 0.5;
        }

        .step-number {
            width: 80px;
            height: 80px;
            background: var(--gradient);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            font-weight: 700;
            margin: 0 auto 25px;
            box-shadow: 0 10px 20px rgba(111,66,193,0.3);
        }

        .process-step h4 {
            font-size: 1.4rem;
            font-weight: 700;
            margin-bottom: 15px;
        }

        .process-step p {
            color: #666;
        }

        .testimonial-card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            margin: 20px 0;
        }

        .testimonial-card img {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid var(--gradient);
        }

        .faq-section {
            background: white;
            padding: 60px 0;
        }

        .accordion-item {
            border: none;
            margin-bottom: 15px;
            border-radius: 10px !important;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }

        .accordion-button {
            font-weight: 600;
            color: #333;
            background: white;
            border: none;
            padding: 20px;
        }

        .accordion-button:not(.collapsed) {
            background: var(--gradient);
            color: white;
        }

        .accordion-button:focus {
            box-shadow: none;
        }

        .accordion-body {
            padding: 20px;
            background: #f8f9fa;
        }

        .cta-section {
            background: var(--gradient);
            color: white;
            padding: 80px 0;
            text-align: center;
        }

        .cta-section h2 {
            font-size: 3rem;
            font-weight: 800;
            margin-bottom: 20px;
        }

        .cta-section .btn-cta {
            background: white;
            color: var(--purple);
            padding: 15px 40px;
            border-radius: 50px;
            font-weight: 700;
            font-size: 1.2rem;
            transition: all 0.3s ease;
            margin: 0 10px;
        }

        .cta-section .btn-cta:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }

        .cta-section .btn-cta-outline {
            background: transparent;
            color: white;
            border: 2px solid white;
        }

        .cta-section .btn-cta-outline:hover {
            background: white;
            color: var(--purple);
        }

        @media (max-width: 768px) {
            .services-hero h1 {
                font-size: 2.5rem;
            }
            
            .process-step:not(:last-child)::after {
                display: none;
            }
            
            .cta-section h2 {
                font-size: 2rem;
            }
            
            .cta-section .btn-cta {
                display: block;
                margin: 10px auto;
                max-width: 250px;
            }
        }
    </style>
</head>
<body>

<!-- Navbar -->
<?php include 'includes/navbar.php'; ?>

<!-- Hero Section -->
<section class="services-hero text-center">
    <div class="container">
        <h1 data-aos="fade-down">Our Premium Services</h1>
        <p data-aos="fade-up" data-aos-delay="100">From custom celebration cakes to baking workshops – we bring sweetness to every moment of your life</p>
    </div>
</section>

<!-- Services Grid -->
<section class="py-5">
    <div class="container">
        <div class="row g-4">
            <?php if (empty($services)): ?>
                <!-- Default services if database is empty -->
                <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="100">
                    <div class="service-card">
                        <div class="service-image">
                            <img src="https://images.unsplash.com/photo-1563729783412-159409d089a0?w=600" alt="Custom Cakes">
                            <div class="service-icon">
                                <i class="fas fa-birthday-cake"></i>
                            </div>
                        </div>
                        <div class="service-content">
                            <h3>Custom Celebration Cakes</h3>
                            <p>Birthday, wedding, anniversary, baby shower – designed exactly to your theme and taste with premium ingredients.</p>
                            <div class="service-price">UGX 120,000+</div>
                            <ul class="service-features">
                                <li><i class="fas fa-check-circle"></i> Free consultation</li>
                                <li><i class="fas fa-check-circle"></i> Multiple flavor options</li>
                                <li><i class="fas fa-check-circle"></i> Delivery available</li>
                                <li><i class="fas fa-check-circle"></i> Free tasting session</li>
                            </ul>
                            <a href="contact.php" class="btn-service">Inquire Now</a>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="200">
                    <div class="service-card">
                        <div class="service-image">
                            <img src="https://images.unsplash.com/photo-1550617931-eb3a88e84519?w=600" alt="Fresh Pastries">
                            <div class="service-icon">
                                <i class="fas fa-bread-slice"></i>
                            </div>
                        </div>
                        <div class="service-content">
                            <h3>Fresh Daily Pastries</h3>
                            <p>Croissants, donuts, cinnamon rolls, scones, muffins – baked fresh every morning using traditional French techniques.</p>
                            <div class="service-price">UGX 5,000 – 18,000</div>
                            <ul class="service-features">
                                <li><i class="fas fa-check-circle"></i> Baked fresh daily</li>
                                <li><i class="fas fa-check-circle"></i> All-butter recipes</li>
                                <li><i class="fas fa-check-circle"></i> Gluten-free options</li>
                                <li><i class="fas fa-check-circle"></i> Bulk order discounts</li>
                            </ul>
                            <a href="products.php" class="btn-service">Order Now</a>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="300">
                    <div class="service-card">
                        <div class="service-image">
                            <img src="https://images.unsplash.com/photo-1612201142855-fa6e8a26d7e4?w=600" alt="Cupcakes">
                            <div class="service-icon">
                                <i class="fas fa-cupcake"></i>
                            </div>
                        </div>
                        <div class="service-content">
                            <h3>Cupcakes & Mini Desserts</h3>
                            <p>Perfect for events, gifts or just because. Available in elegant boxes of 6, 12 or 24 with various designs.</p>
                            <div class="service-price">UGX 48,000 – 180,000</div>
                            <ul class="service-features">
                                <li><i class="fas fa-check-circle"></i> 15+ flavors available</li>
                                <li><i class="fas fa-check-circle"></i> Custom designs</li>
                                <li><i class="fas fa-check-circle"></i> Vegan options</li>
                                <li><i class="fas fa-check-circle"></i> Gift wrapping</li>
                            </ul>
                            <a href="contact.php" class="btn-service">Order Box</a>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="400">
                    <div class="service-card">
                        <div class="service-image">
                            <img src="https://images.unsplash.com/photo-1551107696-a4b0c5a0d9a2?w=600" alt="Delivery">
                            <div class="service-icon">
                                <i class="fas fa-truck"></i>
                            </div>
                        </div>
                        <div class="service-content">
                            <h3>Island-wide Delivery</h3>
                            <p>Fast, careful delivery across Kampala and surrounding areas. Same-day available for orders before 12 PM.</p>
                            <div class="service-price">UGX 10,000+</div>
                            <ul class="service-features">
                                <li><i class="fas fa-check-circle"></i> Real-time tracking</li>
                                <li><i class="fas fa-check-circle"></i> Free delivery > UGX 100k</li>
                                <li><i class="fas fa-check-circle"></i> Temperature controlled</li>
                                <li><i class="fas fa-check-circle"></i> Gift messaging</li>
                            </ul>
                            <a href="check-delivery.php" class="btn-service">Check Coverage</a>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="500">
                    <div class="service-card">
                        <div class="service-image">
                            <img src="https://images.unsplash.com/photo-1600585154340-be6161a56a0c?w=600" alt="Workshops">
                            <div class="service-icon">
                                <i class="fas fa-chalkboard-teacher"></i>
                            </div>
                        </div>
                        <div class="service-content">
                            <h3>Baking Workshops</h3>
                            <p>Learn to decorate cakes, make macarons, or perfect your croissant technique with our master pastry chefs.</p>
                            <div class="service-price">UGX 150,000 – 350,000</div>
                            <ul class="service-features">
                                <li><i class="fas fa-check-circle"></i> All materials included</li>
                                <li><i class="fas fa-check-circle"></i> Small class sizes</li>
                                <li><i class="fas fa-check-circle"></i> Take-home creations</li>
                                <li><i class="fas fa-check-circle"></i> Certificate provided</li>
                            </ul>
                            <a href="workshops.php" class="btn-service">Book Now</a>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="600">
                    <div class="service-card">
                        <div class="service-image">
                            <img src="https://images.unsplash.com/photo-1550617931-eb3a88e84519?w=600" alt="Corporate">
                            <div class="service-icon">
                                <i class="fas fa-briefcase"></i>
                            </div>
                        </div>
                        <div class="service-content">
                            <h3>Corporate Catering</h3>
                            <p>Office meetings, launches, weddings, parties – custom dessert tables, branded cookies, and tiered cakes.</p>
                            <div class="service-price">Custom Quote</div>
                            <ul class="service-features">
                                <li><i class="fas fa-check-circle"></i> Corporate accounts</li>
                                <li><i class="fas fa-check-circle"></i> Branded desserts</li>
                                <li><i class="fas fa-check-circle"></i> Event planning</li>
                                <li><i class="fas fa-check-circle"></i> Staff appreciation</li>
                            </ul>
                            <a href="contact.php" class="btn-service">Get Quote</a>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($services as $index => $service): ?>
                <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="<?php echo ($index * 100) + 100; ?>">
                    <div class="service-card">
                        <div class="service-image">
                            <img src="<?php echo $service['image'] ?: 'https://images.unsplash.com/photo-1550617931-eb3a88e84519?w=600'; ?>" alt="<?php echo htmlspecialchars($service['name']); ?>">
                            <div class="service-icon">
                                <i class="fas <?php echo $service['icon'] ?: 'fa-concierge-bell'; ?>"></i>
                            </div>
                        </div>
                        <div class="service-content">
                            <h3><?php echo htmlspecialchars($service['name']); ?></h3>
                            <p><?php echo htmlspecialchars($service['description']); ?></p>
                            <div class="service-price"><?php echo $service['price_range']; ?></div>
                            <ul class="service-features">
                                <li><i class="fas fa-check-circle"></i> Professional service</li>
                                <li><i class="fas fa-check-circle"></i> Quality guaranteed</li>
                                <li><i class="fas fa-check-circle"></i> Customized options</li>
                                <li><i class="fas fa-check-circle"></i> Best price promise</li>
                            </ul>
                            <a href="contact.php?service=<?php echo $service['id']; ?>" class="btn-service">Inquire Now</a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- How It Works Section -->
<section class="process-section">
    <div class="container">
        <h2 class="section-title text-center mb-5" data-aos="fade-up">How It Works</h2>
        <div class="row">
            <div class="col-md-4" data-aos="fade-right">
                <div class="process-step">
                    <div class="step-number">1</div>
                    <h4>Choose Your Service</h4>
                    <p>Browse our services and select what you need – from custom cakes to catering.</p>
                </div>
            </div>
            <div class="col-md-4" data-aos="fade-up">
                <div class="process-step">
                    <div class="step-number">2</div>
                    <h4>Consult With Us</h4>
                    <p>Meet with our team to discuss your requirements, preferences, and budget.</p>
                </div>
            </div>
            <div class="col-md-4" data-aos="fade-left">
                <div class="process-step">
                    <div class="step-number">3</div>
                    <h4>Enjoy & Celebrate</h4>
                    <p>We deliver fresh, beautiful creations that make your occasion memorable.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Testimonials -->
<section class="py-5">
    <div class="container">
        <h2 class="section-title text-center mb-5" data-aos="fade-up">What Our Clients Say</h2>
        <div class="row">
            <div class="col-md-4" data-aos="fade-right">
                <div class="testimonial-card">
                    <img src="https://randomuser.me/api/portraits/women/44.jpg" alt="Sarah" class="mb-3">
                    <i class="fas fa-quote-left text-primary mb-3"></i>
                    <p>"The wedding cake Mercy Pastries made for us was absolutely stunning! Not only beautiful but delicious. Our guests are still talking about it!"</p>
                    <h5 class="mb-0">Sarah & James</h5>
                    <small>Wedding Cake, March 2024</small>
                </div>
            </div>
            <div class="col-md-4" data-aos="fade-up">
                <div class="testimonial-card">
                    <img src="https://randomuser.me/api/portraits/men/32.jpg" alt="Michael" class="mb-3">
                    <i class="fas fa-quote-left text-primary mb-3"></i>
                    <p>"I attend their croissant workshop and it was amazing! Learned so much and the croissants turned out perfect. Highly recommend!"</p>
                    <h5 class="mb-0">Michael Okello</h5>
                    <small>Baking Workshop, Feb 2024</small>
                </div>
            </div>
            <div class="col-md-4" data-aos="fade-left">
                <div class="testimonial-card">
                    <img src="https://randomuser.me/api/portraits/women/68.jpg" alt="Grace" class="mb-3">
                    <i class="fas fa-quote-left text-primary mb-3"></i>
                    <p>"We use Mercy Pastries for all our corporate events. Professional, punctual, and the pastries are always fresh and delicious!"</p>
                    <h5 class="mb-0">Grace Achieng</h5>
                    <small>Corporate Client</small>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- FAQ Section -->
<section class="faq-section">
    <div class="container">
        <h2 class="section-title text-center mb-5" data-aos="fade-up">Frequently Asked Questions</h2>
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="accordion" id="faqAccordion">
                    <div class="accordion-item" data-aos="fade-up">
                        <h2 class="accordion-header">
                            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                                How far in advance should I order a custom cake?
                            </button>
                        </h2>
                        <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                We recommend ordering custom cakes at least 5-7 days in advance. For complex wedding cakes or large events, please allow 2-3 weeks. This ensures we have enough time for consultations, design, and perfect execution.
                            </div>
                        </div>
                    </div>
                    
                    <div class="accordion-item" data-aos="fade-up" data-aos-delay="100">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                                Do you accommodate dietary restrictions?
                            </button>
                        </h2>
                        <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Yes! We offer gluten-free, vegan, nut-free, and dairy-free options. Please let us know your requirements during consultation so we can prepare accordingly. Note that while we take precautions, our kitchen handles allergens.
                            </div>
                        </div>
                    </div>
                    
                    <div class="accordion-item" data-aos="fade-up" data-aos-delay="200">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                                What is your delivery area and cost?
                            </button>
                        </h2>
                        <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                We deliver throughout Kampala and surrounding areas including Entebbe, Mukono, and Wakiso. Delivery starts at UGX 10,000 and is FREE for orders above UGX 100,000. Same-day delivery available for orders placed before 12 PM.
                            </div>
                        </div>
                    </div>
                    
                    <div class="accordion-item" data-aos="fade-up" data-aos-delay="300">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq4">
                                Do you offer tastings?
                            </button>
                        </h2>
                        <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Yes! For wedding cakes and large celebration cakes, we offer complimentary tasting sessions. You can sample different cake flavors, fillings, and frostings to find your perfect combination. Please schedule an appointment.
                            </div>
                        </div>
                    </div>
                    
                    <div class="accordion-item" data-aos="fade-up" data-aos-delay="400">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq5">
                                What is your cancellation policy?
                            </button>
                        </h2>
                        <div id="faq5" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Orders can be cancelled up to 48 hours before the scheduled pickup/delivery time for a full refund. Cancellations within 48 hours may incur a 50% fee. For wedding cakes, please refer to your contract for specific terms.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Call to Action -->
<section class="cta-section">
    <div class="container">
        <h2 data-aos="zoom-in">Ready to Order?</h2>
        <p class="lead mb-5" data-aos="zoom-in" data-aos-delay="100">Let's create something beautiful and delicious for your next occasion!</p>
        <div data-aos="fade-up" data-aos-delay="200">
            <a href="contact.php" class="btn-cta">Contact Us Today</a>
            <a href="products.php" class="btn-cta btn-cta-outline">Browse Products</a>
        </div>
    </div>
</section>

<!-- Footer -->
<?php include 'includes/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/aos@next/dist/aos.js"></script>
<script src="js/main.js"></script>
<script>
    AOS.init({
        duration: 1000,
        once: true,
        offset: 100
    });
</script>
</body>
</html>