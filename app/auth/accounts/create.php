<?php
// pfms/app/auth/accounts/create.php
require __DIR__ . '/../../../config/env.php';
require __DIR__ . '/../../../db/sqlite.php';
require __DIR__ . '/../common/auth_guard.php';

<<<<<<< HEAD
// ---- utils ----
$util = __DIR__ . '/../util.php';
if (file_exists($util)) require $util;
if (!function_exists('now_iso')) { function now_iso(): string { return date('Y-m-d H:i:s'); } }
if (!function_exists('h')) { function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); } }
=======
// ---- utils (load if exists; otherwise provide fallbacks) ----
$util = __DIR__ . '/../util.php';
if (file_exists($util)) {
    require $util;
}
if (!function_exists('now_iso')) {
    function now_iso(): string { return date('Y-m-d H:i:s'); }
}
if (!function_exists('h')) {
    function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
}
>>>>>>> 664bf7fa94933368395ac1b4cee2ff311c865581

$pdo = sqlite();
$uid = (int)($_SESSION['uid'] ?? 0);
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $name     = trim($_POST['account_name'] ?? '');
  $type     = $_POST['account_type'] ?? '';
  $currency = trim($_POST['currency_code'] ?? 'LKR');
  $opening  = isset($_POST['opening_balance']) ? (float)$_POST['opening_balance'] : 0.0;

  if ($name === '') $errors[] = 'Name is required';
  if (!in_array($type, ['CASH','BANK','CARD','MOBILE'], true)) $errors[] = 'Invalid type';

  if (!$errors) {
    $now  = now_iso();
    $stmt = $pdo->prepare("
      INSERT INTO ACCOUNTS_LOCAL
        (user_local_id, account_name, account_type, currency_code, opening_balance, is_active, created_at, updated_at)
      VALUES (?, ?, ?, ?, ?, 1, ?, ?)
    ");
    $stmt->execute([$uid, $name, $type, $currency, $opening, $now, $now]);
    header('Location: ' . APP_BASE . '/app/auth/accounts/index.php');
    exit;
  }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Create Account</title>
<<<<<<< HEAD
  <link rel="stylesheet" href="<?= APP_BASE ?>/app/auth/accounts/create.css">
</head>
<body>

<div class="container">
  <div class="header">
    <h2>➕ Create Account</h2>
    <a href="<?= APP_BASE ?>/app/auth/accounts/index.php" class="back-btn">← Back</a>
  </div>

  <?php if (!empty($errors)): ?>
    <div class="error-box">
      <b>Please fix:</b>
      <ul>
        <?php foreach ($errors as $e): ?>
          <li><?= h($e) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <form method="post" class="form-grid">

    <div class="form-group">
      <label for="account_name">Name</label>
      <input type="text" id="account_name" name="account_name" 
             placeholder="e.g., Cash Wallet" 
             value="<?= h($_POST['account_name'] ?? '') ?>" required>
    </div>

    <div class="form-group">
      <label for="account_type">Type</label>
      <select id="account_type" name="account_type" required>
        <option value="CASH"   <?= (($_POST['account_type'] ?? '')==='CASH')?'selected':'' ?>>CASH</option>
        <option value="BANK"   <?= (($_POST['account_type'] ?? '')==='BANK')?'selected':'' ?>>BANK</option>
        <option value="CARD"   <?= (($_POST['account_type'] ?? '')==='CARD')?'selected':'' ?>>CARD</option>
        <option value="MOBILE" <?= (($_POST['account_type'] ?? '')==='MOBILE')?'selected':'' ?>>MOBILE</option>
      </select>
    </div>

    <div class="form-group">
      <label for="currency_code">Currency</label>
      <input type="text" id="currency_code" name="currency_code" 
             placeholder="e.g., LKR" 
             value="<?= h($_POST['currency_code'] ?? 'LKR') ?>">
    </div>

    <div class="form-group">
      <label for="opening_balance">Opening Balance</label>
      <input type="number" id="opening_balance" name="opening_balance" 
             step="0.01" value="<?= h($_POST['opening_balance'] ?? '0') ?>">
    </div>

    <div class="form-group checkbox-group">
      <input type="checkbox" id="is_active" name="is_active" checked>
      <label for="is_active">Active</label>
    </div>

    <div class="form-actions">
      <button type="submit">Create Account</button>
      <a href="<?= APP_BASE ?>/app/auth/accounts/index.php" class="cancel-link">Cancel</a>
    </div>

  </form>
</div>

=======
  <link rel="stylesheet" href="create.css">
</head>
<body>
  <div class="page-wrapper">
    <div class="card">
      <p><a href="<?= APP_BASE ?>/app/auth/accounts/index.php">← Back</a></p>
      <h2>Create Account</h2>

      <?php if (!empty($errors)): ?>
        <div class="error">
          <b>Please fix:</b>
          <ul>
            <?php foreach ($errors as $e): ?>
              <li><?= h($e) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <form method="post">
        <label>Name</label>
        <input name="account_name" placeholder="e.g., Cash Wallet" value="<?= h($_POST['account_name'] ?? '') ?>" required>

        <label>Type</label>
        <select name="account_type" required>
          <option value="CASH"   <?= (($_POST['account_type'] ?? '')==='CASH')?'selected':'' ?>>CASH</option>
          <option value="BANK"   <?= (($_POST['account_type'] ?? '')==='BANK')?'selected':'' ?>>BANK</option>
          <option value="CARD"   <?= (($_POST['account_type'] ?? '')==='CARD')?'selected':'' ?>>CARD</option>
          <option value="MOBILE" <?= (($_POST['account_type'] ?? '')==='MOBILE')?'selected':'' ?>>MOBILE</option>
        </select>

        <label>Currency</label>
        <input name="currency_code" value="<?= h($_POST['currency_code'] ?? 'LKR') ?>">

        <label>Opening Balance</label>
        <input name="opening_balance" type="number" step="0.01" value="<?= h($_POST['opening_balance'] ?? '0') ?>">

        <div class="form-actions">
          <button type="submit">💾 Save</button>
          <a class="cancel-btn" href="<?= APP_BASE ?>/app/auth/accounts/index.php">Cancel</a>
        </div>
      </form>
    </div>
  </div>
>>>>>>> 664bf7fa94933368395ac1b4cee2ff311c865581
</body>
</html>
