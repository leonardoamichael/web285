<?php
require_once 'includes/initialize.php';

/* Authentication */
if (!isset($_SESSION['user_id'])) {
  redirect_error('login_required', 'recipes.php');
}

require_once 'includes/recipe-submit-handler.php';

/* Form state */
$errors    = [];
$title     = '';
$image_alt = '';
$units     = [];

/* Units dropdown data */
$res = $db->query(
  "SELECT id_uni, name_uni, abbreviation_uni
   FROM unit_uni
   ORDER BY name_uni ASC"
);

if ($res) {
  while ($row = $res->fetch_assoc()) {
    $units[] = $row;
  }
  $res->free();
}

/* Form submission */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  $title     = trim((string) ($_POST['title'] ?? ''));
  $image_alt = trim((string) ($_POST['image_alt'] ?? ''));

  $res = handle_recipe_submit($db);

  if ($res['ok']) {
    header('Location: recipe.php?id=' . (int) $res['recipe_id']);
    exit;
  }

  $errors = $res['errors'];
}

include 'includes/header.php';
?>

<div id="container">
  <main>

    <h1>Submit Recipe</h1>

    <?php if (!empty($errors['general'])): ?>
      <p role="alert">
        <strong><?= h($errors['general']) ?></strong>
      </p>
    <?php endif; ?>

    <!-- Progressive reveal: hide by default (prevents flash + works even if JS loads late) -->
    <style>
      .is-hidden {
        display: none !important;
      }
    </style>

    <form
      method="post"
      action="submit.php"
      enctype="multipart/form-data"
      class="recipe-form"
      id="recipeSubmitForm"
    >

      <label for="title">Recipe Title</label>

      <input
        id="title"
        name="title"
        type="text"
        maxlength="120"
        required
        value="<?= h($title ?? '') ?>"
      >

      <h2>Ingredients</h2>
      <p>Enter up to 6 ingredients (leave unused rows blank).</p>

      <button type="button" id="addIngredientBtn">+ Add ingredient</button>

      <?php for ($i = 0; $i < 6; $i++): ?>
        <?php
          /*
           * If the user submitted the form and had values in a row,
           * keep it visible on POST-back.
           */
          $qty_val  = $_POST['qty'][$i]  ?? '';
          $unit_val = $_POST['unit'][$i] ?? '';
          $ing_val  = $_POST['ing'][$i]  ?? '';
          $note_val = $_POST['note'][$i] ?? '';

          $row_has_value = (
            trim((string) $qty_val) !== '' ||
            trim((string) $unit_val) !== '' ||
            trim((string) $ing_val) !== '' ||
            trim((string) $note_val) !== ''
          );

          // Default state: hidden unless it has values (POST back).
          // JS will reveal the first 2 rows on a fresh load.
          $hidden_class = $row_has_value ? '' : 'is-hidden';
        ?>

        <fieldset class="ingredient-row <?= $hidden_class ?>">
          <legend>Ingredient <?= $i + 1 ?></legend>

          <label for="qty<?= $i ?>">Quantity</label>
          <input
            id="qty<?= $i ?>"
            name="qty[]"
            type="number"
            step="0.01"
            min="0"
            inputmode="decimal"
            placeholder="e.g., 2"
            value="<?= h($qty_val) ?>"
          >

          <label for="unit<?= $i ?>">Unit</label>
          <select id="unit<?= $i ?>" name="unit[]">
            <option value="">(none)</option>

            <?php foreach ($units as $u): ?>
              <?php
                $selected = ((string) $u['id_uni'] === (string) $unit_val)
                  ? 'selected'
                  : '';
              ?>

              <option value="<?= (int) $u['id_uni'] ?>" <?= $selected ?>>
                <?= h($u['name_uni']) ?>
                <?php if (!empty($u['abbreviation_uni'])): ?>
                  (<?= h($u['abbreviation_uni']) ?>)
                <?php endif; ?>
              </option>
            <?php endforeach; ?>

          </select>

          <label for="ing<?= $i ?>">Ingredient</label>
          <input
            id="ing<?= $i ?>"
            name="ing[]"
            type="text"
            maxlength="120"
            placeholder="e.g., olive oil"
            value="<?= h($ing_val) ?>"
          >

          <label for="note<?= $i ?>">Note (optional)</label>
          <input
            id="note<?= $i ?>"
            name="note[]"
            type="text"
            maxlength="120"
            placeholder="e.g., finely chopped"
            value="<?= h($note_val) ?>"
          >
        </fieldset>
      <?php endfor; ?>

      <h2>Directions</h2>
      <p>Enter up to 8 steps (leave unused steps blank).</p>

      <button type="button" id="addStepBtn">+ Add step</button>

      <?php for ($i = 0; $i < 8; $i++): ?>
        <?php
          $step_val = $_POST['step'][$i] ?? '';

          $step_has_value = (trim((string) $step_val) !== '');
          $step_hidden_class = $step_has_value ? '' : 'is-hidden';
        ?>

        <div class="step-wrap <?= $step_hidden_class ?>">
          <label for="step<?= $i ?>">Step <?= $i + 1 ?></label>

          <textarea
            id="step<?= $i ?>"
            name="step[]"
            class="step-row"
            rows="3"
            placeholder="Write step <?= $i + 1 ?>..."
          ><?= h($step_val) ?></textarea>
        </div>
      <?php endfor; ?>

      <h2>Images</h2>

      <label for="images">
        Recipe Images (optional — you can select multiple)
      </label>

      <input
        id="images"
        name="images[]"
        type="file"
        accept=".jpg,.jpeg,.png,.webp"
        multiple
      >

      <label for="image_alt">Image description (optional)</label>

      <input
        id="image_alt"
        name="image_alt"
        type="text"
        maxlength="120"
        value="<?= h($image_alt ?? '') ?>"
      >

      <button type="submit">Submit Recipe</button>

    </form>
  </main>
</div>

<?php include 'includes/login-modal.php'; ?>
<?php include 'includes/footer.php'; ?>