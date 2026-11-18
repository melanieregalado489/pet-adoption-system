<?php
$ROOT = '';
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/layout.php';

$next = safe_next($_GET['next'] ?? $_POST['next'] ?? '');
if ($u = current_user()) redirect($u['role'] === 'admin' ? 'admin/dashboard.php' : ($next ?: 'index.php'));

$error = '';
$email = '';
if (is_post()) {
    csrf_check();
    $email = strtolower(trim((string)($_POST['email'] ?? '')));
    $user  = q('SELECT * FROM users WHERE email = ?', [$email])->fetch();
    if (!$user || !password_verify((string)($_POST['pass'] ?? ''), $user['password_hash'])) {
        $error = 'Wrong email or password.';
    } elseif (!(int)$user['is_active']) {
        $error = 'This account is deactivated. Please contact the shelter.';
    } else {
        login_user((int)$user['id']);
        redirect($user['role'] === 'admin' ? 'admin/dashboard.php' : ($next ?: 'index.php'));
    }
}

page_header('Login');
?>
<form class="form" method="post" action="login.php">
  <h2>Welcome back</h2>
  <?= csrf_field() ?>
  <input type="hidden" name="next" value="<?= e($next) ?>">
  <label>Email<input name="email" type="email" value="<?= e($email) ?>" required autofocus></label>
  <label>Password<input name="pass" type="password" required></label>
  <?php if ($error): ?><p class="err"><?= e($error) ?></p><?php endif; ?>
  <button>Log in</button>
  <p>New here? <a href="register.php">Sign up</a></p>
  <p class="hint">Admin demo: admin@pawheart.ph / admin123</p>
</form>
<?php page_footer();
