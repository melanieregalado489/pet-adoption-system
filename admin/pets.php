<?php
$ROOT = '../';
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/layout.php';
require_admin();

if (is_post()) {
    csrf_check();
    $id = (int)($_POST['id'] ?? 0);
    $act = $_POST['action'] ?? '';
    if ($act === 'status' && in_array($_POST['status'] ?? '', PET_STATUSES, true)) {
        q('UPDATE pets SET status = ? WHERE id = ?', [$_POST['status'], $id]);
        flash('ok', 'Pet status updated.');
    } elseif ($act === 'delete') {
        $pet = q('SELECT photo FROM pets WHERE id = ?', [$id])->fetch();
        if ($pet) { q('DELETE FROM pets WHERE id = ?', [$id]); delete_pet_photo($pet['photo']); flash('ok', 'Pet deleted.'); }
    }
    redirect('pets.php');
}

$pets = q('SELECT * FROM pets ORDER BY id DESC')->fetchAll();
page_header('Manage pets');
?>
<h1>Manage pets</h1>
<p><a class="btn" href="pet-form.php">+ Add pet</a></p>
<table class="tbl">
  <tr><th>Pet</th><th>Species</th><th>Breed</th><th>Status</th><th>Actions</th></tr>
  <?php foreach ($pets as $p): ?>
  <tr>
    <td><span class="thumb"><?= pic($p) ?></span> <?= e($p['name']) ?></td>
    <td><?= e($p['species']) ?></td><td><?= e($p['breed']) ?></td>
    <td>
      <form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="action" value="status"><input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
        <select name="status" aria-label="Status" onchange="this.form.submit()"><?php foreach (PET_STATUSES as $s) echo '<option' . ($s === $p['status'] ? ' selected' : '') . '>' . e($s) . '</option>'; ?></select>
      </form>
    </td>
    <td>
      <a class="btn sm ghost" href="pet-form.php?id=<?= (int)$p['id'] ?>">Edit</a>
      <form method="post" class="inline" onsubmit="return confirm('Delete <?= e(addslashes($p['name'])) ?>?')"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$p['id'] ?>"><button class="sm red">Delete</button></form>
    </td>
  </tr>
  <?php endforeach; ?>
</table>
<?php page_footer();
