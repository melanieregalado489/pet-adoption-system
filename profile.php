<?php
$ROOT = '';
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/layout.php';

$u = require_login();
$error = '';

if (is_post()) {
    csrf_check();
    if (($_POST['action'] ?? '') === 'password') {
        $new = (string)($_POST['new'] ?? '');
        if (!password_verify((string)($_POST['current'] ?? ''), $u['password_hash'])) $error = 'Current password is wrong.';
        elseif (strlen($new) < 6) $error = 'New password must be at least 6 characters.';
        elseif ($new !== (string)($_POST['new2'] ?? '')) $error = "New passwords don't match.";
        else {
            q('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($new, PASSWORD_DEFAULT), $u['id']]);
            flash('ok', 'Password changed.');
            redirect('profile.php');
        }
    } else {
        $name = trim((string)($_POST['name'] ?? ''));
        $phone = trim((string)($_POST['phone'] ?? ''));
        $address = trim((string)($_POST['address'] ?? ''));
        if ($name === '' || mb_strlen($name) > 120) $error = 'Please enter your full name.';
        elseif (!preg_match('/^[0-9+\-()\s]{7,20}$/', $phone)) $error = 'Please enter a valid contact number.';
        else {
            q('UPDATE users SET name = ?, phone = ?, address = ? WHERE id = ?', [$name, $phone, mb_substr($address, 0, 255), $u['id']]);
            flash('ok', 'Profile saved.');
            redirect('profile.php');
        }
    }
}

$apps = (int)q('SELECT COUNT(*) FROM applications WHERE user_id = ?', [$u['id']])->fetchColumn();
$adopted = q("SELECT p.* FROM applications a JOIN pets p ON p.id = a.pet_id WHERE a.user_id = ? AND a.status = 'Completed'", [$u['id']])->fetchAll();

page_header('My account');
?>
<h1>My account</h1>
<?php if ($error): ?><div class="alert err"><?= e($error) ?></div><?php endif; ?>
<form class="form" method="post" style="margin-left:0">
  <h2>Personal information</h2>
  <?= csrf_field() ?>
  <label>Full name<input name="name" value="<?= e($u['name']) ?>" required></label>
  <label>Contact number<input name="phone" type="tel" value="<?= e($u['phone']) ?>" required></label>
  <label>Address<input name="address" value="<?= e($u['address']) ?>"></label>
  <label>Email<input value="<?= e($u['email']) ?>" disabled></label>
  <button>Save profile</button>
</form>

<form class="form" method="post" style="margin-left:0">
  <h2>Change password</h2>
  <?= csrf_field() ?>
  <input type="hidden" name="action" value="password">
  <label>Current password<input name="current" type="password" required></label>
  <label>New password<input name="new" type="password" minlength="6" required></label>
  <label>Confirm new password<input name="new2" type="password" minlength="6" required></label>
  <button>Change password</button>
</form>

<h2>Adoption applications (<?= $apps ?>)</h2>
<p><a class="btn ghost" href="my-applications.php">View my applications</a></p>

<h2>Adopted pets</h2>
<div class="grid">
<?php foreach ($adopted as $p) echo pet_card($p); ?>
<?php if (!$adopted): ?><div class="empty" style="grid-column:1/-1">No adopted pets yet.</div><?php endif; ?>
</div>
<?php page_footer();
