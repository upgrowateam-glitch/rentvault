<?php
// Streams one document of one vehicle: /vehicle1/photo, /vehicle1/rc, ...
declare(strict_types=1);
require __DIR__ . '/lib/db.php';

$slug = (string)($_GET['slug'] ?? '');
$doc  = (string)($_GET['doc'] ?? '');

$vehicle = in_array($doc, DOC_TYPES, true) ? find_vehicle_by_slug($slug) : null;
$file = null;
if ($vehicle) {
    $docs = vehicle_docs((int)$vehicle['id']);
    $file = $docs[$doc] ?? null;
}
$path = $file ? storage_dir() . '/' . basename($file['stored_name']) : null;

if (!$path || !is_file($path)) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Not found';
    exit;
}

$ext = ALLOWED_MIME[$file['mime']] ?? 'bin';
$downloadName = preg_replace('/[^A-Za-z0-9_-]/', '', $vehicle['reg_number']) . '-' . $doc . '.' . $ext;

header('Content-Type: ' . $file['mime']);
header('Content-Length: ' . filesize($path));
header('Content-Disposition: inline; filename="' . $downloadName . '"');
header('Cache-Control: private, max-age=300');
header('X-Content-Type-Options: nosniff');
header('X-Robots-Tag: noindex, nofollow');
readfile($path);
