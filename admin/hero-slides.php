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
        $title = $_POST['title'];
        $subtitle = $_POST['subtitle'];
        $button_text = $_POST['button_text'];
        $button_link = $_POST['button_link'];
        $order_position = $_POST['order_position'];
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        // Handle image upload - FIXED VERSION
        $image_path = $_POST['current_image'] ?? null;
        $upload_error = null;
        
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            // Use the fixed upload function
            $upload = uploadImage($_FILES['image'], 'hero');
            
            if ($upload['success']) {
                $image_path = $upload['path']; // This will be 'uploads/hero/filename.jpg'
                
                // Delete old image if editing
                if ($action === 'edit' && !empty($_POST['current_image'])) {
                    $old_image = '../' . $_POST['current_image'];
                    if (file_exists($old_image)) {
                        unlink($old_image);
                    }
                }
                
                $_SESSION['success'] = "Image uploaded successfully: " . $upload['filename'];
            } else {
                $upload_error = $upload['message'];
                $_SESSION['error'] = "Image upload failed: " . $upload_error;
            }
        } elseif (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
            // There was an upload error but not "no file"
            $upload_errors = [
                UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize',
                UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE',
                UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
                UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
                UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
                UPLOAD_ERR_EXTENSION => 'File upload stopped by extension'
            ];
            $error_code = $_FILES['image']['error'];
            $error_message = isset($upload_errors[$error_code]) ? $upload_errors[$error_code] : 'Unknown upload error';
            $_SESSION['error'] = "Upload error: " . $error_message;
        }
        
        if ($action === 'add') {
            $query = "INSERT INTO hero_slides (title, subtitle, button_text, button_link, image, order_position, is_active) 
                      VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $db->prepare($query);
            
            if ($stmt->execute([$title, $subtitle, $button_text, $button_link, $image_path, $order_position, $is_active])) {
                $_SESSION['success'] = "Hero slide added successfully!";
            } else {
                $_SESSION['error'] = "Failed to add hero slide to database.";
            }
        } else {
            $query = "UPDATE hero_slides SET title=?, subtitle=?, button_text=?, button_link=?, image=?, order_position=?, is_active=? WHERE id=?";
            $stmt = $db->prepare($query);
            
            if ($stmt->execute([$title, $subtitle, $button_text, $button_link, $image_path, $order_position, $is_active, $id])) {
                $_SESSION['success'] = "Hero slide updated successfully!";
            } else {
                $_SESSION['error'] = "Failed to update hero slide.";
            }
        }
        
        header('Location: hero-slides.php');
        exit;
    }
    
    if ($action === 'delete') {
        $id = $_POST['id'];
        
        // Get image to delete
        $query = "SELECT image FROM hero_slides WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$id]);
        $slide = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($slide && $slide['image'] && file_exists('../' . $slide['image'])) {
            unlink('../' . $slide['image']);
        }
        
        $query = "DELETE FROM hero_slides WHERE id = ?";
        $stmt = $db->prepare($query);
        
        if ($stmt->execute([$id])) {
            $_SESSION['success'] = "Hero slide deleted successfully!";
        } else {
            $_SESSION['error'] = "Failed to delete hero slide.";
        }
        
        header('Location: hero-slides.php');
        exit;
    }
}

// Get all slides
$query = "SELECT * FROM hero_slides ORDER BY order_position";
$stmt = $db->prepare($query);
$stmt->execute();
$slides = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get slide for editing
$edit_slide = null;
if (isset($_GET['edit'])) {
    $query = "SELECT * FROM hero_slides WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$_GET['edit']]);
    $edit_slide = $stmt->fetch(PDO::FETCH_ASSOC);
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
    <title>Hero Slides - Admin - Mercy Pastries</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../css/admin.css">
    <style>
        .slide-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            height: 100%;
            position: relative;
        }
        
        .slide-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.15);
        }
        
        .slide-image {
            height: 200px;
            overflow: hidden;
        }
        
        .slide-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }
        
        .slide-card:hover .slide-image img {
            transform: scale(1.1);
        }
        
        .slide-content {
            padding: 20px;
        }
        
        .slide-title {
            font-size: 1.3rem;
            font-weight: 700;
            margin-bottom: 10px;
        }
        
        .slide-subtitle {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 15px;
        }
        
        .slide-order {
            position: absolute;
            top: 10px;
            right: 10px;
            background: var(--gradient);
            color: white;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            z-index: 2;
        }
        
        .preview-image {
            max-width: 200px;
            max-height: 200px;
            margin-top: 10px;
            border-radius: 8px;
            border: 1px solid #dee2e6;
        }
        
        .status-badge {
            position: absolute;
            top: 10px;
            left: 10px;
            z-index: 2;
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
                <h2><i class="fas fa-images me-2"></i>Hero Slides</h2>
                <div class="header-actions">
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#slideModal">
                        <i class="fas fa-plus me-2"></i>Add New Slide
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

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <?php 
                        echo $_SESSION['error']; 
                        unset($_SESSION['error']);
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (empty($slides)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-images fa-4x text-muted mb-3"></i>
                        <h4>No Hero Slides Yet</h4>
                        <p class="text-muted">Click the "Add New Slide" button to create your first hero slide.</p>
                    </div>
                <?php else: ?>
                    <div class="row g-4">
                        <?php foreach ($slides as $slide): ?>
                        <div class="col-md-6 col-xl-4">
                            <div class="slide-card">
                                <div class="status-badge">
                                    <?php if ($slide['is_active']): ?>
                                        <span class="badge bg-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Inactive</span>
                                    <?php endif; ?>
                                </div>
                                <div class="slide-order"><?php echo $slide['order_position']; ?></div>
                                <div class="slide-image">
                                    <?php if ($slide['image'] && file_exists('../' . $slide['image'])): ?>
                                        <img src="../<?php echo $slide['image']; ?>" alt="<?php echo $slide['title']; ?>">
                                    <?php else: ?>
                                        <img src="https://via.placeholder.com/800x400/6f42c1/ffffff?text=No+Image" alt="No Image">
                                    <?php endif; ?>
                                </div>
                                <div class="slide-content">
                                    <h5 class="slide-title"><?php echo htmlspecialchars($slide['title']); ?></h5>
                                    <p class="slide-subtitle"><?php echo htmlspecialchars($slide['subtitle']); ?></p>
                                    
                                    <?php if ($slide['button_text'] && $slide['button_link']): ?>
                                        <div class="mb-3">
                                            <span class="badge bg-info">Button: <?php echo $slide['button_text']; ?></span>
                                            <small class="d-block text-muted">Link: <?php echo $slide['button_link']; ?></small>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="btn-group w-100">
                                        <button class="btn btn-sm btn-info" onclick="editSlide(<?php echo htmlspecialchars(json_encode($slide)); ?>)">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <button class="btn btn-sm btn-danger" onclick="deleteSlide(<?php echo $slide['id']; ?>, '<?php echo addslashes($slide['title']); ?>')">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Slide Modal -->
    <div class="modal fade" id="slideModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Add New Slide</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" enctype="multipart/form-data" id="slideForm">
                    <div class="modal-body">
                        <input type="hidden" name="action" id="action" value="add">
                        <input type="hidden" name="id" id="slide_id">
                        <input type="hidden" name="current_image" id="current_image">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Title *</label>
                                <input type="text" name="title" id="title" class="form-control" required maxlength="100">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Order Position</label>
                                <input type="number" name="order_position" id="order_position" class="form-control" value="0" min="0">
                                <small class="text-muted">Lower numbers appear first</small>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Subtitle</label>
                            <textarea name="subtitle" id="subtitle" class="form-control" rows="2" maxlength="200"></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Button Text</label>
                                <input type="text" name="button_text" id="button_text" class="form-control" placeholder="e.g., Shop Now">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Button Link</label>
                                <input type="text" name="button_link" id="button_link" class="form-control" placeholder="e.g., products.php">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Slide Image *</label>
                            <input type="file" name="image" id="image" class="form-control" accept="image/*" <?php echo !$edit_slide ? 'required' : ''; ?>>
                            <small class="text-muted">
                                <i class="fas fa-info-circle me-1"></i>
                                Recommended size: 1920x1080px. Max size: 5MB. Formats: JPG, PNG, GIF, WEBP
                            </small>
                            <div id="imagePreview" class="mt-2"></div>
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1" checked>
                                <label class="form-check-label" for="is_active">Active (visible on homepage)</label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Slide</button>
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
                    <p>Are you sure you want to delete the slide: <strong id="deleteSlideTitle"></strong>?</p>
                    <p class="text-danger"><i class="fas fa-exclamation-triangle me-2"></i>This action cannot be undone!</p>
                </div>
                <div class="modal-footer">
                    <form method="POST">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" id="deleteSlideId">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Delete Slide</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function editSlide(slide) {
            document.getElementById('modalTitle').textContent = 'Edit Slide';
            document.getElementById('action').value = 'edit';
            document.getElementById('slide_id').value = slide.id;
            document.getElementById('title').value = slide.title;
            document.getElementById('subtitle').value = slide.subtitle || '';
            document.getElementById('button_text').value = slide.button_text || '';
            document.getElementById('button_link').value = slide.button_link || '';
            document.getElementById('order_position').value = slide.order_position;
            document.getElementById('is_active').checked = slide.is_active == 1;
            document.getElementById('current_image').value = slide.image || '';
            
            // Make image not required for edit
            document.getElementById('image').required = false;
            
            // Show current image
            if (slide.image) {
                document.getElementById('imagePreview').innerHTML = 
                    '<img src="../' + slide.image + '" class="preview-image" onerror="this.src=\'https://via.placeholder.com/200/6f42c1/ffffff?text=Image+Not+Found\'">';
            } else {
                document.getElementById('imagePreview').innerHTML = '';
            }
            
            new bootstrap.Modal(document.getElementById('slideModal')).show();
        }
        
        function deleteSlide(id, title) {
            document.getElementById('deleteSlideId').value = id;
            document.getElementById('deleteSlideTitle').textContent = title;
            new bootstrap.Modal(document.getElementById('deleteModal')).show();
        }
        
        // Reset form when modal is closed
        document.getElementById('slideModal').addEventListener('hidden.bs.modal', function() {
            if (document.getElementById('action').value !== 'edit') {
                document.getElementById('modalTitle').textContent = 'Add New Slide';
                document.getElementById('action').value = 'add';
                document.getElementById('slideForm').reset();
                document.getElementById('imagePreview').innerHTML = '';
                document.getElementById('current_image').value = '';
                document.getElementById('image').required = true;
            }
        });
        
        // Preview image on file select
        document.getElementById('image').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                // Validate file size (5MB)
                if (file.size > 5 * 1024 * 1024) {
                    alert('File is too large. Maximum size is 5MB.');
                    this.value = '';
                    return;
                }
                
                // Validate file type
                const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
                if (!validTypes.includes(file.type)) {
                    alert('Please select a valid image file (JPG, PNG, GIF, WEBP).');
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
        
        // Initialize edit modal if URL has edit parameter
        <?php if ($edit_slide): ?>
        window.onload = function() {
            editSlide(<?php echo json_encode($edit_slide); ?>);
        };
        <?php endif; ?>
    </script>
</body>
</html>