<?php

require_once '../config/db.php';
include 'header.php';

$msg = '';

// Delete image
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $pid = intval($_GET['pid'] ?? 0);
    $conn->query("DELETE FROM product_gallery WHERE id=$id");
    header('Location: product_gallery.php?msg=deleted&pid='.$pid); exit();
}
if (isset($_GET['msg'])) $msg = $_GET['msg']==='deleted' ? 'Image deleted.' : ($_GET['msg']==='added' ? 'Images added!' : '');

// Add gallery images
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $pid  = intval($_POST['product_id']);
    $urls = array_filter(array_map('trim', explode("\n", $_POST['image_urls'])));
    $sort = 0;
    foreach($urls as $url) {
        if (!empty($url)) {
            $safeUrl = $conn->real_escape_string($url);
            $conn->query("INSERT INTO product_gallery (product_id, image_url, sort_order) VALUES ($pid, '$safeUrl', $sort)");
            $sort++;
        }
    }
    header('Location: product_gallery.php?msg=added&pid='.$pid); exit();
}

$selPid   = isset($_GET['pid']) ? intval($_GET['pid']) : 0;
$products = $conn->query("SELECT id, name FROM products ORDER BY name");
if(!$products){ die("SQL Error: ".$conn->error); }
$gallery  = [];
$selProduct = null;
if ($selPid) {
    $res = $conn->query("SELECT * FROM product_gallery WHERE product_id=$selPid ORDER BY sort_order ASC");
    if($res){ while($row=$res->fetch_assoc()) $gallery[] = $row; }
    $sp = $conn->query("SELECT name FROM products WHERE id=$selPid");
    $selProduct = $sp ? $sp->fetch_assoc() : null;
}
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold mb-0">🖼️ Product Gallery</h2>
    <?php if($selProduct): ?>
    <span class="badge bg-primary fs-6"><?php echo htmlspecialchars($selProduct['name']); ?></span>
    <?php endif; ?>
</div>
<?php if ($msg): ?><div class="alert alert-success"><?php echo $msg; ?></div><?php endif; ?>

<div class="row g-4">
    <div class="col-md-4">
        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-header fw-bold">Select Product</div>
            <div class="card-body">
                <form method="GET">
                    <select name="pid" class="form-select mb-3" onchange="this.form.submit()">
                        <option value="">-- Choose product --</option>
                        <?php while($p=$products->fetch_assoc()): ?>
                        <option value="<?php echo $p['id']; ?>" <?php echo $selPid==$p['id']?'selected':''; ?>>
                            <?php echo htmlspecialchars($p['name']); ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </form>
                <?php if ($selPid): ?>
                <hr>
                <h6 class="fw-bold">➕ Add Images</h6>
                <p class="text-muted small">Enter one image URL per line</p>
                <form method="POST">
                    <input type="hidden" name="product_id" value="<?php echo $selPid; ?>">
                    <textarea name="image_urls" class="form-control mb-3" rows="5"
                        placeholder="https://example.com/image1.jpg&#10;https://example.com/image2.jpg"></textarea>
                    <button type="submit" class="btn btn-primary w-100">Add Images</button>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <?php if ($selPid && !empty($gallery)): ?>
        <div class="row g-3">
        <?php foreach($gallery as $i=>$img): ?>
        <div class="col-6 col-md-4">
            <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
                <img src="<?php echo htmlspecialchars($img['image_url']); ?>" 
                     class="card-img-top" style="height:140px;object-fit:cover;"
                     onerror="this.src='https://via.placeholder.com/300x140?text=Invalid+URL'">
                <div class="card-body p-2 d-flex justify-content-between align-items-center">
                    <span class="text-muted small">#<?php echo $i+1; ?></span>
                    <a href="?delete=<?php echo $img['id']; ?>&pid=<?php echo $selPid; ?>" 
                       class="btn btn-sm btn-outline-danger"
                       onclick="return confirm('Remove this image?')">🗑</a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        </div>
        <?php elseif ($selPid): ?>
        <div class="alert alert-info rounded-4">No gallery images yet. Add image URLs using the form.</div>
        <?php else: ?>
        <div class="alert alert-secondary rounded-4">Select a product to manage its gallery images.</div>
        <?php endif; ?>
    </div>
</div>

<?php include 'footer.php'; ?>
