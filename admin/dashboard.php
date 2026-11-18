<?php
$ROOT = '../';
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/layout.php';
require_admin();

$one = fn(string $sql) => (int)q($sql)->fetchColumn();
$stats = [
    ['Total pets', $one('SELECT COUNT(*) FROM pets')],
    ['Available', $one("SELECT COUNT(*) FROM pets WHERE status = 'Available'")],
    ['Adopted', $one("SELECT COUNT(*) FROM pets WHERE status = 'Adopted'")],
    ['Pending applications', $one("SELECT COUNT(*) FROM applications WHERE status = 'Pending'")],
    ['Approved applications', $one("SELECT COUNT(*) FROM applications WHERE status = 'Approved'")],
    ['Registered users', $one("SELECT COUNT(*) FROM users WHERE role <> 'admin'")],
];
$recent = q('SELECT a.id, a.full_name, a.status, a.created_at, p.name AS pet_name
             FROM applications a LEFT JOIN pets p ON p.id = a.pet_id ORDER BY a.id DESC LIMIT 5')->fetchAll();

page_header('Admin dashboard');
?>
<h1>Admin dashboard</h1>
<div class="stats">
<?php foreach ($stats as [$label, $n]): ?><div class="stat"><b><?= $n ?></b><?= e($label) ?></div><?php endforeach; ?>
</div>

<h2>Latest applications</h2>
<?php if ($recent): ?>
<table class="tbl">
  <tr><th>Applicant</th><th>Pet</th><th>Date</th><th>Status</th><th></th></tr>
  <?php foreach ($recent as $a): ?>
  <tr>
    <td><?= e($a['full_name']) ?></td><td><?= e($a['pet_name'] ?? '(removed)') ?></td>
    <td><?= e(fmt_date($a['created_at'])) ?></td><td><?= badge($a['status']) ?></td>
    <td><a class="btn sm ghost" href="application.php?id=<?= (int)$a['id'] ?>">Review + ID</a></td>
  </tr>
  <?php endforeach; ?>
</table>
<?php else: ?><div class="empty">No applications yet</div><?php endif; ?>

<h2>Manage</h2>
<div class="cats"><a href="pets.php">Pets</a><a href="applications.php">Applications</a><a href="users.php">Users</a></div>
<?php page_footer();
