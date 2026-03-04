<?php
/**
 * InventoryIQ v2.0 — Company Registration (Screen 02)
 * AI Rules §4 — Company self-registration flow
 */
session_start();
require_once 'config/db.php';
require_once 'includes/audit.php';

$error = '';
$success = '';
$form = [
    'company_name' => '', 'owner_name' => '', 'email' => '',
    'phone' => '', 'address' => '', 'handle' => '',
    'password' => '', 'confirm_password' => '',
    'security_question' => '', 'security_answer' => ''
];

// Predefined security questions
$security_questions = [
    'What is your mother\'s maiden name?',
    'What was the name of your first pet?',
    'What city were you born in?',
    'What is your favourite food?',
    'What was the name of your first school?',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect form data
    foreach ($form as $key => $val) {
        $form[$key] = isset($_POST[$key]) ? trim($_POST[$key]) : '';
    }

    // Validation
    if (empty($form['company_name']) || empty($form['owner_name']) || empty($form['email']) ||
        empty($form['handle']) || empty($form['password']) || empty($form['confirm_password']) ||
        empty($form['security_question']) || empty($form['security_answer'])) {
        $error = 'All required fields must be filled.';
    } elseif (!filter_var($form['email'], FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($form['password']) < 8) {
        $error = 'Password must be at least 8 characters long.';
    } elseif ($form['password'] !== $form['confirm_password']) {
        $error = 'Passwords do not match.';
    } elseif (!preg_match('/^[a-zA-Z0-9_-]+$/', $form['handle'])) {
        $error = 'Company handle can only contain letters, numbers, hyphens, and underscores.';
    } else {
        // Check for duplicate handle
        $check_stmt = mysqli_prepare($conn, 'SELECT company_id FROM companies WHERE handle = ?');
        mysqli_stmt_bind_param($check_stmt, 's', $form['handle']);
        mysqli_stmt_execute($check_stmt);
        $check_result = mysqli_stmt_get_result($check_stmt);
        if (mysqli_fetch_assoc($check_result)) {
            $error = 'This company handle is already taken. Please choose another.';
        }
        mysqli_stmt_close($check_stmt);

        // Check for duplicate email
        if (empty($error)) {
            $email_stmt = mysqli_prepare($conn, 'SELECT company_id FROM companies WHERE email = ?');
            mysqli_stmt_bind_param($email_stmt, 's', $form['email']);
            mysqli_stmt_execute($email_stmt);
            $email_result = mysqli_stmt_get_result($email_stmt);
            if (mysqli_fetch_assoc($email_result)) {
                $error = 'This email is already registered.';
            }
            mysqli_stmt_close($email_stmt);
        }

        // Insert company + admin user in transaction
        if (empty($error)) {
            mysqli_begin_transaction($conn);
            try {
                // 1. Insert company
                $co_stmt = mysqli_prepare($conn,
                    'INSERT INTO companies (company_name, handle, owner_name, email, phone, address)
                     VALUES (?, ?, ?, ?, ?, ?)'
                );
                mysqli_stmt_bind_param($co_stmt, 'ssssss',
                    $form['company_name'], $form['handle'], $form['owner_name'],
                    $form['email'], $form['phone'], $form['address']
                );
                mysqli_stmt_execute($co_stmt);
                $company_id = mysqli_insert_id($conn);
                mysqli_stmt_close($co_stmt);

                // 2. Insert default categories
                $default_cats = ['Electronics', 'Clothing', 'Food and Beverages', 'Furniture', 'Stationery', 'Other'];
                $cat_stmt = mysqli_prepare($conn,
                    'INSERT INTO categories (company_id, category_name) VALUES (?, ?)'
                );
                foreach ($default_cats as $cat) {
                    mysqli_stmt_bind_param($cat_stmt, 'is', $company_id, $cat);
                    mysqli_stmt_execute($cat_stmt);
                }
                mysqli_stmt_close($cat_stmt);

                // 3. Insert company admin user
                $password_hash = password_hash($form['password'], PASSWORD_BCRYPT, ['cost' => 12]);
                $login_identifier = $form['email']; // Company Admin uses email as login
                $sec_q = $form['security_question'];
                $sec_a_hash = password_hash(strtolower(trim($form['security_answer'])), PASSWORD_BCRYPT, ['cost' => 12]);
                $user_stmt = mysqli_prepare($conn,
                    'INSERT INTO users (company_id, warehouse_id, full_name, login_identifier, password_hash, role, security_question, security_answer_hash)
                     VALUES (?, NULL, ?, ?, ?, ?, ?, ?)'
                );
                $admin_role = 'company_admin';
                mysqli_stmt_bind_param($user_stmt, 'issssss',
                    $company_id, $form['owner_name'], $login_identifier, $password_hash, $admin_role, $sec_q, $sec_a_hash
                );
                mysqli_stmt_execute($user_stmt);
                $user_id = mysqli_insert_id($conn);
                mysqli_stmt_close($user_stmt);

                mysqli_commit($conn);

                // Audit log
                write_audit_log($conn, $user_id, 'company_admin', $company_id, null, 'USER_CREATE', 'Company registered: ' . $form['company_name']);

                header('Location: /inventoryiq/login.php?success=1');
                exit;

            } catch (Exception $e) {
                mysqli_rollback($conn);
                $error = 'Registration failed. Please try again.';
            }
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
  <title>Register — InventoryIQ</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@600;700;800&family=DM+Sans:wght@400;500;600&family=Fira+Code&display=swap" rel="stylesheet">
  <script src="https://unpkg.com/lucide@latest"></script>
  <link rel="stylesheet" href="/inventoryiq/css/style.css">
</head>
<body class="aurora-bg">

<div class="login-wrapper">
  <div class="login-card tilt-card" style="width:600px;">

    <div class="login-logo">
      <span class="text-gradient text-gradient-glow">InventoryIQ</span>
    </div>
    <h2 style="text-align:center;font-size:24px;margin-bottom:4px;">Create Your Company</h2>
    <p class="login-subtitle">Register your business on InventoryIQ</p>

    <?php if (!empty($error)): ?>
      <div class="alert-banner alert-error" style="margin-bottom:var(--space-6);">
        <i data-lucide="alert-circle" style="width:18px;height:18px;flex-shrink:0;"></i>
        <span><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></span>
      </div>
    <?php endif; ?>

    <form method="POST" action="/inventoryiq/register.php" style="display:flex;flex-direction:column;gap:20px;">

      <div class="grid-2">
        <div class="form-group">
          <label class="form-label" for="company_name">Company Name <span class="required">*</span></label>
          <input type="text" id="company_name" name="company_name" class="glass-input"
                 value="<?php echo htmlspecialchars($form['company_name'], ENT_QUOTES, 'UTF-8'); ?>" required>
        </div>
        <div class="form-group">
          <label class="form-label" for="owner_name">Owner Name <span class="required">*</span></label>
          <input type="text" id="owner_name" name="owner_name" class="glass-input"
                 value="<?php echo htmlspecialchars($form['owner_name'], ENT_QUOTES, 'UTF-8'); ?>" required>
        </div>
      </div>

      <div class="grid-2">
        <div class="form-group">
          <label class="form-label" for="email">Email <span class="required">*</span></label>
          <input type="email" id="email" name="email" class="glass-input"
                 value="<?php echo htmlspecialchars($form['email'], ENT_QUOTES, 'UTF-8'); ?>" required>
        </div>
        <div class="form-group">
          <label class="form-label" for="phone">Phone</label>
          <input type="tel" id="phone" name="phone" class="glass-input"
                 value="<?php echo htmlspecialchars($form['phone'], ENT_QUOTES, 'UTF-8'); ?>">
        </div>
      </div>

      <div class="form-group">
        <label class="form-label" for="address">Company Address</label>
        <textarea id="address" name="address" class="glass-textarea" rows="3"><?php echo htmlspecialchars($form['address'], ENT_QUOTES, 'UTF-8'); ?></textarea>
      </div>

      <div class="form-group">
        <label class="form-label" for="handle">Company Handle <span class="required">*</span></label>
        <div class="input-group">
          <span class="input-icon" style="color:var(--accent-indigo-light);font-size:14px;font-weight:600;pointer-events:none;">@</span>
          <input type="text" id="handle" name="handle" class="glass-input"
                 placeholder="e.g. RetailCorp"
                 value="<?php echo htmlspecialchars($form['handle'], ENT_QUOTES, 'UTF-8'); ?>" required>
        </div>
        <small style="color:var(--text-muted);font-size:12px;">Used in login identifiers. Letters, numbers, hyphens only.</small>
      </div>

      <div class="grid-2">
        <div class="form-group">
          <label class="form-label" for="password">Password <span class="required">*</span></label>
          <input type="password" id="password" name="password" class="glass-input"
                 placeholder="Min 8 characters" required>
        </div>
        <div class="form-group">
          <label class="form-label" for="confirm_password">Confirm Password <span class="required">*</span></label>
          <input type="password" id="confirm_password" name="confirm_password" class="glass-input"
                 placeholder="Re-enter password" required>
        </div>
      </div>

      <div class="grid-2">
        <div class="form-group">
          <label class="form-label" for="security_question">Security Question <span class="required">*</span></label>
          <select id="security_question" name="security_question" class="glass-select" required>
            <option value="">Select a question</option>
            <?php foreach ($security_questions as $q): ?>
              <option value="<?php echo htmlspecialchars($q, ENT_QUOTES, 'UTF-8'); ?>"
                <?php echo $form['security_question'] === $q ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($q, ENT_QUOTES, 'UTF-8'); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label" for="security_answer">Security Answer <span class="required">*</span></label>
          <input type="text" id="security_answer" name="security_answer" class="glass-input"
                 placeholder="Your answer" value="<?php echo htmlspecialchars($form['security_answer'], ENT_QUOTES, 'UTF-8'); ?>" required>
          <small style="color:var(--text-muted);font-size:11px;">Used to reset your password. Case-insensitive.</small>
        </div>
      </div>

      <button type="submit" class="btn btn-primary btn-full btn-lg">
        <i data-lucide="building-2" style="width:18px;height:18px;"></i>
        Register Company
      </button>

    </form>

    <div class="login-footer">
      Already registered? <a href="/inventoryiq/login.php"><strong>Sign In</strong></a>
    </div>

  </div>
</div>

<script src="/inventoryiq/js/app.js"></script>
</body>
</html>
