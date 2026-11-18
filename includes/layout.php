<?php
declare(strict_types=1);

function page_header(string $title): void
{
    $u = current_user();
    $admin = $u && $u['role'] === 'admin';
    $r = ROOT;
    $link = fn(string $href, string $label, string $cls = '') =>
        '<a ' . ($cls ? 'class="' . $cls . '" ' : '') . 'href="' . $r . $href . '">' . $label . '</a>';
    $logout = '<form method="post" action="' . $r . 'logout.php" class="inline">' . csrf_field() . '<button class="navbtn">Logout</button></form>';

    if ($admin) {
        $pending = (int)q("SELECT COUNT(*) FROM applications WHERE status = 'Pending'")->fetchColumn();
        $nav = $link('admin/dashboard.php', 'Dashboard') . $link('admin/pets.php', 'Pets')
             . $link('admin/applications.php', 'Applications' . ($pending ? ' <span class="count">' . $pending . '</span>' : ''))
             . $link('admin/users.php', 'Users') . $logout;
        $home = 'admin/dashboard.php';
    } else {
        $nav = $link('index.php', 'Home') . $link('pets.php', 'Adopt a pet');
        $nav .= $u
            ? $link('my-applications.php', 'My applications') . $link('profile.php', e(explode(' ', trim($u['name']))[0])) . $logout
            : $link('login.php', 'Login') . $link('register.php', 'Sign up', 'btn sm');
        $home = 'index.php';
    }
    ?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>PawHeart · <?= e($title) ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@400;600&family=Nunito:wght@400;700&family=Pacifico&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= $r ?>css/style.css">
</head>
<body>
<header id="nav"><a class="logo" href="<?= $r . $home ?>">🐾 PawHeart</a><nav><?= $nav ?></nav></header>
<main id="app">
<?php foreach (take_flashes() as [$type, $msg]): ?>
  <div class="alert <?= $type === 'error' ? 'err' : 'ok' ?>" role="status"><?= e($msg) ?></div>
<?php endforeach;
}

function page_footer(): void
{ ?>
</main>
<footer>Made with 🐾 and love · PawHeart Pet Adoption</footer>
<script src="<?= ROOT ?>js/script.js"></script>
</body>
</html>
<?php }
