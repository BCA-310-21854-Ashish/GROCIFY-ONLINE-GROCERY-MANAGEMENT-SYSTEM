<?php

session_start();
require_once 'config/db.php';
include 'partials/header.php';

$query = isset($_GET['q']) ? trim($_GET['q']) : '';
?>
<h1>Search Results</h1>
<?php if ($query): ?>
    <p>

Showing results for

<strong>

<?php echo htmlspecialchars($query); ?>

</strong>

</p>

<?php
    $sql = "SELECT * FROM products WHERE name LIKE ? OR description LIKE ? OR category LIKE ?";
    $stmt = $conn->prepare($sql);
    $searchTerm = "%$query%";
    $stmt->bind_param("sss", $searchTerm, $searchTerm, $searchTerm);
    $stmt->execute();
    $result = $stmt->get_result();
?>
<p class="text-muted">
<?php echo $result->num_rows; ?>
products found
</p>
<?php
    $sql = "SELECT * FROM products WHERE name LIKE ? OR description LIKE ? OR category LIKE ?";
    $stmt = $conn->prepare($sql);
    $searchTerm = "%$query%";
    $stmt->bind_param("sss", $searchTerm, $searchTerm, $searchTerm);
    $stmt->execute();
    $result = $stmt->get_result();
    ?>
    <div class="row g-4">
        <?php if ($result->num_rows > 0): ?>
            <?php while($product = $result->fetch_assoc()): ?>
                <div class="col-sm-6 col-md-4 col-lg-3">
                    <div class="card product-card shadow-sm h-100 position-relative">
                        <?php if(($product['discount'] ?? 0) > 0): ?>

<div class="badge bg-danger position-absolute"
     style="top:10px;left:10px;z-index:10;">

<?php echo $product['discount']; ?>% OFF

</div>

<?php endif; ?>
                        <img src="<?php echo htmlspecialchars($product['image']); ?>"
     class="card-img-top"
     alt="<?php echo htmlspecialchars($product['name']); ?>"
     style="height:220px;object-fit:cover;"
     onerror="this.src='https://via.placeholder.com/300x220?text=No+Image'">
                        <div class="card-body d-flex flex-column">
                            <span class="badge bg-success mb-2">
<?php echo htmlspecialchars($product['category']); ?>
</span>

<h5 class="card-title">
<?php echo htmlspecialchars($product['name']); ?>
</h5>
                            <p class="card-text text-muted small"><?php echo htmlspecialchars(substr($product['description'], 0, 50)) . '...'; ?></p>
                            <p class="text-success fw-bold fs-5 mt-auto">₹<?php echo number_format($product['price'], 2); ?></p>
                            <div class="d-grid gap-2">
                                <a href="cart_actions.php?action=add&id=<?php echo $product['id']; ?>&qty=1"
   class="btn btn-success">

🛒 Add to Cart

</a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12"><div class="alert alert-warning">No products found matching your search.</div></div>
        <?php endif; ?>
    </div>
<?php else: ?>
    <div class="alert alert-info">Please enter a search term.</div>
<?php endif; ?>
<?php include 'partials/footer.php'; ?>