<?php
$ROOT = '../';
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/layout.php';
require_admin();

$id  = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$pet = $id ? q('SELECT * FROM pets WHERE id = ?', [$id])->fetch() : null;
if ($id && !$pet) { flash('error', 'Pet not found.'); redirect('pets.php'); }

$p = $pet ?: ['name' => '', 'species' => 'Dog', 'breed' => '', 'age' => '', 'gender' => 'Male', 'color' => '', 'location' => '',
              'emoji' => '🐶', 'status' => 'Available', 'health' => '', 'personality' => '', 'description' => '', 'photo' => null, 'featured' => 0];
$error = '';

if (is_post()) {
    csrf_check();
    foreach (['name', 'breed', 'color', 'location', 'emoji', 'health', 'personality', 'description'] as $k) $p[$k] = trim((string)($_POST[$k] ?? ''));
    $p['species']  = (string)($_POST['species'] ?? '');
    $p['gender']   = (string)($_POST['gender'] ?? '');
    $p['status']   = (string)($_POST['status'] ?? '');
    $p['age']      = (string)($_POST['age'] ?? '');
    $p['featured'] = empty($_POST['featured']) ? 0 : 1;

    if ($p['name'] === '' || $p['breed'] === '') $error = 'Name and breed are required.';
    elseif (!in_array($p['species'], ['Dog', 'Cat'], true) || !in_array($p['gender'], ['Male', 'Female'], true) || !in_array($p['status'], PET_STATUSES, true)) $error = 'Invalid option selected.';
    elseif (!is_numeric($p['age']) || $p['age'] < 0 || $p['age'] > 40) $error = 'Please enter a valid age.';
    else {
        $newPhoto = null;
        try {
            if (!empty($_FILES['photo']['name'])) {
                $up = handle_upload($_FILES['photo'], PET_MIMES, PET_MAX_BYTES, PET_DIR);
                $newPhoto = 'uploads/pets/' . $up['name'];
            }
            $vals = [mb_substr($p['name'], 0, 80), $p['species'], mb_substr($p['breed'], 0, 100), (float)$p['age'], $p['gender'],
                     mb_substr($p['color'], 0, 60), mb_substr($p['location'], 0, 100), mb_substr($p['emoji'] ?: '🐾', 0, 16), $p['status'],
                     mb_substr($p['health'], 0, 255), mb_substr($p['personality'], 0, 255), $p['description'], $p['featured']];
            if ($pet) {
                q('UPDATE pets SET name=?, species=?, breed=?, age=?, gender=?, color=?, location=?, emoji=?, status=?, health=?, personality=?, description=?, featured=?'
                  . ($newPhoto ? ', photo=?' : '') . ' WHERE id=?',
                  array_merge($vals, $newPhoto ? [$newPhoto] : [], [$id]));
                if ($newPhoto) delete_pet_photo($pet['photo']);
            } else {
                q('INSERT INTO pets (name, species, breed, age, gender, color, location, emoji, status, health, personality, description, featured, photo, created_at)
                   VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', array_merge($vals, [$newPhoto, now()]));
            }
            flash('ok', 'Pet saved.');
            redirect('pets.php');
        } catch (UploadException $ex) {
            $error = $ex->getMessage();
        }
    }
}

$sel = fn(array $opts, string $cur) => implode('', array_map(fn($o) => '<option' . ($o === $cur ? ' selected' : '') . '>' . e($o) . '</option>', $opts));
$in  = fn(string $n, string $label, string $type = 'text', string $extra = '') =>
    '<label>' . $label . '<input name="' . $n . '" type="' . $type . '" value="' . e($p[$n]) . '" ' . $extra . '></label>';

page_header($pet ? 'Edit pet' : 'Add pet');
?>
<a href="pets.php">← All pets</a>
<form class="form" method="post" enctype="multipart/form-data">
  <h2><?= $pet ? 'Edit' : 'Add' ?> pet</h2>
  <?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>">
  <?= $in('name', 'Name', 'text', 'required maxlength="80"') ?>
  <label>Species<select name="species"><?= $sel(['Dog', 'Cat'], $p['species']) ?></select></label>
  <?= $in('breed', 'Breed', 'text', 'required maxlength="100"') ?>
  <?= $in('age', 'Age (years)', 'number', 'required min="0" max="40" step="0.5"') ?>
  <label>Gender<select name="gender"><?= $sel(['Male', 'Female'], $p['gender']) ?></select></label>
  <?= $in('color', 'Color') ?>
  <?= $in('location', 'Location') ?>
  <?= $in('emoji', 'Emoji (fallback photo)', 'text', 'maxlength="8"') ?>
  <label>Status<select name="status"><?= $sel(PET_STATUSES, $p['status']) ?></select></label>
  <?= $in('health', 'Health info') ?>
  <?= $in('personality', 'Personality') ?>
  <label>Description<textarea name="description" rows="3"><?= e($p['description']) ?></textarea></label>
  <label>Photo (JPG, PNG or WEBP · max 3 MB)
    <?php if ($p['photo']): ?><span class="thumb big"><?= pic($p) ?></span><?php endif; ?>
    <input type="file" name="photo" accept="image/jpeg,image/png,image/webp">
  </label>
  <label class="chk"><input type="checkbox" name="featured" <?= $p['featured'] ? 'checked' : '' ?>> Show in “Featured pets” on the home page</label>
  <?php if ($error): ?><p class="err" role="alert"><?= e($error) ?></p><?php endif; ?>
  <button>Save pet</button>
</form>
<?php page_footer();
