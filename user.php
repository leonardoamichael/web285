<?php
require_once 'includes/initialize.php';
include 'includes/header.php';

/* Input validation */
$user_id = (int) ($_GET['id'] ?? 0);

if ($user_id <= 0) {
  redirect_error('not_found', 'recipes.php');
}

/* Fetch user */
$stmt = $db->prepare(
  "SELECT id_usr, username_usr
   FROM user_usr
   WHERE id_usr = ?
   LIMIT 1"
);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
  redirect_error('not_found', 'recipes.php');
}

/* Fetch approved recipes by this user */
$stmt = $db->prepare(
  "SELECT id_rec, title_rec, created_at_rec
   FROM recipe_rec
   WHERE id_usr_rec = ?
     AND status_rec = 'approved'
   ORDER BY created_at_rec DESC
   LIMIT 100"
);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();

$recipes = [];
while ($row = $result->fetch_assoc()) {
  $recipes[] = $row;
}
$stmt->close();
?>

<div id="container">
  <main>

    <h1>Recipes by <?= h($user['username_usr']) ?></h1>

    <?php if (empty($recipes)): ?>
      <p>No approved recipes yet.</p>
    <?php else: ?>
      <ul>
        <?php foreach ($recipes as $r): ?>
          <li>
            <a href="recipe.php?id=<?= (int) $r['id_rec'] ?>">
              <?= h($r['title_rec']) ?>
            </a>
            <?php if (!empty($r['created_at_rec'])): ?>
              <small> (<?= h($r['created_at_rec']) ?>)</small>
            <?php endif; ?>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>

    <p><a href="recipes.php">← Back to Recipes</a></p>

  </main>
</div>

<?php include 'includes/login-modal.php'; ?>
<?php include 'includes/footer.php'; ?>