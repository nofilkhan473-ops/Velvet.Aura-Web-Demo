<?php
$page_title = 'Contact Messages';
require_once 'includes/header.php';
require_once '../backend/config/database.php';

// Mark message as read
if(isset($_GET['read'])) {
    $id = (int)$_GET['read'];
    mysqli_query($conn, "UPDATE contacts SET is_read = 1 WHERE id = $id");
}

// Delete message
if(isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    mysqli_query($conn, "DELETE FROM contacts WHERE id = $id");
    echo "<script>showNotification('Message deleted successfully!');</script>";
}

// View single message
if(isset($_GET['view'])) {
    $id = (int)$_GET['view'];
    $message = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM contacts WHERE id = $id"));
    
    if($message && $message['is_read'] == 0) {
        mysqli_query($conn, "UPDATE contacts SET is_read = 1 WHERE id = $id");
    }
}
?>

<style>
    .message-detail {
        background: #faf9f8;
        border-radius: 16px;
        padding: 25px;
        margin-top: 20px;
    }
    .badge-unread {
        background: #ff6b6b;
        color: white;
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 11px;
    }
    .badge-read {
        background: #4CAF50;
        color: white;
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 11px;
    }
    .btn-reply {
        background: #1f1511;
        color: white;
        padding: 10px 25px;
        border-radius: 40px;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    .btn-reply:hover {
        background: #D4B5A7;
        color: #1f1511;
    }
</style>

<div class="table-container">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
        <h2 style="margin: 0;">Contact Messages</h2>
        <div>
            <span class="badge-unread" style="margin-right: 10px;">
                <?php 
                    $unread = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM contacts WHERE is_read = 0"));
                    echo ($unread ? $unread['count'] : 0) . ' Unread';
                ?>
            </span>
        </div>
    </div>
    
    <?php if(isset($message)): ?>
        <!-- Single Message View -->
        <div class="message-detail">
            <a href="contacts.php" class="btn-back" style="display: inline-block; margin-bottom: 20px;">
                <i class="fa-solid fa-arrow-left"></i> Back to Messages
            </a>
            <div class="row">
                <div class="col-md-8">
                    <h4><?php echo htmlspecialchars($message['subject']); ?></h4>
                    <p><strong>From:</strong> <?php echo htmlspecialchars($message['name']); ?></p>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($message['email']); ?></p>
                    <p><strong>Phone:</strong> <?php echo $message['phone'] ? htmlspecialchars($message['phone']) : 'Not provided'; ?></p>
                    <p><strong>Date:</strong> <?php echo date('F j, Y, g:i a', strtotime($message['created_at'])); ?></p>
                </div>
                <div class="col-md-4" style="text-align: right;">
                    <a href="mailto:<?php echo $message['email']; ?>" class="btn-reply">
                        <i class="fa-regular fa-reply"></i> Reply via Email
                    </a>
                </div>
            </div>
            <div class="message-content" style="background: white; padding: 20px; border-radius: 12px; margin-top: 15px;">
                <strong>Message:</strong>
                <p style="margin-top: 10px;"><?php echo nl2br(htmlspecialchars($message['message'])); ?></p>
            </div>
            <div style="margin-top: 20px;">
                <a href="contacts.php?delete=<?php echo $message['id']; ?>" onclick="confirmDelete('contacts.php?delete=<?php echo $message['id']; ?>')" class="btn-delete">
                    <i class="fa-solid fa-trash"></i> Delete Message
                </a>
            </div>
        </div>
    <?php else: ?>
        <!-- All Messages List -->
        <table class="table table-hover">
            <thead>
                <tr><th>ID</th><th>Name</th><th>Email</th><th>Subject</th><th>Status</th><th>Date</th><th>Actions</th></tr>
            </thead>
            <tbody>
                <?php
                $messages = mysqli_query($conn, "SELECT * FROM contacts ORDER BY created_at DESC");
                if(mysqli_num_rows($messages) > 0):
                while($msg = mysqli_fetch_assoc($messages)):
                ?>
                <tr>
                    <td><?php echo $msg['id']; ?></td>
                    <td><strong><?php echo htmlspecialchars($msg['name']); ?></strong></td>
                    <td><?php echo htmlspecialchars($msg['email']); ?></td>
                    <td><?php echo htmlspecialchars(substr($msg['subject'], 0, 30)); ?></td>
                    <td>
                        <?php if($msg['is_read'] == 0): ?>
                            <span class="badge-unread">Unread</span>
                        <?php else: ?>
                            <span class="badge-read">Read</span>
                        <?php endif; ?>
                    </div></td>
                    <td><?php echo date('M d, Y', strtotime($msg['created_at'])); ?></td>
                    <td>
                        <a href="contacts.php?view=<?php echo $msg['id']; ?>" class="btn-edit"><i class="fa-regular fa-eye"></i> View</a>
                        <a href="javascript:void(0)" onclick="confirmDelete('contacts.php?delete=<?php echo $msg['id']; ?>')" class="btn-delete"><i class="fa-solid fa-trash"></i> Del</a>
                    </div>
                </tr>
                <?php 
                endwhile;
                else:
                ?>
                <tr><td colspan="7" style="text-align: center;">No messages yet!</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>