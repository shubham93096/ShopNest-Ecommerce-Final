/* ============================================================
   ShopNest — Admin JavaScript
   ============================================================ */

'use strict';

// ── Sidebar Toggle ────────────────────────────────────────────
const sidebar  = document.querySelector('.admin-sidebar');
const overlay  = document.querySelector('.sidebar-overlay');
const menuBtn  = document.getElementById('menu-toggle');

menuBtn?.addEventListener('click', () => {
  sidebar?.classList.toggle('open');
  overlay?.classList.toggle('show');
});
overlay?.addEventListener('click', () => {
  sidebar?.classList.remove('open');
  overlay?.classList.remove('show');
});

// ── Toast ─────────────────────────────────────────────────────
const AdminToast = {
  show(message, type = 'success') {
    const container = document.getElementById('toast-container');
    if (!container) return;
    const id = `atst-${Date.now()}`;
    const icons = { success: '✅', error: '❌', warning: '⚠️', info: 'ℹ️' };
    container.insertAdjacentHTML('beforeend', `
      <div id="${id}" class="toast align-items-center" role="alert">
        <div class="d-flex">
          <div class="toast-body">${icons[type] || ''} ${message}</div>
          <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
      </div>`);
    const el = document.getElementById(id);
    const toast = new bootstrap.Toast(el, { delay: 3500 });
    toast.show();
    el.addEventListener('hidden.bs.toast', () => el.remove());
  }
};

// ── Confirm Delete ────────────────────────────────────────────
document.addEventListener('click', e => {
  const btn = e.target.closest('[data-confirm]');
  if (!btn) return;
  if (!confirm(btn.dataset.confirm || 'Are you sure?')) {
    e.preventDefault();
    e.stopPropagation();
  }
});

// ── Image Preview ─────────────────────────────────────────────
document.querySelectorAll('.img-upload-input').forEach(input => {
  input.addEventListener('change', function() {
    const preview = document.getElementById(this.dataset.preview);
    if (!preview) return;
    preview.innerHTML = '';
    Array.from(this.files).forEach(file => {
      const reader = new FileReader();
      reader.onload = (e) => {
        preview.insertAdjacentHTML('beforeend', `
          <div class="image-preview-item">
            <img src="${e.target.result}" alt="">
          </div>`);
      };
      reader.readAsDataURL(file);
    });
  });
});

// ── Image Upload Drag & Drop ──────────────────────────────────
document.querySelectorAll('.image-upload-area').forEach(area => {
  area.addEventListener('dragover', e => {
    e.preventDefault();
    area.classList.add('drag-over');
  });
  area.addEventListener('dragleave', () => area.classList.remove('drag-over'));
  area.addEventListener('drop', e => {
    e.preventDefault();
    area.classList.remove('drag-over');
    const input = area.querySelector('input[type=file]');
    if (input) {
      input.files = e.dataTransfer.files;
      input.dispatchEvent(new Event('change'));
    }
  });
  area.addEventListener('click', () => area.querySelector('input[type=file]')?.click());
});

// ── Table Search Filter ───────────────────────────────────────
document.querySelectorAll('[data-table-search]').forEach(input => {
  const tableId = input.dataset.tableSearch;
  const table = document.getElementById(tableId);
  if (!table) return;
  input.addEventListener('input', () => {
    const q = input.value.toLowerCase();
    table.querySelectorAll('tbody tr').forEach(row => {
      const text = row.textContent.toLowerCase();
      row.style.display = text.includes(q) ? '' : 'none';
    });
  });
});

// ── Status Filter ─────────────────────────────────────────────
document.querySelectorAll('[data-table-filter]').forEach(sel => {
  sel.addEventListener('change', () => {
    const tableId = sel.dataset.tableFilter;
    const colIdx  = parseInt(sel.dataset.col || 0);
    const val     = sel.value.toLowerCase();
    const table   = document.getElementById(tableId);
    if (!table) return;
    table.querySelectorAll('tbody tr').forEach(row => {
      const cell = row.cells[colIdx];
      const text = cell ? cell.textContent.toLowerCase() : '';
      row.style.display = (val === '' || text.includes(val)) ? '' : 'none';
    });
  });
});

// ── Revenue Chart (Chart.js) ──────────────────────────────────
function initRevenueChart(labels, data) {
  const ctx = document.getElementById('revenue-chart');
  if (!ctx || typeof Chart === 'undefined') return;
  new Chart(ctx, {
    type: 'line',
    data: {
      labels,
      datasets: [{
        label: 'Revenue (₹)',
        data,
        borderColor: '#6366f1',
        backgroundColor: 'rgba(99,102,241,0.1)',
        fill: true,
        tension: 0.4,
        pointBackgroundColor: '#6366f1',
        pointRadius: 4,
        pointHoverRadius: 6,
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { display: false },
        tooltip: {
          backgroundColor: '#1a2236',
          borderColor: 'rgba(99,102,241,0.3)',
          borderWidth: 1,
          titleColor: '#e2e8f0',
          bodyColor: '#94a3b8',
          callbacks: {
            label: ctx => '₹' + ctx.parsed.y.toLocaleString('en-IN')
          }
        }
      },
      scales: {
        x: {
          grid: { color: 'rgba(255,255,255,0.04)' },
          ticks: { color: '#64748b', font: { size: 11 } }
        },
        y: {
          grid: { color: 'rgba(255,255,255,0.04)' },
          ticks: {
            color: '#64748b',
            font: { size: 11 },
            callback: v => '₹' + v.toLocaleString('en-IN')
          }
        }
      }
    }
  });
}

// ── Orders by Status Donut Chart ──────────────────────────────
function initOrdersChart(labels, data) {
  const ctx = document.getElementById('orders-chart');
  if (!ctx || typeof Chart === 'undefined') return;
  new Chart(ctx, {
    type: 'doughnut',
    data: {
      labels,
      datasets: [{
        data,
        backgroundColor: ['#f59e0b','#06b6d4','#6366f1','#10b981','#ef4444','#94a3b8'],
        borderWidth: 0,
        hoverOffset: 6
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          position: 'bottom',
          labels: { color: '#94a3b8', font: { size: 11 }, padding: 16 }
        },
        tooltip: {
          backgroundColor: '#1a2236',
          borderColor: 'rgba(99,102,241,0.3)',
          borderWidth: 1,
          titleColor: '#e2e8f0',
          bodyColor: '#94a3b8'
        }
      },
      cutout: '65%'
    }
  });
}

// ── Category Sales Bar Chart ──────────────────────────────────
function initCategoryChart(labels, data) {
  const ctx = document.getElementById('category-chart');
  if (!ctx || typeof Chart === 'undefined') return;
  new Chart(ctx, {
    type: 'bar',
    data: {
      labels,
      datasets: [{
        label: 'Sales (₹)',
        data,
        backgroundColor: 'rgba(99,102,241,0.7)',
        borderColor: '#6366f1',
        borderWidth: 1,
        borderRadius: 6
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { display: false },
        tooltip: {
          backgroundColor: '#1a2236',
          titleColor: '#e2e8f0',
          bodyColor: '#94a3b8'
        }
      },
      scales: {
        x: { grid: { display: false }, ticks: { color: '#64748b' } },
        y: { grid: { color: 'rgba(255,255,255,0.04)' }, ticks: { color: '#64748b' } }
      }
    }
  });
}

// ── Stock Toggle ──────────────────────────────────────────────
document.addEventListener('change', async e => {
  const toggle = e.target.closest('.status-toggle');
  if (!toggle) return;
  const id = toggle.dataset.id;
  const type = toggle.dataset.type;
  const checked = toggle.checked;
  try {
    const baseUrl = window.location.pathname.startsWith('/aws-ecommerce') ? '/aws-ecommerce' : '';
    const res = await fetch(`${baseUrl}/api/toggle-status.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id, type, status: checked ? 1 : 0 })
    });
    const data = await res.json();
    AdminToast.show(data.message || 'Status updated.', data.success ? 'success' : 'error');
  } catch(err) {
    AdminToast.show('Network error.', 'error');
    toggle.checked = !checked;
  }
});

// ── Slug Generator ────────────────────────────────────────────
const nameInput = document.getElementById('product-name') || document.getElementById('category-name');
const slugInput = document.getElementById('product-slug') || document.getElementById('category-slug');
if (nameInput && slugInput) {
  nameInput.addEventListener('input', () => {
    slugInput.value = nameInput.value
      .toLowerCase().trim()
      .replace(/[^a-z0-9\s-]/g, '')
      .replace(/[\s-]+/g, '-')
      .replace(/^-|-$/g, '');
  });
}

// ── Date Range Picker (report) ────────────────────────────────
const reportForm = document.getElementById('report-form');
reportForm?.addEventListener('submit', e => {
  const from = document.getElementById('date-from')?.value;
  const to   = document.getElementById('date-to')?.value;
  if (from && to && from > to) {
    e.preventDefault();
    AdminToast.show('Start date must be before end date.', 'warning');
  }
});

// ── CSV Export ────────────────────────────────────────────────
document.getElementById('export-csv')?.addEventListener('click', () => {
  const table = document.querySelector('.admin-table');
  if (!table) return;
  const rows = table.querySelectorAll('tr');
  const csv = Array.from(rows).map(row =>
    Array.from(row.querySelectorAll('th,td'))
      .map(cell => `"${cell.textContent.trim().replace(/"/g, '""')}"`)
      .join(',')
  ).join('\n');
  const blob = new Blob([csv], { type: 'text/csv' });
  const url  = URL.createObjectURL(blob);
  const a    = document.createElement('a');
  a.href = url;
  a.download = `report-${new Date().toISOString().slice(0,10)}.csv`;
  a.click();
  URL.revokeObjectURL(url);
});

// ── DOMContentLoaded ──────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el =>
    new bootstrap.Tooltip(el)
  );

  // Animate stat values
  document.querySelectorAll('.stat-value[data-value]').forEach(el => {
    const target = parseFloat(el.dataset.value.replace(/[^0-9.]/g, ''));
    if (isNaN(target)) return;
    const prefix = el.dataset.prefix || '';
    let current = 0;
    const step = target / 50;
    const t = setInterval(() => {
      current = Math.min(current + step, target);
      el.textContent = prefix + Math.round(current).toLocaleString('en-IN');
      if (current >= target) clearInterval(t);
    }, 20);
  });
});
