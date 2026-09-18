<?php
// customer/profile.php
define('PAGE_TITLE', 'My Profile');
require_once __DIR__ . '/../includes/auth_check.php';

$pdo = getDB();
$uid = $_SESSION[SESSION_USER]['id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id=?");
$stmt->execute([$uid]);
$profile = $stmt->fetch();

$errors = []; $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $tab = $_POST['tab'] ?? 'profile';

    if ($tab === 'profile') {
        $name    = trim($_POST['name'] ?? '');
        $phone   = trim($_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $city    = trim($_POST['city'] ?? '');
        $state   = trim($_POST['state'] ?? '');
        $pincode = trim($_POST['pincode'] ?? '');

        if (empty($name)) $errors[] = 'Name is required.';
        if (empty($errors)) {
            $pdo->prepare("UPDATE users SET name=?,phone=?,address=?,city=?,state=?,pincode=?,updated_at=NOW() WHERE id=?")
                ->execute([$name,$phone,$address,$city,$state,$pincode,$uid]);
            $_SESSION[SESSION_USER]['name'] = $name;
            $profile['name']   = $name;
            $profile['phone']  = $phone;
            $profile['address']= $address;
            $profile['city']   = $city;
            $profile['state']  = $state;
            $profile['pincode']= $pincode;
            $success = 'Profile updated successfully!';
        }
    } elseif ($tab === 'password') {
        $current = $_POST['current_password'] ?? '';
        $new     = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        if (!password_verify($current, $profile['password'])) {
            $errors[] = 'Current password is incorrect.';
        } elseif (strlen($new) < 6) {
            $errors[] = 'New password must be at least 6 characters.';
        } elseif ($new !== $confirm) {
            $errors[] = 'Passwords do not match.';
        } else {
            $hash = password_hash($new, PASSWORD_BCRYPT, ['cost'=>12]);
            $pdo->prepare("UPDATE users SET password=? WHERE id=?")->execute([$hash, $uid]);
            $success = 'Password changed successfully!';
        }
    }
}

// Order stats
$orderCount  = (int)$pdo->prepare("SELECT COUNT(*) FROM orders WHERE user_id=?")->execute([$uid]) ? $pdo->prepare("SELECT COUNT(*) FROM orders WHERE user_id=?") : 0;
$osStmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE user_id=?");
$osStmt->execute([$uid]);
$orderCount = (int)$osStmt->fetchColumn();
$wsStmt = $pdo->prepare("SELECT COUNT(*) FROM wishlist WHERE user_id=?");
$wsStmt->execute([$uid]);
$wishCount = (int)$wsStmt->fetchColumn();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
  <div class="breadcrumb-wrap">
    <nav><ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="<?= APP_URL ?>/">Home</a></li>
      <li class="breadcrumb-item active">My Profile</li>
    </ol></nav>
  </div>

  <div class="row g-4">
    <div class="col-lg-3">
      <div class="profile-sidebar">
        <div class="profile-avatar"><?= strtoupper(substr($profile['name'],0,1)) ?></div>
        <div class="profile-name"><?= e($profile['name']) ?></div>
        <div class="profile-email"><?= e($profile['email']) ?></div>
        <div class="d-flex justify-content-around mt-3 pt-3" style="border-top:1px solid var(--border-light);">
          <div class="text-center">
            <div style="font-weight:700;color:var(--primary-light);font-size:1.2rem;"><?= $orderCount ?></div>
            <div style="font-size:.72rem;color:var(--text-muted);">Orders</div>
          </div>
          <div class="text-center">
            <div style="font-weight:700;color:#f87171;font-size:1.2rem;"><?= $wishCount ?></div>
            <div style="font-size:.72rem;color:var(--text-muted);">Wishlist</div>
          </div>
        </div>
        <ul class="profile-nav">
          <li><a href="<?= APP_URL ?>/customer/profile.php" class="active"><i class="bi bi-person"></i> Profile</a></li>
          <li><a href="<?= APP_URL ?>/customer/orders.php"><i class="bi bi-bag-check"></i> My Orders</a></li>
          <li><a href="<?= APP_URL ?>/customer/wishlist.php"><i class="bi bi-heart"></i> Wishlist</a></li>
          <li><a href="<?= APP_URL ?>/customer/logout.php" style="color:#f87171;"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
        </ul>
      </div>
    </div>

    <div class="col-lg-9">
      <?php if($errors): ?>
      <div class="alert alert-danger mb-3">
        <?php foreach($errors as $er): ?><div><i class="bi bi-x-circle me-1"></i><?= e($er) ?></div><?php endforeach; ?>
      </div>
      <?php endif; ?>
      <?php if($success): ?>
      <div class="alert alert-success mb-3"><i class="bi bi-check-circle me-2"></i><?= e($success) ?></div>
      <?php endif; ?>

      <!-- Profile Form -->
      <div class="card mb-4">
        <div class="card-header"><i class="bi bi-person-circle me-2" style="color:var(--primary-light);"></i>Personal Information</div>
        <div class="card-body">
          <form method="POST">
            <?= csrfInput() ?>
            <input type="hidden" name="tab" value="profile">
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label">Full Name *</label>
                <input type="text" name="name" class="form-control" value="<?= e($profile['name']) ?>" required>
              </div>
              <div class="col-md-6">
                <label class="form-label">Email</label>
                <input type="email" class="form-control" value="<?= e($profile['email']) ?>" disabled>
                <div class="form-text">Email cannot be changed.</div>
              </div>
              <div class="col-md-6">
                <label class="form-label">Phone</label>
                <input type="tel" name="phone" class="form-control" value="<?= e($profile['phone']) ?>">
              </div>
              <div class="col-md-6">
                <label class="form-label">Member Since</label>
                <input type="text" class="form-control" value="<?= date('d M Y', strtotime($profile['created_at'])) ?>" disabled>
              </div>
              <div class="col-12">
                <label class="form-label">Address</label>
                <input type="text" name="address" class="form-control" placeholder="House no., Street, Area" value="<?= e($profile['address'] ?? '') ?>">
              </div>
              <div class="col-md-4">
                <label class="form-label">City</label>
                <input type="text" name="city" class="form-control" value="<?= e($profile['city'] ?? '') ?>">
              </div>
              <div class="col-md-4">
                <label class="form-label">State</label>
                <input type="text" name="state" class="form-control" value="<?= e($profile['state'] ?? '') ?>">
              </div>
              <div class="col-md-4">
                <label class="form-label">Pincode</label>
                <input type="text" name="pincode" class="form-control" maxlength="6" value="<?= e($profile['pincode'] ?? '') ?>">
              </div>
              <div class="col-12">
                <button type="submit" class="btn btn-primary">
                  <i class="bi bi-save me-2"></i>Save Changes
                </button>
              </div>
            </div>
          </form>
        </div>
      </div>

      <!-- Change Password -->
      <div class="card">
        <div class="card-header"><i class="bi bi-shield-lock me-2" style="color:var(--primary-light);"></i>Change Password</div>
        <div class="card-body">
          <form method="POST">
            <?= csrfInput() ?>
            <input type="hidden" name="tab" value="password">
            <div class="row g-3">
              <div class="col-md-4">
                <label class="form-label">Current Password</label>
                <input type="password" name="current_password" class="form-control" required>
              </div>
              <div class="col-md-4">
                <label class="form-label">New Password</label>
                <input type="password" name="new_password" class="form-control" placeholder="Min. 6 characters" required>
              </div>
              <div class="col-md-4">
                <label class="form-label">Confirm New Password</label>
                <input type="password" name="confirm_password" class="form-control" required>
              </div>
              <div class="col-12">
                <button type="submit" class="btn btn-outline-primary">
                  <i class="bi bi-key me-2"></i>Update Password
                </button>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
