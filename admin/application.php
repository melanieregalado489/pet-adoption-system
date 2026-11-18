<?php
$ROOT = '../';
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/layout.php';
require_admin();

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);

if (is_post()) {
    csrf_check();
    $remarks = mb_substr(trim((string)($_POST['remarks'] ?? '')), 0, 500);
    if (change_application_status($id, (string)($_POST['status'] ?? ''), $remarks)) flash('ok', 'Application updated ✓');
    else flash('error', 'Could not update that application.');
    redirect('application.php?id=' . $id);
}

$a = q('SELECT a.*, p.name AS pet_name, p.species, p.breed, p.status AS pet_status
        FROM applications a LEFT JOIN pets p ON p.id = a.pet_id WHERE a.id = ?', [$id])->fetch();

page_header('Application');
if (!$a): ?>
  <div class="empty">Application not found. <a href="applications.php">Back</a></div>
<?php page_footer(); exit; endif;

$isPdf = $a['id_mime'] === 'application/pdf';
$idUrl = '../view-id.php?id=' . (int)$a['id'];
?>
<a href="applications.php">← All applications</a>
<h1><?= e($a['full_name']) ?>’s application <?= badge($a['status']) ?></h1>

<div class="split">
  <div>
    <div class="box"><b>Applicant</b><br>
      Age <?= (int)$a['age'] ?> · <?= e($a['phone']) ?> · <?= e($a['email']) ?><br><?= e($a['address']) ?>
    </div>
    <div class="box"><b>Pet</b><br>
      <?= $a['pet_id'] ? e($a['pet_name']) . ' (' . e($a['species']) . ', ' . e($a['breed']) . ') ' . badge($a['pet_status']) : '(removed)' ?>
    </div>
    <div class="box"><b>Home & experience</b><br><?= e($a['home_type']) ?> · <?= e($a['experience']) ?></div>
    <div class="box"><b>Reason</b><br><?= nl2br(e($a['reason'])) ?></div>
    <div class="meta">Sent <?= e(fmt_date($a['created_at'])) ?><?= $a['reviewed_at'] ? ' · Last reviewed ' . e(fmt_date($a['reviewed_at'])) : '' ?></div>

    <form class="form" method="post" style="margin:1rem 0;padding:1.2rem">
      <h3 style="margin:0">Decision</h3>
      <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
      <label>Status
        <select name="status"><?php foreach (APP_STATUSES as $s) echo '<option' . ($s === $a['status'] ? ' selected' : '') . '>' . e($s) . '</option>'; ?></select>
      </label>
      <label>Message to the applicant (optional)
        <textarea name="remarks" rows="3" maxlength="500" placeholder="e.g. Your ID is blurry, please re-apply with a clearer photo."><?= e($a['admin_remarks']) ?></textarea>
      </label>
      <button>Save decision</button>
    </form>
  </div>

  <div id="id">
    <div class="box idview">
      <b>🪪 Submitted ID</b>
      <div class="meta"><?= e($a['id_type']) ?> · <?= e($a['id_original_name']) ?></div>
      <?php if ($isPdf): ?>
        <p>This ID is a PDF file.</p>
        <p><a class="btn sm" href="<?= e($idUrl) ?>" target="_blank" rel="noopener">Open PDF</a></p>
      <?php else: ?>
        <a href="<?= e($idUrl) ?>" target="_blank" rel="noopener"><img class="idimg" src="<?= e($idUrl) ?>" alt="ID submitted by <?= e($a['full_name']) ?>"></a>
        <p class="meta">Click the image to open it full size.</p>
      <?php endif; ?>
      <a class="btn sm ghost" href="<?= e($idUrl) ?>&download=1">Download</a>
    </div>
  </div>
</div>
<?php page_footer();
