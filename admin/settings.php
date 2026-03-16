<?php
session_start();
require_once '../config/database.php';

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

// Update settings
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($_POST as $key => $value) {
        if ($key !== 'submit') {
            // Check if setting exists
            $query = "SELECT id FROM settings WHERE setting_key = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$key]);
            
            if ($stmt->fetch()) {
                $query = "UPDATE settings SET setting_value = ? WHERE setting_key = ?";
                $stmt = $db->prepare($query);
                $stmt->execute([$value, $key]);
            } else {
                $query = "INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)";
                $stmt = $db->prepare($query);
                $stmt->execute([$key, $value]);
            }
        }
    }
    
    // Handle file uploads
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === 0) {
        $upload = uploadImage($_FILES['logo'], 'settings');
        if ($upload['success']) {
            $query = "UPDATE settings SET setting_value = ? WHERE setting_key = 'site_logo'";
            $stmt = $db->prepare($query);
            $stmt->execute(['uploads/settings/' . $upload['filename']]);
        }
    }
    
    if (isset($_FILES['favicon']) && $_FILES['favicon']['error'] === 0) {
        $upload = uploadImage($_FILES['favicon'], 'settings');
        if ($upload['success']) {
            $query = "UPDATE settings SET setting_value = ? WHERE setting_key = 'site_favicon'";
            $stmt = $db->prepare($query);
            $stmt->execute(['uploads/settings/' . $upload['filename']]);
        }
    }
    
    header('Location: settings.php?msg=updated');
    exit;
}

// Get all settings
$query = "SELECT * FROM settings";
$stmt = $db->prepare($query);
$stmt->execute();
$settings = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Site Settings - Admin</title>
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
                <h2>Site Settings</h2>
            </div>

            <div class="admin-content">
                <?php if (isset($_GET['msg'])): ?>
                    <div class="alert alert-success">Settings updated successfully!</div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data">
                    <ul class="nav nav-tabs mb-4" id="settingsTab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="general-tab" data-bs-toggle="tab" data-bs-target="#general" type="button">General</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="contact-tab" data-bs-toggle="tab" data-bs-target="#contact" type="button">Contact</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="social-tab" data-bs-toggle="tab" data-bs-target="#social" type="button">Social Media</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="seo-tab" data-bs-toggle="tab" data-bs-target="#seo" type="button">SEO</button>
                        </li>
                    </ul>
                    
                    <div class="tab-content" id="settingsTabContent">
                        <!-- General Settings -->
                        <div class="tab-pane fade show active" id="general" role="tabpanel">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Site Name</label>
                                    <input type="text" name="site_name" class="form-control" value="<?php echo $settings['site_name'] ?? 'Mercy Pastries'; ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Site Tagline</label>
                                    <input type="text" name="site_tagline" class="form-control" value="<?php echo $settings['site_tagline'] ?? 'Fresh & Sweet'; ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Site Logo</label>
                                    <input type="file" name="logo" class="form-control" accept="image/*">
                                    <?php if (isset($settings['site_logo'])): ?>
                                    <div class="mt-2">
                                        <img src="../<?php echo $settings['site_logo']; ?>" style="max-height: 50px;">
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Favicon</label>
                                    <input type="file" name="favicon" class="form-control" accept="image/*">
                                    <?php if (isset($settings['site_favicon'])): ?>
                                    <div class="mt-2">
                                        <img src="../<?php echo $settings['site_favicon']; ?>" style="max-height: 32px;">
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <div class="col-12 mb-3">
                                    <label class="form-label">About Text</label>
                                    <textarea name="about_text" class="form-control" rows="4"><?php echo $settings['about_text'] ?? ''; ?></textarea>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Contact Settings -->
                        <div class="tab-pane fade" id="contact" role="tabpanel">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Email Address</label>
                                    <input type="email" name="site_email" class="form-control" value="<?php echo $settings['site_email'] ?? ''; ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Phone Number</label>
                                    <input type="text" name="site_phone" class="form-control" value="<?php echo $settings['site_phone'] ?? ''; ?>">
                                </div>
                                <div class="col-12 mb-3">
                                    <label class="form-label">Address</label>
                                    <textarea name="site_address" class="form-control" rows="2"><?php echo $settings['site_address'] ?? ''; ?></textarea>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">WhatsApp Number</label>
                                    <input type="text" name="whatsapp" class="form-control" value="<?php echo $settings['whatsapp'] ?? ''; ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Map Embed URL</label>
                                    <input type="text" name="map_url" class="form-control" value="<?php echo $settings['map_url'] ?? ''; ?>">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Social Media Settings -->
                        <div class="tab-pane fade" id="social" role="tabpanel">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Facebook URL</label>
                                    <input type="url" name="facebook_url" class="form-control" value="<?php echo $settings['facebook_url'] ?? ''; ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Instagram URL</label>
                                    <input type="url" name="instagram_url" class="form-control" value="<?php echo $settings['instagram_url'] ?? ''; ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Twitter URL</label>
                                    <input type="url" name="twitter_url" class="form-control" value="<?php echo $settings['twitter_url'] ?? ''; ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">TikTok URL</label>
                                    <input type="url" name="tiktok_url" class="form-control" value="<?php echo $settings['tiktok_url'] ?? ''; ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">YouTube URL</label>
                                    <input type="url" name="youtube_url" class="form-control" value="<?php echo $settings['youtube_url'] ?? ''; ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">LinkedIn URL</label>
                                    <input type="url" name="linkedin_url" class="form-control" value="<?php echo $settings['linkedin_url'] ?? ''; ?>">
                                </div>
                            </div>
                        </div>
                        
                        <!-- SEO Settings -->
                        <div class="tab-pane fade" id="seo" role="tabpanel">
                            <div class="row">
                                <div class="col-12 mb-3">
                                    <label class="form-label">Meta Title</label>
                                    <input type="text" name="meta_title" class="form-control" value="<?php echo $settings['meta_title'] ?? ''; ?>">
                                </div>
                                <div class="col-12 mb-3">
                                    <label class="form-label">Meta Description</label>
                                    <textarea name="meta_description" class="form-control" rows="3"><?php echo $settings['meta_description'] ?? ''; ?></textarea>
                                </div>
                                <div class="col-12 mb-3">
                                    <label class="form-label">Meta Keywords</label>
                                    <input type="text" name="meta_keywords" class="form-control" value="<?php echo $settings['meta_keywords'] ?? ''; ?>" placeholder="cake, pastries, bakery, kampala">
                                </div>
                                <div class="col-12 mb-3">
                                    <label class="form-label">Google Analytics Code</label>
                                    <textarea name="google_analytics" class="form-control" rows="3"><?php echo $settings['google_analytics'] ?? ''; ?></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <button type="submit" name="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Save Settings
                    </button>
                </form>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>