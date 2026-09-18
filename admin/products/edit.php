<?php
// admin/products/edit.php
define('ADMIN_PAGE_TITLE', 'Edit Product');
require_once __DIR__ . '/../../admin/includes/header.php';

$pdo = getDB();
$id  = (int)($_GET['id'] ?? 0);
if (!$id) redirect(APP_URL . '/admin/products/index.php');

$product = $pdo->prepare("SELECT * FROM products WHERE id=?");
$product->execute([$id]);
$product = $product->fetch();
if (!$product) redirect(APP_URL . '/admin/products/index.php');

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

    if (empty($name))  $errors[] = 'Product name is required.';
    if (!$catId)       $errors[] = 'Category is required.';
    if ($price <= 0)   $errors[] = 'Valid price is required.';

    // Handle new images
    $existingImgs = json_decode($product['images'] ?? '[]', true) ?: [];
    if (!empty($_FILES['images']['name'][0])) {
        foreach($_FILES['images']['name'] as $i=>$fname) {
            if ($_FILES['images']['error'][$i] === UPLOAD_ERR_OK) {
                $file = ['name'=>$fname,'tmp_name'=>$_FILES['images']['tmp_name'][$i],'type'=>$_FILES['images']['type'][$i],'size'=>$_FILES['images']['size'][$i],'error'=>$_FILES['images']['error'][$i]];
                $result = uploadProductImage($file);
                if ($result) $existingImgs[] = $result;
            }
        }
    }
    $imagesJson = !empty($existingImgs) ? json_encode($existingImgs) : null;

    if (empty($errors)) {
        $pdo->prepare("UPDATE products SET category_id=?,name=?,description=?,short_desc=?,sku=?,price=?,sale_price=?,stock=?,images=?,brand=?,weight=?,is_featured=?,status=?,updated_at=NOW() WHERE id=?")
            ->execute([$catId,$name,$desc,$shortDesc,$sku,$price,$salePrice,$stock,$imagesJson,$brand,$weight,$featured,$status,$id]);
        setFlash('success', "Product updated successfully!");
        redirect(APP_URL . '/admin/products/edit.php?id=' . $id);
    }
}

$imgs = getProductImages($product);
?>

<div class="d-flex align-items-center gap-3 mb-4">
  <a href="<?= APP_URL ?>/admin/products/index.php" class="btn-icon" style="width:36px;height:36px;"><i class="bi bi-arrow-left"></i></a>
  <h2 style="color:var(--text);font-weight:700;margin:0;">Edit: <?= e($product['name']) ?></h2>
</div>

<?php if($errors): ?>
<div class="alert alert-danger mb-4">
  <?php foreach($errors as $err): ?><div><i class="bi bi-x-circle me-1"></i><?= e($err) ?></div><?php endforeach; ?>
</div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data">
  <?= csrfInput() ?>
  <div class="row g-4">
    <div class="col-lg-8">
      <div class="admin-form-card mb-4">
        <div class="admin-form-section">
          <div class="admin-form-section-title"><i class="bi bi-info-circle"></i> Basic Information</div>
          <div class="row g-3">
            <div class="col-12">
              <label class="form-label">Product Name *</label>
              <input type="text" name="name" class="form-control" value="<?= e($_POST['name']??$product['name']) ?>" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Category *</label>
              <select name="category_id" class="form-select" required>
                <?php foreach($cats as $c): ?>
                <option value="<?= $c['id'] ?>" <?= (($_POST['category_id']??$product['category_id'])==$c['id'])?'selected':'' ?>><?= e($c['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Slug</label>
              <input type="text" class="form-control" value="<?= e($product['slug']) ?>" disabled>
            </div>
            <div class="col-12">
              <label class="form-label">Short Description</label>
              <input type="text" name="short_desc" class="form-control" value="<?= e($_POST['short_desc']??$product['short_desc']) ?>">
            </div>
            <div class="col-12">
              <label class="form-label">Full Description</label>
              <textarea name="description" class="form-control" rows="6"><?= e($_POST['description']??$product['description']) ?></textarea>
            </div>
          </div>
        </div>
        <div class="admin-form-section">
          <div class="admin-form-section-title"><i class="bi bi-currency-rupee"></i> Pricing & Stock</div>
          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label">Price *</label>
              <input type="number" name="price" class="form-control" min="0" step="0.01" value="<?= e($_POST['price']??$product['price']) ?>" required>
            </div>
            <div class="col-md-4">
              <label class="form-label">Sale Price</label>
              <input type="number" name="sale_price" class="form-control" min="0" step="0.01" value="<?= e($_POST['sale_price']??$product['sale_price']) ?>">
            </div>
            <div class="col-md-4">
              <label class="form-label">Stock</label>
              <input type="number" name="stock" class="form-control" min="0" value="<?= e($_POST['stock']??$product['stock']) ?>">
            </div>
            <div class="col-md-4">
              <label class="form-label">SKU</label>
              <input type="text" name="sku" class="form-control" value="<?= e($_POST['sku']??$product['sku']) ?>">
            </div>
            <div class="col-md-4">
              <label class="form-label">Brand</label>
              <input type="text" name="brand" class="form-control" value="<?= e($_POST['brand']??$product['brand']) ?>">
            </div>
            <div class="col-md-4">
              <label class="form-label">Weight (kg)</label>
              <input type="number" name="weight" class="form-control" step="0.01" value="<?= e($_POST['weight']??$product['weight']) ?>">
            </div>
          </div>
        </div>
      </div>

      <!-- Images -->
      <div class="admin-form-card">
        <div class="admin-form-section-title" style="margin-bottom:1rem;"><i class="bi bi-images"></i> Images</div>
        <?php if($imgs): ?>
        <div class="d-flex gap-2 flex-wrap mb-3">
          <?php foreach($imgs as $img): ?>
          <div class="image-preview-item">
            <img src="<?= e(getProductImageUrl($img)) ?>" alt="" onerror="this.src='<?= APP_URL ?>/assets/images/no-image.png'">
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <div class="image-upload-area">
          <input type="file" name="images[]" class="img-upload-input" multiple accept="image/*" data-preview="new-preview" style="display:none;">
          <div class="image-upload-icon">📎</div>
          <p style="color:var(--text-muted);">Upload additional images</p>
        </div>
        <div id="new-preview" class="image-preview-grid mt-2"></div>
      </div>
    </div>

    <div class="col-lg-4">
      <div class="admin-form-card mb-4">
        <div class="admin-form-section-title" style="margin-bottom:1rem;"><i class="bi bi-toggles"></i> Settings</div>
        <div class="mb-3 d-flex align-items-center justify-content-between">
          <label style="color:var(--text);">Active</label>
          <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" name="status" <?= (($_POST['status']??$product['status'])==1)?'checked':'' ?>>
          </div>
        </div>
        <div class="mb-3 d-flex align-items-center justify-content-between">
          <label style="color:var(--text);">⭐ Featured</label>
          <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" name="is_featured" <?= (($_POST['is_featured']??$product['is_featured'])==1||isset($_POST['is_featured']))?'checked':'' ?>>
          </div>
        </div>
        <div class="mt-2 pt-2" style="border-top:1px solid var(--admin-border-lg);">
          <div style="font-size:.78rem;color:var(--text-dim);">Created: <?= date('d M Y', strtotime($product['created_at'])) ?></div>
        </div>
      </div>
      <div class="admin-form-card">
        <div class="d-grid gap-2">
          <button type="submit" class="btn-admin-primary py-2 justify-content-center"><i class="bi bi-save2"></i> Update Product</button>
          <a href="<?= APP_URL ?>/products/detail.php?slug=<?= e($product['slug']) ?>" target="_blank" class="btn-admin-outline py-2 justify-content-center text-center"><i class="bi bi-eye me-1"></i>View on Store</a>
          <a href="<?= APP_URL ?>/admin/products/index.php" class="btn-admin-outline py-2 justify-content-center text-center">Cancel</a>
        </div>
      </div>
    </div>
  </div>
</form>

<?php require_once __DIR__ . '/../../admin/includes/footer.php'; ?>
