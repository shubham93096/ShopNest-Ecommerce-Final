<?php // includes/footer.php ?>
<!-- ── Footer ─────────────────────────────────────────── -->
<footer class="footer">
  <div class="container">
    <div class="row g-4">
      <div class="col-lg-4">
        <span class="footer-brand">ShopNest</span>
        <p>Your premium cloud-powered shopping destination. Quality products, lightning-fast delivery, and unbeatable prices.</p>
        <div class="social-links mt-3">
          <a href="#" class="social-link"><i class="bi bi-facebook"></i></a>
          <a href="#" class="social-link"><i class="bi bi-twitter-x"></i></a>
          <a href="#" class="social-link"><i class="bi bi-instagram"></i></a>
          <a href="#" class="social-link"><i class="bi bi-linkedin"></i></a>
          <a href="#" class="social-link"><i class="bi bi-youtube"></i></a>
        </div>
      </div>
      <div class="col-sm-6 col-lg-2">
        <h6 class="footer-heading">Shop</h6>
        <ul class="footer-links">
          <li><a href="<?= APP_URL ?>/products/index.php">All Products</a></li>
          <li><a href="<?= APP_URL ?>/products/index.php?featured=1">Featured</a></li>
          <li><a href="<?= APP_URL ?>/products/index.php?sale=1">Sale</a></li>
          <li><a href="<?= APP_URL ?>/products/index.php">New Arrivals</a></li>
        </ul>
      </div>
      <div class="col-sm-6 col-lg-2">
        <h6 class="footer-heading">Account</h6>
        <ul class="footer-links">
          <li><a href="<?= APP_URL ?>/customer/profile.php">My Profile</a></li>
          <li><a href="<?= APP_URL ?>/customer/orders.php">My Orders</a></li>
          <li><a href="<?= APP_URL ?>/customer/wishlist.php">Wishlist</a></li>
          <li><a href="<?= APP_URL ?>/customer/cart.php">Cart</a></li>
        </ul>
      </div>
      <div class="col-sm-6 col-lg-2">
        <h6 class="footer-heading">Support</h6>
        <ul class="footer-links">
          <li><a href="#">Help Center</a></li>
          <li><a href="#">Track Order</a></li>
          <li><a href="#">Returns</a></li>
          <li><a href="#">Contact Us</a></li>
        </ul>
      </div>
      <div class="col-sm-6 col-lg-2">
        <h6 class="footer-heading">Company</h6>
        <ul class="footer-links">
          <li><a href="#">About Us</a></li>
          <li><a href="#">Privacy Policy</a></li>
          <li><a href="#">Terms of Service</a></li>
          <li><a href="#">Sitemap</a></li>
        </ul>
      </div>
    </div>

    <div class="footer-bottom">
      <p>© <?= date('Y') ?> ShopNest. All rights reserved. Powered by AWS.</p>
      <div class="d-flex align-items-center gap-3">
        <div class="d-flex align-items-center gap-2">
          <span style="font-size:.75rem;color:var(--text-dim);">Secure payments:</span>
          <span style="font-size:1.2rem;">💳 🏦 📱</span>
        </div>
        <a href="<?= APP_URL ?>/admin/login.php"
           style="font-size:.75rem;color:var(--text-dim);text-decoration:none;display:flex;align-items:center;gap:.3rem;opacity:.6;transition:opacity .2s;"
           onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='.6'">
          <i class="bi bi-shield-lock"></i> Admin Panel
        </a>
      </div>
    </div>
  </div>
</footer>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<!-- Custom JS -->
<script src="<?= APP_URL ?>/assets/js/main.js"></script>
</body>
</html>
