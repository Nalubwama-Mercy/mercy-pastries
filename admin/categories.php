<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check admin access
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$database = new Database();
$db = $database->getConnection();

// Check if user is admin
$query = "SELECT role FROM users WHERE id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user['role'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add' || $action === 'edit') {
        $id = $_POST['id'] ?? null;
        $name = $_POST['name'];
        $description = $_POST['description'];
        
        $slug = createSlug($name);
        
        // Handle image upload
        $image_path = $_POST['current_image'] ?? null;
        
        if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
            $upload = uploadImage($_FILES['image'], 'categories');
            if ($upload['success']) {
                $image_path = 'uploads/categories/' . $upload['filename'];
                
                // Delete old image
                if ($action === 'edit' && $_POST['current_image'] && file_exists('../' . $_POST['current_image'])) {
                    unlink('../' . $_POST['current_image']);
                }
            }
        }
        
        if ($action === 'add') {
            $query = "INSERT INTO categories (name, slug, description, image) VALUES (?, ?, ?, ?)";
            $stmt = $db->prepare($query);
            $stmt->execute([$name, $slug, $description, $image_path]);
        } else {
            $query = "UPDATE categories SET name=?, slug=?, description=?, image=? WHERE id=?";
            $stmt = $db->prepare($query);
            $stmt->execute([$name, $slug, $description, $image_path, $id]);
        }
        
        header('Location: categories.php?msg=success');
        exit;
    }
    
    if ($action === 'delete') {
        $id = $_POST['id'];
        
        // Get image to delete
        $query = "SELECT image FROM categories WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$id]);
        $category = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($category && $category['image'] && file_exists('../' . $category['image'])) {
            unlink('../' . $category['image']);
        }
        
        $query = "DELETE FROM categories WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$id]);
        
        header('Location: categories.php?msg=deleted');
        exit;
    }
}

// Get all categories
$query = "SELECT * FROM categories ORDER BY name";
$stmt = $db->prepare($query);
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Categories - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../css/admin.css">
</head>
<body>
    <div class="admin-wrapper">
        <!-- Sidebar -->
        <?php include 'includes/sidebar.php'; ?>

        <!-- Main Content -->
        <main class="admin-main">
            <div class="admin-header">
                <h2>Manage Categories</h2>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#categoryModal">
                    <i class="fas fa-plus me-2"></i>Add New Category
                </button>
            </div>

            <div class="admin-content">
                <?php if (isset($_GET['msg'])): ?>
                    <div class="alert alert-success">
                        <?php 
                        if ($_GET['msg'] == 'success') echo 'Category saved successfully!';
                        if ($_GET['msg'] == 'deleted') echo 'Category deleted successfully!';
                        ?>
                    </div>
                <?php endif; ?>

                <div class="row">
                    <?php foreach ($categories as $category): ?>
                    <div class="col-md-4 mb-4">
                        <div class="card">
                            <?php if ($category['image']): ?>
                            <img src="../<?php echo $category['image']; ?>" class="card-img-top" alt="<?php echo $category['name']; ?>" style="height: 200px; object-fit: cover;">
                            <?php endif; ?>
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($category['name']); ?></h5>
                                <p class="card-text"><?php echo htmlspecialchars($category['description']); ?></p>
                                <div class="btn-group">
                                    <button class="btn btn-sm btn-info" onclick="editCategory(<?php echo htmlspecialchars(json_encode($category)); ?>)">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $category['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure? This will affect all products in this category.')">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </main>
    </div>

    <!-- Category Modal -->
    <div class="modal fade" id="categoryModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Add New Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data" id="categoryForm">
                    <div class="modal-body">
                        <input type="hidden" name="action" id="action" value="add">
                        <input type="hidden" name="id" id="category_id">
                        <input type="hidden" name="current_image" id="current_image">
                        
                        <div class="mb-3">
                            <label class="form-label">Category Name</label>
                            <input type="text" name="name" id="name" class="form-control" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" id="description" class="form-control" rows="3"></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Category Image</label>
                            <input type="file" name="image" class="form-control" accept="image/*">
                            <small class="text-muted">Optional</small>
                        </div>
                        
                        <div id="imagePreview"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Category</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editCategory(category) {
            document.getElementById('modalTitle').textContent = 'Edit Category';
            document.getElementById('action').value = 'edit';
            document.getElementById('category_id').value = category.id;
            document.getElementById('name').value = category.name;
            document.getElementById('description').value = category.description;
            document.getElementById('current_image').value = category.image;
            
            if (category.image) {
                document.getElementById('imagePreview').innerHTML = 
                    '<img src="../' + category.image + '" style="max-width: 100px; margin-top: 10px;">';
            }
        }
        
        document.getElementById('categoryModal').addEventListener('hidden.bs.modal', function() {
            document.getElementById('modalTitle').textContent = 'Add New Category';
            document.getElementById('action').value = 'add';
            document.getElementById('categoryForm').reset();
            document.getElementById('imagePreview').innerHTML = '';
        });
    </script>
</body>
</html>