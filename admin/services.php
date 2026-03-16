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
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);
        $price_range = trim($_POST['price_range']);
        $icon = trim($_POST['icon']);
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        $slug = createSlug($name);
        
        // Handle image upload
        $image_path = $_POST['current_image'] ?? null;
        
        if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
            $upload = uploadImage($_FILES['image'], 'services');
            if ($upload['success']) {
                $image_path = 'uploads/services/' . $upload['filename'];
                
                // Delete old image
                if ($action === 'edit' && $_POST['current_image'] && file_exists('../' . $_POST['current_image'])) {
                    unlink('../' . $_POST['current_image']);
                }
            }
        }
        
        if ($action === 'add') {
            $query = "INSERT INTO services (name, slug, description, price_range, icon, image, is_active) 
                      VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $db->prepare($query);
            $stmt->execute([$name, $slug, $description, $price_range, $icon, $image_path, $is_active]);
            
            $_SESSION['success'] = "Service added successfully!";
        } else {
            $query = "UPDATE services SET name=?, slug=?, description=?, price_range=?, icon=?, image=?, is_active=? WHERE id=?";
            $stmt = $db->prepare($query);
            $stmt->execute([$name, $slug, $description, $price_range, $icon, $image_path, $is_active, $id]);
            
            $_SESSION['success'] = "Service updated successfully!";
        }
        
        header('Location: services.php');
        exit;
    }
    
    if ($action === 'delete') {
        $id = $_POST['id'];
        
        // Get image to delete
        $query = "SELECT image FROM services WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$id]);
        $service = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($service && $service['image'] && file_exists('../' . $service['image'])) {
            unlink('../' . $service['image']);
        }
        
        $query = "DELETE FROM services WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$id]);
        
        $_SESSION['success'] = "Service deleted successfully!";
        header('Location: services.php');
        exit;
    }
}

// Get all services
$query = "SELECT * FROM services ORDER BY id";
$stmt = $db->prepare($query);
$stmt->execute();
$services = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get service for editing
$edit_service = null;
if (isset($_GET['edit'])) {
    $query = "SELECT * FROM services WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$_GET['edit']]);
    $edit_service = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Get current user
$query = "SELECT * FROM users WHERE id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$_SESSION['user_id']]);
$current_user = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Services - Admin - Mercy Pastries</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../css/admin.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        .service-icon-preview {
            font-size: 2rem;
            width: 60px;
            height: 60px;
            background: var(--gradient);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
        }
        
        .icon-selector {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(50px, 1fr));
            gap: 10px;
            margin-top: 10px;
            max-height: 200px;
            overflow-y: auto;
            padding: 10px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
        }
        
        .icon-option {
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 1.5rem;
        }
        
        .icon-option:hover,
        .icon-option.selected {
            background: var(--gradient);
            color: white;
            border-color: transparent;
            transform: scale(1.1);
        }
        
        .service-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
        }
        
        .preview-image {
            max-width: 200px;
            max-height: 200px;
            margin-top: 10px;
            border-radius: 8px;
            border: 1px solid var(--border-color);
        }
    </style>
</head>
<body>
    <div class="admin-wrapper">
        <!-- Sidebar -->
        <?php include 'includes/sidebar.php'; ?>

        <!-- Main Content -->
        <main class="admin-main">
            <div class="admin-header">
                <h2><i class="fas fa-concierge-bell me-2"></i>Manage Services</h2>
                <div class="header-actions">
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#serviceModal">
                        <i class="fas fa-plus me-2"></i>Add New Service
                    </button>
                </div>
            </div>

            <div class="admin-content">
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i>
                        <?php 
                        echo $_SESSION['success']; 
                        unset($_SESSION['success']);
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (empty($services)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-concierge-bell fa-4x text-muted mb-3"></i>
                        <h4>No Services Added Yet</h4>
                        <p class="text-muted">Click the "Add New Service" button to create your first service.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Icon</th>
                                    <th>Image</th>
                                    <th>Service Name</th>
                                    <th>Price Range</th>
                                    <th>Description</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($services as $service): ?>
                                <tr>
                                    <td><?php echo $service['id']; ?></td>
                                    <td class="text-center">
                                        <div class="service-icon-preview" style="width: 40px; height: 40px; font-size: 1.2rem;">
                                            <i class="fas <?php echo $service['icon'] ?: 'fa-concierge-bell'; ?>"></i>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($service['image']): ?>
                                            <img src="../<?php echo $service['image']; ?>" alt="<?php echo $service['name']; ?>" class="service-image">
                                        <?php else: ?>
                                            <span class="text-muted">No image</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($service['name']); ?></strong>
                                    </td>
                                    <td><?php echo htmlspecialchars($service['price_range']); ?></td>
                                    <td>
                                        <span class="d-inline-block text-truncate" style="max-width: 200px;">
                                            <?php echo htmlspecialchars($service['description']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($service['is_active']): ?>
                                            <span class="badge bg-success">Active</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="table-actions">
                                            <button class="btn btn-sm btn-info" onclick="editService(<?php echo htmlspecialchars(json_encode($service)); ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-danger" onclick="deleteService(<?php echo $service['id']; ?>, '<?php echo addslashes($service['name']); ?>')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                            <a href="../services.php#service-<?php echo $service['id']; ?>" class="btn btn-sm btn-secondary" target="_blank">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Service Modal -->
    <div class="modal fade" id="serviceModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Add New Service</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" enctype="multipart/form-data" id="serviceForm">
                    <div class="modal-body">
                        <input type="hidden" name="action" id="action" value="add">
                        <input type="hidden" name="id" id="service_id">
                        <input type="hidden" name="current_image" id="current_image">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Service Name *</label>
                                <input type="text" name="name" id="name" class="form-control" required maxlength="100">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Price Range *</label>
                                <input type="text" name="price_range" id="price_range" class="form-control" required 
                                       placeholder="e.g., UGX 120,000+ or UGX 5,000 – 18,000">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Description *</label>
                            <textarea name="description" id="description" class="form-control" rows="4" required 
                                      placeholder="Describe the service in detail..."></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Icon (Font Awesome) *</label>
                                <input type="text" name="icon" id="icon" class="form-control" required 
                                       placeholder="e.g., fa-birthday-cake" value="fa-concierge-bell">
                                <small class="text-muted">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Browse icons at <a href="https://fontawesome.com/icons" target="_blank">FontAwesome</a>
                                </small>
                                
                                <!-- Common Icons Quick Select -->
                                <div class="icon-selector mt-2">
                                    <div class="icon-option <?php echo (!isset($edit_service) || $edit_service['icon'] == 'fa-birthday-cake') ? 'selected' : ''; ?>" data-icon="fa-birthday-cake">
                                        <i class="fas fa-birthday-cake"></i>
                                    </div>
                                    <div class="icon-option" data-icon="fa-bread-slice">
                                        <i class="fas fa-bread-slice"></i>
                                    </div>
                                    <div class="icon-option" data-icon="fa-cupcake">
                                        <i class="fas fa-cupcake"></i>
                                    </div>
                                    <div class="icon-option" data-icon="fa-truck">
                                        <i class="fas fa-truck"></i>
                                    </div>
                                    <div class="icon-option" data-icon="fa-chalkboard-teacher">
                                        <i class="fas fa-chalkboard-teacher"></i>
                                    </div>
                                    <div class="icon-option" data-icon="fa-briefcase">
                                        <i class="fas fa-briefcase"></i>
                                    </div>
                                    <div class="icon-option" data-icon="fa-cookie">
                                        <i class="fas fa-cookie"></i>
                                    </div>
                                    <div class="icon-option" data-icon="fa-candy-cane">
                                        <i class="fas fa-candy-cane"></i>
                                    </div>
                                    <div class="icon-option" data-icon="fa-mug-hot">
                                        <i class="fas fa-mug-hot"></i>
                                    </div>
                                    <div class="icon-option" data-icon="fa-utensils">
                                        <i class="fas fa-utensils"></i>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Service Image</label>
                                <input type="file" name="image" id="image" class="form-control" accept="image/*">
                                <small class="text-muted">Recommended size: 800x600px. Max 2MB.</small>
                                <div id="imagePreview" class="mt-2"></div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1" checked>
                                <label class="form-check-label" for="is_active">Active (visible on website)</label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Service</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">Confirm Delete</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete the service: <strong id="deleteServiceName"></strong>?</p>
                    <p class="text-danger"><i class="fas fa-exclamation-triangle me-2"></i>This action cannot be undone!</p>
                </div>
                <div class="modal-footer">
                    <form method="POST">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" id="deleteServiceId">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Delete Service</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    
    <script>
        // Initialize icon selector
        $(document).ready(function() {
            $('.icon-option').click(function() {
                $('.icon-option').removeClass('selected');
                $(this).addClass('selected');
                $('#icon').val($(this).data('icon'));
            });
        });
        
        // Edit service function
        function editService(service) {
            document.getElementById('modalTitle').textContent = 'Edit Service';
            document.getElementById('action').value = 'edit';
            document.getElementById('service_id').value = service.id;
            document.getElementById('name').value = service.name;
            document.getElementById('description').value = service.description;
            document.getElementById('price_range').value = service.price_range;
            document.getElementById('icon').value = service.icon || 'fa-concierge-bell';
            document.getElementById('is_active').checked = service.is_active == 1;
            document.getElementById('current_image').value = service.image || '';
            
            // Highlight selected icon
            $('.icon-option').removeClass('selected');
            $(`.icon-option[data-icon="${service.icon}"]`).addClass('selected');
            
            // Show image preview
            if (service.image) {
                document.getElementById('imagePreview').innerHTML = 
                    '<img src="../' + service.image + '" class="preview-image">';
            } else {
                document.getElementById('imagePreview').innerHTML = '';
            }
            
            // Open modal
            new bootstrap.Modal(document.getElementById('serviceModal')).show();
        }
        
        // Delete service function
        function deleteService(id, name) {
            document.getElementById('deleteServiceId').value = id;
            document.getElementById('deleteServiceName').textContent = name;
            new bootstrap.Modal(document.getElementById('deleteModal')).show();
        }
        
        // Reset form when modal is closed
        document.getElementById('serviceModal').addEventListener('hidden.bs.modal', function() {
            if (document.getElementById('action').value !== 'edit') {
                document.getElementById('modalTitle').textContent = 'Add New Service';
                document.getElementById('action').value = 'add';
                document.getElementById('serviceForm').reset();
                document.getElementById('imagePreview').innerHTML = '';
                document.getElementById('current_image').value = '';
                
                // Reset icon selection
                $('.icon-option').removeClass('selected');
                $('.icon-option[data-icon="fa-concierge-bell"]').addClass('selected');
                $('#icon').val('fa-concierge-bell');
            }
        });
        
        // Preview image on file select
        document.getElementById('image').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                // Validate file size (2MB)
                if (file.size > 2 * 1024 * 1024) {
                    alert('File is too large. Maximum size is 2MB.');
                    this.value = '';
                    return;
                }
                
                // Validate file type
                if (!file.type.match('image.*')) {
                    alert('Please select an image file.');
                    this.value = '';
                    return;
                }
                
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('imagePreview').innerHTML = 
                        '<img src="' + e.target.result + '" class="preview-image">';
                }
                reader.readAsDataURL(file);
            }
        });
        
        // Form validation
        document.getElementById('serviceForm').addEventListener('submit', function(e) {
            const name = document.getElementById('name').value.trim();
            const price = document.getElementById('price_range').value.trim();
            const desc = document.getElementById('description').value.trim();
            const icon = document.getElementById('icon').value.trim();
            
            if (!name || !price || !desc || !icon) {
                e.preventDefault();
                alert('Please fill in all required fields.');
                return false;
            }
            
            return true;
        });
        
        // Initialize any edit modal if URL has edit parameter
        <?php if ($edit_service): ?>
        window.onload = function() {
            editService(<?php echo json_encode($edit_service); ?>);
        };
        <?php endif; ?>
    </script>
</body>
</html>