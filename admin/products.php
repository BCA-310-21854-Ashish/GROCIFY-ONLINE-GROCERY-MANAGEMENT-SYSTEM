<?php

require_once '../config/db.php';
include 'header.php';

// Handle delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM products WHERE id = $id");
    header('Location: products.php?msg=deleted');
    exit();
}

// Handle add/edit form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $name = $_POST['name'];
    $description = $_POST['description'];
    $price = floatval($_POST['price']);
    $category = $_POST['category'];
    $image_url = $_POST['image_url'] ?: 'https://via.placeholder.com/300x200?text=Product';

    if ($id > 0) {
        $stmt = $conn->prepare("UPDATE products SET name=?, description=?, price=?, category=?, image_url=? WHERE id=?");
        $stmt->bind_param("ssdssi", $name, $description, $price, $category, $image_url, $id);
    } else {
        $stmt = $conn->prepare("INSERT INTO products (name, description, price, category, image_url) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssdss", $name, $description, $price, $category, $image_url);
    }
    $stmt->execute();
    $stmt->close();
    header('Location: products.php?msg=' . ($id ? 'updated' : 'added'));
    exit();
}

$msg = isset($_GET['msg']) ? $_GET['msg'] : '';
$editProduct = null;
if (isset($_GET['edit'])) {
    $id = intval($_GET['edit']);
    $result = $conn->query("SELECT * FROM products WHERE id = $id");
    $editProduct = $result->fetch_assoc();
}

// Fetch all products
$products = $conn->query("SELECT * FROM products ORDER BY id DESC");
?>

<h1 class="mb-4">Manage Products</h1>

<?php if ($msg == 'added'): ?><div class="alert alert-success">Product added successfully.</div>
<?php elseif ($msg == 'updated'): ?><div class="alert alert-success">Product updated successfully.</div>
<?php elseif ($msg == 'deleted'): ?><div class="alert alert-success">Product deleted.</div>
<?php endif; ?>

<div class="row">
    <div class="col-md-4">
        <div class="card">
            <div class="card-header"><?php echo $editProduct ? 'Edit Product' : 'Add New Product'; ?></div>
            <div class="card-body">
                <form method="post">
                    <?php if ($editProduct): ?>
                        <input type="hidden" name="id" value="<?php echo $editProduct['id']; ?>">
                    <?php endif; ?>
                    <div class="mb-3">
                        <label>Name</label>
                        <input type="text" name="name" class="form-control" value="<?php echo $editProduct['name'] ?? ''; ?>" required>
                    </div>
                    <div class="mb-3">
                        <label>Description</label>
                        <textarea name="description" class="form-control" rows="2"><?php echo $editProduct['description'] ?? ''; ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label>Price (₹)</label>
                        <input type="number" step="0.01" name="price" class="form-control" value="<?php echo $editProduct['price'] ?? ''; ?>" required>
                    </div>
                    <div class="mb-3">
                        <label>Category</label>
                        <input type="text" name="category" class="form-control" value="<?php echo $editProduct['category'] ?? ''; ?>">
                    </div>
                    <div class="mb-3">
                        <label>Image URL</label>
                        <input type="text" name="image_url" class="form-control" value="<?php echo $editProduct['image_url'] ?? ''; ?>">
                    </div>
                    <button type="submit" class="btn btn-primary"><?php echo $editProduct ? 'Update' : 'Add'; ?></button>
                    <?php if ($editProduct): ?>
                        <a href="products.php" class="btn btn-secondary">Cancel</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <table class="table table-bordered">
            <thead>
                <tr><th>ID</th><th>Image</th><th>Name</th><th>Price</th><th>Category</th><th>Actions</th></tr>
            </thead>
            <tbody>
                <?php while($p = $products->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $p['id']; ?></td>
                    <td><img src="<?php echo $p['image_url']; ?>" width="50" height="50" style="object-fit:cover;"></td>
                    <td><?php echo htmlspecialchars($p['name']); ?></td>
                    <td>₹<?php echo number_format($p['price'], 2); ?></td>
                    <td><?php echo $p['category']; ?></td>
                    <td>
                        <a href="?edit=<?php echo $p['id']; ?>" class="btn btn-sm btn-warning">Edit</a>
                        <a href="?delete=<?php echo $p['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this product?')">Delete</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'footer.php'; ?>