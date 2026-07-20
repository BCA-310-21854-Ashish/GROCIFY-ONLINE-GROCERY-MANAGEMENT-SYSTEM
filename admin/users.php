<?php

require_once '../config/db.php';
include 'header.php';

$users = $conn->query("SELECT id, username, email, is_admin, created_at FROM users ORDER BY id DESC");
?>

<h1 class="mb-4">Manage Users</h1>

<table class="table table-bordered">
    <thead>
        <tr><th>ID</th><th>Username</th><th>Email</th><th>Admin</th><th>Joined</th><th>Actions</th></tr>
    </thead>
    <tbody>
        <?php while($u = $users->fetch_assoc()): ?>
        <tr>
            <td><?php echo $u['id']; ?></td>
            <td><?php echo htmlspecialchars($u['username']); ?></td>
            <td><?php echo htmlspecialchars($u['email']); ?></td>
            <td><?php echo $u['is_admin'] ? 'Yes' : 'No'; ?></td>
            <td><?php echo date('M d, Y', strtotime($u['created_at'])); ?></td>
            <td>
                <?php if ($u['id'] != $_SESSION['user_id']): ?>
                <a href="toggle_admin.php?id=<?php echo $u['id']; ?>" class="btn btn-sm btn-warning">Toggle Admin</a>
                <?php else: ?>
                <span class="text-muted">Current User</span>
                <?php endif; ?>
            </td>
        </tr>
        <?php endwhile; ?>
    </tbody>
</table>

<?php include 'footer.php'; ?>