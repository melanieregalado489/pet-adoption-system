<?php
$ROOT = '';
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/layout.php';

$u = require_login();
$apps = q('SELECT a.*, p.name AS pet_name, p.emoji AS pet_emoji
           FROM applications a LEFT JOIN pets p ON p.id = a.pet_id
           WHERE a.user_id = ? ORDER BY a.id DESC', [$u['id']])->fetchAll();
$flow = ['Pending', 'Under Review', 'Approved', 'Completed'];

page_header('My applications');
?>
<h1>My applications</h1>
<?php foreach ($apps as $a):
    $i = array_search($a['status'], $flow, true);
    $rejected = $a['status'] === 'Rejected'; ?>
  <div class="app">
    <h3><?= e($a['pet_emoji'] ?? '🐾') ?> <?= e($a['pet_name'] ?? '(removed)') ?> <?= badge($a['status']) ?></h3>
    <div class="meta">Sent <?= e(fmt_date($a['created_at'])) ?> · ID submitted: <?= e($a['id_type']) ?>
      · <a href="view-id.php?id=<?= (int)$a['id'] ?>" target="_blank" rel="noopener">View my ID</a></div>
    <div class="track"><?php foreach ($flow as $j => $s): ?><i class="<?= !$rejected && $i !== false && $j <= $i ? 'on' : '' ?>" title="<?= e($s) ?>"></i><?php endforeach; ?></div>
    <div class="meta"><?= $rejected ? 'Sorry, this one wasn’t approved. You can apply for another pet!' : e(implode(' → ', $flow)) ?></div>
    <?php if ($a['admin_remarks'] !== ''): ?><div class="box"><b>Message from the shelter</b><br><?= e($a['admin_remarks']) ?></div><?php endif; ?>
  </div>
<?php endforeach; ?>
<?php if (!$apps): ?><div class="empty">No applications yet. <a href="pets.php">Meet the pets</a></div><?php endif; ?>
<?php page_footer();
