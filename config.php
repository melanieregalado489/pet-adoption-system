<?php
// ===== Database settings (XAMPP default) =====
const DB_HOST = 'localhost';
const DB_NAME = 'pawheart';
const DB_USER = 'root';
const DB_PASS = '';

// Para sa testing lang: puwedeng i-override ng environment variable PH_DB_DSN
define('DB_DSN', getenv('PH_DB_DSN') ?: 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4');

date_default_timezone_set('Asia/Manila');

// ===== Uploads =====
// ID ng adopters: HINDI public. Nasa storage/ at admin lang ang may access via view-id.php
const ID_DIR       = __DIR__ . '/storage/ids';
const PET_DIR      = __DIR__ . '/uploads/pets';
const ID_MAX_BYTES = 5 * 1024 * 1024; // 5 MB
const PET_MAX_BYTES = 3 * 1024 * 1024; // 3 MB
