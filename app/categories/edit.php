<?php
require __DIR__ . '/../../config/env.php';
require __DIR__ . '/../../db/sqlite.php';
require __DIR__ . '/../auth/common/auth_guard.php';

$pdo = sqlite();
$uid = (int)$_SESSION['uid'];
$id  = (int)($_GET['id'] ?? 0);

$find = $pdo->prepare("
  SELECT local_category_id, parent_local_id, category_name, category_type
  FROM CATEGORIES_LOCAL
  WHERE local_category_id=? AND user_local_id=?");
$find->execute([$id, $uid]);
$cat = $find->fetch(PDO::FETCH_ASSOC);
if (!$cat) { http_response_code(404); echo "Not found"; exit; }

$errors = [];
$selectedType = $_POST['category_type'] ?? $cat['category_type'];

$parentStmt = $pdo->prepare("
  SELECT local_category_id, category_name
  FROM CATEGORIES_LOCAL
  WHERE user_local_id=? AND category_type=? AND local_category_id <> ?
  ORDER BY category_name");
$parentStmt->execute([$uid, $selectedType, $id]);
$parentOptions = $parentStmt->fetchAll(PDO::FETCH_ASSOC);

$txnCnt = (int)$pdo->query("SELECT COUNT(*) FROM TRANSACTIONS_LOCAL WHERE category_local_id=".$id)->fetchColumn();
$childCnt = (int)$pdo->query("SELECT COUNT(*) FROM CATEGORIES_LOCAL WHERE parent_local_id=".$id)->fetchColumn();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $name = trim($_POST['category_name'] ?? '');
  $type = $_POST['category_type'] ?? '';
  $parentId = $_POST['parent_local_id'] !== '' ? (int)$_POST['parent_local_id'] : null;

  if ($name === '') $errors[] = "Name is required";
  if (!in_array($type, ['INCOME','EXPENSE'])) $errors[] = "Invalid type";
  if ($parentId === $id) $errors[] = "A category cannot be its own parent";

  if ($parentId !== null) {
    $chk = $pdo->prepare("SELECT category_type FROM CATEGORIES_LOCAL WHERE local_category_id=? AND user_local_id=?");
    $chk->execute([$parentId, $uid]);
    $parent = $chk->fetch(PDO::FETCH_ASSOC);
    if (!$parent) $errors[] = "Invalid parent";
    elseif ($parent['category_type'] !== $type) $errors[] = "Parent must be same type";
  }

  if ($type !== $cat['category_type'] && $childCnt > 0)
    $errors[] = "Cannot change type while this category has children";
  if ($type !== $cat['category_type'] && $txnCnt > 0)
    $errors[] = "Cannot change type because transactions exist";

  if (!$errors) {
    $now = date('Y-m-d H:i:s');
    $upd = $pdo->prepare("
      UPDATE CATEGORIES_LOCAL
      SET category_name=?, category_type=?, parent_local_id=?, updated_at=?
      WHERE local_category_id=? AND user_local_id=?");
    $upd->execute([$name, $type, $parentId, $now, $id, $uid]);
    header('Location: ' . APP_BASE . '/app/categories/index.php'); exit;
  }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Edit Category</title>
  <style>
    body {
      font-family: 'Segoe UI', sans-serif;
      background: #f5f7fa;
      margin: 0;
      padding: 0;
    }
    .container {
      width: 90%;
      max-width: 600px;
      margin: 60px auto;
      background: white;
      border-radius: 14px;
      box-shadow: 0 4px 15px rgba(0,0,0,0.1);
      padding: 25px 35px;
    }
    .header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 25px;
    }
    .header h2 {
      margin: 0;
      color: #333;
    }
    .header a {
      text-decoration: none;
      color: #0077cc;
      font-weight: 600;
      transition: 0.3s;
    }
    .header a:hover {
      color: #004f8a;
    }
    form {
      display: flex;
      flex-direction: column;
      gap: 18px;
    }
    label {
      font-weight: 600;
      margin-bottom: 5px;
      color: #444;
    }
    input, select {
      padding: 10px;
      border-radius: 6px;
      border: 1px solid #ccc;
      width: 100%;
      font-size: 15px;
      transition: border 0.3s;
    }
    input:focus, select:focus {
      border-color: #0077cc;
      outline: none;
    }
    button {
      padding: 12px;
      border: none;
      background: #0077cc;
      color: white;
      font-weight: 600;
      border-radius: 6px;
      cursor: pointer;
      transition: background 0.3s;
    }
    button:hover {
      background: #005fa3;
    }
    .info {
      background: #eaf4ff;
      color: #004a80;
      border-left: 4px solid #0077cc;
      padding: 10px 14px;
      margin-bottom: 10px;
      border-radius: 6px;
      font-size: 14px;
    }
    .error-box {
      background: #ffecec;
      color: #b30000;
      border-left: 4px solid #d10000;
      padding: 10px 14px;
      border-radius: 6px;
    }
    .form-actions {
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    .cancel-btn {
      text-decoration: none;
      color: #666;
      background: #eee;
      padding: 10px 16px;
      border-radius: 6px;
      transition: background 0.3s;
    }
    .cancel-btn:hover {
      background: #ddd;
    }
  </style>
</head>
<body>
<div class="container">
  <div class="header">
    <h2> Edit Category</h2>
    <a href="<?= APP_BASE ?>/app/categories/index.php">← Back</a>
  </div>

  <?php if ($txnCnt>0): ?><div class="info">ℹ️ This category has <b><?= $txnCnt ?></b> transactions.</div><?php endif; ?>
  <?php if ($childCnt>0): ?><div class="info">ℹ️ This category has <b><?= $childCnt ?></b> child categories.</div><?php endif; ?>

  <?php if ($errors): ?>
  <div class="error-box">
    <b>Please fix:</b>
    <ul>
      <?php foreach ($errors as $e): ?>
        <li><?= htmlspecialchars($e) ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
  <?php endif; ?>

  <form method="post">
    <div>
      <label>Name</label>
      <input name="category_name" value="<?= htmlspecialchars($_POST['category_name'] ?? $cat['category_name']) ?>" required>
    </div>

    <div>
      <label>Type</label>
      <select name="category_type" onchange="this.form.submit()">
        <option value="INCOME"  <?= ($selectedType==='INCOME')?'selected':'' ?>>INCOME</option>
        <option value="EXPENSE" <?= ($selectedType==='EXPENSE')?'selected':'' ?>>EXPENSE</option>
      </select>
      <small style="color:#666;">Changing type reloads parent list</small>
    </div>

    <div>
      <label>Parent (optional, same type)</label>
      <select name="parent_local_id">
        <option value="">— None —</option>
        <?php
        $selParent = $_POST['parent_local_id'] ?? $cat['parent_local_id'];
        foreach($parentOptions as $p): ?>
          <option value="<?= $p['local_category_id'] ?>" <?= ($selParent == $p['local_category_id'])?'selected':'' ?>>
            <?= htmlspecialchars($p['category_name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="form-actions">
      <button type="submit">💾 Update</button>
      <a href="<?= APP_BASE ?>/app/categories/index.php" class="cancel-btn">Cancel</a>
    </div>
  </form>
</div>
</body>
</html>
