<?php include 'includes/header.php'; ?>

<div id="container">
  <main>

    <h1>Recipe Selection</h1>

    <p>
      Browse our current recipe collection below. Use the search box to quickly
      narrow down results by title or ingredient. Want to share your own recipe?
      Create an account to submit recipes and build your personal collection.
    </p>

    <form class="recipe-search" action="#" method="get">
      <label for="search">Search recipes</label>
      <input
        id="search"
        name="search"
        type="search"
        placeholder="e.g., chicken, pasta, soup"
      />
      <button type="submit">Search</button>
    </form>

    <?php include 'includes/recipe-grid.php'; ?>

  </main>
</div>

<?php include 'includes/login-modal.php'; ?>
<?php include 'includes/footer.php'; ?>