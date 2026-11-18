<?php
declare(strict_types=1);

// Ang page ang nagse-set ng $ROOT ('' para sa root pages, '../' para sa admin pages)
define('ROOT', $ROOT ?? '');
require_once __DIR__ . '/../config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => !empty($_SERVER['HTTPS']),
    ]);
    session_start();
}

const APP_STATUSES = ['Pending', 'Under Review', 'Approved', 'Rejected', 'Completed'];
const PET_STATUSES = ['Available', 'Pending', 'Adopted'];
const ACTIVE_APP_STATUSES = ['Pending', 'Under Review', 'Approved'];
const ID_TYPES = [
    'Philippine National ID (PhilSys)', "Driver's License", 'Passport', 'UMID', 'SSS ID',
    'PRC ID', 'Postal ID', "Voter's ID", 'School ID', 'Other government-issued ID',
];
const ID_MIMES  = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'application/pdf' => 'pdf'];
const PET_MIMES = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];

class UploadException extends RuntimeException {}

/* ---------- Database ---------- */
function db(): PDO
{
    static $pdo = null;
    if ($pdo) return $pdo;
    try {
        $pdo = new PDO(DB_DSN, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
        if ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite') $pdo->exec('PRAGMA foreign_keys = ON');
    } catch (PDOException $e) {
        http_response_code(500);
        exit('Hindi maka-connect sa database. I-check ang config.php at siguraduhing na-import ang database.sql.');
    }
    return $pdo;
}

function q(string $sql, array $params = []): PDOStatement
{
    $st = db()->prepare($sql);
    $st->execute($params);
    return $st;
}

/* ---------- Helpers ---------- */
function e($s): string { return htmlspecialchars((string)($s ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function now(): string { return date('Y-m-d H:i:s'); }
function fmt_date(?string $d): string { return $d ? date('M j, Y', strtotime($d)) : '—'; }
function status_class(string $s): string { return str_replace(' ', '-', $s); }
function badge(string $s): string { return '<span class="badge ' . e(status_class($s)) . '">' . e($s) . '</span>'; }

function redirect(string $to): never
{
    header('Location: ' . $to);
    exit;
}

function flash(string $type, string $msg): void { $_SESSION['flash'][] = [$type, $msg]; }
function take_flashes(): array { $f = $_SESSION['flash'] ?? []; unset($_SESSION['flash']); return $f; }

/* ---------- CSRF ---------- */
function csrf_token(): string
{
    return $_SESSION['csrf'] ??= bin2hex(random_bytes(32));
}
function csrf_field(): string { return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">'; }
function csrf_check(): void
{
    $ok = isset($_POST['csrf']) && hash_equals(csrf_token(), (string)$_POST['csrf']);
    if (!$ok) {
        http_response_code(419);
        exit('Expired ang form. Bumalik at subukan ulit.');
    }
}
function is_post(): bool { return ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST'; }

/* ---------- Auth ---------- */
function current_user(): ?array
{
    static $cache = false;
    if ($cache !== false) return $cache;
    $cache = null;
    if (!empty($_SESSION['uid'])) {
        $u = q('SELECT * FROM users WHERE id = ?', [$_SESSION['uid']])->fetch();
        if ($u && (int)$u['is_active'] === 1) $cache = $u;
        else unset($_SESSION['uid']);
    }
    return $cache;
}

function login_user(int $id): void
{
    session_regenerate_id(true);
    $_SESSION['uid'] = $id;
}

function require_login(): array
{
    $u = current_user();
    if (!$u) {
        $next = basename($_SERVER['SCRIPT_NAME']) . (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '');
        redirect(ROOT . 'login.php' . (ROOT === '' ? '?next=' . urlencode($next) : ''));
    }
    return $u;
}

function require_admin(): array
{
    $u = require_login();
    if ($u['role'] !== 'admin') {
        http_response_code(403);
        exit('Admin lang ang puwedeng pumasok dito.');
    }
    return $u;
}

// Tinatanggap lang ang relative na .php page para walang open redirect
function safe_next(?string $n): string
{
    return ($n && preg_match('/^[A-Za-z0-9_\-]+\.php(\?[A-Za-z0-9_=&%\-]*)?$/', $n)) ? $n : '';
}

/* ---------- Uploads ---------- */
/** Nagbabalik ['name','mime','orig'] o nag-throw ng UploadException na may malinaw na message. */
function handle_upload(array $f, array $allowed, int $maxBytes, string $dir): array
{
    $err = $f['error'] ?? UPLOAD_ERR_NO_FILE;
    $mb  = round($maxBytes / 1048576) . ' MB';
    if ($err === UPLOAD_ERR_NO_FILE) throw new UploadException('Please choose a file to upload.');
    if ($err === UPLOAD_ERR_INI_SIZE || $err === UPLOAD_ERR_FORM_SIZE)
        throw new UploadException("File is too large (max $mb). Kung mas maliit na ito, taasan ang upload_max_filesize sa php.ini.");
    if ($err !== UPLOAD_ERR_OK) throw new UploadException('Upload failed. Please try again.');
    if ($f['size'] > $maxBytes) throw new UploadException("File is too large (max $mb).");
    if (!is_uploaded_file($f['tmp_name'])) throw new UploadException('Invalid upload.');

    // Tinitingnan ang totoong laman ng file, hindi ang extension na sinabi ng browser
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
    if (!isset($allowed[$mime])) throw new UploadException('Unsupported file type. Allowed: ' . strtoupper(implode(', ', array_unique($allowed))) . '.');
    if (str_starts_with($mime, 'image/') && !@getimagesize($f['tmp_name'])) throw new UploadException('That image looks corrupted.');

    if (!is_dir($dir)) mkdir($dir, 0755, true);
    $name = bin2hex(random_bytes(16)) . '.' . $allowed[$mime];
    if (!move_uploaded_file($f['tmp_name'], $dir . '/' . $name)) throw new UploadException('Could not save the file on the server.');
    return ['name' => $name, 'mime' => $mime, 'orig' => mb_substr(basename((string)$f['name']), 0, 180)];
}

function delete_id_file(?string $name): void
{
    if ($name && preg_match('/^[a-f0-9]{32}\.(jpg|png|webp|pdf)$/', $name)) @unlink(ID_DIR . '/' . $name);
}
function delete_pet_photo(?string $photo): void
{
    if ($photo && str_starts_with($photo, 'uploads/pets/')) @unlink(__DIR__ . '/../' . $photo);
}

/* ---------- Pets & applications ---------- */
function pic(array $p): string
{
    if (!empty($p['photo']) && is_file(__DIR__ . '/../' . $p['photo']))
        return '<img src="' . e(ROOT . $p['photo']) . '" alt="' . e($p['name']) . '" loading="lazy">';
    return '<span>' . e($p['emoji'] ?: '🐾') . '</span>';
}

function pet_card(array $p): string
{
    $desc = mb_strimwidth((string)$p['description'], 0, 80, '…');
    return '<a class="card" href="' . ROOT . 'pet-details.php?id=' . (int)$p['id'] . '" style="text-decoration:none;color:inherit">'
        . '<div class="ph">' . pic($p) . '</div><div class="in">'
        . '<h3>' . e($p['name']) . ' ' . badge($p['status']) . '</h3>'
        . '<div class="meta">' . e($p['species']) . ' · ' . e($p['breed']) . ' · ' . e(rtrim(rtrim((string)$p['age'], '0'), '.')) . ' yr · ' . e($p['gender']) . '</div>'
        . '<div class="meta">' . e($p['color']) . ' · ' . e($p['location']) . '</div>'
        . '<p>' . e($desc) . '</p></div></a>';
}

/**
 * Binabago ang status ng application at sine-sync ang status ng pet.
 *  Approved  -> pet Pending
 *  Completed -> pet Adopted (at ire-reject ang ibang active applications para sa pet na iyon)
 *  Rejected  -> pet Available (kung Pending siya at wala nang ibang Approved)
 */
function change_application_status(int $appId, string $status, ?string $remarks = null): bool
{
    if (!in_array($status, APP_STATUSES, true)) return false;
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $a = q('SELECT * FROM applications WHERE id = ?', [$appId])->fetch();
        if (!$a) { $pdo->rollBack(); return false; }

        q('UPDATE applications SET status = ?, reviewed_at = ?, admin_remarks = ? WHERE id = ?',
          [$status, now(), $remarks ?? $a['admin_remarks'], $appId]);

        $petId = $a['pet_id'] ? (int)$a['pet_id'] : 0;
        if ($petId) {
            $pet = q('SELECT status FROM pets WHERE id = ?', [$petId])->fetch();
            if ($status === 'Approved') {
                q("UPDATE pets SET status = 'Pending' WHERE id = ? AND status = 'Available'", [$petId]);
            } elseif ($status === 'Completed') {
                q("UPDATE pets SET status = 'Adopted' WHERE id = ?", [$petId]);
                q("UPDATE applications SET status = 'Rejected', reviewed_at = ?, admin_remarks = 'This pet has already been adopted.'
                   WHERE pet_id = ? AND id <> ? AND status IN ('Pending','Under Review','Approved')", [now(), $petId, $appId]);
            } elseif ($status === 'Rejected' && $pet && $pet['status'] === 'Pending') {
                $other = q("SELECT COUNT(*) FROM applications WHERE pet_id = ? AND id <> ? AND status = 'Approved'", [$petId, $appId])->fetchColumn();
                if ((int)$other === 0) q("UPDATE pets SET status = 'Available' WHERE id = ?", [$petId]);
            }
        }
        $pdo->commit();
        return true;
    } catch (Throwable $t) {
        $pdo->rollBack();
        throw $t;
    }
}
