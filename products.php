<?php
session_start();
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

// Get categories for filter
$query = "SELECT * FROM categories ORDER BY name";
$stmt = $db->prepare($query);
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get products with filters
$category_filter = isset($_GET['category']) ? $_GET['category'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 12;
$offset = ($page - 1) * $limit;

$query = "SELECT p.*, c.name as category_name 
          FROM products p 
          LEFT JOIN categories c ON p.category_id = c.id 
          WHERE p.is_active = 1";

$count_query = "SELECT COUNT(*) as total FROM products p WHERE p.is_active = 1";

$params = [];

if ($category_filter) {
    $query .= " AND p.category_id = :category";
    $count_query .= " AND p.category_id = :category";
    $params[':category'] = $category_filter;
}

if ($search) {
    $query .= " AND (p.name LIKE :search OR p.description LIKE :search)";
    $count_query .= " AND (p.name LIKE :search OR p.description LIKE :search)";
    $params[':search'] = "%$search%";
}

$query .= " ORDER BY p.created_at DESC LIMIT :limit OFFSET :offset";

$stmt = $db->prepare($count_query);
foreach ($params as $key => $value) {
    if ($key !== ':limit' && $key !== ':offset') {
        $stmt->bindValue($key, $value);
    }
}
$stmt->execute();
$total_products = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_products / $limit);

$stmt = $db->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
    <title>Our Products - Mercy Pastries</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/aos@next/dist/aos.css" />
    <link rel="stylesheet" href="css/style.css">
    <style>
        .products-header {
            background: linear-gradient(rgba(111, 66, 193, 0.9), rgba(13, 110, 253, 0.9)),
                        url('images/products-bg.jpg') center/cover;
            color: white;
            padding: 100px 0 80px;
            margin-bottom: 50px;
        }
        
        .filter-sidebar {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            position: sticky;
            top: 100px;
        }
        
        .pagination .page-link {
            color: var(--purple);
            border: none;
            margin: 0 5px;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        
        .pagination .page-link:hover,
        .pagination .active .page-link {
            background: var(--gradient);
            color: white;
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <!-- Navbar (same as index.php) -->
    <?php include 'includes/navbar.php'; ?>

    <!-- Products Header -->
    <section class="products-header text-center">
        <div class="container">
            <h1 class="display-4 fw-bold mb-4" data-aos="fade-up">Our Delicious Products</h1>
            <p class="lead" data-aos="fade-up" data-aos-delay="100">Freshly baked with love every day</p>
        </div>
    </section>

    <!-- Products Section -->
    <section class="py-5">
        <div class="container">
            <div class="row">
                <!-- Sidebar Filters -->
                <div class="col-lg-3 mb-4" data-aos="fade-right">
                    <div class="filter-sidebar">
                        <h5 class="mb-4">Filter Products</h5>
                        
                        <!-- Search Form -->
                        <form method="GET" class="mb-4">
                            <div class="input-group">
                                <input type="text" name="search" class="form-control" placeholder="Search products..." value="<?php echo htmlspecialchars($search); ?>">
                                <button class="btn btn-mercy" type="submit">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </form>
                        
                        <!-- Categories -->
                        <h6 class="mb-3">Categories</h6>
                        <div class="list-group mb-4">
                            <a href="products.php" class="list-group-item list-group-item-action <?php echo !$category_filter ? 'active' : ''; ?>">
                                All Products
                            </a>
                            <?php foreach ($categories as $category): ?>
                            <a href="products.php?category=<?php echo $category['id']; ?>" 
                               class="list-group-item list-group-item-action <?php echo $category_filter == $category['id'] ? 'active' : ''; ?>">
                                <?php echo htmlspecialchars($category['name']); ?>
                            </a>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- Price Range (can be expanded) -->
                        <h6 class="mb-3">Price Range</h6>
                        <div class="mb-4">
                            <input type="range" class="form-range" min="0" max="500000" step="10000" id="priceRange">
                            <div class="d-flex justify-content-between mt-2">
                                <span>UGX 0</span>
                                <span>UGX 500k+</span>
                            </div>
                        </div>
                        
                        <button class="btn btn-mercy w-100" onclick="applyFilters()">Apply Filters</button>
                    </div>
                </div>
                
                <!-- Products Grid -->
                <div class="col-lg-9">
                    <!-- Products Count -->
                    <div class="d-flex justify-content-between align-items-center mb-4" data-aos="fade-up">
                        <p>Showing <?php echo count($products); ?> of <?php echo $total_products; ?> products</p>
                        <select class="form-select w-auto" onchange="sortProducts(this.value)">
                            <option value="newest">Newest First</option>
                            <option value="price_low">Price: Low to High</option>
                            <option value="price_high">Price: High to Low</option>
                            <option value="name">Name: A to Z</option>
                        </select>
                    </div>
                    
                    <!-- Products Grid -->
                    <div class="row g-4">
                        <?php if (empty($products)): ?>
                        <div class="col-12 text-center py-5">
                            <i class="fas fa-box-open fa-4x text-muted mb-3"></i>
                            <h4>No products found</h4>
                            <p class="text-muted">Try adjusting your filters or search terms.</p>
                        </div>
                        <?php else: ?>
                            <?php foreach ($products as $index => $product): ?>
                            <div class="col-md-6 col-xl-4" data-aos="fade-up" data-aos-delay="<?php echo ($index % 3) * 100; ?>">
                                <div class="product-card">
                                    <div class="product-image">
                                        <img src="<?php echo $product['image']; ?>" alt="<?php echo $product['name']; ?>">
                                        <?php if ($product['sale_price']): ?>
                                        <span class="product-badge">-<?php echo round((($product['price'] - $product['sale_price']) / $product['price']) * 100); ?>%</span>
                                        <?php endif; ?>
                                        <div class="product-overlay">
                                            <button class="btn btn-light btn-sm add-to-cart" data-id="<?php echo $product['id']; ?>">
                                                <i class="fas fa-cart-plus"></i> Add to Cart
                                            </button>
                                            <a href="product.php?id=<?php echo $product['id']; ?>" class="btn btn-light btn-sm">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                        </div>
                                    </div>
                                    <div class="product-info">
                                        <span class="product-category"><?php echo htmlspecialchars($product['category_name']); ?></span>
                                        <h5><?php echo htmlspecialchars($product['name']); ?></h5>
                                        <div class="product-price">
                                            <?php if ($product['sale_price']): ?>
                                                <span class="old-price">UGX <?php echo number_format($product['price']); ?></span>
                                                <span class="new-price">UGX <?php echo number_format($product['sale_price']); ?></span>
                                            <?php else: ?>
                                                <span>UGX <?php echo number_format($product['price']); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                    <nav class="mt-5" data-aos="fade-up">
                        <ul class="pagination justify-content-center">
                            <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $page-1; ?>&category=<?php echo $category_filter; ?>&search=<?php echo urlencode($search); ?>">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            </li>
                            
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>&category=<?php echo $category_filter; ?>&search=<?php echo urlencode($search); ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                            <?php endfor; ?>
                            
                            <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $page+1; ?>&category=<?php echo $category_filter; ?>&search=<?php echo urlencode($search); ?>">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            </li>
                        </ul>
                    </nav>
                    <?php endif; ?>
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
        function sortProducts(value) {
            // Implement sorting logic
            console.log('Sort by:', value);
        }
        
        function applyFilters() {
            // Implement filter logic
            const price = document.getElementById('priceRange').value;
            window.location.href = 'products.php?price=' + price;
        }
    </script>
</body>
</html>