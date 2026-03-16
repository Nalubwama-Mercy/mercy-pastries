<?php
/**
 * Helper functions for Mercy Pastries
 */

/**
 * Format price in UGX
 */
function formatPrice($price) {
    return 'UGX ' . number_format($price);
}

/**
 * Generate slug from string
 */
function createSlug($string) {
    $string = strtolower($string);
    $string = preg_replace('/[^a-z0-9-]/', '-', $string);
    $string = preg_replace('/-+/', '-', $string);
    return trim($string, '-');
}

/**
 * Upload image function - FIXED VERSION
 */
function uploadImage($file, $folder = 'products') {
    // Define paths
    $upload_dir = "uploads/{$folder}/";
    $absolute_path = $_SERVER['DOCUMENT_ROOT'] . '/mercy_pastries/' . $upload_dir;
    
    // Create directory if it doesn't exist
    if (!file_exists($absolute_path)) {
        if (!mkdir($absolute_path, 0777, true)) {
            return ['success' => false, 'message' => 'Failed to create upload directory'];
        }
    }
    
    // Set permissions
    chmod($absolute_path, 0777);
    
    // Validate file
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors = [
            UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'File upload stopped by extension'
        ];
        $error_message = isset($errors[$file['error']]) ? $errors[$file['error']] : 'Unknown upload error';
        return ['success' => false, 'message' => $error_message];
    }
    
    // Check if image is actually an image
    $check = getimagesize($file['tmp_name']);
    if ($check === false) {
        return ['success' => false, 'message' => 'File is not a valid image.'];
    }
    
    // Check file size (max 5MB for hero images)
    $max_size = 5 * 1024 * 1024; // 5MB
    if ($file['size'] > $max_size) {
        return ['success' => false, 'message' => 'File is too large. Maximum size is 5MB.'];
    }
    
    // Get file extension
    $imageFileType = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    // Allow certain file formats
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    if (!in_array($imageFileType, $allowed)) {
        return ['success' => false, 'message' => 'Only JPG, JPEG, PNG, GIF & WEBP files are allowed.'];
    }
    
    // Generate unique filename
    $filename = time() . '_' . uniqid() . '.' . $imageFileType;
    $relative_path = $upload_dir . $filename; // For database
    $absolute_file = $absolute_path . $filename; // For file operations
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $absolute_file)) {
        // Set file permissions
        chmod($absolute_file, 0644);
        
        return [
            'success' => true,
            'filename' => $filename,
            'path' => $relative_path // This is what should be stored in database
        ];
    } else {
        $error = error_get_last();
        return [
            'success' => false,
            'message' => 'Error uploading file: ' . ($error['message'] ?? 'Unknown error')
        ];
    }
}

/**
 * Get cart count
 */
function getCartCount() {
    if (isset($_COOKIE['cart'])) {
        $cart = json_decode($_COOKIE['cart'], true) ?: [];
        return array_sum($cart);
    }
    return 0;
}

/**
 * Send email notification
 */
function sendEmail($to, $subject, $message) {
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= 'From: Mercy Pastries <noreply@mercypastries.com>' . "\r\n";
    $headers .= 'Reply-To: noreply@mercypastries.com' . "\r\n";
    $headers .= 'X-Mailer: PHP/' . phpversion();
    
    return mail($to, $subject, $message, $headers);
}

/**
 * Generate order number
 */
function generateOrderNumber() {
    return 'ORD-' . date('Ymd') . '-' . strtoupper(uniqid());
}

/**
 * Get order status badge
 */
function getOrderStatusBadge($status) {
    $colors = [
        'pending' => 'warning',
        'processing' => 'info',
        'completed' => 'success',
        'cancelled' => 'danger'
    ];
    
    $color = $colors[$status] ?? 'secondary';
    return "<span class='badge bg-{$color}'>{$status}</span>";
}

/**
 * Truncate text
 */
function truncateText($text, $limit = 100) {
    if (strlen($text) <= $limit) {
        return $text;
    }
    
    return substr($text, 0, $limit) . '...';
}

/**
 * Get time ago
 */
function timeAgo($datetime) {
    $time = strtotime($datetime);
    $now = time();
    $diff = $now - $time;
    
    if ($diff < 60) {
        return $diff . ' seconds ago';
    } elseif ($diff < 3600) {
        $mins = floor($diff / 60);
        return $mins . ' minute' . ($mins > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 2592000) {
        $days = floor($diff / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    } else {
        return date('M d, Y', $time);
    }
}

/**
 * Debug function
 */
function debug_log($data, $title = 'Debug') {
    $log_file = __DIR__ . '/../debug.log';
    $timestamp = date('Y-m-d H:i:s');
    $message = "[$timestamp] $title: " . print_r($data, true) . "\n";
    file_put_contents($log_file, $message, FILE_APPEND);
}
?>