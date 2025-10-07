<?php
require __DIR__ . '/../config/env.php';
require __DIR__ . '/../db/sqlite.php';
require __DIR__ . '/../app/auth/common/auth_guard.php';

if (!function_exists('h')) {
    function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
}

$pdo = sqlite();
$uid = (int)($_SESSION['uid'] ?? 0);

$stmt = $pdo->prepare("SELECT COUNT(*) FROM TRANSACTIONS_LOCAL WHERE user_local_id=? AND sync_status='PENDING'");
$stmt->execute([$uid]);
$pendingTxn = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM TRANSACTIONS_LOCAL WHERE user_local_id=? AND sync_status='SYNCED'");
$stmt->execute([$uid]);
$syncedTxn = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM ACCOUNTS_LOCAL WHERE user_local_id=? AND (server_account_id IS NULL OR server_account_id='')");
$stmt->execute([$uid]);
$accNoSrv = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM CATEGORIES_LOCAL WHERE user_local_id=? AND (server_category_id IS NULL OR server_category_id='')");
$stmt->execute([$uid]);
$catNoSrv = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT MAX(last_sync_at) FROM TRANSACTIONS_LOCAL WHERE user_local_id=?");
$stmt->execute([$uid]);
$lastSync = $stmt->fetchColumn();

$msg = $_GET['msg'] ?? '';
?>
<!doctype html>
<<<<<<< HEAD
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Sync Center</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="icon" href="https://cdn-icons-png.flaticon.com/512/942/942751.png">
</head>
<body class="bg-gradient-to-br from-indigo-50 to-white text-gray-800 min-h-screen">

  <div class="max-w-5xl mx-auto p-6">
    <!-- Header -->
    <header class="flex items-center justify-between mb-8">
      <h1 class="text-3xl font-bold flex items-center gap-2 text-indigo-700">
        🔁 Sync Center
      </h1>
      <a href="<?= APP_BASE ?>/public/dashboard.php"
         class="text-indigo-600 hover:text-indigo-800 transition text-sm font-medium flex items-center gap-1">
         ← Back to Dashboard
      </a>
    </header>

    <!-- Alert -->
    <?php if ($msg): ?>
      <div class="bg-green-100 border border-green-300 text-green-800 px-4 py-3 rounded-lg mb-6 shadow-sm">
        ✅ <?= h($msg) ?>
      </div>
    <?php endif; ?>

    <!-- Status Cards -->
    <section class="mb-10">
      <h2 class="text-2xl font-semibold mb-4 flex items-center gap-2">📊 Current Status</h2>
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <div class="bg-white rounded-2xl shadow p-4 text-center hover:shadow-md transition">
          <p class="text-sm text-gray-500">Pending Transactions</p>
          <p class="text-2xl font-bold text-indigo-600"><?= $pendingTxn ?></p>
        </div>
        <div class="bg-white rounded-2xl shadow p-4 text-center hover:shadow-md transition">
          <p class="text-sm text-gray-500">Synced Transactions</p>
          <p class="text-2xl font-bold text-green-600"><?= $syncedTxn ?></p>
        </div>
        <div class="bg-white rounded-2xl shadow p-4 text-center hover:shadow-md transition">
          <p class="text-sm text-gray-500">Accounts Missing Server ID</p>
          <p class="text-2xl font-bold text-red-600"><?= $accNoSrv ?></p>
        </div>
        <div class="bg-white rounded-2xl shadow p-4 text-center hover:shadow-md transition">
          <p class="text-sm text-gray-500">Categories Missing Server ID</p>
          <p class="text-2xl font-bold text-red-600"><?= $catNoSrv ?></p>
        </div>
        <div class="bg-white rounded-2xl shadow p-4 text-center sm:col-span-2 lg:col-span-3 hover:shadow-md transition">
          <p class="text-sm text-gray-500">Last Sync</p>
          <p class="text-xl font-semibold text-gray-700"><?= $lastSync ?: '— never —' ?></p>
        </div>
      </div>
    </section>

    <!-- Actions -->
    <section class="mb-10">
      <h2 class="text-2xl font-semibold mb-4 flex items-center gap-2">⚙️ Sync Actions</h2>
      <div class="flex flex-wrap gap-4">
        <form method="post" action="<?= APP_BASE ?>/api/transactions_push.php">
          <button type="submit"
            class="bg-indigo-600 text-white px-5 py-3 rounded-xl font-medium shadow hover:bg-indigo-700 transition flex items-center gap-2">
            📤 Push Transactions
          </button>
        </form>
        <form method="post" action="<?= APP_BASE ?>/api/accounts_push.php">
          <button type="submit"
            class="bg-indigo-600 text-white px-5 py-3 rounded-xl font-medium shadow hover:bg-indigo-700 transition flex items-center gap-2">
            📤 Push Accounts
          </button>
        </form>
        <form method="post" action="<?= APP_BASE ?>/api/categories_pull.php">
          <button type="submit"
            class="bg-gray-100 text-gray-700 px-5 py-3 rounded-xl font-medium shadow hover:bg-gray-200 transition flex items-center gap-2">
            📥 Pull Categories
          </button>
        </form>
      </div>
    </section>

    <!-- Recent Transactions -->
    <section>
      <h2 class="text-2xl font-semibold mb-4 flex items-center gap-2">🧪 Recent Pending Transactions</h2>
      <div class="overflow-x-auto bg-white rounded-2xl shadow">
        <table class="min-w-full text-sm text-left border-collapse">
          <thead class="bg-indigo-100 text-indigo-800">
            <tr>
              <th class="py-3 px-4 font-semibold">Date</th>
              <th class="py-3 px-4 font-semibold">Type</th>
              <th class="py-3 px-4 font-semibold text-right">Amount</th>
              <th class="py-3 px-4 font-semibold">Client UUID</th>
            </tr>
          </thead>
          <tbody>
            <?php
              $dbg = $pdo->prepare("
                SELECT txn_date, txn_type, amount, client_txn_uuid
                FROM TRANSACTIONS_LOCAL
                WHERE user_local_id=? AND sync_status='PENDING'
                ORDER BY datetime(txn_date) DESC, local_txn_id DESC
                LIMIT 10
              ");
              $dbg->execute([$uid]);
              foreach ($dbg->fetchAll(PDO::FETCH_ASSOC) as $d):
            ?>
            <tr class="border-t hover:bg-gray-50 transition">
              <td class="py-2 px-4"><?= h($d['txn_date']) ?></td>
              <td class="py-2 px-4"><?= h($d['txn_type']) ?></td>
              <td class="py-2 px-4 text-right font-medium text-gray-700">
                <?= number_format((float)$d['amount'], 2) ?>
              </td>
              <td class="py-2 px-4 text-gray-500"><?= h($d['client_txn_uuid']) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>
  </div>

=======
<html>
<head>
    <meta charset="utf-8">
    <title>Sync Center</title>
    <link rel="stylesheet" href="<?= APP_BASE ?>/app/sync/sync.css">
</head>
<body>
<h2>Sync Center 🔁</h2>
<p><a href="<?= APP_BASE ?>/public/dashboard.php">← Back to Dashboard</a></p>

<?php if ($msg): ?>
    <div class="msg">✅ <?= h($msg) ?></div>
<?php endif; ?>

<h3>Status 📈</h3>
<ul>
    <li>Pending Transactions: <b><?= $pendingTxn ?></b></li>
    <li>Synced Transactions: <b><?= $syncedTxn ?></b></li>
    <li>Accounts without Server ID: <b><?= $accNoSrv ?></b></li>
    <li>Categories without Server ID: <b><?= $catNoSrv ?></b></li>
    <li>Last Sync: <b><?= $lastSync ?: '— never —' ?></b></li>
</ul>

<h3>Actions ⚙️</h3>
<form method="post" action="<?= APP_BASE ?>/api/transactions_push.php" style="display:inline-block; margin-right:8px;">
    <button type="submit">📤 Push Transactions ➜ Oracle (simulate)</button>
</form>

<form method="post" action="<?= APP_BASE ?>/api/accounts_push.php" style="display:inline-block; margin-right:8px;">
    <button type="submit">📤 Push Accounts ➜ Oracle (simulate)</button>
</form>

<form method="post" action="<?= APP_BASE ?>/api/categories_pull.php" style="display:inline-block;">
    <button type="submit">📥 Pull Categories ⬅ Oracle (simulate)</button>
</form>

<h3>Recent Pending (debug) 🧪</h3>
<table>
    <tr><th>Date</th><th>Type</th><th>Amount</th><th>Client UUID</th></tr>
    <?php
    $dbg = $pdo->prepare("
        SELECT txn_date, txn_type, amount, client_txn_uuid
        FROM TRANSACTIONS_LOCAL
        WHERE user_local_id=? AND sync_status='PENDING'
        ORDER BY datetime(txn_date) DESC, local_txn_id DESC
        LIMIT 10
    ");
    $dbg->execute([$uid]);
    foreach ($dbg->fetchAll(PDO::FETCH_ASSOC) as $d):
    ?>
        <tr>
            <td><?= h($d['txn_date']) ?></td>
            <td><?= h($d['txn_type']) ?></td>
            <td style="text-align:right"><?= number_format((float)$d['amount'],2) ?></td>
            <td><?= h($d['client_txn_uuid']) ?></td>
        </tr>
    <?php endforeach; ?>
</table>
>>>>>>> 664bf7fa94933368395ac1b4cee2ff311c865581
</body>
</html>
