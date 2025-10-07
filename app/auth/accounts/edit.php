<?php
// pfms/app/auth/accounts/edit.php

require __DIR__ . '/../../../config/env.php';
require __DIR__ . '/../../../db/sqlite.php';
require __DIR__ . '/../common/auth_guard.php';

foreach ([
    __DIR__ . '/../util.php',
    __DIR__ . '/../common/util.php',
] as $u) {
    if (file_exists($u)) { require_once $u; }
}
if (!function_exists('now_iso')) {
    function now_iso(): string { return date('Y-m-d H:i:s'); }
}
if (!function_exists('h')) {
    function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
}

$pdo = sqlite();
$uid = (int)($_SESSION['uid'] ?? 0);
$id  = (int)($_GET['id'] ?? 0);

// Load current account
$stmt = $pdo->prepare("
  SELECT local_account_id, account_name, account_type, currency_code, opening_balance, is_active
  FROM ACCOUNTS_LOCAL
  WHERE local_account_id = ? AND user_local_id = ?
");
$stmt->execute([$id, $uid]);
$acc = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$acc) { http_response_code(404); echo "Not found"; exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $name     = trim($_POST['account_name'] ?? '');
  $type     = $_POST['account_type'] ?? '';
  $currency = $_POST['currency_code'] ?? 'LKR';
  $opening  = (float)($_POST['opening_balance'] ?? 0);
  $active   = isset($_POST['is_active']) ? 1 : 0;

  if ($name === '' || !in_array($type, ['CASH','BANK','CARD','MOBILE'], true)) {
    http_response_code(422);
    echo "Invalid input"; exit;
  }

  $now = now_iso();
  $upd = $pdo->prepare("
    UPDATE ACCOUNTS_LOCAL
       SET account_name=?, account_type=?, currency_code=?, opening_balance=?, is_active=?, updated_at=?
     WHERE local_account_id=? AND user_local_id=?
  ");
  $upd->execute([$name, $type, $currency, $opening, $active, $now, $id, $uid]);

  header('Location: ' . APP_BASE . '/app/auth/accounts/index.php');
  exit;
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Edit Account</title>
  <link rel="stylesheet" href="<?= APP_BASE ?>/app/auth/accounts/edit.css">
</head>
<body>

<div class="container">
  <div class="header">
    <h2>Edit Account</h2>
    <a href="<?= APP_BASE ?>/app/auth/accounts/index.php">← Back</a>
  </div>

  <form method="post">
    <div class="form-grid">
      <div>
        <label>Name</label>
        <input type="text" name="account_name" value="<?= h($acc['account_name']) ?>">
      </div>

      <div>
        <label>Type</label>
        <select name="account_type">
          <?php foreach (['CASH','BANK','CARD','MOBILE'] as $t): ?>
            <option value="<?= h($t) ?>" <?= $acc['account_type']===$t?'selected':'' ?>><?= h($t) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div>
        <label>Currency</label>
        <input type="text" name="currency_code" value="<?= h($acc['currency_code']) ?>">
      </div>

      <div>
        <label>Opening Balance</label>
        <input type="number" name="opening_balance" step="0.01" value="<?= h($acc['opening_balance']) ?>">
      </div>

      <div style="display:flex;align-items:center;margin-top:8px;">
        <input type="checkbox" name="is_active" <?= $acc['is_active'] ? 'checked' : '' ?>>
        <label style="margin:0;">Active</label>
      </div>
    </div>

    <button type="submit">Update</button>
    <a href="<?= APP_BASE ?>/app/auth/accounts/index.php" class="cancel-link">Cancel</a>
  </form>
</div>

</body>
</html>
