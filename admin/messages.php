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

// Mark as read
if (isset($_GET['read'])) {
    $id = $_GET['read'];
    
    $query = "UPDATE contacts SET status = 'read' WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$id]);
    
    header('Location: messages.php');
    exit;
}

// Mark as replied
if (isset($_GET['replied'])) {
    $id = $_GET['replied'];
    
    $query = "UPDATE contacts SET status = 'replied' WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$id]);
    
    header('Location: messages.php');
    exit;
}

// Delete message
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    
    $query = "DELETE FROM contacts WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$id]);
    
    header('Location: messages.php?msg=deleted');
    exit;
}

// Get all messages
$query = "SELECT * FROM contacts ORDER BY 
          CASE status 
            WHEN 'unread' THEN 1 
            WHEN 'read' THEN 2 
            ELSE 3 
          END, created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Messages - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../css/admin.css">
    <style>
        .message-unread {
            background-color: #f0f7ff;
            font-weight: 500;
        }
        
        .message-read {
            opacity: 0.8;
        }
        
        .message-replied {
            opacity: 0.6;
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
                <h2>Contact Messages</h2>
            </div>

            <div class="admin-content">
                <?php if (isset($_GET['msg']) && $_GET['msg'] == 'deleted'): ?>
                    <div class="alert alert-success">Message deleted successfully!</div>
                <?php endif; ?>

                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Status</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Subject</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($messages as $msg): ?>
                            <tr class="message-<?php echo $msg['status']; ?>">
                                <td>
                                    <span class="badge bg-<?php 
                                        echo $msg['status'] == 'unread' ? 'danger' : 
                                            ($msg['status'] == 'read' ? 'warning' : 'success'); 
                                    ?>">
                                        <?php echo ucfirst($msg['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($msg['name']); ?></td>
                                <td><?php echo $msg['email']; ?></td>
                                <td><?php echo $msg['phone'] ?: '-'; ?></td>
                                <td><?php echo htmlspecialchars($msg['subject'] ?: 'No subject'); ?></td>
                                <td><?php echo date('M d, Y H:i', strtotime($msg['created_at'])); ?></td>
                                <td>
                                    <button class="btn btn-sm btn-info" onclick="viewMessage(<?php echo htmlspecialchars(json_encode($msg)); ?>)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <?php if ($msg['status'] == 'unread'): ?>
                                    <a href="?read=<?php echo $msg['id']; ?>" class="btn btn-sm btn-warning">
                                        <i class="fas fa-check"></i>
                                    </a>
                                    <?php endif; ?>
                                    <?php if ($msg['status'] != 'replied'): ?>
                                    <a href="?replied=<?php echo $msg['id']; ?>" class="btn btn-sm btn-success">
                                        <i class="fas fa-reply"></i>
                                    </a>
                                    <?php endif; ?>
                                    <a href="?delete=<?php echo $msg['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this message?')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <!-- Message Modal -->
    <div class="modal fade" id="messageModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Message Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <strong>From:</strong> <span id="msg_name"></span><br>
                        <strong>Email:</strong> <span id="msg_email"></span><br>
                        <strong>Phone:</strong> <span id="msg_phone"></span><br>
                        <strong>Subject:</strong> <span id="msg_subject"></span><br>
                        <strong>Date:</strong> <span id="msg_date"></span>
                    </div>
                    <hr>
                    <div class="mb-3">
                        <strong>Message:</strong>
                        <p id="msg_message" class="mt-2 p-3 bg-light rounded"></p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <a href="#" id="replyBtn" class="btn btn-primary" target="_blank">
                        <i class="fas fa-reply"></i> Reply via Email
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function viewMessage(msg) {
            document.getElementById('msg_name').textContent = msg.name;
            document.getElementById('msg_email').textContent = msg.email;
            document.getElementById('msg_phone').textContent = msg.phone || 'Not provided';
            document.getElementById('msg_subject').textContent = msg.subject || 'No subject';
            document.getElementById('msg_date').textContent = new Date(msg.created_at).toLocaleString();
            document.getElementById('msg_message').textContent = msg.message;
            
            document.getElementById('replyBtn').href = 'mailto:' + msg.email + '?subject=Re: ' + (msg.subject || 'Your inquiry');
            
            new bootstrap.Modal(document.getElementById('messageModal')).show();
        }
    </script>
</body>
</html>