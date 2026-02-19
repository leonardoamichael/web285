<?php
require_once 'includes/initialize.php';
include 'includes/header.php';
?>

<div id="container">
  <main>

    <h1>Discover and Share Homemade Recipes</h1>

    <?php
      /* Fetch approved recipes for public home page */
      $recipes = fetch_random_recipes($db, 12);

      include 'includes/recipe-grid.php';
    ?>

  </main>
</div>

<?php include 'includes/login-modal.php'; ?>
<?php include 'includes/footer.php'; ?>