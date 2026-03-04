<?php
/**
 * InventoryIQ v2.0 — Header Include
 * AI Rules §9.2 — Role-aware top bar + HTML head
 */

// Determine if Super Admin portal
$is_sa = isset($_SESSION['role']) && $_SESSION['role'] === 'super_admin';

// Get current page title
if (!isset($page_title)) {
    $page_title = 'InventoryIQ';
}

// Get unread notification count for bell badge
$unread_count = 0;
if (isset($_SESSION['user_id']) && isset($conn)) {
    if ($_SESSION['role'] === 'company_admin') {
        $stmt = mysqli_prepare($conn, 'SELECT COUNT(notification_id) AS cnt FROM notifications WHERE company_id = ? AND is_read = 0');
        mysqli_stmt_bind_param($stmt, 'i', $_SESSION['company_id']);
    } elseif ($_SESSION['role'] === 'wh_manager' || $_SESSION['role'] === 'wh_staff') {
        $stmt = mysqli_prepare($conn, 'SELECT COUNT(notification_id) AS cnt FROM notifications WHERE company_id = ? AND (recipient_warehouse_id = ? OR recipient_warehouse_id IS NULL) AND is_read = 0');
        mysqli_stmt_bind_param($stmt, 'ii', $_SESSION['company_id'], $_SESSION['warehouse_id']);
    }
    if (isset($stmt)) {
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        $unread_count = (int)$row['cnt'];
        mysqli_stmt_close($stmt);
    }
}

// User initials for avatar
$user_initials = '';
if (isset($_SESSION['full_name'])) {
    $parts = explode(' ', $_SESSION['full_name']);
    $user_initials = strtoupper(substr($parts[0], 0, 1));
    if (count($parts) > 1) {
        $user_initials .= strtoupper(substr(end($parts), 0, 1));
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($page_title, ENT_QUOTES, 'UTF-8'); ?> — InventoryIQ</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@600;700;800&family=DM+Sans:wght@400;500;600&family=Fira+Code&display=swap" rel="stylesheet">
  <script src="https://unpkg.com/lucide@latest"></script>
  <link rel="stylesheet" href="/inventoryiq/css/style.css">
  <link rel="stylesheet" href="/inventoryiq/css/dark.css">
<?php if ($is_sa): ?>
  <link rel="stylesheet" href="/inventoryiq/css/superadmin.css">
<?php endif; ?>
</head>
<body class="<?php echo $is_sa ? 'cosmic-bg' : 'aurora-bg'; ?>">

<?php if ($is_sa): ?>
<div class="scanline-overlay"></div>
<?php endif; ?>

<!-- Top Bar -->
<header class="top-bar">
  <div class="top-bar-title">
    <?php if ($is_sa): ?>
      <span class="sa-label">SUPER ADMIN</span>
    <?php else: ?>
      <?php echo htmlspecialchars($page_title, ENT_QUOTES, 'UTF-8'); ?>
    <?php endif; ?>
  </div>
  <div class="top-bar-right">
    <?php if (isset($_SESSION['user_id']) && !$is_sa): ?>
    <button class="notification-bell" onclick="window.location.href='/inventoryiq/notifications/index.php'" title="Notifications">
      <i data-lucide="bell" style="width:22px;height:22px;"></i>
      <?php if ($unread_count > 0): ?>
        <span class="bell-badge"><?php echo $unread_count > 9 ? '9+' : $unread_count; ?></span>
      <?php endif; ?>
    </button>
    <?php endif; ?>
    <!-- Light/Dark Mode Toggle -->
    <button class="theme-toggle-btn" onclick="toggleDarkMode()" title="Toggle Light/Dark Mode" id="theme-toggle">
      <i data-lucide="sun" class="theme-icon-light" style="width:20px;height:20px;"></i>
      <i data-lucide="moon" class="theme-icon-dark" style="width:20px;height:20px;"></i>
    </button>
    <?php if (isset($_SESSION['full_name'])): ?>
    <div class="user-avatar" title="<?php echo htmlspecialchars($_SESSION['full_name'], ENT_QUOTES, 'UTF-8'); ?>">
      <?php echo htmlspecialchars($user_initials, ENT_QUOTES, 'UTF-8'); ?>
    </div>
    <?php endif; ?>
  </div>
</header>

<main class="content-area">
