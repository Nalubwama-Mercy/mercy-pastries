<?php
// Get current page for active state
$current_page = basename($_SERVER['PHP_SELF']);
?>

<!-- Sidebar -->
<div class="admin-sidebar" id="adminSidebar">
    <div class="sidebar-header">
        <h4>Mercy Pastries</h4>
        <p>Admin Panel</p>
        <button class="toggle-sidebar" id="toggleSidebar" title="Toggle Sidebar">
            <i class="fas fa-chevron-left"></i>
        </button>
    </div>
    
    <ul class="sidebar-menu">
        <li class="<?php echo $current_page == 'index.php' ? 'active' : ''; ?>">
            <a href="index.php">
                <i class="fas fa-tachometer-alt"></i>
                <span>Dashboard</span>
            </a>
        </li>
        
        <li class="<?php echo in_array($current_page, ['products.php', 'add-product.php', 'edit-product.php']) ? 'active' : ''; ?>">
            <a href="products.php">
                <i class="fas fa-cake-candles"></i>
                <span>Products</span>
            </a>
        </li>
        
        <li class="<?php echo in_array($current_page, ['categories.php', 'add-category.php', 'edit-category.php']) ? 'active' : ''; ?>">
            <a href="categories.php">
                <i class="fas fa-tags"></i>
                <span>Categories</span>
            </a>
        </li>
        
        <li class="<?php echo in_array($current_page, ['services.php', 'add-service.php', 'edit-service.php']) ? 'active' : ''; ?>">
            <a href="services.php">
                <i class="fas fa-concierge-bell"></i>
                <span>Services</span>
            </a>
        </li>
        
        <li class="<?php echo in_array($current_page, ['orders.php', 'order-details.php']) ? 'active' : ''; ?>">
            <a href="orders.php">
                <i class="fas fa-shopping-bag"></i>
                <span>Orders</span>
                <?php
                // Get pending orders count
                if (isset($db)) {
                    $query = "SELECT COUNT(*) as count FROM orders WHERE status = 'pending'";
                    $stmt = $db->prepare($query);
                    $stmt->execute();
                    $pending = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($pending['count'] > 0) {
                        echo '<span class="badge bg-danger ms-2">' . $pending['count'] . '</span>';
                    }
                }
                ?>
            </a>
        </li>
        
        <li class="<?php echo $current_page == 'users.php' ? 'active' : ''; ?>">
            <a href="users.php">
                <i class="fas fa-users"></i>
                <span>Users</span>
                <?php
                // Get new users count (last 7 days)
                if (isset($db)) {
                    $query = "SELECT COUNT(*) as count FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) AND role = 'user'";
                    $stmt = $db->prepare($query);
                    $stmt->execute();
                    $new_users = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($new_users['count'] > 0) {
                        echo '<span class="badge bg-success ms-2">+' . $new_users['count'] . '</span>';
                    }
                }
                ?>
            </a>
        </li>
        
        <li class="<?php echo $current_page == 'messages.php' ? 'active' : ''; ?>">
            <a href="messages.php">
                <i class="fas fa-envelope"></i>
                <span>Messages</span>
                <?php
                // Get unread messages count
                if (isset($db)) {
                    $query = "SELECT COUNT(*) as count FROM contacts WHERE status = 'unread'";
                    $stmt = $db->prepare($query);
                    $stmt->execute();
                    $unread = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($unread['count'] > 0) {
                        echo '<span class="badge bg-warning ms-2">' . $unread['count'] . '</span>';
                    }
                }
                ?>
            </a>
        </li>
        
        <li class="<?php echo $current_page == 'hero-slides.php' ? 'active' : ''; ?>">
            <a href="hero-slides.php">
                <i class="fas fa-images"></i>
                <span>Hero Slides</span>
            </a>
        </li>
        
        <li class="<?php echo $current_page == 'settings.php' ? 'active' : ''; ?>">
            <a href="settings.php">
                <i class="fas fa-cog"></i>
                <span>Settings</span>
            </a>
        </li>
        
        <li class="divider">
            <hr style="border-color: rgba(255,255,255,0.1); margin: 15px 0;">
        </li>
        
        <li>
            <a href="../index.php" target="_blank">
                <i class="fas fa-external-link-alt"></i>
                <span>View Site</span>
            </a>
        </li>
        
        <li>
            <a href="../logout.php">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </li>
    </ul>
    
    <!-- Sidebar Footer -->
    <div class="sidebar-footer">
        <?php if (isset($current_user)): ?>
        <div class="user-info">
            <img src="<?php echo $current_user['profile_picture'] ?? '../images/default-avatar.png'; ?>" alt="Admin" class="user-avatar">
            <div class="user-details">
                <strong><?php echo htmlspecialchars($current_user['full_name']); ?></strong>
                <small>Administrator</small>
            </div>
        </div>
        <?php endif; ?>
        <div class="version mt-2">
            <small>v2.0.0</small>
        </div>
    </div>
</div>

<!-- Mobile Menu Overlay -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('adminSidebar');
    const toggleBtn = document.getElementById('toggleSidebar');
    const overlay = document.getElementById('sidebarOverlay');
    const mainContent = document.querySelector('.admin-main');
    
    // Toggle sidebar
    toggleBtn?.addEventListener('click', function() {
        sidebar.classList.toggle('collapsed');
        mainContent.classList.toggle('expanded');
        
        // Change icon
        const icon = this.querySelector('i');
        if (sidebar.classList.contains('collapsed')) {
            icon.classList.remove('fa-chevron-left');
            icon.classList.add('fa-chevron-right');
            localStorage.setItem('sidebarCollapsed', 'true');
        } else {
            icon.classList.remove('fa-chevron-right');
            icon.classList.add('fa-chevron-left');
            localStorage.setItem('sidebarCollapsed', 'false');
        }
    });
    
    // Check saved state
    if (localStorage.getItem('sidebarCollapsed') === 'true') {
        sidebar.classList.add('collapsed');
        mainContent.classList.add('expanded');
        const icon = toggleBtn?.querySelector('i');
        if (icon) {
            icon.classList.remove('fa-chevron-left');
            icon.classList.add('fa-chevron-right');
        }
    }
    
    // Mobile menu toggle
    if (window.innerWidth <= 992) {
        sidebar.classList.remove('collapsed');
        mainContent.classList.remove('expanded');
    }
    
    // Handle window resize
    window.addEventListener('resize', function() {
        if (window.innerWidth <= 992) {
            sidebar.classList.remove('show');
            overlay.classList.remove('show');
        }
    });
    
    // Close sidebar when clicking overlay on mobile
    overlay?.addEventListener('click', function() {
        sidebar.classList.remove('show');
        overlay.classList.remove('show');
    });
    
    // Highlight active menu item
    const currentPage = window.location.pathname.split('/').pop();
    const menuItems = document.querySelectorAll('.sidebar-menu li');
    
    menuItems.forEach(item => {
        const link = item.querySelector('a');
        if (link && link.getAttribute('href') === currentPage) {
            item.classList.add('active');
        }
    });
});
</script>

<style>
/* Mobile overlay */
.sidebar-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.5);
    z-index: 1040;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.sidebar-overlay.show {
    display: block;
    opacity: 1;
}

@media (max-width: 992px) {
    .admin-sidebar {
        transform: translateX(-100%);
        transition: transform 0.3s ease;
    }
    
    .admin-sidebar.show {
        transform: translateX(0);
    }
    
    .admin-sidebar.collapsed {
        width: var(--sidebar-width);
    }
    
    .admin-sidebar.collapsed .sidebar-header h4,
    .admin-sidebar.collapsed .sidebar-header p,
    .admin-sidebar.collapsed .sidebar-menu li a span {
        display: block;
    }
    
    .admin-sidebar.collapsed .sidebar-menu li a {
        justify-content: flex-start;
        padding: 12px 15px;
    }
    
    .admin-sidebar.collapsed .sidebar-menu li a i {
        margin-right: 10px;
    }
}

/* Version badge */
.version {
    color: rgba(255,255,255,0.5);
    font-size: 0.8rem;
    text-align: center;
}

/* Divider */
.divider {
    padding: 0 !important;
}
</style>