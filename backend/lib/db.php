<?php
declare(strict_types=1);

require __DIR__ . '/config.php';

const DOC_TYPES = ['photo', 'insurance', 'rc', 'pollution'];
const DOC_LABELS = [
    'photo'     => 'Vehicle Photo',
    'insurance' => 'Insurance',
    'rc'        => 'RC (Registration Certificate)',
    'pollution' => 'Pollution Certificate (PUC)',
];
const ALLOWED_MIME = [
    'image/jpeg'      => 'jpg',
    'image/png'       => 'png',
    'image/webp'      => 'webp',
    'application/pdf' => 'pdf',
];

function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $pdo = new PDO(
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]
        );
    }
    return $pdo;
}

function storage_dir(): string
{
    return dirname(__DIR__) . '/storage';
}

function find_vehicle_by_slug(string $slug): ?array
{
    if (!preg_match('/^vehicle[0-9]+$/', $slug)) {
        return null;
    }
    $st = db()->prepare('SELECT * FROM vehicles WHERE slug = ?');
    $st->execute([$slug]);
    $row = $st->fetch();
    return $row ?: null;
}

/** Returns [docType => row] for a vehicle. */
function vehicle_docs(int $vehicleId): array
{
    $st = db()->prepare('SELECT * FROM documents WHERE vehicle_id = ?');
    $st->execute([$vehicleId]);
    $docs = [];
    foreach ($st->fetchAll() as $d) {
        $docs[$d['doc_type']] = $d;
    }
    return $docs;
}

function h(?string $s): string
{
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}
