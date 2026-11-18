<?php
// Ito lang ang daan para makita ang na-upload na ID.
// Puwede lang ang admin, o ang mismong may-ari ng application.
$ROOT = '';
require __DIR__ . '/includes/bootstrap.php';

$u = current_user();
if (!$u) { http_response_code(401); exit('Please log in.'); }

$a = q('SELECT user_id, id_file, id_mime, id_original_name FROM applications WHERE id = ?', [(int)($_GET['id'] ?? 0)])->fetch();
if (!$a || ($u['role'] !== 'admin' && (int)$a['user_id'] !== (int)$u['id'])) { http_response_code(404); exit('Not found.'); }

$path = ID_DIR . '/' . basename($a['id_file']);
if (!preg_match('/^[a-f0-9]{32}\.(jpg|png|webp|pdf)$/', $a['id_file']) || !is_file($path)) {
    http_response_code(404);
    exit('The ID file is missing.');
}

$download = isset($_GET['download']);
$fname = preg_replace('/[^A-Za-z0-9._-]/', '_', $a['id_original_name'] ?: $a['id_file']);

header('Content-Type: ' . $a['id_mime']);
header('Content-Length: ' . filesize($path));
header('Content-Disposition: ' . ($download ? 'attachment' : 'inline') . '; filename="' . $fname . '"');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, no-store');
if (str_starts_with($a['id_mime'], 'image/')) header("Content-Security-Policy: default-src 'none'; img-src 'self'; style-src 'unsafe-inline'");
readfile($path);
