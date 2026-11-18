<?php
// Admin JSON API used by the RentVault mobile app.
// Every call: https://rentdocs.upgrowa.com/api/?action=<name>
// Auth: header "X-Auth-Token: <token from login>"
declare(strict_types=1);
require __DIR__ . '/../lib/db.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Robots-Tag: noindex, nofollow');

function out(array $data, int $code = 200): void
{
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

function fail(string $msg, int $code = 400): void
{
    out(['error' => $msg], $code);
}

function input(): array
{
    static $b = null;
    if ($b === null) {
        $ct = $_SERVER['CONTENT_TYPE'] ?? '';
        $b = str_contains($ct, 'application/json')
            ? (json_decode((string)file_get_contents('php://input'), true) ?: [])
            : $_POST;
    }
    return $b;
}

function require_post(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        fail('Method not allowed', 405);
    }
}

function require_admin(): int
{
    $tok = $_SERVER['HTTP_X_AUTH_TOKEN'] ?? '';
    if (!preg_match('/^[a-f0-9]{64}$/', $tok)) {
        fail('Unauthorized', 401);
    }
    $st = db()->prepare('SELECT admin_id FROM admin_tokens WHERE token_hash = ? AND expires_at > NOW()');
    $st->execute([hash('sha256', $tok)]);
    $id = $st->fetchColumn();
    if (!$id) {
        fail('Unauthorized', 401);
    }
    return (int)$id;
}

function vehicle_json(array $v): array
{
    $docs = [];
    $existing = vehicle_docs((int)$v['id']);
    foreach (DOC_TYPES as $t) {
        $d = $existing[$t] ?? null;
        $docs[$t] = $d ? [
            'url'         => BASE_URL . '/' . $v['slug'] . '/' . $t . '?v=' . strtotime($d['uploaded_at']),
            'mime'        => $d['mime'],
            'name'        => $d['original_name'],
            'size'        => (int)$d['size_bytes'],
            'uploaded_at' => $d['uploaded_at'],
        ] : null;
    }
    return [
        'id'         => (int)$v['id'],
        'slug'       => $v['slug'],
        'name'       => $v['name'],
        'reg_number' => $v['reg_number'],
        'public_url' => BASE_URL . '/' . $v['slug'],
        'docs'       => $docs,
        'created_at' => $v['created_at'],
    ];
}

function get_vehicle(int $id): array
{
    $st = db()->prepare('SELECT * FROM vehicles WHERE id = ?');
    $st->execute([$id]);
    $v = $st->fetch();
    if (!$v) {
        fail('Vehicle not found', 404);
    }
    return $v;
}

function clean_vehicle_input(): array
{
    $name = trim((string)(input()['name'] ?? ''));
    $reg  = strtoupper(preg_replace('/\s+/', '', (string)(input()['reg_number'] ?? '')));
    if ($name === '' || mb_strlen($name) > 120) {
        fail('Vehicle name is required (max 120 characters).');
    }
    if (!preg_match('/^[A-Z0-9-]{4,32}$/', $reg)) {
        fail('Registration number must be 4–32 letters/numbers, e.g. KL07AB1234.');
    }
    return [$name, $reg];
}

function delete_stored_file(string $storedName): void
{
    $p = storage_dir() . '/' . basename($storedName);
    if (is_file($p)) {
        @unlink($p);
    }
}

try {
    $action = (string)($_GET['action'] ?? '');

    switch ($action) {

        case 'login': {
            require_post();
            $ip = $_SERVER['REMOTE_ADDR'] ?? '';
            $st = db()->prepare('SELECT COUNT(*) FROM login_attempts WHERE ip = ? AND attempted_at > (NOW() - INTERVAL 15 MINUTE)');
            $st->execute([$ip]);
            if ((int)$st->fetchColumn() >= 10) {
                fail('Too many failed attempts. Try again in 15 minutes.', 429);
            }

            $user = trim((string)(input()['username'] ?? ''));
            $pass = (string)(input()['password'] ?? '');
            $st = db()->prepare('SELECT id, password_hash FROM admins WHERE username = ?');
            $st->execute([$user]);
            $admin = $st->fetch();

            if (!$admin || !password_verify($pass, $admin['password_hash'])) {
                db()->prepare('INSERT INTO login_attempts (ip) VALUES (?)')->execute([$ip]);
                fail('Invalid username or password.', 401);
            }

            db()->prepare('DELETE FROM login_attempts WHERE ip = ? OR attempted_at < (NOW() - INTERVAL 1 DAY)')->execute([$ip]);
            db()->exec('DELETE FROM admin_tokens WHERE expires_at < NOW()');

            $token = bin2hex(random_bytes(32));
            db()->prepare('INSERT INTO admin_tokens (token_hash, admin_id, expires_at) VALUES (?, ?, NOW() + INTERVAL 30 DAY)')
                ->execute([hash('sha256', $token), $admin['id']]);

            out(['token' => $token, 'username' => $user]);
        }

        case 'logout': {
            require_post();
            require_admin();
            $tok = $_SERVER['HTTP_X_AUTH_TOKEN'];
            db()->prepare('DELETE FROM admin_tokens WHERE token_hash = ?')->execute([hash('sha256', $tok)]);
            out(['ok' => true]);
        }

        case 'vehicles': {
            require_admin();
            $rows = db()->query('SELECT * FROM vehicles ORDER BY id ASC')->fetchAll();
            out(['vehicles' => array_map('vehicle_json', $rows)]);
        }

        case 'vehicle': {
            require_admin();
            out(['vehicle' => vehicle_json(get_vehicle((int)($_GET['id'] ?? 0)))]);
        }

        case 'vehicle_create': {
            require_post();
            require_admin();
            [$name, $reg] = clean_vehicle_input();
            $pdo = db();
            $st = $pdo->prepare('SELECT COUNT(*) FROM vehicles WHERE reg_number = ?');
            $st->execute([$reg]);
            if ((int)$st->fetchColumn() > 0) {
                fail('A vehicle with this registration number already exists.', 409);
            }
            $pdo->beginTransaction();
            $pdo->prepare('INSERT INTO vehicles (name, reg_number) VALUES (?, ?)')->execute([$name, $reg]);
            $id = (int)$pdo->lastInsertId();
            $pdo->prepare('UPDATE vehicles SET slug = ? WHERE id = ?')->execute(['vehicle' . $id, $id]);
            $pdo->commit();
            out(['vehicle' => vehicle_json(get_vehicle($id))], 201);
        }

        case 'vehicle_update': {
            require_post();
            require_admin();
            $id = (int)(input()['id'] ?? 0);
            get_vehicle($id);
            [$name, $reg] = clean_vehicle_input();
            $st = db()->prepare('SELECT COUNT(*) FROM vehicles WHERE reg_number = ? AND id <> ?');
            $st->execute([$reg, $id]);
            if ((int)$st->fetchColumn() > 0) {
                fail('Another vehicle already uses this registration number.', 409);
            }
            db()->prepare('UPDATE vehicles SET name = ?, reg_number = ? WHERE id = ?')->execute([$name, $reg, $id]);
            out(['vehicle' => vehicle_json(get_vehicle($id))]);
        }

        case 'vehicle_delete': {
            require_post();
            require_admin();
            $id = (int)(input()['id'] ?? 0);
            get_vehicle($id);
            foreach (vehicle_docs($id) as $d) {
                delete_stored_file($d['stored_name']);
            }
            db()->prepare('DELETE FROM vehicles WHERE id = ?')->execute([$id]); // documents cascade
            out(['ok' => true]);
        }

        case 'upload': {
            // multipart/form-data: id, doc (photo|insurance|rc|pollution), file
            require_post();
            require_admin();
            $id  = (int)($_POST['id'] ?? 0);
            $doc = (string)($_POST['doc'] ?? '');
            get_vehicle($id);
            if (!in_array($doc, DOC_TYPES, true)) {
                fail('Invalid document type.');
            }
            $f = $_FILES['file'] ?? null;
            if (!$f || ($f['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                $code = $f['error'] ?? UPLOAD_ERR_NO_FILE;
                fail($code === UPLOAD_ERR_INI_SIZE || $code === UPLOAD_ERR_FORM_SIZE
                    ? 'File is too large for the server.'
                    : 'No file received (upload error ' . $code . ').');
            }
            if ($f['size'] > MAX_UPLOAD_BYTES) {
                fail('File is larger than ' . (MAX_UPLOAD_BYTES / 1024 / 1024) . ' MB.');
            }
            $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']) ?: '';
            if (!isset(ALLOWED_MIME[$mime])) {
                fail('Only JPG, PNG, WEBP images or PDF files are allowed.');
            }
            if ($doc === 'photo' && !str_starts_with($mime, 'image/')) {
                fail('Vehicle photo must be an image.');
            }

            $dir = storage_dir();
            if (!is_dir($dir)) {
                mkdir($dir, 0750, true);
            }
            $stored = bin2hex(random_bytes(16)) . '.' . ALLOWED_MIME[$mime];
            if (!move_uploaded_file($f['tmp_name'], $dir . '/' . $stored)) {
                fail('Could not save the file on the server.', 500);
            }

            $old = vehicle_docs($id)[$doc] ?? null;
            $original = mb_substr(basename((string)$f['name']), 0, 255);
            db()->prepare(
                'INSERT INTO documents (vehicle_id, doc_type, stored_name, original_name, mime, size_bytes, uploaded_at)
                 VALUES (?, ?, ?, ?, ?, ?, NOW())
                 ON DUPLICATE KEY UPDATE stored_name = VALUES(stored_name), original_name = VALUES(original_name),
                     mime = VALUES(mime), size_bytes = VALUES(size_bytes), uploaded_at = NOW()'
            )->execute([$id, $doc, $stored, $original, $mime, (int)$f['size']]);
            if ($old) {
                delete_stored_file($old['stored_name']);
            }
            out(['vehicle' => vehicle_json(get_vehicle($id))]);
        }

        case 'doc_delete': {
            require_post();
            require_admin();
            $id  = (int)(input()['id'] ?? 0);
            $doc = (string)(input()['doc'] ?? '');
            get_vehicle($id);
            $existing = vehicle_docs($id)[$doc] ?? null;
            if ($existing) {
                delete_stored_file($existing['stored_name']);
                db()->prepare('DELETE FROM documents WHERE id = ?')->execute([$existing['id']]);
            }
            out(['vehicle' => vehicle_json(get_vehicle($id))]);
        }

        default:
            fail('Not found', 404);
    }
} catch (Throwable $e) {
    error_log('[RentVault API] ' . $e->getMessage());
    fail('Server error. Please try again.', 500);
}
