<?php
require_once 'includes/initialize.php';

$recipes = [];
$search  = trim((string) ($_GET['search'] ?? ''));

/* Recipe list query (approved only) */
if ($search !== '') {

  $like = '%' . $search . '%';

  $stmt = $db->prepare(
    "SELECT id_rec, title_rec
     FROM recipe_rec
     WHERE status_rec = 'approved'
       AND title_rec LIKE ?
     ORDER BY created_at_rec DESC
     LIMIT 30"
  );

  $stmt->bind_param('s', $like);
  $stmt->execute();

  $result = $stmt->get_result();

  while ($row = $result->fetch_assoc()) {
    $recipes[] = $row;
  }

  $stmt->close();

} else {

  $stmt = $db->prepare(
    "SELECT id_rec, title_rec
     FROM recipe_rec
     WHERE status_rec = 'approved'
     ORDER BY created_at_rec DESC
     LIMIT 30"
  );

  $stmt->execute();

  $result = $stmt->get_result();

  while ($row = $result->fetch_assoc()) {
    $recipes[] = $row;
  }

  $stmt->close();
}

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

    <?php include 'includes/recipe-grid.php'; ?>

  </main>
</div>

<?php include 'includes/login-modal.php'; ?>
<?php include 'includes/footer.php'; ?>