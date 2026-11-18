<?php
$ROOT = '';
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/layout.php';

$u = require_login();
if ($u['role'] === 'admin') {
    flash('error', 'Admin accounts cannot submit adoption applications.');
    redirect('admin/dashboard.php');
}

const HOME_TYPES = ['House with yard', 'House without yard', 'Apartment / condo', 'Dorm / rented room'];
const EXP_TYPES  = ['Have owned pets before', 'First-time owner', 'Currently have pets'];

$available = q("SELECT id, name, species, breed FROM pets WHERE status = 'Available' ORDER BY name")->fetchAll();
$v = [
    'name' => $u['name'], 'age' => '', 'phone' => $u['phone'], 'email' => $u['email'], 'address' => $u['address'],
    'home' => HOME_TYPES[0], 'exp' => EXP_TYPES[0], 'reason' => '', 'id_type' => ID_TYPES[0],
    'pet' => (int)($_GET['pet'] ?? 0),
];
$error = '';

if (is_post()) {
    csrf_check();
    foreach (['name', 'age', 'phone', 'email', 'address', 'home', 'exp', 'reason', 'id_type'] as $k)
        $v[$k] = trim((string)($_POST[$k] ?? ''));
    $v['pet'] = (int)($_POST['pet'] ?? 0);

    $pet = q("SELECT * FROM pets WHERE id = ? AND status = 'Available'", [$v['pet']])->fetch();

    if ($v['name'] === '' || mb_strlen($v['name']) > 120) $error = 'Please enter your full name.';
    elseif (!ctype_digit($v['age']) || (int)$v['age'] < 18 || (int)$v['age'] > 120) $error = 'You must be at least 18 years old to adopt.';
    elseif (!preg_match('/^[0-9+\-()\s]{7,20}$/', $v['phone'])) $error = 'Please enter a valid contact number.';
    elseif (!filter_var($v['email'], FILTER_VALIDATE_EMAIL)) $error = 'Please enter a valid email.';
    elseif ($v['address'] === '' || mb_strlen($v['address']) > 255) $error = 'Please enter your address.';
    elseif (!in_array($v['home'], HOME_TYPES, true) || !in_array($v['exp'], EXP_TYPES, true)) $error = 'Please choose from the given options.';
    elseif ($v['reason'] === '') $error = 'Please tell us why you want to adopt.';
    elseif (!$pet) $error = 'That pet is no longer available. Please choose another one.';
    elseif (!in_array($v['id_type'], ID_TYPES, true)) $error = 'Please choose your ID type.';
    elseif (empty($_POST['agree']) || empty($_POST['consent'])) $error = 'Please tick both checkboxes to continue.';
    elseif (q("SELECT 1 FROM applications WHERE user_id = ? AND pet_id = ? AND status IN ('Pending','Under Review','Approved')", [$u['id'], $pet['id']])->fetch())
        $error = 'You already have an active application for ' . $pet['name'] . '.';
    else {
        try {
            $up = handle_upload($_FILES['id_file'] ?? [], ID_MIMES, ID_MAX_BYTES, ID_DIR);
            try {
                q('INSERT INTO applications (user_id, pet_id, full_name, age, phone, email, address, home_type, experience, reason,
                                             id_type, id_file, id_original_name, id_mime, status, created_at)
                   VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
                  [$u['id'], $pet['id'], $v['name'], (int)$v['age'], $v['phone'], $v['email'], $v['address'], $v['home'], $v['exp'], $v['reason'],
                   $v['id_type'], $up['name'], $up['orig'], $up['mime'], 'Pending', now()]);
            } catch (Throwable $t) {
                delete_id_file($up['name']); // huwag mag-iwan ng ulilang ID file kapag pumalya ang database
                throw $t;
            }
            flash('ok', 'Application sent! 🐾 Our team will review your ID and application.');
            redirect('my-applications.php');
        } catch (UploadException $ex) {
            $error = $ex->getMessage();
        }
    }
}

page_header('Adoption application');
?>
<?php if (!$available): ?>
  <div class="empty">No pets are available for adoption right now. <a href="pets.php">Browse pets</a></div>
<?php else: ?>
<form class="form" method="post" action="adoption-form.php" enctype="multipart/form-data" id="adoptform">
  <h2>Adoption application</h2>
  <?= csrf_field() ?>
  <label>Full name<input name="name" value="<?= e($v['name']) ?>" required></label>
  <label>Age<input name="age" type="number" min="18" max="120" value="<?= e($v['age']) ?>" required></label>
  <label>Contact number<input name="phone" type="tel" value="<?= e($v['phone']) ?>" required></label>
  <label>Email<input name="email" type="email" value="<?= e($v['email']) ?>" required></label>
  <label>Address<input name="address" value="<?= e($v['address']) ?>" required></label>
  <label>Type of home
    <select name="home"><?php foreach (HOME_TYPES as $o) echo '<option' . ($o === $v['home'] ? ' selected' : '') . '>' . e($o) . '</option>'; ?></select>
  </label>
  <label>Previous experience with pets
    <select name="exp"><?php foreach (EXP_TYPES as $o) echo '<option' . ($o === $v['exp'] ? ' selected' : '') . '>' . e($o) . '</option>'; ?></select>
  </label>
  <label>Reason for adopting<textarea name="reason" rows="3" required><?= e($v['reason']) ?></textarea></label>
  <label>Preferred pet
    <select name="pet">
      <?php foreach ($available as $p): ?>
        <option value="<?= (int)$p['id'] ?>" <?= (int)$p['id'] === $v['pet'] ? 'selected' : '' ?>><?= e($p['name']) ?> (<?= e($p['species']) ?>, <?= e($p['breed']) ?>)</option>
      <?php endforeach; ?>
    </select>
  </label>

  <fieldset class="idbox">
    <legend>🪪 Valid ID</legend>
    <p class="meta">Required for verification. Only the PawHeart admin can see your ID.</p>
    <label>ID type
      <select name="id_type"><?php foreach (ID_TYPES as $o) echo '<option' . ($o === $v['id_type'] ? ' selected' : '') . '>' . e($o) . '</option>'; ?></select>
    </label>
    <label>Upload your ID (JPG, PNG, WEBP or PDF · max 5 MB)
      <input type="file" name="id_file" id="id_file" accept="image/jpeg,image/png,image/webp,application/pdf" required>
    </label>
    <div id="idpreview" class="idpreview" hidden></div>
  </fieldset>

  <?php if ($error): ?><p class="err" role="alert"><?= e($error) ?></p><?php endif; ?>
  <label class="chk"><input type="checkbox" name="agree" required> I agree to provide a loving home, a vet check-up, and a home visit.</label>
  <label class="chk"><input type="checkbox" name="consent" required> I allow PawHeart to collect and review my ID for adoption verification only.</label>
  <button>Submit application</button>
</form>
<?php endif;
page_footer();
