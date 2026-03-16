<?php
// contact.php - Mercy Pastries Contact
session_start();

$success = false;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = trim($_POST['name']    ?? '');
    $email   = trim($_POST['email']   ?? '');
    $phone   = trim($_POST['phone']   ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if (empty($name))     $errors[] = "Name is required.";
    if (empty($email))    $errors[] = "Email is required.";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email format.";
    if (empty($message))  $errors[] = "Message cannot be empty.";

    if (empty($errors)) {
        // Here you would normally send email or save to DB
        // For now we just show success
        $success = true;
        // Optional: mail("your@email.com", $subject, $message, "From: $email");
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Contact Us – Mercy Pastries</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <style>
    :root {
      --purple: #6f42c1;
      --purple-dark: #5a32a3;
      --blue: #0d6efd;
      --blue-dark: #0b5ed7;
    }
    body {
      font-family: 'Segoe UI', system-ui, sans-serif;
      background: linear-gradient(135deg, #f5f0ff 0%, #e8f0ff 100%);
      color: #333;
      min-height: 100vh;
    }
    .navbar {
      background: linear-gradient(90deg, var(--purple) 0%, var(--blue) 100%) !important;
    }
    .navbar-brand {
      font-weight: 800;
      letter-spacing: 1.5px;
      color: white !important;
    }
    .contact-hero {
      background: linear-gradient(rgba(95, 60, 170, 0.75), rgba(13, 110, 253, 0.75)),
                  url('https://images.unsplash.com/photo-1550617931-eb3a88e84519?ixlib=rb-4.0.3&auto=format&fit=crop&w=1350&q=80') center/cover;
      color: white;
      padding: 110px 0 90px;
      text-shadow: 1px 1px 5px rgba(0,0,0,0.6);
    }
    .contact-card {
      border-radius: 16px;
      box-shadow: 0 10px 35px rgba(0,0,0,0.12);
      overflow: hidden;
    }
    .info-item {
      font-size: 1.1rem;
      margin-bottom: 1.5rem;
    }
    .btn-mercy {
      background: linear-gradient(to right, var(--purple), var(--blue));
      border: none;
      color: white;
      font-weight: 600;
      transition: all 0.25s;
    }
    .btn-mercy:hover {
      background: linear-gradient(to right, var(--purple-dark), var(--blue-dark));
      transform: scale(1.04);
    }
    .section-title {
      position: relative;
      display: inline-block;
      padding-bottom: 10px;
      margin-bottom: 2rem;
    }
    .section-title::after {
      content: '';
      position: absolute;
      bottom: 0; left: 50%; transform: translateX(-50%);
      width: 90px; height: 4px;
      background: linear-gradient(to right, var(--purple), var(--blue));
      border-radius: 2px;
    }
    footer {
      background: linear-gradient(90deg, var(--purple) 0%, var(--blue) 100%);
      color: white;
    }
  </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark shadow">
  <div class="container">
    <a class="navbar-brand" href="index.php">Mercy Pastries</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ms-auto align-items-center">
        <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
        <li class="nav-item"><a class="nav-link" href="services.php">Services</a></li>
        <li class="nav-item"><a class="nav-link active" href="contact us.php">Contact</a></li>
        <?php if (isset($_SESSION['user_id'])): ?>
          <li class="nav-item"><a class="nav-link" href="index.php?view=dashboard">Profile</a></li>
          <li class="nav-item">
            <form method="post" action="index.php" class="d-inline">
              <input type="hidden" name="action" value="logout">
              <button type="submit" class="btn btn-sm btn-outline-light ms-2">Logout</button>
            </form>
          </li>
        <?php else: ?>
          <li class="nav-item"><a class="nav-link" href="index.php?view=login">Login</a></li>
          <li class="nav-item"><a class="nav-link btn btn-mercy btn-sm ms-2 px-4" href="index.php?view=signup">Sign Up</a></li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>

<!-- Hero -->
<section class="contact-hero text-center">
  <div class="container">
    <h1 class="display-4 fw-bold mb-4">Get in Touch</h1>
    <p class="lead fs-4">We'd love to hear from you! Let's plan something sweet together.</p>
  </div>
</section>

<!-- Contact Content -->
<section class="py-5">
  <div class="container">
    <div class="row g-5">

      <!-- Contact Info -->
      <div class="col-lg-5">
        <div class="contact-card bg-white p-4 p-md-5 h-100">
          <h3 class="mb-4 section-title">Reach Us</h3>

          <div class="info-item d-flex">
            <i class="fas fa-map-marker-alt fa-2x text-primary me-4 mt-1"></i>
            <div>
              <strong>Katabi – Entebbe Road</strong><br>
              Next to Lake Victoria, Kampala, Uganda
            </div>
          </div>

          <div class="info-item d-flex">
            <i class="fas fa-phone-volume fa-2x text-primary me-4 mt-1"></i>
            <div>
              <strong>Call / WhatsApp</strong><br>
              0706083004<br>
              
            </div>
          </div>

          <div class="info-item d-flex">
            <i class="fas fa-envelope fa-2x text-primary me-4 mt-1"></i>
            <div>
              <strong>Email</strong><br>
              my.nalubwama@unik.ac.ug <br>
              orders@mercypastries.ug
            </div>
          </div>

          <div class="info-item d-flex">
            <i class="fas fa-clock fa-2x text-primary me-4 mt-1"></i>
            <div>
              <strong>Opening Hours</strong><br>
              Monday – Saturday: 7:00 AM – 8:00 PM<br>
              Sunday: 8:00 AM – 6:00 PM
            </div>
          </div>

          <hr class="my-4">

          <h5 class="mb-3">Follow Us</h5>
          <div class="d-flex gap-3 fs-4">
            <a href="#" class="text-primary"><i class="fab fa-facebook-f"></i></a>
            <a href="#" class="text-primary"><i class="fab fa-instagram"></i></a>
            <a href="#" class="text-primary"><i class="fab fa-whatsapp"></i></a>
            <a href="#" class="text-primary"><i class="fab fa-tiktok"></i></a>
          </div>
        </div>
      </div>

      <!-- Contact Form -->
      <div class="col-lg-7">
        <div class="contact-card bg-white p-4 p-md-5">
          <h3 class="mb-4 section-title">Send us a Message</h3>

          <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
              <strong>Thank you!</strong> Your message has been sent. We'll get back to you soon.
              <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
          <?php endif; ?>

          <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
              <ul class="mb-0">
                <?php foreach ($errors as $err): ?>
                  <li><?= htmlspecialchars($err) ?></li>
                <?php endforeach; ?>
              </ul>
            </div>
          <?php endif; ?>

          <form method="post" class="needs-validation" novalidate>
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label">Your Name</label>
                <input type="text" name="name" class="form-control form-control-lg" required value="<?= htmlspecialchars($name ?? '') ?>">
              </div>
              <div class="col-md-6">
                <label class="form-label">Email Address</label>
                <input type="email" name="email" class="form-control form-control-lg" required value="<?= htmlspecialchars($email ?? '') ?>">
              </div>
              <div class="col-12">
                <label class="form-label">Phone Number (WhatsApp preferred)</label>
                <input type="tel" name="phone" class="form-control form-control-lg" value="<?= htmlspecialchars($phone ?? '') ?>">
              </div>
              <div class="col-12">
                <label class="form-label">Subject</label>
                <input type="text" name="subject" class="form-control form-control-lg" value="<?= htmlspecialchars($subject ?? '') ?>">
              </div>
              <div class="col-12">
                <label class="form-label">Your Message</label>
                <textarea name="message" rows="6" class="form-control form-control-lg" required><?= htmlspecialchars($message ?? '') ?></textarea>
              </div>
              <div class="col-12 text-end">
                <button type="submit" class="btn btn-mercy btn-lg px-5 py-3 mt-2">
                  <i class="fas fa-paper-plane me-2"></i> Send Message
                </button>
              </div>
            </div>
          </form>
        </div>
      </div>

    </div>
  </div>
</section>

<!-- Footer -->
<footer class="py-5 mt-5 text-center">
  <div class="container">
    <p class="mb-2">© <?= date('Y') ?> Mercy Pastries – Baked with love in Kampala</p>
    <small>Fresh cakes • Pastries • Custom orders • Delivery available • Baking classes</small>
  </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>