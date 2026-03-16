<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

$database = new Database();
$db = $database->getConnection();

$success = false;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    
    // Validation
    if (empty($name)) $errors[] = "Name is required";
    if (empty($email)) $errors[] = "Email is required";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email format";
    if (empty($message)) $errors[] = "Message is required";
    
    if (empty($errors)) {
        // Save to database
        $query = "INSERT INTO contacts (name, email, phone, subject, message) VALUES (?, ?, ?, ?, ?)";
        $stmt = $db->prepare($query);
        $stmt->execute([$name, $email, $phone, $subject, $message]);
        
        // Send email notification to admin
        $admin_email = "admin@mercypastries.com"; // Get from settings
        $email_subject = "New Contact Form Submission: " . ($subject ?: 'No Subject');
        $email_message = "
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background: linear-gradient(135deg, #6f42c1, #0d6efd); color: white; padding: 20px; text-align: center; }
                    .content { padding: 20px; background: #f8f9fa; }
                    .footer { text-align: center; padding: 20px; color: #666; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h2>New Contact Form Submission</h2>
                    </div>
                    <div class='content'>
                        <p><strong>Name:</strong> {$name}</p>
                        <p><strong>Email:</strong> {$email}</p>
                        <p><strong>Phone:</strong> " . ($phone ?: 'Not provided') . "</p>
                        <p><strong>Subject:</strong> " . ($subject ?: 'Not provided') . "</p>
                        <p><strong>Message:</strong></p>
                        <p>" . nl2br($message) . "</p>
                    </div>
                    <div class='footer'>
                        <p>This message was sent from the Mercy Pastries contact form.</p>
                    </div>
                </div>
            </body>
            </html>
        ";
        
        sendEmail($admin_email, $email_subject, $email_message);
        
        // Send auto-reply to customer
        $customer_subject = "Thank you for contacting Mercy Pastries";
        $customer_message = "
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background: linear-gradient(135deg, #6f42c1, #0d6efd); color: white; padding: 20px; text-align: center; }
                    .content { padding: 20px; background: #f8f9fa; }
                    .signature { margin-top: 30px; padding-top: 20px; border-top: 1px solid #dee2e6; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h2>Thank You for Contacting Us!</h2>
                    </div>
                    <div class='content'>
                        <p>Dear {$name},</p>
                        <p>We have received your message and will get back to you within 24 hours.</p>
                        <p>Here's a copy of your message for your reference:</p>
                        <div style='background: white; padding: 15px; border-radius: 8px; margin: 20px 0;'>
                            <p><strong>Subject:</strong> " . ($subject ?: 'Not provided') . "</p>
                            <p><strong>Message:</strong></p>
                            <p>" . nl2br($message) . "</p>
                        </div>
                        <p>If you need immediate assistance, please call us at +256 706 083004.</p>
                        <div class='signature'>
                            <p>Warm regards,<br>
                            <strong>The Mercy Pastries Team</strong><br>
                            <small>Baked with love in Kampala</small></p>
                        </div>
                    </div>
                </div>
            </body>
            </html>
        ";
        
        sendEmail($email, $customer_subject, $customer_message);
        
        $success = true;
    }
}

// Get site settings for contact info
$settings = [];
$query = "SELECT setting_key, setting_value FROM settings";
$stmt = $db->prepare($query);
$stmt->execute();
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

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
    <title>Contact Us - Mercy Pastries</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/aos@next/dist/aos.css" />
    <link rel="stylesheet" href="css/style.css">
    <style>
        .contact-hero {
            background: linear-gradient(rgba(111,66,193,0.9), rgba(13,110,253,0.9)), 
                        url('https://images.unsplash.com/photo-1550617931-eb3a88e84519?ixlib=rb-4.0.3&auto=format&fit=crop&w=1350&q=80') center/cover;
            color: white;
            padding: 120px 0;
            margin-bottom: 50px;
        }
        
        .info-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            height: 100%;
        }
        
        .info-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
        }
        
        .info-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #6f42c1, #0d6efd);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            margin: 0 auto 20px;
        }
        
        .contact-form {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 15px 40px rgba(0,0,0,0.1);
        }
        
        .form-control, .form-select {
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            padding: 12px 15px;
            transition: all 0.3s ease;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: #6f42c1;
            box-shadow: 0 0 0 0.2rem rgba(111,66,193,0.25);
        }
        
        .map-container {
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 15px 40px rgba(0,0,0,0.1);
        }
        
        .working-hours {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 25px;
        }
        
        .hours-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #dee2e6;
        }
        
        .hours-item:last-child {
            border-bottom: none;
        }
        
        .social-links-large a {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #6f42c1, #0d6efd);
            color: white;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin: 0 5px;
            transition: all 0.3s ease;
        }
        
        .social-links-large a:hover {
            transform: translateY(-5px) scale(1.1);
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <?php include 'includes/navbar.php'; ?>

    <!-- Hero Section -->
    <section class="contact-hero text-center">
        <div class="container">
            <h1 class="display-4 fw-bold mb-4" data-aos="fade-up">Get in Touch</h1>
            <p class="lead fs-4" data-aos="fade-up" data-aos-delay="100">We'd love to hear from you! Let's plan something sweet together.</p>
        </div>
    </section>

    <!-- Contact Info Cards -->
    <section class="py-5">
        <div class="container">
            <div class="row g-4">
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="100">
                    <div class="info-card">
                        <div class="info-icon">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <h4>Visit Us</h4>
                        <p class="mb-0"><?php echo $settings['site_address'] ?? 'Katabi – Entebbe Road, Kampala, Uganda'; ?></p>
                    </div>
                </div>
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="200">
                    <div class="info-card">
                        <div class="info-icon">
                            <i class="fas fa-phone-alt"></i>
                        </div>
                        <h4>Call Us</h4>
                        <p class="mb-0"><?php echo $settings['site_phone'] ?? '+256 706 083004'; ?></p>
                        <small>Mon-Sat, 7am-8pm</small>
                    </div>
                </div>
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="300">
                    <div class="info-card">
                        <div class="info-icon">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <h4>Email Us</h4>
                        <p class="mb-0"><?php echo $settings['site_email'] ?? 'info@mercypastries.com'; ?></p>
                        <small>We reply within 24 hours</small>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Form & Map -->
    <section class="py-5 bg-light">
        <div class="container">
            <div class="row g-5">
                <!-- Contact Form -->
                <div class="col-lg-7" data-aos="fade-right">
                    <div class="contact-form">
                        <h2 class="section-title mb-4">Send us a Message</h2>
                        
                        <?php if ($success): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i>
                            <strong>Thank you!</strong> Your message has been sent successfully. We'll get back to you within 24 hours.
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            <strong>Please fix the following errors:</strong>
                            <ul class="mb-0 mt-2">
                                <?php foreach ($errors as $error): ?>
                                <li><?php echo $error; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <?php endif; ?>
                        
                        <form method="POST" action="" id="contactForm">
                            <div class="row g-4">
                                <div class="col-md-6">
                                    <label class="form-label">Your Name *</label>
                                    <input type="text" name="name" class="form-control" required 
                                           value="<?php echo htmlspecialchars($_POST['name'] ?? ($current_user['full_name'] ?? '')); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Email Address *</label>
                                    <input type="email" name="email" class="form-control" required 
                                           value="<?php echo htmlspecialchars($_POST['email'] ?? ($current_user['email'] ?? '')); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Phone Number</label>
                                    <input type="tel" name="phone" class="form-control" 
                                           value="<?php echo htmlspecialchars($_POST['phone'] ?? ($current_user['phone'] ?? '')); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Subject</label>
                                    <select name="subject" class="form-select">
                                        <option value="">Select a subject</option>
                                        <option value="General Inquiry" <?php echo ($_POST['subject'] ?? '') == 'General Inquiry' ? 'selected' : ''; ?>>General Inquiry</option>
                                        <option value="Order Question" <?php echo ($_POST['subject'] ?? '') == 'Order Question' ? 'selected' : ''; ?>>Order Question</option>
                                        <option value="Custom Cake" <?php echo ($_POST['subject'] ?? '') == 'Custom Cake' ? 'selected' : ''; ?>>Custom Cake Request</option>
                                        <option value="Catering" <?php echo ($_POST['subject'] ?? '') == 'Catering' ? 'selected' : ''; ?>>Catering Inquiry</option>
                                        <option value="Feedback" <?php echo ($_POST['subject'] ?? '') == 'Feedback' ? 'selected' : ''; ?>>Feedback</option>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Your Message *</label>
                                    <textarea name="message" class="form-control" rows="6" required><?php echo htmlspecialchars($_POST['message'] ?? ''); ?></textarea>
                                </div>
                                <div class="col-12">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="newsletter" name="newsletter" checked>
                                        <label class="form-check-label" for="newsletter">
                                            Subscribe to our newsletter for updates and offers
                                        </label>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <button type="submit" class="btn btn-mercy btn-lg px-5">
                                        <i class="fas fa-paper-plane me-2"></i>Send Message
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Map & Additional Info -->
                <div class="col-lg-5" data-aos="fade-left">
                    <div class="map-container mb-4">
                        <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3989.753622471012!2d32.5826!3d0.3136!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zMMKwMTgnNDkuMCJOIDMywrAzNQ!5e0!3m2!1sen!2sug!4v1620000000000!5m2!1sen!2sug" 
                                width="100%" height="300" style="border:0;" allowfullscreen="" loading="lazy"></iframe>
                    </div>
                    
                    <div class="working-hours">
                        <h5 class="mb-4"><i class="fas fa-clock text-primary me-2"></i>Working Hours</h5>
                        <div class="hours-item">
                            <span>Monday - Friday</span>
                            <strong>7:00 AM - 8:00 PM</strong>
                        </div>
                        <div class="hours-item">
                            <span>Saturday</span>
                            <strong>8:00 AM - 8:00 PM</strong>
                        </div>
                        <div class="hours-item">
                            <span>Sunday</span>
                            <strong>8:00 AM - 6:00 PM</strong>
                        </div>
                        <div class="hours-item">
                            <span>Public Holidays</span>
                            <strong>9:00 AM - 5:00 PM</strong>
                        </div>
                    </div>
                    
                    <div class="mt-4 text-center">
                        <h5 class="mb-3">Connect With Us</h5>
                        <div class="social-links-large">
                            <a href="<?php echo $settings['facebook_url'] ?? '#'; ?>" target="_blank"><i class="fab fa-facebook-f"></i></a>
                            <a href="<?php echo $settings['instagram_url'] ?? '#'; ?>" target="_blank"><i class="fab fa-instagram"></i></a>
                            <a href="<?php echo $settings['whatsapp_url'] ?? '#'; ?>" target="_blank"><i class="fab fa-whatsapp"></i></a>
                            <a href="<?php echo $settings['tiktok_url'] ?? '#'; ?>" target="_blank"><i class="fab fa-tiktok"></i></a>
                            <a href="<?php echo $settings['twitter_url'] ?? '#'; ?>" target="_blank"><i class="fab fa-x-twitter"></i></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- FAQ Section -->
    <section class="py-5">
        <div class="container">
            <h2 class="section-title text-center mb-5" data-aos="fade-up">Frequently Asked Questions</h2>
            <div class="row g-4">
                <div class="col-md-6" data-aos="fade-up" data-aos-delay="100">
                    <div class="faq-item">
                        <h5><i class="fas fa-question-circle text-primary me-2"></i>How far in advance should I order a custom cake?</h5>
                        <p class="text-muted">We recommend ordering custom cakes at least 3-5 days in advance. For complex designs or large orders, please allow 1-2 weeks.</p>
                    </div>
                </div>
                <div class="col-md-6" data-aos="fade-up" data-aos-delay="200">
                    <div class="faq-item">
                        <h5><i class="fas fa-question-circle text-primary me-2"></i>Do you offer delivery?</h5>
                        <p class="text-muted">Yes! We offer delivery across Kampala and surrounding areas. Delivery is free for orders above UGX 100,000.</p>
                    </div>
                </div>
                <div class="col-md-6" data-aos="fade-up" data-aos-delay="300">
                    <div class="faq-item">
                        <h5><i class="fas fa-question-circle text-primary me-2"></i>Do you accommodate dietary restrictions?</h5>
                        <p class="text-muted">Yes, we offer gluten-free, vegan, and nut-free options. Please mention your requirements when ordering.</p>
                    </div>
                </div>
                <div class="col-md-6" data-aos="fade-up" data-aos-delay="400">
                    <div class="faq-item">
                        <h5><i class="fas fa-question-circle text-primary me-2"></i>What is your cancellation policy?</h5>
                        <p class="text-muted">Orders can be cancelled up to 48 hours before the pickup/delivery time for a full refund.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@next/dist/aos.js"></script>
    <script src="js/main.js"></script>
    
    <script>
        // Form validation
        document.getElementById('contactForm')?.addEventListener('submit', function(e) {
            const name = document.querySelector('input[name="name"]').value.trim();
            const email = document.querySelector('input[name="email"]').value.trim();
            const message = document.querySelector('textarea[name="message"]').value.trim();
            
            if (!name || !email || !message) {
                e.preventDefault();
                alert('Please fill in all required fields');
            }
        });
    </script>
</body>
</html>