<?php
/**
 * InventoryIQ v2.0 — Warehouse User Management
 * AI Rules §5 — Company Admin manages WH staff
 */
require_once '../config/db.php';
require_once '../auth/check.php';
require_once '../includes/audit.php';
check_role(['company_admin']);

$company_id = $_SESSION['company_id'];
$wh_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$page_title = 'Warehouse Users';
$error = '';

// Predefined security questions
$security_questions = [
    'What is your mother\'s maiden name?',
    'What was the name of your first pet?',
    'What city were you born in?',
    'What is your favourite food?',
    'What was the name of your first school?',
];

// Verify warehouse belongs to company
$stmt = mysqli_prepare($conn, 'SELECT warehouse_name, handle FROM warehouses WHERE warehouse_id = ? AND company_id = ?');
mysqli_stmt_bind_param($stmt, 'ii', $wh_id, $company_id);
mysqli_stmt_execute($stmt);
$wh = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$wh) { header('Location: /inventoryiq/warehouse/list.php'); exit; }

// Handle add user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add') {
        $full_name = trim($_POST['full_name'] ?? '');
        $login_id = trim($_POST['login_identifier'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $user_role = in_array($_POST['role'] ?? '', ['wh_manager', 'wh_staff']) ? $_POST['role'] : 'wh_staff';
        $sec_q = trim($_POST['security_question'] ?? '');
        $sec_a = trim($_POST['security_answer'] ?? '');

        if (empty($full_name) || empty($login_id) || empty($password) || empty($sec_q) || empty($sec_a)) {
            $error = 'All fields are required.';
        } elseif (strlen($password) < 8) {
            $error = 'Password must be at least 8 characters.';
        } else {
            // Check duplicate login_identifier
            $dup = mysqli_prepare($conn, 'SELECT user_id FROM users WHERE login_identifier = ?');
            mysqli_stmt_bind_param($dup, 's', $login_id);
            mysqli_stmt_execute($dup);
            if (mysqli_fetch_assoc(mysqli_stmt_get_result($dup))) {
                $error = 'Login ID already exists.';
            }
            mysqli_stmt_close($dup);

            if (empty($error)) {
                $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
                $sec_a_hash = password_hash(strtolower($sec_a), PASSWORD_BCRYPT, ['cost' => 12]);
                $stmt = mysqli_prepare($conn,
                    'INSERT INTO users (company_id, warehouse_id, full_name, login_identifier, password_hash, role, security_question, security_answer_hash)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
                );
                mysqli_stmt_bind_param($stmt, 'iissssss', $company_id, $wh_id, $full_name, $login_id, $hash, $user_role, $sec_q, $sec_a_hash);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);

                write_audit_log($conn, $_SESSION['user_id'], 'company_admin', $company_id, $wh_id,
                    'USER_CREATE', 'Added user: ' . $full_name . ' (' . $user_role . ')');

                header('Location: /inventoryiq/warehouse/users.php?id=' . $wh_id . '&success=1');
                exit;
            }
        }
    } elseif ($_POST['action'] === 'toggle_status') {
        $uid = (int)$_POST['user_id'];
        $new_status = $_POST['new_status'] === 'inactive' ? 'inactive' : 'active';
        $upd = mysqli_prepare($conn, 'UPDATE users SET status = ? WHERE user_id = ? AND company_id = ?');
        mysqli_stmt_bind_param($upd, 'sii', $new_status, $uid, $company_id);
        mysqli_stmt_execute($upd);
        mysqli_stmt_close($upd);
        write_audit_log($conn, $_SESSION['user_id'], 'company_admin', $company_id, $wh_id,
            'USER_UPDATE', 'User #' . $uid . ' status set to ' . $new_status);
        header('Location: /inventoryiq/warehouse/users.php?id=' . $wh_id . '&success=1');
        exit;
    }
}

// Fetch users for this warehouse
$stmt = mysqli_prepare($conn,
    'SELECT user_id, full_name, login_identifier, role, status, created_at
     FROM users WHERE company_id = ? AND warehouse_id = ?
     ORDER BY role, full_name'
);
mysqli_stmt_bind_param($stmt, 'ii', $company_id, $wh_id);
mysqli_stmt_execute($stmt);
$users = [];
$r = mysqli_stmt_get_result($stmt);
while ($row = mysqli_fetch_assoc($r)) { $users[] = $row; }
mysqli_stmt_close($stmt);

require_once '../includes/header.php';
?>

<div class="flex-between mb-6">
  <div>
    <h1 style="font-size:28px;">Users — <?php echo htmlspecialchars($wh['warehouse_name'], ENT_QUOTES, 'UTF-8'); ?></h1>
    <p class="label">@<?php echo htmlspecialchars($wh['handle'], ENT_QUOTES, 'UTF-8'); ?></p>
  </div>
  <a href="/inventoryiq/warehouse/list.php" class="btn btn-ghost">
    <i data-lucide="arrow-left" style="width:16px;height:16px;"></i> Back
  </a>
</div>

<?php if (!empty($error)): ?>
<div class="alert-banner alert-error mb-6">
  <i data-lucide="alert-circle" style="width:18px;height:18px;flex-shrink:0;"></i>
  <span><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></span>
</div>
<?php endif; ?>

<div style="display:grid;grid-template-columns:4fr 35fr;gap:var(--space-6);">

  <!-- Add User -->
  <div class="glass-card-static" style="min-width:320px;">
    <h3 class="section-title mb-4">Add User</h3>
    <form method="POST" style="display:flex;flex-direction:column;gap:16px;">
      <input type="hidden" name="action" value="add">
      <div class="form-group">
        <label class="form-label">Full Name <span class="required">*</span></label>
        <input type="text" name="full_name" class="glass-input" required>
      </div>
      <div class="form-group">
        <label class="form-label">Login ID <span class="required">*</span></label>
        <input type="text" name="login_identifier" class="glass-input" placeholder="e.g. john@<?php echo htmlspecialchars($wh['handle'], ENT_QUOTES, 'UTF-8'); ?>" required>
      </div>
      <div class="form-group">
        <label class="form-label">Password <span class="required">*</span></label>
        <input type="password" name="password" class="glass-input" placeholder="Min 8 characters" required>
      </div>
      <div class="form-group">
        <label class="form-label">Role</label>
        <select name="role" class="glass-select">
          <option value="wh_staff">Warehouse Staff</option>
          <option value="wh_manager">Warehouse Manager</option>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Security Question <span class="required">*</span></label>
        <select name="security_question" class="glass-select" required>
          <option value="">Select a question</option>
          <?php foreach ($security_questions as $q): ?>
            <option value="<?php echo htmlspecialchars($q, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($q, ENT_QUOTES, 'UTF-8'); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Security Answer <span class="required">*</span></label>
        <input type="text" name="security_answer" class="glass-input" placeholder="Case-insensitive" required>
      </div>
      <button type="submit" class="btn btn-primary">
        <i data-lucide="user-plus" style="width:16px;height:16px;"></i> Add User
      </button>
    </form>
  </div>

  <!-- User List -->
  <div>
    <h3 class="section-title mb-4"><?php echo count($users); ?> User(s)</h3>
    <?php if (empty($users)): ?>
      <div class="glass-card-static" style="text-align:center;padding:40px;">
        <p style="color:var(--text-muted);">No users assigned to this warehouse yet.</p>
      </div>
    <?php else: ?>
    <div class="data-table-container">
      <table class="data-table">
        <thead>
          <tr><th>Name</th><th>Login ID</th><th>Role</th><th>Status</th><th>Joined</th><th>Actions</th></tr>
        </thead>
        <tbody>
        <?php foreach ($users as $u): ?>
          <tr>
            <td style="font-weight:600;color:var(--text-primary);"><?php echo htmlspecialchars($u['full_name'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td><span class="mono" style="font-size:11px;"><?php echo htmlspecialchars($u['login_identifier'], ENT_QUOTES, 'UTF-8'); ?></span></td>
            <td><?php echo str_replace('_',' ',ucwords($u['role'],'_')); ?></td>
            <td><span class="badge badge-<?php echo $u['status']; ?>"><?php echo ucfirst($u['status']); ?></span></td>
            <td style="font-size:12px;color:var(--text-muted);"><?php echo date('d M Y', strtotime($u['created_at'])); ?></td>
            <td>
              <form method="POST" style="display:inline;">
                <input type="hidden" name="action" value="toggle_status">
                <input type="hidden" name="user_id" value="<?php echo (int)$u['user_id']; ?>">
                <?php if ($u['status'] === 'active'): ?>
                  <input type="hidden" name="new_status" value="inactive">
                  <button type="submit" class="btn btn-danger" style="padding:4px 10px;font-size:11px;">Deactivate</button>
                <?php else: ?>
                  <input type="hidden" name="new_status" value="active">
                  <button type="submit" class="btn btn-success" style="padding:4px 10px;font-size:11px;">Activate</button>
                <?php endif; ?>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php
require_once '../includes/footer.php';
mysqli_close($conn);
?>
