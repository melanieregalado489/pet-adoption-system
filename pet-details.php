<?php
$ROOT = '';
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/layout.php';

$p = q('SELECT * FROM pets WHERE id = ?', [(int)($_GET['id'] ?? 0)])->fetch();
page_header($p ? $p['name'] : 'Pet not found');

if (!$p): ?>
  <div class="empty">Pet not found. <a href="pets.php">Back to pets</a></div>
<?php else: ?>
  <a href="pets.php">← All pets</a>
  <div class="detail">
    <div class="big"><?= pic($p) ?></div>
    <div>
      <h1 style="margin:0"><?= e($p['name']) ?> <?= badge($p['status']) ?></h1>
      <p class="meta"><?= e($p['species']) ?> · <?= e($p['breed']) ?> · <?= e(rtrim(rtrim((string)$p['age'], '0'), '.')) ?> yr · <?= e($p['gender']) ?> · <?= e($p['color']) ?> · <?= e($p['location']) ?></p>
      <div class="box"><b>Health</b><br><?= e($p['health']) ?></div>
      <div class="box"><b>Personality</b><br><?= e($p['personality']) ?></div>
      <div class="box"><b>About</b><br><?= nl2br(e($p['description'])) ?></div>
      <div class="box"><b>Adoption requirements</b>
        <ul>
          <li>18 years old or above</li>
          <li>Valid government-issued ID (you'll upload a photo or scan when you apply)</li>
          <li>Safe, pet-friendly home</li>
          <li>Agree to a home visit and vet check-ups</li>
        </ul>
      </div>
      <?php if ($p['status'] === 'Available'): ?>
        <a class="btn big" href="adoption-form.php?pet=<?= (int)$p['id'] ?>">💖 Apply for Adoption</a>
      <?php else: ?>
        <button disabled style="opacity:.5">Not available right now</button>
      <?php endif; ?>
    </div>
  </div>
<?php endif;
page_footer();
