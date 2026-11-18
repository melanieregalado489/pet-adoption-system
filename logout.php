<?php
$ROOT = '';
require __DIR__ . '/includes/bootstrap.php';

if (is_post()) {
    csrf_check();
    $_SESSION = [];
    session_destroy();
}
redirect('login.php');
