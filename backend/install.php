<?php
// ONE-TIME SETUP: creates the tables and the single admin account.
// Open https://rentdocs.upgrowa.com/install.php, fill the form, then DELETE THIS FILE.
declare(strict_types=1);
require __DIR__ . '/lib/db.php';

header('Content-Type: text/html; charset=utf-8');
header('X-Robots-Tag: noindex, nofollow');

$msg = '';
$ok = false;

try {
    $sql = preg_replace('/^\s*--.*$/m', '', file_get_contents(__DIR__ . '/schema.sql'));
    foreach (array_filter(array_map('trim', explode(';', $sql))) as $stmt) {
        db()->exec($stmt);
    }
    $adminExists = (int)db()->query('SELECT COUNT(*) FROM admins')->fetchColumn() > 0;
} catch (Throwable $e) {
    $msg = 'Database error: check DB_HOST / DB_NAME / DB_USER / DB_PASS in lib/config.php.';
    $adminExists = true;
}

if (!$adminExists && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $key  = (string)($_POST['key'] ?? '');
    $user = trim((string)($_POST['username'] ?? ''));
    $pass = (string)($_POST['password'] ?? '');

    if (INSTALL_KEY === 'CHANGE_ME_TO_A_LONG_RANDOM_STRING' || !hash_equals(INSTALL_KEY, $key)) {
        $msg = 'Wrong install key (set INSTALL_KEY in lib/config.php first).';
    } elseif (!preg_match('/^[A-Za-z0-9_.@-]{3,64}$/', $user)) {
        $msg = 'Username: 3–64 letters, numbers, or _ . @ -';
    } elseif (strlen($pass) < 8) {
        $msg = 'Password must be at least 8 characters.';
    } else {
        $st = db()->prepare('INSERT INTO admins (username, password_hash) VALUES (?, ?)');
        $st->execute([$user, password_hash($pass, PASSWORD_DEFAULT)]);
        $ok = true;
        $adminExists = true;
        $msg = 'Admin created. Now DELETE install.php from the server and sign in from the app.';
    }
} elseif ($adminExists && !$msg) {
    $msg = 'Setup already completed. Delete install.php from the server.';
}
?><!doctype html>
<html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>RentVault setup</title>
<style>body{font-family:system-ui,sans-serif;max-width:420px;margin:40px auto;padding:0 16px}input,button{width:100%;padding:10px;margin:6px 0 14px;font-size:1rem}.msg{padding:10px;border-radius:8px;background:<?= $ok ? '#e6f7ee' : '#fdecea' ?>}</style>
</head><body>
<h2>RentVault setup</h2>
<?php if ($msg): ?><p class="msg"><?= h($msg) ?></p><?php endif; ?>
<?php if (!$adminExists): ?>
<form method="post">
  <label>Install key (INSTALL_KEY from config.php)<input name="key" type="password" required></label>
  <label>Admin username<input name="username" required></label>
  <label>Admin password (min 8 chars)<input name="password" type="password" minlength="8" required></label>
  <button type="submit">Create admin</button>
</form>
<?php endif; ?>
</body></html>
