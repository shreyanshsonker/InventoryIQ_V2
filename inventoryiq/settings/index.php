<?php
/**
 * InventoryIQ v2.0 — Settings Page
 * AI Rules §5 — All roles (own profile management)
 */
require_once '../config/db.php';
require_once '../auth/check.php';
require_once '../includes/audit.php';

$page_title = 'Settings';
$error = '';
$success = '';
$role = $_SESSION['role'];

// Super Admin settings redirect
if ($role === 'super_admin') {
    // SA password change
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
        $current = $_POST['current_password'] ?? '';
        $new_pass = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        $stmt = mysqli_prepare($conn, 'SELECT password_hash FROM super_admin WHERE admin_id = ?');
        mysqli_stmt_bind_param($stmt, 'i', $_SESSION['admin_id']);
        mysqli_stmt_execute($stmt);
        $admin = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);

        if (!password_verify($current, $admin['password_hash'])) {
            $error = 'Current password is incorrect.';
        } elseif (strlen($new_pass) < 8) {
            $error = 'New password must be at least 8 characters.';
        } elseif ($new_pass !== $confirm) {
            $error = 'New passwords do not match.';
        } else {
            $hash = password_hash($new_pass, PASSWORD_BCRYPT);
            $upd = mysqli_prepare($conn, 'UPDATE super_admin SET password_hash = ? WHERE admin_id = ?');
            mysqli_stmt_bind_param($upd, 'si', $hash, $_SESSION['admin_id']);
            mysqli_stmt_execute($upd);
            mysqli_stmt_close($upd);
            $success = 'Password updated successfully.';
            write_audit_log($conn, null, 'super_admin', null, null, 'PASSWORD_CHANGE', 'Super Admin changed password');
        }
    }
} else {
    // Company user settings
    $stmt = mysqli_prepare($conn, 'SELECT full_name, login_identifier FROM users WHERE user_id = ?');
    mysqli_stmt_bind_param($stmt, 'i', $_SESSION['user_id']);
    mysqli_stmt_execute($stmt);
    $user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    // Update profile
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
        $full_name = trim($_POST['full_name'] ?? '');
        if (empty($full_name)) {
            $error = 'Name is required.';
        } else {
            $upd = mysqli_prepare($conn, 'UPDATE users SET full_name = ? WHERE user_id = ?');
            mysqli_stmt_bind_param($upd, 'si', $full_name, $_SESSION['user_id']);
            mysqli_stmt_execute($upd);
            mysqli_stmt_close($upd);
            $_SESSION['full_name'] = $full_name;
            $user['full_name'] = $full_name;
            $success = 'Profile updated.';
            write_audit_log($conn, $_SESSION['user_id'], $role, $_SESSION['company_id'], $_SESSION['warehouse_id'], 'PROFILE_UPDATE', 'Updated profile');
        }
    }

    // Change password
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
        $current = $_POST['current_password'] ?? '';
        $new_pass = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        $pw_stmt = mysqli_prepare($conn, 'SELECT password_hash FROM users WHERE user_id = ?');
        mysqli_stmt_bind_param($pw_stmt, 'i', $_SESSION['user_id']);
        mysqli_stmt_execute($pw_stmt);
        $pw_row = mysqli_fetch_assoc(mysqli_stmt_get_result($pw_stmt));
        mysqli_stmt_close($pw_stmt);

        if (!password_verify($current, $pw_row['password_hash'])) {
            $error = 'Current password is incorrect.';
        } elseif (strlen($new_pass) < 8) {
            $error = 'New password must be at least 8 characters.';
        } elseif ($new_pass !== $confirm) {
            $error = 'New passwords do not match.';
        } else {
            $hash = password_hash($new_pass, PASSWORD_BCRYPT);
            $upd = mysqli_prepare($conn, 'UPDATE users SET password_hash = ? WHERE user_id = ?');
            mysqli_stmt_bind_param($upd, 'si', $hash, $_SESSION['user_id']);
            mysqli_stmt_execute($upd);
            mysqli_stmt_close($upd);
            $success = 'Password changed successfully.';
            write_audit_log($conn, $_SESSION['user_id'], $role, $_SESSION['company_id'], $_SESSION['warehouse_id'], 'PASSWORD_CHANGE', 'Changed password');
        }
    }
}

require_once '../includes/header.php';
?>

<div class="mb-6">
  <h1 style="font-size:28px;">Settings</h1>
</div>

<?php if (!empty($error)): ?>
<div class="alert-banner alert-error mb-6">
  <i data-lucide="alert-circle" style="width:18px;height:18px;flex-shrink:0;"></i>
  <span><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></span>
</div>
<?php endif; ?>
<?php if (!empty($success)): ?>
<div class="alert-banner alert-success mb-6">
  <i data-lucide="check-circle" style="width:18px;height:18px;flex-shrink:0;"></i>
  <span><?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></span>
</div>
<?php endif; ?>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--space-6);max-width:900px;">

  <?php if ($role !== 'super_admin'): ?>
  <!-- Profile -->
  <div class="glass-card-static">
    <h3 class="section-title mb-4">
      <i data-lucide="user" style="width:18px;height:18px;display:inline;"></i> Profile
    </h3>
    <form method="POST" style="display:flex;flex-direction:column;gap:16px;">
      <div class="form-group">
        <label class="form-label">Full Name</label>
        <input type="text" name="full_name" class="glass-input"
               value="<?php echo htmlspecialchars($user['full_name'], ENT_QUOTES, 'UTF-8'); ?>" required>
      </div>
      <div class="form-group">
        <label class="form-label">Login ID</label>
        <p class="mono" style="padding:10px 0;"><?php echo htmlspecialchars($user['login_identifier'], ENT_QUOTES, 'UTF-8'); ?></p>
      </div>
      <div class="form-group">
        <label class="form-label">Role</label>
        <p style="color:var(--text-primary);padding:10px 0;"><?php echo htmlspecialchars(str_replace('_',' ',ucwords($_SESSION['role'],'_')), ENT_QUOTES, 'UTF-8'); ?></p>
      </div>
      <button type="submit" name="update_profile" value="1" class="btn btn-primary">
        <i data-lucide="save" style="width:16px;height:16px;"></i> Save Profile
      </button>
    </form>
  </div>
  <?php endif; ?>

  <!-- Change Password -->
  <div class="glass-card-static">
    <h3 class="section-title mb-4">
      <i data-lucide="lock" style="width:18px;height:18px;display:inline;"></i> Change Password
    </h3>
    <form method="POST" style="display:flex;flex-direction:column;gap:16px;">
      <div class="form-group">
        <label class="form-label">Current Password</label>
        <input type="password" name="current_password" class="glass-input" required>
      </div>
      <div class="form-group">
        <label class="form-label">New Password</label>
        <input type="password" name="new_password" class="glass-input" placeholder="Min 8 characters" required>
      </div>
      <div class="form-group">
        <label class="form-label">Confirm New Password</label>
        <input type="password" name="confirm_password" class="glass-input" required>
      </div>
      <button type="submit" name="change_password" value="1" class="btn btn-primary">
        <i data-lucide="key" style="width:16px;height:16px;"></i> Change Password
      </button>
    </form>
  </div>

  <!-- Logout -->
  <div class="glass-card-static" style="display:flex;align-items:center;justify-content:space-between;">
    <div>
      <h3 class="section-title" style="margin-bottom:4px;">Session</h3>
      <p style="color:var(--text-muted);font-size:13px;">End your current session</p>
    </div>
    <a href="/inventoryiq/logout.php" class="btn btn-danger">
      <i data-lucide="log-out" style="width:16px;height:16px;"></i> Logout
    </a>
  </div>
</div>

<?php
require_once '../includes/footer.php';
mysqli_close($conn);
?>
