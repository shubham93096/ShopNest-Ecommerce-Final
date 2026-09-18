<?php
// admin/categories/index.php
define('ADMIN_PAGE_TITLE', 'Categories');
require_once __DIR__ . '/../../admin/includes/header.php';

$pdo = getDB();
$errors = []; $success = '';

// Handle add
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    verifyCsrf();
    $action = $_POST['action'];
    $name   = trim($_POST['name'] ?? '');
    $desc   = trim($_POST['description'] ?? '');
    $slug   = slugify($name);

    if ($action === 'add') {
        if (empty($name)) { $errors[] = 'Category name is required.'; }
        else {
            $slugCheck = $pdo->prepare("SELECT id FROM categories WHERE slug=?");
            $slugCheck->execute([$slug]);
            if ($slugCheck->fetch()) $slug .= '-' . rand(100,999);
            $pdo->prepare("INSERT INTO categories (name,slug,description,status,created_at) VALUES (?,?,?,1,NOW())")->execute([$name,$slug,$desc]);
            $success = "Category \"$name\" added!";
        }
    } elseif ($action === 'edit') {
        $cid = (int)($_POST['category_id'] ?? 0);
        if ($cid && $name) {
            $pdo->prepare("UPDATE categories SET name=?,description=? WHERE id=?")->execute([$name,$desc,$cid]);
            $success = "Category updated!";
        }
    } elseif ($action === 'delete') {
        $cid = (int)($_POST['category_id'] ?? 0);
        if ($cid) {
            $cnt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE category_id=?");
            $cnt->execute([$cid]);
            if ($cnt->fetchColumn() > 0) $errors[] = 'Cannot delete: category has products.';
            else { $pdo->prepare("DELETE FROM categories WHERE id=?")->execute([$cid]); $success = 'Category deleted.'; }
        }
    }
}

$cats = $pdo->query("SELECT c.*,COUNT(p.id) AS product_count FROM categories c LEFT JOIN products p ON p.category_id=c.id GROUP BY c.id ORDER BY c.sort_order,c.name")->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <h2 style="color:var(--text);font-weight:700;margin:0;">Categories</h2>
  <button class="btn-admin-primary" data-bs-toggle="modal" data-bs-target="#addModal">
    <i class="bi bi-plus-lg"></i> Add Category
  </button>
</div>

<?php if($errors): ?><div class="alert alert-danger mb-3"><?php foreach($errors as $e): ?><div><?= e($e) ?></div><?php endforeach; ?></div><?php endif; ?>
<?php if($success): ?><div class="alert alert-success mb-3"><i class="bi bi-check-circle me-2"></i><?= e($success) ?></div><?php endif; ?>

<div class="admin-table-card">
  <div class="table-responsive">
    <table class="admin-table" id="cats-table">
      <thead><tr>
        <th>#</th><th>Name</th><th>Slug</th><th>Description</th><th>Products</th><th>Status</th><th>Actions</th>
      </tr></thead>
      <tbody>
        <?php foreach($cats as $c): ?>
        <tr>
          <td class="cell-muted"><?= $c['id'] ?></td>
          <td class="cell-primary"><?= e($c['name']) ?></td>
          <td style="font-family:monospace;font-size:.8rem;color:var(--text-dim);"><?= e($c['slug']) ?></td>
          <td style="color:var(--text-muted);max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= e(substr($c['description']??'',0,60)) ?></td>
          <td>
            <span style="color:var(--primary-light);font-weight:600;"><?= $c['product_count'] ?></span>
          </td>
          <td>
            <span class="status-badge <?= $c['status']?'active':'inactive' ?>">
              <span class="status-dot"></span><?= $c['status']?'Active':'Inactive' ?>
            </span>
          </td>
          <td>
            <div class="d-flex gap-1">
              <a href="<?= APP_URL ?>/products/index.php?category=<?= e($c['slug']) ?>" target="_blank" class="btn-icon view"><i class="bi bi-eye"></i></a>
              <button class="btn-icon edit" onclick="openEdit(<?= htmlspecialchars(json_encode($c)) ?>)" title="Edit"><i class="bi bi-pencil"></i></button>
              <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this category?')">
                <?= csrfInput() ?>
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="category_id" value="<?= $c['id'] ?>">
                <button type="submit" class="btn-icon delete"><i class="bi bi-trash"></i></button>
              </form>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Add Modal -->
<div class="modal fade" id="addModal" tabindex="-1">
  <div class="modal-dialog"><div class="modal-content">
    <div class="modal-header">
      <h5 class="modal-title">Add Category</h5>
      <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
    </div>
    <form method="POST">
      <?= csrfInput() ?>
      <input type="hidden" name="action" value="add">
      <div class="modal-body">
        <div class="mb-3"><label class="form-label">Name *</label><input type="text" name="name" class="form-control" required></div>
        <div class="mb-3"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="3"></textarea></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn-admin-outline" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn-admin-primary">Add Category</button>
      </div>
    </form>
  </div></div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
  <div class="modal-dialog"><div class="modal-content">
    <div class="modal-header">
      <h5 class="modal-title">Edit Category</h5>
      <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
    </div>
    <form method="POST">
      <?= csrfInput() ?>
      <input type="hidden" name="action" value="edit">
      <input type="hidden" name="category_id" id="edit-cat-id">
      <div class="modal-body">
        <div class="mb-3"><label class="form-label">Name *</label><input type="text" name="name" id="edit-cat-name" class="form-control" required></div>
        <div class="mb-3"><label class="form-label">Description</label><textarea name="description" id="edit-cat-desc" class="form-control" rows="3"></textarea></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn-admin-outline" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn-admin-primary">Save Changes</button>
      </div>
    </form>
  </div></div>
</div>

<script>
function openEdit(cat) {
  document.getElementById('edit-cat-id').value = cat.id;
  document.getElementById('edit-cat-name').value = cat.name;
  document.getElementById('edit-cat-desc').value = cat.description || '';
  new bootstrap.Modal(document.getElementById('editModal')).show();
}
</script>

<?php require_once __DIR__ . '/../../admin/includes/footer.php'; ?>
