<?php
$ROOT = '';
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/layout.php';

$f = [
    'q'       => trim((string)($_GET['q'] ?? '')),
    'species' => (string)($_GET['species'] ?? ''),
    'age'     => (string)($_GET['age'] ?? ''),
    'gender'  => (string)($_GET['gender'] ?? ''),
    'loc'     => trim((string)($_GET['loc'] ?? '')),
    'status'  => (string)($_GET['status'] ?? ''),
];

$where = []; $args = [];
if ($f['q'] !== '')   { $where[] = '(name LIKE ? OR breed LIKE ?)'; $args[] = "%{$f['q']}%"; $args[] = "%{$f['q']}%"; }
if (in_array($f['species'], ['Dog', 'Cat'], true))   { $where[] = 'species = ?'; $args[] = $f['species']; }
if (in_array($f['gender'], ['Male', 'Female'], true)) { $where[] = 'gender = ?';  $args[] = $f['gender']; }
if (in_array($f['status'], PET_STATUSES, true))       { $where[] = 'status = ?';  $args[] = $f['status']; }
if ($f['loc'] !== '') { $where[] = 'location LIKE ?'; $args[] = "%{$f['loc']}%"; }
if ($f['age'] === '0') $where[] = 'age < 2';
if ($f['age'] === '1') $where[] = 'age >= 2 AND age < 7';
if ($f['age'] === '2') $where[] = 'age >= 7';

$pets = q('SELECT * FROM pets' . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY id', $args)->fetchAll();
$opt = fn(string $v, string $cur, string $label = '') => '<option value="' . e($v) . '"' . ($v === $cur ? ' selected' : '') . '>' . e($label ?: $v) . '</option>';

page_header('Pets');
?>
<h1>Pets looking for a home</h1>
<form class="filters" method="get" action="pets.php">
  <input name="q" placeholder="Name or breed" value="<?= e($f['q']) ?>" aria-label="Name or breed">
  <select name="species" aria-label="Species" onchange="this.form.submit()"><?= $opt('', $f['species'], 'All species') . $opt('Dog', $f['species']) . $opt('Cat', $f['species']) ?></select>
  <select name="age" aria-label="Age" onchange="this.form.submit()"><?= $opt('', $f['age'], 'Any age') . $opt('0', $f['age'], 'Under 2 yrs') . $opt('1', $f['age'], '2–6 yrs') . $opt('2', $f['age'], '7+ yrs') ?></select>
  <select name="gender" aria-label="Gender" onchange="this.form.submit()"><?= $opt('', $f['gender'], 'Any gender') . $opt('Male', $f['gender']) . $opt('Female', $f['gender']) ?></select>
  <input name="loc" placeholder="Location" value="<?= e($f['loc']) ?>" aria-label="Location">
  <select name="status" aria-label="Status" onchange="this.form.submit()"><?= $opt('', $f['status'], 'Any status') . implode('', array_map(fn($s) => $opt($s, $f['status']), PET_STATUSES)) ?></select>
  <button>Filter</button>
  <a class="btn ghost" href="pets.php">Clear</a>
</form>
<div class="grid">
<?php foreach ($pets as $p) echo pet_card($p); ?>
<?php if (!$pets): ?><div class="empty" style="grid-column:1/-1">No pets match. Try clearing a filter.</div><?php endif; ?>
</div>
<?php page_footer();
