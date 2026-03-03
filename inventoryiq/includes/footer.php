<?php
/**
 * InventoryIQ v2.0 — Footer Include
 * AI Rules §9.2 — Capsule nav + toast container + JS
 */

$current_role = isset($_SESSION['role']) ? $_SESSION['role'] : '';
$current_page = basename($_SERVER['PHP_SELF']);
$current_dir = basename(dirname($_SERVER['PHP_SELF']));
$is_sa = ($current_role === 'super_admin');

// Define nav items per role (AI Rules §5.3)
$nav_items = [];

switch ($current_role) {
    case 'company_admin':
        $nav_items = [
            ['url' => '/inventoryiq/dashboard/index.php', 'icon' => 'layout-dashboard', 'label' => 'Home', 'dir' => 'dashboard'],
            ['url' => '/inventoryiq/warehouse/list.php', 'icon' => 'warehouse', 'label' => 'Warehouses', 'dir' => 'warehouse'],
            ['url' => '/inventoryiq/notifications/broadcast.php', 'icon' => 'megaphone', 'label' => 'Broadcast', 'page' => 'broadcast.php'],
            ['url' => '/inventoryiq/restock/manage.php', 'icon' => 'package-check', 'label' => 'Requests', 'page' => 'manage.php', 'dir' => 'restock'],
            ['url' => '/inventoryiq/export/csv.php', 'icon' => 'file-bar-chart', 'label' => 'Reports', 'dir' => 'export'],
            ['url' => '/inventoryiq/settings/index.php', 'icon' => 'settings', 'label' => 'Settings', 'dir' => 'settings'],
        ];
        break;
    case 'wh_manager':
        $nav_items = [
            ['url' => '/inventoryiq/dashboard/index.php', 'icon' => 'layout-dashboard', 'label' => 'Home', 'dir' => 'dashboard'],
            ['url' => '/inventoryiq/products/view.php', 'icon' => 'package', 'label' => 'Inventory', 'dir' => 'products'],
            ['url' => '/inventoryiq/notifications/index.php', 'icon' => 'bell', 'label' => 'Notifications', 'dir' => 'notifications'],
            ['url' => '/inventoryiq/restock/request.php', 'icon' => 'package-plus', 'label' => 'Restock', 'dir' => 'restock'],
            ['url' => '/inventoryiq/export/csv.php', 'icon' => 'download', 'label' => 'Export', 'dir' => 'export'],
            ['url' => '/inventoryiq/settings/index.php', 'icon' => 'settings', 'label' => 'Settings', 'dir' => 'settings'],
        ];
        break;
    case 'wh_staff':
        $nav_items = [
            ['url' => '/inventoryiq/dashboard/index.php', 'icon' => 'layout-dashboard', 'label' => 'Home', 'dir' => 'dashboard'],
            ['url' => '/inventoryiq/products/view.php', 'icon' => 'package', 'label' => 'Inventory', 'dir' => 'products'],
            ['url' => '/inventoryiq/settings/index.php', 'icon' => 'settings', 'label' => 'Settings', 'dir' => 'settings'],
        ];
        break;
    case 'super_admin':
        $nav_items = [
            ['url' => '/inventoryiq/superadmin/dashboard.php', 'icon' => 'layout-dashboard', 'label' => 'Home', 'page' => 'dashboard.php'],
            ['url' => '/inventoryiq/superadmin/companies.php', 'icon' => 'building-2', 'label' => 'Companies', 'page' => 'companies.php'],
            ['url' => '/inventoryiq/audit/log.php', 'icon' => 'activity', 'label' => 'Activity', 'dir' => 'audit'],
            ['url' => '/inventoryiq/superadmin/audit_export.php', 'icon' => 'file-text', 'label' => 'Audit', 'page' => 'audit_export.php'],
            ['url' => '/inventoryiq/superadmin/maintenance.php', 'icon' => 'shield', 'label' => 'Maintenance', 'page' => 'maintenance.php'],
            ['url' => '/inventoryiq/logout.php', 'icon' => 'log-out', 'label' => 'Logout', 'page' => 'logout.php'],
        ];
        break;
}
?>

</main><!-- end .content-area -->

<?php if (!empty($nav_items)): ?>
<!-- Bottom Capsule Navigation -->
<nav class="capsule-nav" aria-label="Main Navigation">
  <?php foreach ($nav_items as $item):
    $is_active = false;
    if (isset($item['page']) && $current_page === $item['page']) {
        $is_active = true;
    } elseif (isset($item['dir']) && $current_dir === $item['dir']) {
        $is_active = true;
    }
  ?>
  <a href="<?php echo $item['url']; ?>" class="capsule-item<?php echo $is_active ? ' active' : ''; ?>" title="<?php echo htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8'); ?>">
    <i data-lucide="<?php echo $item['icon']; ?>"></i>
    <span><?php echo htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8'); ?></span>
  </a>
  <?php endforeach; ?>
</nav>
<?php endif; ?>

<!-- Toast Container -->
<div id="toast-container" class="toast-container"></div>

<!-- Confirmation Modal -->
<div id="confirm-modal" class="modal-backdrop">
  <div class="modal-card">
    <h3 id="modal-title" class="modal-title"></h3>
    <p id="modal-message" class="modal-body"></p>
    <div class="modal-actions">
      <button id="modal-cancel-btn" class="btn btn-ghost">Cancel</button>
      <button id="modal-confirm-btn" class="btn btn-danger">Confirm</button>
    </div>
  </div>
</div>

<?php
// Flash toast from session
if (isset($_GET['success'])): ?>
  <div data-toast="Operation completed successfully" data-toast-type="success" style="display:none;"></div>
<?php endif;
if (isset($_GET['error'])): ?>
  <div data-toast="<?php echo htmlspecialchars(isset($_GET['msg']) ? $_GET['msg'] : 'An error occurred', ENT_QUOTES, 'UTF-8'); ?>" data-toast-type="error" style="display:none;"></div>
<?php endif;
?>

<script src="/inventoryiq/js/app.js"></script>
</body>
</html>
