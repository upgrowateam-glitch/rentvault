<?php
// Customer-facing, read-only page for one vehicle: rentdocs.upgrowa.com/vehicle1
declare(strict_types=1);
require __DIR__ . '/lib/db.php';

$vehicle = find_vehicle_by_slug((string)($_GET['slug'] ?? ''));
if (!$vehicle) {
    require __DIR__ . '/index.php'; // same "not found" page as the root
    exit;
}

$docs = vehicle_docs((int)$vehicle['id']);
$base = '/' . $vehicle['slug'] . '/';

header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-store');
header('X-Robots-Tag: noindex, nofollow');

function doc_url(string $base, string $type, array $doc): string
{
    return $base . $type . '?v=' . strtotime($doc['uploaded_at']);
}
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title><?= h($vehicle['reg_number']) ?> · Vehicle Documents</title>
<style>
  :root{--bg:#f6f7f3;--card:#fff;--text:#1b2230;--muted:#667085;--accent:#14532d;--gold:#e5b80b;--line:#e4e7e0;--ok:#12805c}
  @media (prefers-color-scheme:dark){:root{--bg:#0e1410;--card:#17201a;--text:#e8ece6;--muted:#98a29a;--accent:#2f8a52;--gold:#f2c94c;--line:#26332a;--ok:#3fcf8e}}
  header{border-bottom:3px solid var(--gold)}
  *{box-sizing:border-box}
  body{margin:0;font-family:system-ui,-apple-system,"Segoe UI",Roboto,sans-serif;background:var(--bg);color:var(--text)}
  main{max-width:640px;margin:0 auto;padding:16px}
  header{padding:8px 0 16px}
  .reg{display:inline-block;font-weight:700;letter-spacing:.06em;border:2px solid var(--text);border-radius:6px;padding:4px 10px;font-size:1.05rem}
  h1{font-size:1.4rem;margin:10px 0 2px}
  .muted{color:var(--muted);font-size:.9rem;margin:0}
  .card{background:var(--card);border:1px solid var(--line);border-radius:14px;margin:12px 0;overflow:hidden}
  .card h2{font-size:1rem;margin:0;padding:12px 14px;border-bottom:1px solid var(--line);display:flex;justify-content:space-between;align-items:center}
  .badge{font-size:.75rem;font-weight:600;color:var(--ok)}
  .badge.none{color:var(--muted)}
  .body{padding:12px 14px}
  img{display:block;width:100%;height:auto;border-radius:8px;background:var(--line)}
  .btn{display:block;text-align:center;padding:12px;border-radius:10px;background:var(--accent);color:#fff;text-decoration:none;font-weight:600}
  .empty{color:var(--muted);font-size:.9rem;margin:0}
  footer{text-align:center;color:var(--muted);font-size:.8rem;padding:20px 0}
</style>
</head>
<body>
<main>
  <header>
    <span class="reg"><?= h($vehicle['reg_number']) ?></span>
    <h1><?= h($vehicle['name']) ?></h1>
    <p class="muted">Official vehicle documents</p>
  </header>

<?php foreach (DOC_TYPES as $type):
    $d = $docs[$type] ?? null; ?>
  <section class="card">
    <h2><?= h(DOC_LABELS[$type]) ?>
      <span class="badge<?= $d ? '' : ' none' ?>"><?= $d ? 'Available' : 'Not uploaded' ?></span>
    </h2>
    <div class="body">
    <?php if (!$d): ?>
      <p class="empty">This document has not been uploaded yet.</p>
    <?php elseif (str_starts_with($d['mime'], 'image/')): ?>
      <a href="<?= h(doc_url($base, $type, $d)) ?>" target="_blank" rel="noopener">
        <img src="<?= h(doc_url($base, $type, $d)) ?>" alt="<?= h(DOC_LABELS[$type]) ?>" loading="lazy">
      </a>
    <?php else: ?>
      <a class="btn" href="<?= h(doc_url($base, $type, $d)) ?>" target="_blank" rel="noopener">Open <?= h(DOC_LABELS[$type]) ?> (PDF)</a>
    <?php endif; ?>
    </div>
  </section>
<?php endforeach; ?>

  <footer>Documents provided by the vehicle owner.</footer>
</main>
</body>
</html>
