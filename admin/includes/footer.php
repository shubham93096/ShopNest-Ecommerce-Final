    </div><!-- /.admin-body -->
  </main>
</div><!-- /.admin-layout -->

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<!-- Admin JS -->
<script src="<?= APP_URL ?>/assets/js/admin.js"></script>
<script>
// Sidebar toggle wiring
const adminSidebar = document.getElementById('admin-sidebar');
const sidebarOverlay = document.getElementById('sidebar-overlay');
document.getElementById('menu-toggle')?.addEventListener('click', () => {
  adminSidebar?.classList.toggle('open');
  sidebarOverlay?.classList.toggle('show');
});
sidebarOverlay?.addEventListener('click', () => {
  adminSidebar?.classList.remove('open');
  sidebarOverlay?.classList.remove('show');
});
</script>
</body>
</html>
