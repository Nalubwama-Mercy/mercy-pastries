<?php
session_start();
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

// Get team members (you can create a team table or use static data)
$team = [
    [
        'name' => 'Mercy Nalubwama',
        'role' => 'Founder & Head Baker',
        'image' => 'images/team/mercy.jpg',
        'bio' => 'Passionate baker with 10+ years of experience in creating delightful pastries.'
    ],
    [
        'name' => 'John Mukasa',
        'role' => 'Pastry Chef',
        'image' => 'images/team/john.jpg',
        'bio' => 'Trained in Paris, specializing in French pastries and croissants.'
    ],
    [
        'name' => 'Sarah Akello',
        'role' => 'Cake Decorator',
        'image' => 'images/team/sarah.jpg',
        'bio' => 'Artist who turns cakes into masterpieces for special occasions.'
    ]
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - Mercy Pastries</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/aos@next/dist/aos.css" />
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <!-- Navbar -->
    <?php include 'includes/navbar.php'; ?>

    <!-- Page Header -->
    <section class="page-header" style="background: linear-gradient(rgba(111,66,193,0.9), rgba(13,110,253,0.9)), url('images/about-bg.jpg') center/cover; padding: 100px 0; color: white;">
        <div class="container text-center">
            <h1 class="display-4 fw-bold">About Mercy Pastries</h1>
            <p class="lead">Our story, our passion, our promise</p>
        </div>
    </section>

    <!-- Our Story -->
    <section class="py-5">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6" data-aos="fade-right">
                    <img src="images/about-story.jpg" alt="Our Story" class="img-fluid rounded-3 shadow">
                </div>
                <div class="col-lg-6" data-aos="fade-left">
                    <h2 class="section-title">Our Story</h2>
                    <p class="lead">Founded in 2018, Mercy Pastries began with a simple dream: to bring joy to every occasion through delicious, beautifully crafted baked goods.</p>
                    <p>What started as a small home kitchen operation has grown into one of Kampala's most beloved bakeries. We've maintained our commitment to quality, using only the finest ingredients and traditional baking techniques combined with creative innovation.</p>
                    <p>Every cake, every pastry, every cookie is made with love and attention to detail. We believe that baking is not just about following recipes – it's about creating moments of happiness that our customers will remember.</p>
                    
                    <div class="row mt-5">
                        <div class="col-4 text-center">
                            <h3 class="text-primary">5+</h3>
                            <p>Years of Excellence</p>
                        </div>
                        <div class="col-4 text-center">
                            <h3 class="text-primary">1000+</h3>
                            <p>Happy Customers</p>
                        </div>
                        <div class="col-4 text-center">
                            <h3 class="text-primary">50+</h3>
                            <p>Unique Recipes</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Our Values -->
    <section class="py-5 bg-light">
        <div class="container">
            <h2 class="section-title text-center mb-5" data-aos="fade-up">Our Values</h2>
            <div class="row g-4">
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="100">
                    <div class="text-center">
                        <div class="value-icon mb-4">
                            <i class="fas fa-heart fa-4x text-primary"></i>
                        </div>
                        <h4>Made with Love</h4>
                        <p>Every item is crafted with passion and care, ensuring the best quality and taste.</p>
                    </div>
                </div>
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="200">
                    <div class="text-center">
                        <div class="value-icon mb-4">
                            <i class="fas fa-leaf fa-4x text-primary"></i>
                        </div>
                        <h4>Quality Ingredients</h4>
                        <p>We use only the finest, freshest ingredients in all our baked goods.</p>
                    </div>
                </div>
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="300">
                    <div class="text-center">
                        <div class="value-icon mb-4">
                            <i class="fas fa-hand-holding-heart fa-4x text-primary"></i>
                        </div>
                        <h4>Customer First</h4>
                        <p>Your satisfaction is our top priority. We go above and beyond for every order.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Our Team -->
    <section class="py-5">
        <div class="container">
            <h2 class="section-title text-center mb-5" data-aos="fade-up">Meet Our Team</h2>
            <div class="row g-4">
                <?php foreach ($team as $index => $member): ?>
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="<?php echo $index * 100; ?>">
                    <div class="card team-card text-center">
                        <img src="<?php echo $member['image']; ?>" class="card-img-top" alt="<?php echo $member['name']; ?>" style="height: 300px; object-fit: cover;">
                        <div class="card-body">
                            <h4><?php echo $member['name']; ?></h4>
                            <p class="text-primary"><?php echo $member['role']; ?></p>
                            <p><?php echo $member['bio']; ?></p>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Call to Action -->
    <section class="py-5" style="background: var(--gradient); color: white;">
        <div class="container text-center">
            <h2 class="mb-4">Ready to taste something amazing?</h2>
            <p class="lead mb-5">Browse our selection of freshly baked goods and place your order today!</p>
            <a href="products.php" class="btn btn-light btn-lg px-5">View Our Products</a>
        </div>
    </section>

    <!-- Footer -->
    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@next/dist/aos.js"></script>
    <script src="js/main.js"></script>
</body>
</html>