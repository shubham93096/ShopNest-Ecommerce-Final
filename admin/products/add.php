<?php
// admin/products/add.php
define('ADMIN_PAGE_TITLE', 'Add Product');
require_once __DIR__ . '/../../admin/includes/header.php';

$pdo  = getDB();
$cats = $pdo->query("SELECT id,name FROM categories WHERE status=1 ORDER BY name")->fetchAll();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $name      = trim($_POST['name'] ?? '');
    $catId     = (int)($_POST['category_id'] ?? 0);
    $price     = (float)($_POST['price'] ?? 0);
    $salePrice = $_POST['sale_price']!=='' ? (float)$_POST['sale_price'] : null;
    $stock     = (int)($_POST['stock'] ?? 0);
    $desc      = trim($_POST['description'] ?? '');
    $shortDesc = trim($_POST['short_desc'] ?? '');
    $sku       = trim($_POST['sku'] ?? '') ?: null;
    $brand     = trim($_POST['brand'] ?? '') ?: null;
    $weight    = $_POST['weight']!=='' ? (float)$_POST['weight'] : null;
    $featured  = isset($_POST['is_featured']) ? 1 : 0;
    $status    = isset($_POST['status']) ? 1 : 0;
    $slug      = slugify($name);

    if (empty($name))  $errors[] = 'Product name is required.';
    if (!$catId)       $errors[] = 'Category is required.';
    if ($price <= 0)   $errors[] = 'Valid price is required.';

    // Unique slug
    if (empty($errors)) {
        $slugCheck = $pdo->prepare("SELECT id FROM products WHERE slug=?");
        $slugCheck->execute([$slug]);
        if ($slugCheck->fetch()) $slug .= '-' . time();
    }

    // Handle images
    $imageFiles = [];
    if (!empty($_FILES['images']['name'][0])) {
        foreach($_FILES['images']['name'] as $i=>$fname) {
            if ($_FILES['images']['error'][$i] === UPLOAD_ERR_OK) {
                $file = [
                    'name'     => $fname,
                    'tmp_name' => $_FILES['images']['tmp_name'][$i],
                    'type'     => $_FILES['images']['type'][$i],
                    'size'     => $_FILES['images']['size'][$i],
                    'error'    => $_FILES['images']['error'][$i],
                ];
                $result = uploadProductImage($file);
                if ($result) $imageFiles[] = $result;
            }
        }
    }
    $imagesJson = !empty($imageFiles) ? json_encode($imageFiles) : null;

    if (empty($errors)) {
        $stmt = $pdo->prepare("INSERT INTO products (category_id,name,slug,description,short_desc,sku,price,sale_price,stock,images,brand,weight,is_featured,status,created_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,NOW())");
        $stmt->execute([$catId,$name,$slug,$desc,$shortDesc,$sku,$price,$salePrice,$stock,$imagesJson,$brand,$weight,$featured,$status]);
        $pid = $pdo->lastInsertId();
        if ($stock > 0) {
            $pdo->prepare("INSERT INTO inventory (product_id,quantity,type,note,created_at) VALUES (?,?,'in','Initial stock',NOW())")->execute([$pid,$stock]);
        }
        setFlash('success', "Product \"$name\" added successfully!");
        redirect(APP_URL . '/admin/products/index.php');
    }
}
?>

<div class="d-flex align-items-center gap-3 mb-4">
  <a href="<?= APP_URL ?>/admin/products/index.php" class="btn-icon" style="width:36px;height:36px;"><i class="bi bi-arrow-left"></i></a>
  <h2 style="color:var(--text);font-weight:700;margin:0;">Add New Product</h2>
</div>

<?php if($errors): ?>
<div class="alert alert-danger mb-4">
  <?php foreach($errors as $e): ?><div><i class="bi bi-x-circle me-1"></i><?= e($e) ?></div><?php endforeach; ?>
</div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data" novalidate>
  <?= csrfInput() ?>
  <div class="row g-4">
    <div class="col-lg-8">
      <div class="admin-form-card mb-4">
        <div class="admin-form-section">
          <div class="admin-form-section-title"><i class="bi bi-info-circle"></i> Basic Information</div>
          <div class="row g-3">
            <div class="col-12">
              <label class="form-label">Product Name *</label>
              <input type="text" id="product-name" name="name" class="form-control" placeholder="e.g. Wireless Bluetooth Headphones" value="<?= e($_POST['name']??'') ?>" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Slug (auto-generated)</label>
              <input type="text" id="product-slug" class="form-control" placeholder="product-slug" readonly>
            </div>
            <div class="col-md-6">
              <label class="form-label">Category *</label>
              <select name="category_id" class="form-select" required>
                <option value="">Select category…</option>
                <?php foreach($cats as $c): ?>
                <option value="<?= $c['id'] ?>" <?= ($_POST['category_id']??'')==$c['id']?'selected':'' ?>><?= e($c['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-12">
              <label class="form-label">Short Description</label>
              <input type="text" name="short_desc" class="form-control" maxlength="500" placeholder="1-2 sentences summary shown on product card" value="<?= e($_POST['short_desc']??'') ?>">
            </div>
            <div class="col-12">
              <label class="form-label">Full Description</label>
              <textarea name="description" class="form-control" rows="6" placeholder="Detailed product description…"><?= e($_POST['description']??'') ?></textarea>
            </div>
          </div>
        </div>

        <div class="admin-form-section">
          <div class="admin-form-section-title"><i class="bi bi-currency-rupee"></i> Pricing & Stock</div>
          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label">Price (₹) *</label>
              <input type="number" name="price" class="form-control" min="0" step="0.01" placeholder="0.00" value="<?= e($_POST['price']??'') ?>" required>
            </div>
            <div class="col-md-4">
              <label class="form-label">Sale Price (₹)</label>
              <input type="number" name="sale_price" class="form-control" min="0" step="0.01" placeholder="Leave blank if no sale" value="<?= e($_POST['sale_price']??'') ?>">
            </div>
            <div class="col-md-4">
              <label class="form-label">Stock Quantity</label>
              <input type="number" name="stock" class="form-control" min="0" value="<?= e($_POST['stock']??0) ?>">
            </div>
          </div>
        </div>

        <div class="admin-form-section">
          <div class="admin-form-section-title"><i class="bi bi-upc-scan"></i> Product Details</div>
          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label">SKU</label>
              <input type="text" name="sku" class="form-control" placeholder="e.g. ELEC-001" value="<?= e($_POST['sku']??'') ?>">
            </div>
            <div class="col-md-4">
              <label class="form-label">Brand</label>
              <input type="text" name="brand" class="form-control" placeholder="e.g. Samsung" value="<?= e($_POST['brand']??'') ?>">
            </div>
            <div class="col-md-4">
              <label class="form-label">Weight (kg)</label>
              <input type="number" name="weight" class="form-control" min="0" step="0.01" placeholder="0.00" value="<?= e($_POST['weight']??'') ?>">
            </div>
          </div>
        </div>
      </div>

      <!-- Images -->
      <div class="admin-form-card">
        <div class="admin-form-section-title" style="margin-bottom:1rem;"><i class="bi bi-images"></i> Product Images</div>
        <div class="image-upload-area" id="image-upload-area">
          <input type="file" name="images[]" id="image-input" class="img-upload-input" multiple accept="image/*" data-preview="image-preview-grid" style="display:none;">
          <div class="image-upload-icon">🖼️</div>
          <p style="color:var(--text-muted);margin-bottom:.5rem;">Drag & drop images or click to upload</p>
          <small style="color:var(--text-dim);">JPG, PNG, WEBP — max 5MB each</small>
        </div>
        <div id="image-preview-grid" class="image-preview-grid mt-2"></div>
      </div>
    </div>

    <div class="col-lg-4">
      <div class="admin-form-card mb-4">
        <div class="admin-form-section-title" style="margin-bottom:1rem;"><i class="bi bi-toggles"></i> Settings</div>
        <div class="mb-3 d-flex align-items-center justify-content-between">
          <label class="form-check-label" style="color:var(--text);">Active / Published</label>
          <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" name="status" id="status" <?= (($_POST['status']??1)==1)?'checked':'' ?>>
          </div>
        </div>
        <div class="mb-3 d-flex align-items-center justify-content-between">
          <label class="form-check-label" style="color:var(--text);">⭐ Featured Product</label>
          <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" name="is_featured" id="is_featured" <?= isset($_POST['is_featured'])?'checked':'' ?>>
          </div>
        </div>
      </div>

      <div class="admin-form-card">
        <div class="d-grid gap-2">
          <button type="submit" class="btn-admin-primary py-2 justify-content-center">
            <i class="bi bi-save2"></i> Save Product
          </button>
          <a href="<?= APP_URL ?>/admin/products/index.php" class="btn-admin-outline py-2 justify-content-center text-center">Cancel</a>
        </div>
      </div>
    </div>
  </div>
</form>

<?php require_once __DIR__ . '/../../admin/includes/footer.php'; ?>
