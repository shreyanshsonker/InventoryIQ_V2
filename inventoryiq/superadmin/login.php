<?php
/**
 * InventoryIQ v2.0 — Super Admin Login (Screen 14)
 * AI Rules §4.4 — Separate SA login querying super_admin table
 */
session_start();
require_once '../config/db.php';
require_once '../includes/audit.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        $stmt = mysqli_prepare($conn,
            'SELECT admin_id, username, password_hash FROM super_admin WHERE username = ?'
        );
        mysqli_stmt_bind_param($stmt, 's', $username);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $admin = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        if (!$admin || !password_verify($password, $admin['password_hash'])) {
            $error = 'Invalid credentials.';
            write_audit_log($conn, null, 'super_admin', null, null, 'LOGIN_FAIL', 'SA login failed for: ' . $username);
        } else {
            session_regenerate_id(true);
            $_SESSION['user_id'] = null; // SA has no user_id in users table
            $_SESSION['admin_id'] = $admin['admin_id'];
            $_SESSION['role'] = 'super_admin';
            $_SESSION['company_id'] = null;
            $_SESSION['warehouse_id'] = null;
            $_SESSION['full_name'] = 'Super Admin';
            $_SESSION['last_activity'] = time();

            write_audit_log($conn, null, 'super_admin', null, null, 'LOGIN_SUCCESS', 'Super Admin logged in');

            header('Location: /inventoryiq/superadmin/dashboard.php');
            exit;
        }
    }
}

mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Super Admin — InventoryIQ</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@600;700;800&family=DM+Sans:wght@400;500;600&family=Fira+Code&display=swap" rel="stylesheet">
  <script src="https://unpkg.com/lucide@latest"></script>
  <link rel="stylesheet" href="/inventoryiq/css/style.css">
  <link rel="stylesheet" href="/inventoryiq/css/superadmin.css">
</head>
<body class="cosmic-bg">
<div class="scanline-overlay"></div>

<div class="login-wrapper">
  <div class="sa-login-card tilt-card">

    <div class="login-logo">
      <span style="background:linear-gradient(135deg,#A78BFA,#F0ABFC);-webkit-background-clip:text;-webkit-text-fill-color:transparent;text-shadow:0 0 40px rgba(124,58,237,0.5);">InventoryIQ</span>
    </div>
    <p class="sa-label" style="text-align:center;margin-bottom:var(--space-8);">SUPER ADMIN</p>

    <?php if (!empty($error)): ?>
      <div class="alert-banner alert-error" style="margin-bottom:var(--space-6);">
        <i data-lucide="alert-circle" style="width:18px;height:18px;flex-shrink:0;"></i>
        <span><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></span>
      </div>
    <?php endif; ?>

    <form method="POST" style="display:flex;flex-direction:column;gap:20px;">

      <div class="form-group">
        <label class="form-label" for="username">Username</label>
        <div class="input-group">
          <i data-lucide="shield" class="input-icon" style="width:18px;height:18px;"></i>
          <input type="text" id="username" name="username" class="glass-input" placeholder="superadmin" required>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label" for="password">Password</label>
        <div class="input-group">
          <i data-lucide="lock" class="input-icon" style="width:18px;height:18px;"></i>
          <input type="password" id="password" name="password" class="glass-input" placeholder="••••••••" required>
          <button type="button" class="input-action" onclick="togglePasswordVisibility('password', this.querySelector('i'))">
            <i data-lucide="eye" style="width:18px;height:18px;"></i>
          </button>
        </div>
      </div>

      <button type="submit" class="btn btn-sa-primary btn-full btn-lg" style="margin-top:8px;">
        <i data-lucide="shield-check" style="width:18px;height:18px;"></i>
        Access Control Panel
      </button>

    </form>

  </div>
</div>

<script src="/inventoryiq/js/app.js"></script>
</body>
</html>
