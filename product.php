<?php
session_start();
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get product details
$query = "SELECT p.*, c.name as category_name 
          FROM products p 
          LEFT JOIN categories c ON p.category_id = c.id 
          WHERE p.id = ? AND p.is_active = 1";
$stmt = $db->prepare($query);
$stmt->execute([$product_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    header('Location: products.php');
    exit;
}

// Get related products
$query = "SELECT * FROM products 
          WHERE category_id = ? AND id != ? AND is_active = 1 
          ORDER BY RAND() LIMIT 4";
$stmt = $db->prepare($query);
$stmt->execute([$product['category_id'], $product_id]);
$related_products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Parse gallery images
$gallery = $product['gallery'] ? explode(',', $product['gallery']) : [];

// Get current user
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
    <title><?php echo htmlspecialchars($product['name']); ?> - Mercy Pastries</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/aos@next/dist/aos.css" />
    <link rel="stylesheet" href="css/style.css">
    <style>
        .product-detail-image {
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .thumbnail-images {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        
        .thumbnail-img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            opacity: 0.6;
        }
        
        .thumbnail-img:hover,
        .thumbnail-img.active {
            opacity: 1;
            transform: scale(1.05);
            box-shadow: 0 5px 15px rgba(111, 66, 193, 0.3);
        }
        
        .quantity-selector {
            display: flex;
            align-items: center;
            gap: 15px;
            margin: 20px 0;
        }
        
        .quantity-btn {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: 1px solid #ddd;
            background: white;
            font-size: 1.2rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .quantity-btn:hover {
            background: var(--gradient);
            color: white;
            border-color: transparent;
        }
        
        .quantity-input {
            width: 60px;
            text-align: center;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 8px;
        }
        
        .product-meta {
            border-top: 1px solid #eee;
            border-bottom: 1px solid #eee;
            padding: 20px 0;
            margin: 20px 0;
        }
        
        .meta-item {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
        }
        
        .meta-item i {
            width: 25px;
            color: var(--purple);
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <?php include 'includes/navbar.php'; ?>

    <!-- Product Detail -->
    <section class="py-5">
        <div class="container">
            <nav aria-label="breadcrumb" data-aos="fade-up">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="products.php">Products</a></li>
                    <li class="breadcrumb-item"><a href="products.php?category=<?php echo $product['category_id']; ?>">
                        <?php echo htmlspecialchars($product['category_name']); ?>
                    </a></li>
                    <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($product['name']); ?></li>
                </ol>
            </nav>
            
            <div class="row g-5">
                <!-- Product Images -->
                <div class="col-lg-6" data-aos="fade-right">
                    <div class="product-detail-image">
                        <img src="<?php echo $product['image']; ?>" alt="<?php echo $product['name']; ?>" class="img-fluid" id="main-product-image">
                    </div>
                    
                    <?php if (!empty($gallery)): ?>
                    <div class="thumbnail-images">
                        <img src="<?php echo $product['image']; ?>" alt="Main" class="thumbnail-img active" onclick="changeImage(this, '<?php echo $product['image']; ?>')">
                        <?php foreach ($gallery as $image): ?>
                        <img src="<?php echo trim($image); ?>" alt="Gallery" class="thumbnail-img" onclick="changeImage(this, '<?php echo trim($image); ?>')">
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Product Info -->
                <div class="col-lg-6" data-aos="fade-left">
                    <span class="product-category"><?php echo htmlspecialchars($product['category_name']); ?></span>
                    <h1 class="display-5 fw-bold mb-3"><?php echo htmlspecialchars($product['name']); ?></h1>
                    
                    <div class="product-price mb-4">
                        <?php if ($product['sale_price']): ?>
                            <span class="old-price h3">UGX <?php echo number_format($product['price']); ?></span>
                            <span class="new-price h2 text-primary">UGX <?php echo number_format($product['sale_price']); ?></span>
                        <?php else: ?>
                            <span class="h2 text-primary">UGX <?php echo number_format($product['price']); ?></span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="product-description mb-4">
                        <h5>Description</h5>
                        <p><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
                    </div>
                    
                    <div class="product-meta">
                        <div class="meta-item">
                            <i class="fas fa-check-circle text-success"></i>
                            <span><?php echo $product['stock'] > 0 ? 'In Stock' : 'Out of Stock'; ?></span>
                        </div>
                        <div class="meta-item">
                            <i class="fas fa-truck"></i>
                            <span>Free delivery on orders above UGX 100,000</span>
                        </div>
                        <div class="meta-item">
                            <i class="fas fa-shield-alt"></i>
                            <span>Freshness guaranteed</span>
                        </div>
                    </div>
                    
                    <!-- Quantity Selector -->
                    <div class="quantity-selector">
                        <span class="fw-bold">Quantity:</span>
                        <button class="quantity-btn" onclick="updateQuantity(document.getElementById('quantity'), -1)">-</button>
                        <input type="number" id="quantity" class="quantity-input" value="1" min="1" max="<?php echo $product['stock']; ?>">
                        <button class="quantity-btn" onclick="updateQuantity(document.getElementById('quantity'), 1)">+</button>
                    </div>
                    
                    <!-- Action Buttons -->
                    <div class="d-flex gap-3 mb-4">
                        <button class="btn btn-mercy btn-lg flex-grow-1" onclick="addToCart(<?php echo $product['id']; ?>, document.getElementById('quantity').value)">
                            <i class="fas fa-shopping-cart me-2"></i>Add to Cart
                        </button>
                        <button class="btn btn-outline-primary btn-lg" onclick="addToWishlist(<?php echo $product['id']; ?>)">
                            <i class="far fa-heart"></i>
                        </button>
                    </div>
                    
                    <!-- Share -->
                    <div class="d-flex align-items-center gap-3">
                        <span class="fw-bold">Share:</span>
                        <a href="#" class="text-primary"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="text-primary"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="text-primary"><i class="fab fa-pinterest"></i></a>
                        <a href="#" class="text-primary"><i class="fab fa-whatsapp"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Related Products -->
    <?php if (!empty($related_products)): ?>
    <section class="py-5 bg-light">
        <div class="container">
            <h2 class="section-title text-center mb-5" data-aos="fade-up">You May Also Like</h2>
            <div class="row g-4">
                <?php foreach ($related_products as $index => $related): ?>
                <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="<?php echo $index * 100; ?>">
                    <div class="product-card">
                        <div class="product-image">
                            <img src="<?php echo $related['image']; ?>" alt="<?php echo $related['name']; ?>">
                            <div class="product-overlay">
                                <a href="product.php?id=<?php echo $related['id']; ?>" class="btn btn-light btn-sm">
                                    <i class="fas fa-eye"></i> View
                                </a>
                            </div>
                        </div>
                        <div class="product-info">
                            <h5><?php echo htmlspecialchars($related['name']); ?></h5>
                            <div class="product-price">
                                UGX <?php echo number_format($related['price']); ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Footer -->
    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@next/dist/aos.js"></script>
    <script src="js/main.js"></script>
</body>
</html>