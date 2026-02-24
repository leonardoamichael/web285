<?php
require_once 'includes/initialize.php';

$search = trim((string)($_GET['search'] ?? ''));
$recipes = fetch_approved_recipes_with_primary_image($db, $search, 30);

include 'includes/header.php';
?>

<div id="container">
  <main>

    <h1>Recipe Selection</h1>

    <p>
      Browse our current recipe collection below. Use the search box to quickly
      narrow down results by title or ingredient. Want to share your own recipe?
      Create an account to submit recipes and build your personal collection.
    </p>

    <form class="recipe-search" method="get" action="recipes.php">
      <label for="search">Search recipes</label>

      <input
        id="search"
        name="search"
        type="search"
        placeholder="e.g., chicken, pasta, soup"
        value="<?= h($search) ?>"
      >

      <button type="submit">Search</button>
    </form>

    <section class="recipe-controls" id="recipeControls" aria-label="Search, filter, and sort recipes">

      <fieldset class="chip-set filter-chipset" id="typeChips">
        <legend>Type</legend>
      </fieldset>

      <fieldset class="chip-set filter-chipset" id="styleChips">
        <legend>Style</legend>
      </fieldset>

      <fieldset class="chip-set filter-chipset" id="dietChips">
        <legend>Diet</legend>
      </fieldset>

      <div class="filter-row">
        <label for="sortBy">Sort</label>
        <select id="sortBy">
          <option value="newest">Newest</option>
          <option value="rating">Highest rating</option>
        </select>
      </div>

      <button type="button" id="clearFilters">Clear filters</button>

    </section>

    <?php include 'includes/recipe-grid.php'; ?>

  </main>
</div>

<?php include 'includes/login-modal.php'; ?>
<?php include 'includes/footer.php'; ?>