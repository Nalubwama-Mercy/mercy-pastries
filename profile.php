<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_after_login'] = 'profile.php';
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

// Update profile
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_profile') {
        $full_name = $_POST['full_name'];
        $phone = $_POST['phone'];
        $bio = $_POST['bio'];
        
        // Handle profile picture upload
        $profile_picture = $user['profile_picture'];
        
        if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === 0) {
            $upload = uploadImage($_FILES['profile_picture'], 'profiles');
            if ($upload['success']) {
                $profile_picture = 'uploads/profiles/' . $upload['filename'];
                
                // Delete old picture
                if ($user['profile_picture'] && file_exists($user['profile_picture'])) {
                    unlink($user['profile_picture']);
                }
            }
        }
        
        $query = "UPDATE users SET full_name = ?, phone = ?, bio = ?, profile_picture = ? WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$full_name, $phone, $bio, $profile_picture, $_SESSION['user_id']]);
        
        $_SESSION['full_name'] = $full_name;
        
        header('Location: profile.php?msg=updated');
        exit;
    }
    
    if ($action === 'change_password') {
        $current = $_POST['current_password'];
        $new = $_POST['new_password'];
        $confirm = $_POST['confirm_password'];
        
        // Verify current password
        if (!password_verify($current, $user['password'])) {
            $error = 'Current password is incorrect';
        } elseif (strlen($new) < 6) {
            $error = 'New password must be at least 6 characters';
        } elseif ($new !== $confirm) {
            $error = 'New passwords do not match';
        } else {
            $hash = password_hash($new, PASSWORD_DEFAULT);
            
            $query = "UPDATE users SET password = ? WHERE id = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$hash, $_SESSION['user_id']]);
            
            header('Location: profile.php?msg=password_updated');
            exit;
        }
    }
}

// Get user orders
$query = "SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT 5";
$stmt = $db->prepare($query);
$stmt->execute([$_SESSION['user_id']]);
$recent_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Mercy Pastries</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .profile-header {
            background: var(--gradient);
            color: white;
            padding: 80px 0 40px;
            margin-bottom: 50px;
        }
        
        .profile-avatar {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            border: 5px solid white;
            object-fit: cover;
            margin-bottom: 20px;
        }
        
        .profile-tabs {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
        }
        
        .order-card {
            border: 1px solid #eee;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
            transition: all 0.3s ease;
        }
        
        .order-card:hover {
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <?php include 'includes/navbar.php'; ?>

    <!-- Profile Header -->
    <section class="profile-header text-center">
        <div class="container">
            <img src="<?php echo $user['profile_picture'] ?? 'images/default-avatar.png'; ?>" alt="Profile" class="profile-avatar">
            <h2><?php echo htmlspecialchars($user['full_name']); ?></h2>
            <p class="lead">Member since <?php echo date('F Y', strtotime($user['created_at'])); ?></p>
        </div>
    </section>

    <!-- Profile Content -->
    <section class="py-5">
        <div class="container">
            <?php if (isset($_GET['msg'])): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <?php 
                    if ($_GET['msg'] == 'updated') echo 'Profile updated successfully!';
                    if ($_GET['msg'] == 'password_updated') echo 'Password changed successfully!';
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <div class="profile-tabs">
                <ul class="nav nav-tabs" id="profileTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="profile-tab" data-bs-toggle="tab" data-bs-target="#profile" type="button">Profile Information</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="orders-tab" data-bs-toggle="tab" data-bs-target="#orders" type="button">My Orders</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="password-tab" data-bs-toggle="tab" data-bs-target="#password" type="button">Change Password</button>
                    </li>
                </ul>
                
                <div class="tab-content p-4" id="profileTabContent">
                    <!-- Profile Tab -->
                    <div class="tab-pane fade show active" id="profile" role="tabpanel">
                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="update_profile">
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Full Name</label>
                                    <input type="text" name="full_name" class="form-control" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Email</label>
                                    <input type="email" class="form-control" value="<?php echo $user['email']; ?>" readonly disabled>
                                    <small class="text-muted">Email cannot be changed</small>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Phone Number</label>
                                    <input type="tel" name="phone" class="form-control" value="<?php echo htmlspecialchars($user['phone']); ?>">
                                </div>
                                <div class="col-12 mb-3">
                                    <label class="form-label">Bio</label>
                                    <textarea name="bio" class="form-control" rows="4"><?php echo htmlspecialchars($user['bio']); ?></textarea>
                                </div>
                                <div class="col-12 mb-3">
                                    <label class="form-label">Profile Picture</label>
                                    <input type="file" name="profile_picture" class="form-control" accept="image/*">
                                    <small class="text-muted">Leave empty to keep current picture</small>
                                </div>
                                <div class="col-12">
                                    <button type="submit" class="btn btn-mercy">Update Profile</button>
                                </div>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Orders Tab -->
                    <div class="tab-pane fade" id="orders" role="tabpanel">
                        <h5 class="mb-4">Recent Orders</h5>
                        
                        <?php if (empty($recent_orders)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-shopping-bag fa-4x text-muted mb-3"></i>
                                <p>No orders yet</p>
                                <a href="products.php" class="btn btn-mercy">Start Shopping</a>
                            </div>
                        <?php else: ?>
                            <?php foreach ($recent_orders as $order): ?>
                            <div class="order-card">
                                <div class="row align-items-center">
                                    <div class="col-md-3">
                                        <strong>Order #<?php echo $order['order_number']; ?></strong>
                                    </div>
                                    <div class="col-md-2">
                                        <?php echo date('M d, Y', strtotime($order['created_at'])); ?>
                                    </div>
                                    <div class="col-md-2">
                                        UGX <?php echo number_format($order['total_amount']); ?>
                                    </div>
                                    <div class="col-md-2">
                                        <?php echo getOrderStatusBadge($order['status']); ?>
                                    </div>
                                    <div class="col-md-3 text-end">
                                        <a href="order-details.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-outline-primary">View Details</a>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            
                            <div class="text-center mt-4">
                                <a href="orders.php" class="btn btn-outline-primary">View All Orders</a>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Password Tab -->
                    <div class="tab-pane fade" id="password" role="tabpanel">
                        <form method="POST">
                            <input type="hidden" name="action" value="change_password">
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Current Password</label>
                                    <input type="password" name="current_password" class="form-control" required>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">New Password</label>
                                    <input type="password" name="new_password" class="form-control" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Confirm New Password</label>
                                    <input type="password" name="confirm_password" class="form-control" required>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-mercy">Change Password</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/main.js"></script>
</body>
</html>