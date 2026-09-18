/* ============================================================
   ShopNest — Customer JS
   ============================================================ */

'use strict';

// ── Cart API ─────────────────────────────────────────────────
const Cart = {
  async add(productId, qty = 1) {
    return this._request('add', { product_id: productId, quantity: qty });
  },
  async update(productId, qty) {
    return this._request('update', { product_id: productId, quantity: qty });
  },
  async remove(productId) {
    return this._request('remove', { product_id: productId });
  },
  async _request(action, data) {
    try {
      const baseUrl = window.location.pathname.startsWith('/aws-ecommerce') ? '/aws-ecommerce' : '';
      const res = await fetch(`${baseUrl}/api/cart.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action, ...data })
      });
      return await res.json();
    } catch(e) {
      return { success: false, message: 'Network error' };
    }
  }
};

// ── Wishlist API ─────────────────────────────────────────────
const Wishlist = {
  async toggle(productId) {
    try {
      const baseUrl = window.location.pathname.startsWith('/aws-ecommerce') ? '/aws-ecommerce' : '';
      const res = await fetch(`${baseUrl}/api/wishlist.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ product_id: productId })
      });
      return await res.json();
    } catch(e) {
      return { success: false };
    }
  }
};

// ── Toast Notifications ───────────────────────────────────────
const Toast = {
  show(message, type = 'success') {
    const container = document.getElementById('toast-container');
    if (!container) return;
    const id = `toast-${Date.now()}`;
    const icons = { success: '✅', error: '❌', warning: '⚠️', info: 'ℹ️' };
    const html = `
      <div id="${id}" class="toast align-items-center" role="alert" aria-live="assertive">
        <div class="d-flex">
          <div class="toast-body">
            ${icons[type] || ''} ${message}
          </div>
          <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
      </div>`;
    container.insertAdjacentHTML('beforeend', html);
    const el = document.getElementById(id);
    const toast = new bootstrap.Toast(el, { delay: 3000 });
    toast.show();
    el.addEventListener('hidden.bs.toast', () => el.remove());
  }
};

// ── Update Cart Count ─────────────────────────────────────────
function updateCartBadge(count) {
  const badge = document.getElementById('cart-count');
  if (badge) {
    badge.textContent = count;
    badge.style.display = count > 0 ? 'flex' : 'none';
  }
}

// ── Add to Cart Handler ───────────────────────────────────────
document.addEventListener('click', async (e) => {
  const btn = e.target.closest('.btn-add-cart');
  if (!btn) return;
  e.preventDefault();

  const productId = btn.dataset.id;
  const qtyInput  = document.getElementById('qty-input');
  const qty = qtyInput ? parseInt(qtyInput.value) || 1 : 1;

  const orig = btn.innerHTML;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
  btn.disabled = true;

  const res = await Cart.add(productId, qty);

  btn.innerHTML = orig;
  btn.disabled = false;

  if (res.success) {
    Toast.show(res.message || 'Added to cart!', 'success');
    updateCartBadge(res.cart_count);
    btn.innerHTML = '✓ Added';
    setTimeout(() => { btn.innerHTML = orig; }, 2000);
  } else if (res.redirect) {
    window.location.href = res.redirect;
  } else {
    Toast.show(res.message || 'Failed to add to cart.', 'error');
  }
});

// ── Wishlist Handler ──────────────────────────────────────────
document.addEventListener('click', async (e) => {
  const btn = e.target.closest('.btn-wishlist');
  if (!btn) return;
  e.preventDefault();

  const productId = btn.dataset.id;
  const res = await Wishlist.toggle(productId);

  if (res.success) {
    const icon = btn.querySelector('i') || btn;
    if (res.in_wishlist) {
      btn.classList.add('wishlisted');
      Toast.show('Added to wishlist!', 'success');
    } else {
      btn.classList.remove('wishlisted');
      Toast.show('Removed from wishlist.', 'info');
    }
  } else if (res.redirect) {
    window.location.href = res.redirect;
  }
});

// ── Cart Quantity Steppers ────────────────────────────────────
document.addEventListener('click', async (e) => {
  const btn = e.target.closest('.qty-btn');
  if (!btn) return;

  const dir       = btn.dataset.dir;
  const productId = btn.dataset.id;
  const valueEl   = btn.closest('.qty-stepper')?.querySelector('.qty-value');
  if (!valueEl) return;

  let qty = parseInt(valueEl.value || valueEl.textContent) || 1;

  if (dir === 'up') qty = Math.min(99, qty + 1);
  if (dir === 'down') qty = Math.max(1, qty - 1);

  valueEl.value = qty;
  valueEl.textContent = qty;

  if (productId) {
    const res = await Cart.update(productId, qty);
    if (res.success) {
      updateCartBadge(res.cart_count);
      const totalEl = document.querySelector(`[data-total="${productId}"]`);
      if (totalEl && res.item_total) totalEl.textContent = res.item_total;
      const grandEl = document.getElementById('cart-grand-total');
      if (grandEl && res.cart_total) grandEl.textContent = res.cart_total;
    }
  }
});

// ── Product Detail Qty Stepper ────────────────────────────────
const qtyInput = document.getElementById('qty-input');
if (qtyInput) {
  document.getElementById('qty-up')?.addEventListener('click', () => {
    qtyInput.value = Math.min(99, parseInt(qtyInput.value) + 1);
  });
  document.getElementById('qty-down')?.addEventListener('click', () => {
    qtyInput.value = Math.max(1, parseInt(qtyInput.value) - 1);
  });
}

// ── Product Image Gallery ─────────────────────────────────────
document.querySelectorAll('.thumb').forEach(thumb => {
  thumb.addEventListener('click', () => {
    const mainImg = document.getElementById('main-product-img');
    if (mainImg) mainImg.src = thumb.dataset.src;
    document.querySelectorAll('.thumb').forEach(t => t.classList.remove('active'));
    thumb.classList.add('active');
  });
});

// ── Cart Remove Item ──────────────────────────────────────────
document.addEventListener('click', async (e) => {
  const btn = e.target.closest('.btn-remove-cart');
  if (!btn) return;
  e.preventDefault();

  if (!confirm('Remove this item from cart?')) return;

  const productId = btn.dataset.id;
  const row = btn.closest('tr') || btn.closest('.cart-item-row');

  const res = await Cart.remove(productId);
  if (res.success) {
    row?.remove();
    updateCartBadge(res.cart_count);
    Toast.show('Item removed from cart.', 'info');
    const grandEl = document.getElementById('cart-grand-total');
    if (grandEl && res.cart_total) grandEl.textContent = res.cart_total;
    if (res.cart_count === 0) location.reload();
  }
});

// ── Search Autocomplete ───────────────────────────────────────
const searchInput = document.getElementById('global-search');
if (searchInput) {
  let debounceTimer;
  const dropdown = document.getElementById('search-suggestions');

  searchInput.addEventListener('input', () => {
    clearTimeout(debounceTimer);
    const q = searchInput.value.trim();
    if (q.length < 2) {
      dropdown && (dropdown.style.display = 'none');
      return;
    }
    debounceTimer = setTimeout(async () => {
      try {
        const baseUrl = window.location.pathname.startsWith('/aws-ecommerce') ? '/aws-ecommerce' : '';
        const res = await fetch(`${baseUrl}/api/search.php?q=${encodeURIComponent(q)}`);
        const data = await res.json();
        if (dropdown && data.products?.length) {
          dropdown.innerHTML = data.products.map(p => `
            <a href="${baseUrl}/products/detail.php?slug=${p.slug}" class="search-suggestion-item">
              <img src="${p.image}" width="36" height="36" style="border-radius:6px;object-fit:cover;" onerror="this.src='${baseUrl}/assets/images/no-image.png'">
              <div>
                <div style="color:#e2e8f0;font-size:.85rem;font-weight:500;">${p.name}</div>
                <div style="color:#818cf8;font-size:.78rem;">${p.price}</div>
              </div>
            </a>`).join('');
          dropdown.style.display = 'block';
        } else if (dropdown) {
          dropdown.style.display = 'none';
        }
      } catch(e) {}
    }, 300);
  });

  document.addEventListener('click', (e) => {
    if (!searchInput.contains(e.target) && dropdown && !dropdown.contains(e.target)) {
      dropdown.style.display = 'none';
    }
  });
}

// ── Payment method toggle ─────────────────────────────────────
document.querySelectorAll('.payment-option').forEach(opt => {
  opt.addEventListener('click', () => {
    document.querySelectorAll('.payment-option').forEach(o => o.classList.remove('selected'));
    opt.classList.add('selected');
    const radio = opt.querySelector('input[type=radio]');
    if (radio) radio.checked = true;
  });
});

// ── Checkout: use profile address ────────────────────────────
document.getElementById('use-profile-addr')?.addEventListener('change', function() {
  const fields = document.querySelectorAll('.address-field');
  fields.forEach(f => f.disabled = this.checked);
  if (this.checked) {
    const data = JSON.parse(this.dataset.address || '{}');
    Object.keys(data).forEach(k => {
      const el = document.getElementById(`ship_${k}`);
      if (el) el.value = data[k];
    });
  }
});

// ── Price Range Filter ────────────────────────────────────────
const priceMin = document.getElementById('price-min');
const priceMax = document.getElementById('price-max');
const priceDisplay = document.getElementById('price-display');
if (priceMin && priceMax && priceDisplay) {
  function updatePriceDisplay() {
    const minVal = parseInt(priceMin.value) || 0;
    const maxVal = parseInt(priceMax.value);
    const maxDisplay = isNaN(maxVal) ? 'Max' : `₹${maxVal.toLocaleString()}`;
    priceDisplay.textContent = `₹${minVal.toLocaleString()} – ${maxDisplay}`;
  }
  priceMin.addEventListener('input', updatePriceDisplay);
  priceMax.addEventListener('input', updatePriceDisplay);
  updatePriceDisplay();
}

// ── Smooth scroll to reviews ──────────────────────────────────
document.getElementById('scroll-to-reviews')?.addEventListener('click', () => {
  document.getElementById('reviews-section')?.scrollIntoView({ behavior: 'smooth' });
});

// ── DOMContentLoaded ─────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
  // Initialize Bootstrap tooltips
  document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
    new bootstrap.Tooltip(el);
  });

  // Animate numbers on stat cards
  document.querySelectorAll('.stat-value[data-value]').forEach(el => {
    const target = parseFloat(el.dataset.value);
    const isPrice = el.dataset.price === '1';
    let current = 0;
    const step = target / 40;
    const timer = setInterval(() => {
      current = Math.min(current + step, target);
      el.textContent = isPrice
        ? '₹' + current.toLocaleString('en-IN', { maximumFractionDigits: 0 })
        : Math.round(current).toLocaleString();
      if (current >= target) clearInterval(timer);
    }, 25);
  });
});
