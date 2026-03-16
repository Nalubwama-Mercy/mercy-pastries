<?php
session_start();
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

// Get wishlist from cookie/localStorage (passed via AJAX or URL)
$wishlist_ids = isset($_COOKIE['wishlist']) ? json_decode($_COOKIE['wishlist'], true) : [];

$products = [];
if (!empty($wishlist_ids)) {
    $placeholders = implode(',', array_fill(0, count($wishlist_ids), '?'));
    $query = "SELECT * FROM products WHERE id IN ($placeholders) AND is_active = 1";
    $stmt = $db->prepare($query);
    $stmt->execute($wishlist_ids);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Wishlist - Mercy Pastries</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <section class="py-5">
        <div class="container">
            <h1 class="section-title mb-5">My Wishlist</h1>
            
            <?php if (empty($products)): ?>
            <div class="text-center py-5">
                <i class="far fa-heart fa-4x text-muted mb-3"></i>
                <h3>Your wishlist is empty</h3>
                <p class="text-muted">Save your favorite items here!</p>
                <a href="products.php" class="btn btn-mercy">Browse Products</a>
            </div>
            <?php else: ?>
            <div class="row g-4">
                <?php foreach ($products as $product): ?>
                <div class="col-md-4 col-lg-3">
                    <div class="product-card">
                        <div class="product-image">
                            <img src="<?php echo $product['image']; ?>" alt="<?php echo $product['name']; ?>">
                            <div class="product-overlay">
                                <button class="btn btn-light btn-sm add-to-cart" data-id="<?php echo $product['id']; ?>">
                                    <i class="fas fa-cart-plus"></i>
                                </button>
                                <a href="product.php?id=<?php echo $product['id']; ?>" class="btn btn-light btn-sm">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <button class="btn btn-light btn-sm remove-wishlist" data-id="<?php echo $product['id']; ?>">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                        <div class="product-info">
                            <h5><?php echo $product['name']; ?></h5>
                            <div class="product-price">
                                UGX <?php echo number_format($product['price']); ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </section>
    
    <?php include 'includes/footer.php'; ?>
    
    <script>
        document.querySelectorAll('.remove-wishlist').forEach(btn => {
            btn.addEventListener('click', function() {
                const productId = this.dataset.id;
                removeFromWishlist(productId);
                this.closest('.col-md-4').remove();
            });
        });
    </script>
</body>
</html>