<?php
$ROOT = '';
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/layout.php';

$u = current_user();
$featured  = q('SELECT * FROM pets WHERE featured = 1 ORDER BY id')->fetchAll();
$available = q("SELECT * FROM pets WHERE status = 'Available' ORDER BY id LIMIT 6")->fetchAll();

page_header('Home');
?>
<section class="hero">
  <div>
    <h1>Find your new best friend</h1>
    <p>Welcome<?= $u ? ' back, ' . e(explode(' ', trim($u['name']))[0]) : ' to PawHeart' ?>! Every paw deserves a loving home.</p>
    <form class="search" method="get" action="pets.php">
      <input name="q" placeholder="Search by name or breed…" aria-label="Search pets">
      <button>Search</button>
    </form>
    <a class="btn big" href="pets.php">Adopt Now</a>
  </div>
  <div class="heroimg"><img src="images/hero.jpg" alt="A golden retriever puppy and a kitten cuddled in a cozy pet bed"></div>
</section>

<h2>Pet categories</h2>
<div class="cats">
  <a href="pets.php?species=Dog">Dogs</a>
  <a href="pets.php?species=Cat">Cats</a>
</div>

<h2>Featured pets</h2>
<div class="grid"><?php foreach ($featured as $p) echo pet_card($p); ?></div>

<h2>Available pets</h2>
<div class="grid"><?php foreach ($available as $p) echo pet_card($p); ?></div>
<p style="text-align:center"><a class="btn ghost" href="pets.php">See all pets</a></p>
<?php page_footer();
