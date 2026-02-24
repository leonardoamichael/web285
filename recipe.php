<?php
require_once 'includes/initialize.php';
include 'includes/header.php';

/* Input validation */
$id = (int) ($_GET['id'] ?? 0);

if ($id <= 0) {
  redirect_error('not_found', 'recipes.php');
}

/* Viewer context */
$viewer_id  = (int) ($_SESSION['user_id'] ?? 0);
$role_id    = (int) ($_SESSION['role_id'] ?? 2); // 1=admin, 2=member
$is_admin   = ($role_id === 1);

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
  <main class="recipe-page">

    <header class="recipe-header">
      <h1 class="recipe-title"><?= h($recipe['title_rec']) ?></h1>

      <?php if (!$is_approved): ?>
        <p class="recipe-status" role="alert">
          <strong>Status:</strong>
          <?= h($recipe['status_rec']) ?>
          (only visible to you/admin until approved)
        </p>
      <?php endif; ?>

      <p class="recipe-meta">
        By
        <strong>
          <a href="user.php?id=<?= (int) $recipe['id_usr_rec'] ?>">
            <?= h($recipe['username_usr']) ?>
          </a>
        </strong>

        <?php if (!empty($recipe['created_at_rec'])): ?>
          • <span id="createDate"><?= h($recipe['created_at_rec']) ?></span>
        <?php endif; ?>
      </p>
    </header>

    <div class="recipe-layout">

      <section class="recipe-card recipe-photos">
        <h2>Photos</h2>

        <?php if (empty($images)): ?>
          <div class="recipe-gallery">
            <figure class="recipe-photo">
              <img
                src="<?= h($default_image) ?>"
                alt="Default recipe image"
                loading="lazy"
              >
            </figure>
          </div>
        <?php else: ?>
          <div class="recipe-gallery">
            <?php foreach ($images as $img): ?>
              <figure class="recipe-photo">
                <img
                  src="<?= h($img['path_recimg']) ?>"
                  alt="<?= h($img['alt_recimg'] ?? '') ?>"
                  loading="lazy"
                >
                <?php if (!empty($img['alt_recimg'])): ?>
                  <figcaption><?= h($img['alt_recimg']) ?></figcaption>
                <?php endif; ?>
              </figure>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </section>

      <section class="recipe-card recipe-ingredients">
        <h2>Ingredients</h2>

        <div class="scale-controls">
          <button type="button" data-scale="0.5">Half</button>
          <button type="button" data-scale="1">Reset</button>
          <button type="button" data-scale="2">Double</button>
          <button type="button" data-scale="3">Triple</button>
        </div>

        <?php if (empty($ingredients)): ?>
          <p>No ingredients added yet.</p>
        <?php else: ?>
          <ul class="ingredients-list">
            <?php foreach ($ingredients as $ing): ?>
              <li class="ingredient-item">
                <?php
                  $qty  = $ing['quantity_recing'];
                  $unit = $ing['abbreviation_uni'] ?: $ing['name_uni'];
                  $name = $ing['name_ing'];
                  $note = $ing['note_recing'];
                ?>

                <span
                  class="ingredient-qty"
                  data-base="<?= $qty !== null ? h($qty) : '' ?>"
                  data-unit="<?= $unit ? h($unit) : '' ?>"
                >
                  <?= $qty !== null ? h($qty) : '' ?>
                  <?= $unit ? ' ' . h($unit) : '' ?>
                </span>

                <span class="ingredient-name"><?= h($name) ?></span>

                <?php if ($note): ?>
                  <span class="ingredient-note">(<?= h($note) ?>)</span>
                <?php endif; ?>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </section>

    </div>

    <section class="recipe-card recipe-directions">
      <h2>Directions</h2>

      <?php if (empty($steps)): ?>
        <p>No steps added yet.</p>
      <?php else: ?>
        <ol class="steps-list">
          <?php foreach ($steps as $stp): ?>
            <li class="step-item"><?= h($stp['instruction_stp']) ?></li>
          <?php endforeach; ?>
        </ol>
      <?php endif; ?>
    </section>

    <p class="recipe-back"><a href="recipes.php">← Back to Recipes</a></p>

  </main>
</div>

<?php include 'includes/login-modal.php'; ?>
<?php include 'includes/footer.php'; ?>