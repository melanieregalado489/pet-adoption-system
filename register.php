<?php
$ROOT = '';
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/layout.php';

if (current_user()) redirect('index.php');

$error = '';
$v = ['name' => '', 'phone' => '', 'email' => ''];
if (is_post()) {
    csrf_check();
    $v = [
        'name'  => trim((string)($_POST['name'] ?? '')),
        'phone' => trim((string)($_POST['phone'] ?? '')),
        'email' => strtolower(trim((string)($_POST['email'] ?? ''))),
    ];
    $pass = (string)($_POST['pass'] ?? '');

    if ($v['name'] === '' || mb_strlen($v['name']) > 120) $error = 'Please enter your full name.';
    elseif (!preg_match('/^[0-9+\-()\s]{7,20}$/', $v['phone'])) $error = 'Please enter a valid contact number.';
    elseif (!filter_var($v['email'], FILTER_VALIDATE_EMAIL)) $error = 'Please enter a valid email.';
    elseif (strlen($pass) < 6) $error = 'Password must be at least 6 characters.';
    elseif ($pass !== (string)($_POST['pass2'] ?? '')) $error = "Passwords don't match.";
    elseif (q('SELECT 1 FROM users WHERE email = ?', [$v['email']])->fetch()) $error = 'That email is already registered.';
    else {
        q("INSERT INTO users (name, email, phone, address, password_hash, role, is_active, created_at) VALUES (?,?,?,?,?,'user',1,?)",
          [$v['name'], $v['email'], $v['phone'], '', password_hash($pass, PASSWORD_DEFAULT), now()]);
        login_user((int)db()->lastInsertId());
        flash('ok', 'Welcome to PawHeart! 🐾');
        redirect('index.php');
    }
}

page_header('Sign up');
?>
<form class="form" method="post" action="register.php">
  <h2>Create your account</h2>
  <?= csrf_field() ?>
  <label>Full name<input name="name" value="<?= e($v['name']) ?>" required maxlength="120"></label>
  <label>Contact number<input name="phone" type="tel" value="<?= e($v['phone']) ?>" required></label>
  <label>Email<input name="email" type="email" value="<?= e($v['email']) ?>" required></label>
  <label>Password<input name="pass" type="password" minlength="6" required></label>
  <label>Confirm password<input name="pass2" type="password" minlength="6" required></label>
  <?php if ($error): ?><p class="err"><?= e($error) ?></p><?php endif; ?>
  <button>Sign up</button>
  <p>Have an account? <a href="login.php">Log in</a></p>
</form>
<?php page_footer();
