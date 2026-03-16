<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['order'])) {
    header('Location: index.php');
    exit;
}

$order_number = $_GET['order'];

$database = new Database();
$db = $database->getConnection();

// Get order details
$query = "SELECT o.*, u.full_name, u.email 
          FROM orders o 
          LEFT JOIN users u ON o.user_id = u.id 
          WHERE o.order_number = ? AND o.user_id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$order_number, $_SESSION['user_id']]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    header('Location: index.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Success - Mercy Pastries</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <section class="py-5">
        <div class="container">
            <div class="text-center mb-5">
                <i class="fas fa-check-circle text-success fa-5x mb-3"></i>
                <h1 class="display-4">Thank You for Your Order!</h1>
                <p class="lead">Your order has been placed successfully.</p>
            </div>
            
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">Order Details</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-sm-6">
                                    <p><strong>Order Number:</strong> #<?php echo $order['order_number']; ?></p>
                                    <p><strong>Date:</strong> <?php echo date('F j, Y, g:i a', strtotime($order['created_at'])); ?></p>
                                </div>
                                <div class="col-sm-6">
                                    <p><strong>Total Amount:</strong> UGX <?php echo number_format($order['total_amount']); ?></p>
                                    <p><strong>Payment Method:</strong> <?php echo ucfirst(str_replace('_', ' ', $order['payment_method'])); ?></p>
                                </div>
                            </div>
                            
                            <hr>
                            
                            <h6>What's Next?</h6>
                            <ol class="mb-4">
                                <li>You will receive a confirmation email shortly.</li>
                                <li>We'll process your order and contact you within 24 hours.</li>
                                <li>Your order will be delivered to your specified address.</li>
                            </ol>
                            
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                For any questions about your order, please contact us at +256 706 083004 or email orders@mercypastries.com
                            </div>
                        </div>
                        <div class="card-footer text-center">
                            <a href="products.php" class="btn btn-mercy me-2">Continue Shopping</a>
                            <a href="orders.php" class="btn btn-outline-primary">View My Orders</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>