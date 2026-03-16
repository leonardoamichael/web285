<?php
require_once 'includes/initialize.php';
include 'includes/header.php';
?>

<section class="home-hero">
  <div class="home-hero-inner">
    <div class="home-hero-content">
      <p class="home-hero-eyebrow">Welcome to Recipe Share</p>
      <h1>Discover and Share Homemade Recipes</h1>
      <p class="home-hero-text">
        Browse community recipes, find new favorites, and share your own homemade dishes.
      </p>
    </div>
  </div>
</section>

<div id="container">
  <main>

    <!-- Perfect Recipes -->
    <section class="home-recipes-section">
      <div class="home-section-heading">
        <h2>⭐ Top Rated Recipes</h2>
        <p>Recipes the community loves.</p>
      </div>

      <?php
        $recipes = fetch_perfect_recipes_with_primary_image($db, 4);
        include 'includes/recipe-grid.php';
      ?>
    </section>

    <!-- Random Recipes -->
    <section class="home-recipes-section">
      <div class="home-section-heading">
        <h2>Discover More Recipes</h2>
        <p>Explore something new.</p>
      </div>

      <?php
        $recipes = fetch_approved_recipes_with_primary_image($db, '', 8, true);
        include 'includes/recipe-grid.php';
      ?>
    </section>

  </main>
</div>

<?php include 'includes/login-modal.php'; ?>
<?php include 'includes/footer.php'; ?>