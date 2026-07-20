<?php

require_once '../config/db.php';
include 'header.php';

// Handle status update
if (isset($_POST['update_status'])) {
    $id = intval($_POST['feedback_id']);
    $status = $_POST['status'];
    $stmt = $conn->prepare("UPDATE feedback SET status=? WHERE id=?");
    $stmt->bind_param("si", $status, $id);
    $stmt->execute();
    $stmt->close();
    header('Location: feedback.php?msg=updated');
    exit();
}

// Handle delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM feedback WHERE id=$id");
    header('Location: feedback.php?msg=deleted');
    exit();
}

$feedback = $conn->query("SELECT f.*, u.username FROM feedback f JOIN users u ON f.user_id = u.id ORDER BY f.created_at DESC");
?>

<h1 class="mb-4">Customer Feedback</h1>

<?php if (isset($_GET['msg'])): ?>
    <div class="alert alert-success">Feedback updated successfully.</div>
<?php endif; ?>

<table class="table table-bordered">
    <thead>
        <tr>
            <th>ID</th><th>User</th><th>Subject</th><th>Rating</th><th>Date</th><th>Status</th><th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php while($f = $feedback->fetch_assoc()): ?>
        <tr>
            <td><?php echo $f['id']; ?></td>
            <td><?php echo htmlspecialchars($f['username']); ?></td>
            <td><?php echo htmlspecialchars($f['subject']); ?></td>
            <td><?php echo $f['rating']; ?> ★</td>
            <td><?php echo date('M d, Y', strtotime($f['created_at'])); ?></td>
            <td>
                <span class="badge bg-<?php 
                    echo $f['status']=='Pending'?'warning':($f['status']=='Reviewed'?'info':'success'); 
                ?>"><?php echo $f['status']; ?></span>
            </td>
            <td>
                <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#viewModal<?php echo $f['id']; ?>">View</button>
                <a href="?delete=<?php echo $f['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this feedback?')">Delete</a>
                <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#statusModal<?php echo $f['id']; ?>">Status</button>
            </td>
        </tr>
        <!-- View Modal -->
        <div class="modal fade" id="viewModal<?php echo $f['id']; ?>" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header"><h5>Feedback Details</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                    <div class="modal-body">
                        <p><strong>Subject:</strong> <?php echo htmlspecialchars($f['subject']); ?></p>
                        <p><strong>Message:</strong> <?php echo nl2br(htmlspecialchars($f['message'])); ?></p>
                        <p><strong>Rating:</strong> <?php echo $f['rating']; ?>/5</p>
                    </div>
                </div>
            </div>
        </div>
        <!-- Status Modal -->
        <div class="modal fade" id="statusModal<?php echo $f['id']; ?>" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header"><h5>Update Status</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                    <form method="post">
                        <div class="modal-body">
                            <input type="hidden" name="feedback_id" value="<?php echo $f['id']; ?>">
                            <select name="status" class="form-select">
                                <option <?php echo $f['status']=='Pending'?'selected':''; ?>>Pending</option>
                                <option <?php echo $f['status']=='Reviewed'?'selected':''; ?>>Reviewed</option>
                                <option <?php echo $f['status']=='Resolved'?'selected':''; ?>>Resolved</option>
                            </select>
                        </div>
                        <div class="modal-footer">
                            <button type="submit" name="update_status" class="btn btn-primary">Update</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php endwhile; ?>
    </tbody>
</table>

<?php include 'footer.php'; ?>