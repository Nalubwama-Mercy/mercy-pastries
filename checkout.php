<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_after_login'] = 'checkout.php';
    header('Location: login.php');
    exit;
}

$database = new Database();
$db = $database->getConnection();

// Get user data
$query = "SELECT * FROM users WHERE id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Get cart from cookie
$cart = [];
$cart_items = [];
$subtotal = 0;

if (isset($_COOKIE['cart'])) {
    $cart = json_decode($_COOKIE['cart'], true) ?: [];
    
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
}

if (empty($cart_items)) {
    header('Location: cart.php');
    exit;
}

$delivery_fee = $subtotal >= 100000 ? 0 : 10000;
$total = $subtotal + $delivery_fee;

// Process order
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payment_method = $_POST['payment_method'];
    $shipping_address = $_POST['shipping_address'];
    $phone = $_POST['phone'];
    $notes = $_POST['notes'] ?? '';
    
    // Generate order number
    $order_number = 'ORD-' . date('Ymd') . '-' . strtoupper(uniqid());
    
    // Start transaction
    $db->beginTransaction();
    
    try {
        // Insert order
        $query = "INSERT INTO orders (user_id, order_number, total_amount, status, payment_method, shipping_address, phone, notes) 
                  VALUES (?, ?, ?, 'pending', ?, ?, ?, ?)";
        $stmt = $db->prepare($query);
        $stmt->execute([$_SESSION['user_id'], $order_number, $total, $payment_method, $shipping_address, $phone, $notes]);
        $order_id = $db->lastInsertId();
        
        // Insert order items
        foreach ($cart_items as $item) {
            $query = "INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)";
            $stmt = $db->prepare($query);
            $stmt->execute([$order_id, $item['product']['id'], $item['quantity'], $item['price']]);
        }
        
        $db->commit();
        
        // Clear cart
        setcookie('cart', '', time() - 3600, '/');
        
        // Redirect to success page
        header('Location: order-success.php?order=' . $order_number);
        exit;
        
    } catch (Exception $e) {
        $db->rollBack();
        $error = "Order failed. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Mercy Pastries</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <section class="py-5">
        <div class="container">
            <h1 class="section-title mb-5">Checkout</h1>
            
            <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <div class="row">
                <div class="col-lg-8">
                    <form method="POST" id="checkoutForm">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5>Shipping Information</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label">Full Name</label>
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['full_name']); ?>" readonly>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Email</label>
                                    <input type="email" class="form-control" value="<?php echo $user['email']; ?>" readonly>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Phone Number *</label>
                                    <input type="tel" name="phone" class="form-control" required value="<?php echo htmlspecialchars($user['phone']); ?>">
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Shipping Address *</label>
                                    <textarea name="shipping_address" class="form-control" rows="3" required></textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Order Notes (Optional)</label>
                                    <textarea name="notes" class="form-control" rows="2" placeholder="Special instructions, delivery time, etc."></textarea>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5>Payment Method</h5>
                            </div>
                            <div class="card-body">
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="radio" name="payment_method" id="cash" value="cash" checked>
                                    <label class="form-check-label" for="cash">
                                        <i class="fas fa-money-bill-wave text-success me-2"></i>
                                        Cash on Delivery
                                    </label>
                                </div>
                                
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="radio" name="payment_method" id="mobile" value="mobile_money">
                                    <label class="form-check-label" for="mobile">
                                        <i class="fas fa-mobile-alt text-primary me-2"></i>
                                        Mobile Money
                                    </label>
                                </div>
                                
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="radio" name="payment_method" id="card" value="card">
                                    <label class="form-check-label" for="card">
                                        <i class="far fa-credit-card text-info me-2"></i>
                                        Card Payment
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-mercy btn-lg w-100">Place Order</button>
                    </form>
                </div>
                
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header">
                            <h5>Order Summary</h5>
                        </div>
                        <div class="card-body">
                            <?php foreach ($cart_items as $item): ?>
                            <div class="d-flex justify-content-between mb-2">
                                <span><?php echo $item['product']['name']; ?> x<?php echo $item['quantity']; ?></span>
                                <span>UGX <?php echo number_format($item['total']); ?></span>
                            </div>
                            <?php endforeach; ?>
                            
                            <hr>
                            
                            <div class="d-flex justify-content-between mb-2">
                                <span>Subtotal</span>
                                <span>UGX <?php echo number_format($subtotal); ?></span>
                            </div>
                            
                            <div class="d-flex justify-content-between mb-2">
                                <span>Delivery Fee</span>
                                <span><?php echo $delivery_fee > 0 ? 'UGX ' . number_format($delivery_fee) : 'Free'; ?></span>
                            </div>
                            
                            <hr>
                            
                            <div class="d-flex justify-content-between fw-bold">
                                <span>Total</span>
                                <span class="text-primary">UGX <?php echo number_format($total); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>