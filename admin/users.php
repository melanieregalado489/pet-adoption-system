<?php
$ROOT = '../';
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/layout.php';
$admin = require_admin();

if (is_post()) {
    csrf_check();
    $id = (int)($_POST['id'] ?? 0);
    $target = q("SELECT id FROM users WHERE id = ? AND role <> 'admin'", [$id])->fetch(); // admin accounts are protected
    if ($target) {
        if (($_POST['action'] ?? '') === 'toggle') {
            q('UPDATE users SET is_active = 1 - is_active WHERE id = ?', [$id]);
            flash('ok', 'Account updated.');
        } elseif (($_POST['action'] ?? '') === 'delete') {
            $files = q('SELECT id_file FROM applications WHERE user_id = ?', [$id])->fetchAll(PDO::FETCH_COLUMN);
            q('DELETE FROM users WHERE id = ?', [$id]); // cascades to applications
            foreach ($files as $f) delete_id_file($f);   // pati ang mga ID ng user ay buburahin
            flash('ok', 'User and their applications (including uploaded IDs) were deleted.');
        }
    }
    redirect('users.php');
}

$users = q("SELECT u.*, (SELECT COUNT(*) FROM applications a WHERE a.user_id = u.id) AS app_count
            FROM users u WHERE u.role <> 'admin' ORDER BY u.id DESC")->fetchAll();

page_header('Users');
?>
<h1>Registered users</h1>
<?php if ($users): ?>
<table class="tbl">
  <tr><th>Name</th><th>Email</th><th>Phone</th><th>Applications</th><th>Account</th><th>Actions</th></tr>
  <?php foreach ($users as $u): ?>
  <tr>
    <td><?= e($u['name']) ?></td><td><?= e($u['email']) ?></td><td><?= e($u['phone']) ?></td><td><?= (int)$u['app_count'] ?></td>
    <td><span class="badge <?= $u['is_active'] ? 'Available' : 'Rejected' ?>"><?= $u['is_active'] ? 'Active' : 'Deactivated' ?></span></td>
    <td>
      <form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= (int)$u['id'] ?>"><button class="sm ghost"><?= $u['is_active'] ? 'Deactivate' : 'Activate' ?></button></form>
      <form method="post" class="inline" onsubmit="return confirm('Delete this user and all their applications and uploaded IDs?')"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$u['id'] ?>"><button class="sm red">Delete</button></form>
    </td>
  </tr>
  <?php endforeach; ?>
</table>
<?php else: ?><div class="empty">No users have signed up yet.</div><?php endif; ?>
<?php page_footer();
