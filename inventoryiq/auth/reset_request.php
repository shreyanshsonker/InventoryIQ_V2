<?php
/**
 * InventoryIQ v2.0 — Password Reset via Security Question
 * 3-step flow: Identify → Verify Answer → Set New Password
 */
session_start();
require_once '../config/db.php';
require_once '../includes/audit.php';

$error = '';
$success = '';
$step = isset($_POST['step']) ? (int)$_POST['step'] : 1;

// Predefined security questions
$security_questions = [
    'What is your mother\'s maiden name?',
    'What was the name of your first pet?',
    'What city were you born in?',
    'What is your favourite food?',
    'What was the name of your first school?',
];

// Track user across steps
$reset_user_id = isset($_POST['reset_user_id']) ? (int)$_POST['reset_user_id'] : 0;
$login_id = isset($_POST['login_identifier']) ? trim($_POST['login_identifier']) : '';
$question_display = '';

// ============================================================
// Step 1: Identify user by Login ID
// ============================================================
if ($step === 1 && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($login_id)) {
        $error = 'Please enter your Login ID.';
    } else {
        $stmt = mysqli_prepare($conn,
            'SELECT user_id, full_name, security_question, security_answer_hash, locked_until, status
             FROM users WHERE login_identifier = ?'
        );
        mysqli_stmt_bind_param($stmt, 's', $login_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        if (!$user) {
            $error = 'No account found with that Login ID.';
        } elseif ($user['status'] !== 'active') {
            $error = 'This account has been deactivated. Contact your administrator.';
        } elseif ($user['locked_until'] && strtotime($user['locked_until']) > time()) {
            $remaining = ceil((strtotime($user['locked_until']) - time()) / 60);
            $error = 'Account locked. Try again in ' . $remaining . ' minute(s).';
        } elseif (empty($user['security_question']) || empty($user['security_answer_hash'])) {
            $error = 'No security question set for this account. Contact your administrator to reset your password.';
        } else {
            // Move to step 2
            $step = 2;
            $reset_user_id = $user['user_id'];
            $question_display = $user['security_question'];
        }
    }
}

// ============================================================
// Step 2: Verify security answer
// ============================================================
if ($step === 2 && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['security_answer'])) {
    $answer = trim($_POST['security_answer']);

    if (empty($answer)) {
        $error = 'Please enter your answer.';
        // Reload question
        $stmt = mysqli_prepare($conn, 'SELECT security_question FROM users WHERE user_id = ?');
        mysqli_stmt_bind_param($stmt, 'i', $reset_user_id);
        mysqli_stmt_execute($stmt);
        $r = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($r);
        $question_display = $row ? $row['security_question'] : '';
        mysqli_stmt_close($stmt);
    } else {
        // Fetch user for verification
        $stmt = mysqli_prepare($conn,
            'SELECT user_id, security_question, security_answer_hash, failed_attempts, locked_until
             FROM users WHERE user_id = ?'
        );
        mysqli_stmt_bind_param($stmt, 'i', $reset_user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        if (!$user) {
            $error = 'User not found. Please start over.';
            $step = 1;
        } elseif ($user['locked_until'] && strtotime($user['locked_until']) > time()) {
            $remaining = ceil((strtotime($user['locked_until']) - time()) / 60);
            $error = 'Account locked due to too many failed attempts. Try again in ' . $remaining . ' minute(s).';
            $step = 1;
        } elseif (!password_verify(strtolower($answer), $user['security_answer_hash'])) {
            // Wrong answer — increment failed attempts
            $new_attempts = (int)$user['failed_attempts'] + 1;
            if ($new_attempts >= 5) {
                $lock_stmt = mysqli_prepare($conn,
                    'UPDATE users SET failed_attempts = ?, locked_until = DATE_ADD(NOW(), INTERVAL 15 MINUTE) WHERE user_id = ?'
                );
                mysqli_stmt_bind_param($lock_stmt, 'ii', $new_attempts, $reset_user_id);
                mysqli_stmt_execute($lock_stmt);
                mysqli_stmt_close($lock_stmt);
                $error = 'Too many failed attempts. Account locked for 15 minutes.';
                $step = 1;
            } else {
                $fail_stmt = mysqli_prepare($conn, 'UPDATE users SET failed_attempts = ? WHERE user_id = ?');
                mysqli_stmt_bind_param($fail_stmt, 'ii', $new_attempts, $reset_user_id);
                mysqli_stmt_execute($fail_stmt);
                mysqli_stmt_close($fail_stmt);
                $error = 'Incorrect answer. ' . (5 - $new_attempts) . ' attempt(s) remaining.';
                $question_display = $user['security_question'];
            }
            write_audit_log($conn, $reset_user_id, 'unknown', null, null, 'RESET_FAIL', 'Wrong security answer (attempt ' . $new_attempts . ')');
        } else {
            // Correct answer → move to step 3
            $step = 3;
        }
    }
}

// ============================================================
// Step 3: Set new password
// ============================================================
if ($step === 3 && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_password'])) {
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (strlen($new_password) < 8) {
        $error = 'Password must be at least 8 characters.';
        $step = 3;
    } elseif ($new_password !== $confirm_password) {
        $error = 'Passwords do not match.';
        $step = 3;
    } else {
        $hash = password_hash($new_password, PASSWORD_BCRYPT, ['cost' => 12]);
        $stmt = mysqli_prepare($conn,
            'UPDATE users SET password_hash = ?, failed_attempts = 0, locked_until = NULL WHERE user_id = ?'
        );
        mysqli_stmt_bind_param($stmt, 'si', $hash, $reset_user_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        write_audit_log($conn, $reset_user_id, 'unknown', null, null, 'PASSWORD_RESET', 'Password reset via security question');

        header('Location: /inventoryiq/login.php?reset=1');
        exit;
    }
}

mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reset Password — InventoryIQ</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@600;700;800&family=DM+Sans:wght@400;500;600&family=Fira+Code&display=swap" rel="stylesheet">
  <script src="https://unpkg.com/lucide@latest"></script>
  <link rel="stylesheet" href="/inventoryiq/css/style.css">
  <link rel="stylesheet" href="/inventoryiq/css/dark.css">
</head>
<body class="aurora-bg">

<div class="login-wrapper">
  <div class="login-card tilt-card">

    <!-- Logo -->
    <div class="login-logo">
      <span class="text-gradient text-gradient-glow">InventoryIQ</span>
    </div>
    <p class="login-subtitle">Reset your password</p>

    <!-- Progress Steps -->
    <div style="display:flex;align-items:center;justify-content:center;gap:8px;margin-bottom:24px;">
      <div style="width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;
                  <?php echo $step >= 1 ? 'background:linear-gradient(135deg,#6366F1,#818CF8);color:#fff;' : 'background:rgba(255,255,255,0.06);color:var(--text-muted);'; ?>">1</div>
      <div style="width:32px;height:2px;<?php echo $step >= 2 ? 'background:#6366F1;' : 'background:rgba(255,255,255,0.1);'; ?>"></div>
      <div style="width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;
                  <?php echo $step >= 2 ? 'background:linear-gradient(135deg,#6366F1,#818CF8);color:#fff;' : 'background:rgba(255,255,255,0.06);color:var(--text-muted);'; ?>">2</div>
      <div style="width:32px;height:2px;<?php echo $step >= 3 ? 'background:#6366F1;' : 'background:rgba(255,255,255,0.1);'; ?>"></div>
      <div style="width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;
                  <?php echo $step >= 3 ? 'background:linear-gradient(135deg,#6366F1,#818CF8);color:#fff;' : 'background:rgba(255,255,255,0.06);color:var(--text-muted);'; ?>">3</div>
    </div>

    <!-- Error Message -->
    <?php if (!empty($error)): ?>
      <div class="alert-banner alert-error" style="margin-bottom: var(--space-6);">
        <i data-lucide="alert-circle" style="width:18px;height:18px;flex-shrink:0;"></i>
        <span><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></span>
      </div>
    <?php endif; ?>

    <!-- ========== STEP 1: Enter Login ID ========== -->
    <?php if ($step === 1): ?>
    <form method="POST" style="display:flex;flex-direction:column;gap:20px;">
      <input type="hidden" name="step" value="1">
      <div class="form-group">
        <label class="form-label" for="login_identifier">Login ID</label>
        <div class="input-group">
          <i data-lucide="user" class="input-icon" style="width:18px;height:18px;"></i>
          <input type="text" id="login_identifier" name="login_identifier" class="glass-input"
                 placeholder="Enter your login ID" value="<?php echo htmlspecialchars($login_id, ENT_QUOTES, 'UTF-8'); ?>" required autocomplete="username">
        </div>
      </div>
      <button type="submit" class="btn btn-primary btn-full btn-lg">
        Continue <i data-lucide="arrow-right" style="width:18px;height:18px;"></i>
      </button>
    </form>

    <!-- ========== STEP 2: Answer Security Question ========== -->
    <?php elseif ($step === 2): ?>
    <form method="POST" style="display:flex;flex-direction:column;gap:20px;">
      <input type="hidden" name="step" value="2">
      <input type="hidden" name="reset_user_id" value="<?php echo $reset_user_id; ?>">
      <input type="hidden" name="login_identifier" value="<?php echo htmlspecialchars($login_id, ENT_QUOTES, 'UTF-8'); ?>">

      <div class="glass-card-static" style="padding:16px;text-align:center;">
        <p style="color:var(--text-muted);font-size:12px;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:6px;">Security Question</p>
        <p style="color:var(--text-primary);font-size:16px;font-weight:600;"><?php echo htmlspecialchars($question_display, ENT_QUOTES, 'UTF-8'); ?></p>
      </div>

      <div class="form-group">
        <label class="form-label" for="security_answer">Your Answer</label>
        <div class="input-group">
          <i data-lucide="shield-question" class="input-icon" style="width:18px;height:18px;"></i>
          <input type="text" id="security_answer" name="security_answer" class="glass-input"
                 placeholder="Enter your answer" required autocomplete="off">
        </div>
        <span style="font-size:11px;color:var(--text-muted);">Answer is case-insensitive</span>
      </div>

      <button type="submit" class="btn btn-primary btn-full btn-lg">
        Verify <i data-lucide="check" style="width:18px;height:18px;"></i>
      </button>
    </form>

    <!-- ========== STEP 3: Set New Password ========== -->
    <?php elseif ($step === 3): ?>
    <form method="POST" style="display:flex;flex-direction:column;gap:20px;">
      <input type="hidden" name="step" value="3">
      <input type="hidden" name="reset_user_id" value="<?php echo $reset_user_id; ?>">

      <div class="form-group">
        <label class="form-label" for="new_password">New Password</label>
        <div class="input-group">
          <i data-lucide="lock" class="input-icon" style="width:18px;height:18px;"></i>
          <input type="password" id="new_password" name="new_password" class="glass-input"
                 placeholder="Minimum 8 characters" required minlength="8" autocomplete="new-password"
                 style="padding-right:44px;">
          <button type="button" class="input-action" onclick="togglePasswordVisibility('new_password', this.querySelector('i'))">
            <i data-lucide="eye" style="width:18px;height:18px;"></i>
          </button>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label" for="confirm_password">Confirm Password</label>
        <div class="input-group">
          <i data-lucide="lock" class="input-icon" style="width:18px;height:18px;"></i>
          <input type="password" id="confirm_password" name="confirm_password" class="glass-input"
                 placeholder="Re-enter new password" required minlength="8" autocomplete="new-password"
                 style="padding-right:44px;">
          <button type="button" class="input-action" onclick="togglePasswordVisibility('confirm_password', this.querySelector('i'))">
            <i data-lucide="eye" style="width:18px;height:18px;"></i>
          </button>
        </div>
      </div>

      <button type="submit" class="btn btn-primary btn-full btn-lg">
        Reset Password <i data-lucide="key" style="width:18px;height:18px;"></i>
      </button>
    </form>
    <?php endif; ?>

    <!-- Back to Login -->
    <div class="login-footer">
      <a href="/inventoryiq/login.php"><i data-lucide="arrow-left" style="width:14px;height:14px;display:inline;"></i> Back to Sign In</a>
    </div>

  </div>
</div>

<footer style="position: absolute; bottom: 24px; width: 100%; text-align: center; font-size: 13px; color: var(--text-muted); opacity: 0.8;">
  &copy; <?php echo date('Y'); ?> InventoryIQ. All rights reserved. Engineered by Shreyansh Sonker.
</footer>

<script src="/inventoryiq/js/app.js"></script>
</body>
</html>
