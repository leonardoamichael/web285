<?php
require_once 'includes/initialize.php';
include 'includes/header.php';

/* Input validation */
$id = (int) ($_GET['id'] ?? 0);

if ($id <= 0) {
  redirect_error('not_found', 'recipes.php');
}

/* Viewer context */
$viewer_id   = (int) ($_SESSION['user_id'] ?? 0);
$viewer_role = (string) ($_SESSION['role'] ?? ''); // expecting 'admin' or 'member'
$is_admin    = ($viewer_role === 'admin');

/* 1) Fetch recipe + author + status */
$stmt = $db->prepare(
  "SELECT r.id_rec, r.title_rec, r.created_at_rec, r.status_rec, r.id_usr_rec, u.username_usr
   FROM recipe_rec r
   JOIN user_usr u ON u.id_usr = r.id_usr_rec
   WHERE r.id_rec = ?
   LIMIT 1"
);

$stmt->bind_param('i', $id);
$stmt->execute();

$recipe = $stmt->get_result()->fetch_assoc();

$stmt->close();

if (!$recipe) {
  redirect_error('not_found', 'recipes.php');
}

/* Visibility rules */
$is_owner    = ($viewer_id > 0 && (int) $recipe['id_usr_rec'] === $viewer_id);
$is_approved = ((string) $recipe['status_rec'] === 'approved');

if (!$is_approved && !$is_owner && !$is_admin) {
  // Keep pending/rejected recipes private
  redirect_error('not_found', 'recipes.php');
}

/* 2) Fetch ingredients */
$stmt = $db->prepare(
  "SELECT
      ri.quantity_recing,
      un.abbreviation_uni,
      un.name_uni,
      ing.name_ing,
      ri.note_recing
   FROM recipe_ingredient_recing ri
   JOIN ingredient_ing ing ON ing.id_ing = ri.id_ing_recing
   LEFT JOIN unit_uni un ON un.id_uni = ri.id_uni_recing
   WHERE ri.id_rec_recing = ?
   ORDER BY ing.name_ing ASC"
);

$stmt->bind_param('i', $id);
$stmt->execute();

$result = $stmt->get_result();

$ingredients = [];

while ($row = $result->fetch_assoc()) {
  $ingredients[] = $row;
}

$stmt->close();

/* 3) Fetch steps */
$stmt = $db->prepare(
  "SELECT step_number_stp, instruction_stp
   FROM recipe_step_stp
   WHERE id_rec_stp = ?
   ORDER BY step_number_stp ASC"
);

$stmt->bind_param('i', $id);
$stmt->execute();

$result = $stmt->get_result();

$steps = [];

while ($row = $result->fetch_assoc()) {
  $steps[] = $row;
}

$stmt->close();

/* 4) Fetch images */
$stmt = $db->prepare(
  "SELECT path_recimg, alt_recimg
   FROM recipe_image_recimg
   WHERE id_rec_recimg = ?
   ORDER BY sort_order_recimg ASC, id_recimg ASC"
);

$stmt->bind_param('i', $id);
$stmt->execute();

$result = $stmt->get_result();

$images = [];

while ($row = $result->fetch_assoc()) {
  $images[] = $row;
}

$stmt->close();

/* Fallback image */
$default_image = 'images/recipe-book.png';
?>

<div id="container">
  <main>

    <h1><?= h($recipe['title_rec']) ?></h1>

    <?php if (!$is_approved): ?>
      <p role="alert">
        <strong>Status:</strong>
        <?= h($recipe['status_rec']) ?>
        (only visible to you/admin until approved)
      </p>
    <?php endif; ?>

    <section class="tab-panel">
      <h2>Photos</h2>

      <?php if (empty($images)): ?>
        <img
          src="<?= h($default_image) ?>"
          alt="Default recipe image"
        >

      <?php else: ?>
        <?php foreach ($images as $img): ?>
          <img
            src="<?= h($img['path_recimg']) ?>"
            alt="<?= h($img['alt_recimg'] ?? '') ?>"
          >
        <?php endforeach; ?>
      <?php endif; ?>
    </section>

    <p>
      By <strong><?= h($recipe['username_usr']) ?></strong>

      <?php if (!empty($recipe['created_at_rec'])): ?>
        • <span><?= h($recipe['created_at_rec']) ?></span>
      <?php endif; ?>
    </p>

    <section class="tab-panel">
      <h2>Ingredients</h2>

      <?php if (empty($ingredients)): ?>
        <p>No ingredients added yet.</p>

      <?php else: ?>
        <ul>
          <?php foreach ($ingredients as $ing): ?>
            <li>

              <?php
                $qty  = $ing['quantity_recing'];
                $unit = $ing['abbreviation_uni'] ?: $ing['name_uni'];
                $name = $ing['name_ing'];
                $note = $ing['note_recing'];
              ?>

              <?= $qty !== null ? h($qty) : '' ?>
              <?= $unit ? ' ' . h($unit) : '' ?>
              <?= ($qty !== null || $unit) ? ' — ' : '' ?>
              <?= h($name) ?>
              <?= $note ? ' (' . h($note) . ')' : '' ?>

            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </section>

    <section class="tab-panel">
      <h2>Directions</h2>

      <?php if (empty($steps)): ?>
        <p>No steps added yet.</p>

      <?php else: ?>
        <ol>
          <?php foreach ($steps as $stp): ?>
            <li><?= h($stp['instruction_stp']) ?></li>
          <?php endforeach; ?>
        </ol>
      <?php endif; ?>
    </section>

    <p><a href="recipes.php">← Back to Recipes</a></p>

  </main>
</div>

<?php include 'includes/login-modal.php'; ?>
<?php include 'includes/footer.php'; ?>