<?php
/**
 * InventoryIQ v2.0 — Company Login Page (Screen 01)
 * AI Rules §4.3 — Login flow
 */
session_start();
require_once 'config/db.php';
require_once 'includes/audit.php';

$error = '';
$login_id = '';

// Handle timeout message
if (isset($_GET['timeout'])) {
    $error = 'Your session expired due to inactivity. Please log in again.';
}
if (isset($_GET['suspended'])) {
    $error = 'Your company account has been suspended. Contact support.';
}
if (isset($_GET['reset'])) {
    $error = ''; // Clear any error
}
$reset_success = isset($_GET['reset']);

// POST: Login attempt
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login_id = isset($_POST['login_identifier']) ? trim($_POST['login_identifier']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $remember = isset($_POST['remember_me']) ? true : false;

    if (empty($login_id) || empty($password)) {
        $error = 'Please enter both Login ID and Password.';
    } else {
        // Look up user
        $stmt = mysqli_prepare($conn,
            'SELECT u.user_id, u.company_id, u.warehouse_id, u.full_name, u.login_identifier,
                    u.password_hash, u.role, u.failed_attempts, u.locked_until, u.status,
                    c.status AS company_status
             FROM users u
             JOIN companies c ON c.company_id = u.company_id
             WHERE u.login_identifier = ?'
        );
        mysqli_stmt_bind_param($stmt, 's', $login_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        if (!$user) {
            $error = 'Invalid login credentials.';
            write_audit_log($conn, null, 'unknown', null, null, 'LOGIN_FAIL', 'Unknown login_identifier: ' . $login_id);
        } elseif ($user['status'] !== 'active') {
            $error = 'Your account has been deactivated. Contact your administrator.';
        } elseif ($user['company_status'] !== 'active') {
            $error = 'Your company account has been suspended.';
        } elseif ($user['locked_until'] && strtotime($user['locked_until']) > time()) {
            $remaining = ceil((strtotime($user['locked_until']) - time()) / 60);
            $error = 'Account locked. Try again in ' . $remaining . ' minute(s).';
        } elseif (!password_verify($password, $user['password_hash'])) {
            // Failed attempt
            $new_attempts = (int)$user['failed_attempts'] + 1;
            if ($new_attempts >= 5) {
                $lock_stmt = mysqli_prepare($conn,
                    'UPDATE users SET failed_attempts = ?, locked_until = DATE_ADD(NOW(), INTERVAL 15 MINUTE) WHERE user_id = ?'
                );
                mysqli_stmt_bind_param($lock_stmt, 'ii', $new_attempts, $user['user_id']);
                mysqli_stmt_execute($lock_stmt);
                mysqli_stmt_close($lock_stmt);
                $error = 'Too many failed attempts. Account locked for 15 minutes.';
            } else {
                $fail_stmt = mysqli_prepare($conn,
                    'UPDATE users SET failed_attempts = ? WHERE user_id = ?'
                );
                mysqli_stmt_bind_param($fail_stmt, 'ii', $new_attempts, $user['user_id']);
                mysqli_stmt_execute($fail_stmt);
                mysqli_stmt_close($fail_stmt);
                $error = 'Invalid login credentials.';
            }
            write_audit_log($conn, $user['user_id'], $user['role'], $user['company_id'], $user['warehouse_id'], 'LOGIN_FAIL', 'Wrong password (attempt ' . $new_attempts . ')');
        } else {
            // SUCCESS — Reset failed attempts, set session
            $reset_stmt = mysqli_prepare($conn,
                'UPDATE users SET failed_attempts = 0, locked_until = NULL WHERE user_id = ?'
            );
            mysqli_stmt_bind_param($reset_stmt, 'i', $user['user_id']);
            mysqli_stmt_execute($reset_stmt);
            mysqli_stmt_close($reset_stmt);

            // Set session keys (AI Rules §4.1)
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['company_id'] = $user['company_id'];
            $_SESSION['warehouse_id'] = $user['warehouse_id'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['last_activity'] = time();

            // Remember Me
            if ($remember) {
                $token = bin2hex(random_bytes(32));
                $token_hash = password_hash($token, PASSWORD_BCRYPT);
                $rem_stmt = mysqli_prepare($conn, 'UPDATE users SET remember_token = ? WHERE user_id = ?');
                mysqli_stmt_bind_param($rem_stmt, 'si', $token_hash, $user['user_id']);
                mysqli_stmt_execute($rem_stmt);
                mysqli_stmt_close($rem_stmt);
                setcookie('remember_token', $token, time() + (30 * 24 * 60 * 60), '/inventoryiq/', '', false, true);
                setcookie('remember_user', $user['user_id'], time() + (30 * 24 * 60 * 60), '/inventoryiq/', '', false, true);
            }

            write_audit_log($conn, $user['user_id'], $user['role'], $user['company_id'], $user['warehouse_id'], 'LOGIN_SUCCESS', 'Logged in successfully');

            header('Location: /inventoryiq/dashboard/index.php');
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
  <title>Sign In — InventoryIQ</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@600;700;800&family=DM+Sans:wght@400;500;600&family=Fira+Code&display=swap" rel="stylesheet">
  <script src="https://unpkg.com/lucide@latest"></script>
  <link rel="stylesheet" href="/inventoryiq/css/style.css">
</head>
<body class="aurora-bg">

<div class="login-wrapper">
  <div class="login-card tilt-card">

    <!-- Logo -->
    <div class="login-logo">
      <span class="text-gradient text-gradient-glow">InventoryIQ</span>
    </div>
    <p class="login-subtitle">Sign in to your workspace</p>

    <!-- Success Message -->
    <?php if ($reset_success): ?>
      <div class="alert-banner alert-success" style="margin-bottom: var(--space-6);">
        <i data-lucide="check-circle" style="width:18px;height:18px;flex-shrink:0;"></i>
        <span>Password reset successfully. Please sign in with your new password.</span>
      </div>
    <?php endif; ?>

    <!-- Error Message -->
    <?php if (!empty($error)): ?>
      <div class="alert-banner alert-error" style="margin-bottom: var(--space-6);">
        <i data-lucide="alert-circle" style="width:18px;height:18px;flex-shrink:0;"></i>
        <span><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></span>
      </div>
    <?php endif; ?>

    <!-- Login Form -->
    <form method="POST" action="/inventoryiq/login.php" style="display:flex;flex-direction:column;gap:20px;">

      <div class="form-group">
        <label class="form-label" for="login_identifier">Login ID</label>
        <div class="input-group">
          <i data-lucide="user" class="input-icon" style="width:18px;height:18px;"></i>
          <input type="text" id="login_identifier" name="login_identifier" class="glass-input"
                 placeholder="e.g. admin@CompanyHandle"
                 value="<?php echo htmlspecialchars($login_id, ENT_QUOTES, 'UTF-8'); ?>"
                 required autocomplete="username">
        </div>
      </div>

      <div class="form-group">
        <label class="form-label" for="password">Password</label>
        <div class="input-group">
          <i data-lucide="lock" class="input-icon" style="width:18px;height:18px;"></i>
          <input type="password" id="password" name="password" class="glass-input"
                 placeholder="••••••••" required autocomplete="current-password"
                 style="padding-right:44px;">
          <button type="button" class="input-action" onclick="togglePasswordVisibility('password', this.querySelector('i'))">
            <i data-lucide="eye" style="width:18px;height:18px;"></i>
          </button>
        </div>
      </div>

      <div class="flex-between" style="padding:0 2px;">
        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
          <label class="toggle-switch">
            <input type="checkbox" name="remember_me">
            <span class="toggle-slider"></span>
          </label>
          <span style="font-size:14px;color:var(--text-label);">Remember me</span>
        </label>
        <a href="/inventoryiq/auth/reset_request.php" style="font-size:14px;">Forgot password?</a>
      </div>

      <button type="submit" class="btn btn-primary btn-full btn-lg" style="margin-top:8px;">
        Sign In
        <i data-lucide="arrow-right" style="width:18px;height:18px;"></i>
      </button>

    </form>

    <!-- Register Link -->
    <div class="login-footer">
      New to InventoryIQ? <a href="/inventoryiq/register.php"><strong>Register your company</strong></a>
    </div>

  </div>
</div>

<footer style="position: absolute; bottom: 24px; width: 100%; text-align: center; font-size: 13px; color: var(--text-muted); opacity: 0.8;">
  &copy; <?php echo date('Y'); ?> InventoryIQ. All rights reserved. Engineered by Shreyansh Sonker.
</footer>

<script src="/inventoryiq/js/app.js"></script>
</body>
</html>
