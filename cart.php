<?php
session_start();
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

// Get current user
$current_user = null;
if (isset($_SESSION['user_id'])) {
    $query = "SELECT * FROM users WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$_SESSION['user_id']]);
    $current_user = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Handle cart actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update') {
        $cart = json_decode($_POST['cart_data'], true);
        setcookie('cart', json_encode($cart), time() + (86400 * 30), '/');
        header('Location: cart.php');
        exit;
    } elseif ($action === 'clear') {
        setcookie('cart', '', time() - 3600, '/');
        header('Location: cart.php');
        exit;
    }
}

// Get cart from cookie
$cart = [];
if (isset($_COOKIE['cart'])) {
    $cart = json_decode($_COOKIE['cart'], true) ?: [];
}

// Get product details for items in cart
$cart_items = [];
$subtotal = 0;

if (!empty($cart)) {
    $product_ids = array_keys($cart);
    $placeholders = implode(',', array_fill(0, count($product_ids), '?'));
    
    $query = "SELECT * FROM products WHERE id IN ($placeholders)";
    $stmt = $db->prepare($query);
    $stmt->execute($product_ids);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($products as $product) {
        $quantity = $cart[$product['id']];
        $price = $product['sale_price'] ?: $product['price'];
        $total = $price * $quantity;
        $subtotal += $total;
        
        $cart_items[] = [
            'product' => $product,
            'quantity' => $quantity,
            'price' => $price,
            'total' => $total
        ];
    }
}

$delivery_fee = $subtotal >= 100000 ? 0 : 10000;
$total = $subtotal + $delivery_fee;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - Mercy Pastries</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/aos@next/dist/aos.css" />
    <link rel="stylesheet" href="css/style.css">
    <style>
        .cart-header {
            background: linear-gradient(rgba(111, 66, 193, 0.9), rgba(13, 110, 253, 0.9)),
                        url('images/cart-bg.jpg') center/cover;
            color: white;
            padding: 80px 0 60px;
            margin-bottom: 50px;
        }
        
        .cart-table img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 10px;
        }
        
        .cart-quantity {
            width: 100px;
        }
        
        .cart-summary {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 25px;
            position: sticky;
            top: 100px;
        }
        
        .summary-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #dee2e6;
        }
        
        .summary-total {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--purple);
        }
        
        .empty-cart {
            text-align: center;
            padding: 60px 0;
        }
        
        .empty-cart i {
            font-size: 5rem;
            color: #dee2e6;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <?php include 'includes/navbar.php'; ?>

    <!-- Cart Header -->
    <section class="cart-header text-center">
        <div class="container">
            <h1 class="display-4 fw-bold mb-4" data-aos="fade-up">Your Shopping Cart</h1>
            <p class="lead" data-aos="fade-up" data-aos-delay="100">Review your items before checkout</p>
        </div>
    </section>

    <!-- Cart Content -->
    <section class="py-5">
        <div class="container">
            <?php if (empty($cart_items)): ?>
            <div class="empty-cart" data-aos="fade-up">
                <i class="fas fa-shopping-cart"></i>
                <h3>Your cart is empty</h3>
                <p class="text-muted mb-4">Looks like you haven't added any items to your cart yet.</p>
                <a href="products.php" class="btn btn-mercy btn-lg">Start Shopping</a>
            </div>
            <?php else: ?>
            <div class="row g-5">
                <!-- Cart Items -->
                <div class="col-lg-8" data-aos="fade-right">
                    <div class="table-responsive">
                        <table class="table cart-table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Price</th>
                                    <th>Quantity</th>
                                    <th>Total</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($cart_items as $item): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <img src="<?php echo $item['product']['image']; ?>" alt="<?php echo $item['product']['name']; ?>" class="me-3">
                                            <div>
                                                <h6 class="mb-0"><?php echo htmlspecialchars($item['product']['name']); ?></h6>
                                                <small class="text-muted"><?php echo htmlspecialchars($item['product']['category_name'] ?? ''); ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>UGX <?php echo number_format($item['price']); ?></td>
                                    <td>
                                        <input type="number" class="form-control cart-quantity" value="<?php echo $item['quantity']; ?>" min="1" max="<?php echo $item['product']['stock']; ?>" onchange="updateCartItem(<?php echo $item['product']['id']; ?>, this.value)">
                                    </td>
                                    <td>UGX <?php echo number_format($item['total']); ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-danger" onclick="removeFromCart(<?php echo $item['product']['id']; ?>)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="d-flex justify-content-between mt-4">
                        <a href="products.php" class="btn btn-outline-primary">
                            <i class="fas fa-arrow-left me-2"></i>Continue Shopping
                        </a>
                        <button class="btn btn-outline-danger" onclick="clearCart()">
                            <i class="fas fa-trash me-2"></i>Clear Cart
                        </button>
                    </div>
                </div>
                
                <!-- Cart Summary -->
                <div class="col-lg-4" data-aos="fade-left">
                    <div class="cart-summary">
                        <h5 class="mb-4">Order Summary</h5>
                        
                        <div class="summary-item">
                            <span>Subtotal</span>
                            <span>UGX <?php echo number_format($subtotal); ?></span>
                        </div>
                        
                        <div class="summary-item">
                            <span>Delivery Fee</span>
                            <span><?php echo $delivery_fee > 0 ? 'UGX ' . number_format($delivery_fee) : 'Free'; ?></span>
                        </div>
                        
                        <?php if ($subtotal < 100000): ?>
                        <div class="alert alert-info small">
                            <i class="fas fa-info-circle me-2"></i>
                            Add UGX <?php echo number_format(100000 - $subtotal); ?> more to get free delivery
                        </div>
                        <?php endif; ?>
                        
                        <div class="summary-item summary-total">
                            <span>Total</span>
                            <span>UGX <?php echo number_format($total); ?></span>
                        </div>
                        
                        <?php if ($current_user): ?>
                        <a href="checkout.php" class="btn btn-mercy w-100 btn-lg mt-4">
                            Proceed to Checkout
                        </a>
                        <?php else: ?>
                        <div class="alert alert-warning mt-4">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Please <a href="login.php?redirect=cart.php" class="alert-link">login</a> to checkout
                        </div>
                        <?php endif; ?>
                        
                        <!-- Payment Methods -->
                        <div class="text-center mt-4">
                            <p class="small text-muted mb-2">We Accept</p>
                            <div class="d-flex justify-content-center gap-3">
                                <i class="fab fa-cc-visa fa-2x text-muted"></i>
                                <i class="fab fa-cc-mastercard fa-2x text-muted"></i>
                                <i class="fab fa-cc-paypal fa-2x text-muted"></i>
                                <i class="fas fa-mobile-alt fa-2x text-muted"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Related Products -->
    <?php if (!empty($cart_items)): ?>
    <section class="py-5 bg-light">
        <div class="container">
            <h2 class="section-title text-center mb-5" data-aos="fade-up">You Might Also Like</h2>
            <div class="row g-4">
                <?php
                // Get random products for recommendations
                $query = "SELECT * FROM products WHERE is_active = 1 ORDER BY RAND() LIMIT 4";
                $stmt = $db->prepare($query);
                $stmt->execute();
                $recommendations = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                foreach ($recommendations as $index => $rec_product):
                ?>
                <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="<?php echo $index * 100; ?>">
                    <div class="product-card">
                        <div class="product-image">
                            <img src="<?php echo $rec_product['image']; ?>" alt="<?php echo $rec_product['name']; ?>">
                            <div class="product-overlay">
                                <button class="btn btn-light btn-sm add-to-cart" data-id="<?php echo $rec_product['id']; ?>">
                                    <i class="fas fa-cart-plus"></i> Add
                                </button>
                                <a href="product.php?id=<?php echo $rec_product['id']; ?>" class="btn btn-light btn-sm">
                                    <i class="fas fa-eye"></i> View
                                </a>
                            </div>
                        </div>
                        <div class="product-info">
                            <h5><?php echo htmlspecialchars($rec_product['name']); ?></h5>
                            <div class="product-price">
                                UGX <?php echo number_format($rec_product['price']); ?>
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
    
    <script>
        function updateCartItem(productId, quantity) {
            // Get current cart from cookie
            let cart = JSON.parse(getCookie('cart') || '{}');
            
            if (quantity > 0) {
                cart[productId] = parseInt(quantity);
            }
            
            // Save to cookie
            document.cookie = 'cart=' + JSON.stringify(cart) + ';path=/;max-age=' + (30 * 24 * 60 * 60);
            
            // Reload page to update totals
            location.reload();
        }
        
        function removeFromCart(productId) {
            let cart = JSON.parse(getCookie('cart') || '{}');
            delete cart[productId];
            document.cookie = 'cart=' + JSON.stringify(cart) + ';path=/;max-age=' + (30 * 24 * 60 * 60);
            location.reload();
        }
        
        function clearCart() {
            document.cookie = 'cart=;path=/;max-age=0';
            location.reload();
        }
        
        function getCookie(name) {
            const value = `; ${document.cookie}`;
            const parts = value.split(`; ${name}=`);
            if (parts.length === 2) return parts.pop().split(';').shift();
        }
    </script>
</body>
</html>