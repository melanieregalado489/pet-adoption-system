<?php
$ROOT = '../';
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/layout.php';
require_admin();

if (is_post()) {
    csrf_check();
    $status = (string)($_POST['status'] ?? '');
    if (change_application_status((int)($_POST['id'] ?? 0), $status)) flash('ok', 'Updated ✓');
    else flash('error', 'Could not update that application.');
    redirect('applications.php' . (!empty($_POST['filter']) && in_array($_POST['filter'], APP_STATUSES, true) ? '?status=' . urlencode($_POST['filter']) : ''));
}

$filter = in_array($_GET['status'] ?? '', APP_STATUSES, true) ? $_GET['status'] : '';
$apps = q('SELECT a.id, a.full_name, a.email, a.status, a.created_at, a.id_type, a.id_file, a.pet_id, p.name AS pet_name
           FROM applications a LEFT JOIN pets p ON p.id = a.pet_id'
          . ($filter ? ' WHERE a.status = ?' : '') . ' ORDER BY a.id DESC', $filter ? [$filter] : [])->fetchAll();

page_header('Manage applications');
?>
<h1>Adoption applications</h1>
<form class="filters" method="get">
  <select name="status" aria-label="Filter by status" onchange="this.form.submit()">
    <option value="">All statuses</option>
    <?php foreach (APP_STATUSES as $s) echo '<option' . ($s === $filter ? ' selected' : '') . '>' . e($s) . '</option>'; ?>
  </select>
  <noscript><button>Filter</button></noscript>
</form>
<?php if ($apps): ?>
<table class="tbl">
  <tr><th>Applicant</th><th>Pet</th><th>Date</th><th>ID</th><th>Status</th><th>Actions</th></tr>
  <?php foreach ($apps as $a): ?>
  <tr>
    <td><?= e($a['full_name']) ?><br><span class="meta"><?= e($a['email']) ?></span></td>
    <td><?= e($a['pet_name'] ?? '(removed)') ?></td>
    <td><?= e(fmt_date($a['created_at'])) ?></td>
    <td><?php if ($a['id_file']): ?><a class="btn sm ghost" href="application.php?id=<?= (int)$a['id'] ?>#id">🪪 View ID</a><br><span class="meta"><?= e($a['id_type']) ?></span><?php else: ?>—<?php endif; ?></td>
    <td>
      <form method="post" class="inline">
        <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$a['id'] ?>"><input type="hidden" name="filter" value="<?= e($filter) ?>">
        <select name="status" aria-label="Status" onchange="this.form.submit()">
          <?php foreach (APP_STATUSES as $s) echo '<option' . ($s === $a['status'] ? ' selected' : '') . '>' . e($s) . '</option>'; ?>
        </select>
      </form>
    </td>
    <td>
      <a class="btn sm ghost" href="application.php?id=<?= (int)$a['id'] ?>">Review</a>
      <?php foreach (['Approved' => 'green', 'Rejected' => 'red'] as $s => $cls): ?>
      <form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$a['id'] ?>"><input type="hidden" name="filter" value="<?= e($filter) ?>"><input type="hidden" name="status" value="<?= $s ?>"><button class="sm <?= $cls ?>"><?= $s === 'Approved' ? 'Approve' : 'Reject' ?></button></form>
      <?php endforeach; ?>
    </td>
  </tr>
  <?php endforeach; ?>
</table>
<?php else: ?><div class="empty">No applications<?= $filter ? ' with that status' : ' yet' ?></div><?php endif; ?>
<?php page_footer();
