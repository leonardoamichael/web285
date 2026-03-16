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

      <button type="button" id="toggleFilters" class="filter-toggle">
        Show Filters
      </button>

      <section
        class="recipe-controls"
        id="recipeControls"
        aria-label="Search, filter, and sort recipes"
        hidden
      >

      <fieldset class="chip-set filter-chipset filter-group" id="typeChips">
        <legend>Type</legend>

        <input
          class="filter-group-search"
          type="search"
          placeholder="Search types"
          aria-label="Search types"
        >

        <div class="filter-group-body"></div>

        <button type="button" class="filter-group-toggle">Show All</button>
      </fieldset>

      <fieldset class="chip-set filter-chipset filter-group" id="styleChips">
        <legend>Style</legend>

        <input
          class="filter-group-search"
          type="search"
          placeholder="Search styles"
          aria-label="Search styles"
        >

        <div class="filter-group-body"></div>

        <button type="button" class="filter-group-toggle">Show All</button>
      </fieldset>

      <fieldset class="chip-set filter-chipset filter-group" id="dietChips">
        <legend>Diet</legend>

        <input
          class="filter-group-search"
          type="search"
          placeholder="Search diets"
          aria-label="Search diets"
        >

        <div class="filter-group-body"></div>

        <button type="button" class="filter-group-toggle">Show All</button>
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