<?php
require_once 'includes/initialize.php';

/* Authentication */
if (!isset($_SESSION['user_id'])) {
  redirect_error('login_required', 'recipes.php');
}

require_once 'includes/recipe-edit-handler.php';

/* Input validation */
$id = (int) ($_GET['id'] ?? 0);

if ($id <= 0) {
  redirect_error('not_found', 'recipes.php');
}

/* Viewer context */
$viewer_id = (int) ($_SESSION['user_id'] ?? 0);
$role_id   = (int) ($_SESSION['role_id'] ?? 2); // 1=admin, 2=member
$is_admin  = ($role_id === 1);

/* Form state */
$errors = [];
$units  = [];

/* Category option arrays */
$type_options  = [];
$style_options = [];
$diet_options  = [];

/* 1) Fetch recipe core */
$stmt = $db->prepare(
  "SELECT r.id_rec,
          r.title_rec,
          r.description_rec,
          r.status_rec,
          r.id_usr_rec,
          r.prep_minutes_rec,
          r.cook_minutes_rec,
          r.youtube_url_rec
   FROM recipe_rec r
   WHERE r.id_rec = ?
   LIMIT 1"
);

if (!$stmt) {
  internal_error("Prepare failed: " . $db->error);
}

$stmt->bind_param('i', $id);
$stmt->execute();

$recipe = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$recipe) {
  redirect_error('not_found', 'recipes.php');
}

/* 2) Edit permission */
$is_owner   = ($viewer_id > 0 && (int) $recipe['id_usr_rec'] === $viewer_id);
$is_pending = ((string) $recipe['status_rec'] === 'pending');
$can_edit   = ($is_admin || ($is_owner && $is_pending));

if (!$can_edit) {
  header('Location: recipe.php?id=' . $id);
  exit;
}

/* 3) Load category options */
$res = $db->query(
  "SELECT id_cat, group_cat, name_cat
   FROM category_cat
   ORDER BY name_cat ASC"
);

if ($res) {
  while ($row = $res->fetch_assoc()) {
    $cat_id = (int) $row['id_cat'];
    $group  = (string) ($row['group_cat'] ?? '');
    $name   = (string) ($row['name_cat'] ?? '');

    if ($group === 'type') {
      $type_options[$cat_id] = $name;
    }

    if ($group === 'style') {
      $style_options[$cat_id] = $name;
    }

    if ($group === 'diet') {
      $diet_options[$cat_id] = $name;
    }
  }

  $res->free();
}

/* 4) Load units */
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

/* 5) Load selected category IDs for this recipe */
$selected_categories = [
  'type'  => [],
  'style' => [],
  'diet'  => [],
];

$stmt = $db->prepare(
  "SELECT c.id_cat, c.group_cat
   FROM recipe_category_reccat rc
   JOIN category_cat c ON c.id_cat = rc.id_cat_reccat
   WHERE rc.id_rec_reccat = ?"
);

if (!$stmt) {
  internal_error("Prepare failed: " . $db->error);
}

$stmt->bind_param('i', $id);
$stmt->execute();

$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
  $group = (string) ($row['group_cat'] ?? '');
  $cat_id = (int) ($row['id_cat'] ?? 0);

  if ($cat_id > 0 && isset($selected_categories[$group])) {
    $selected_categories[$group][] = $cat_id;
  }
}

$stmt->close();

/* 6) Load ingredients */
$ingredient_rows = [];

$stmt = $db->prepare(
  "SELECT
      ri.quantity_recing,
      ri.id_uni_recing,
      ing.name_ing,
      ri.note_recing
   FROM recipe_ingredient_recing ri
   JOIN ingredient_ing ing ON ing.id_ing = ri.id_ing_recing
   WHERE ri.id_rec_recing = ?
   ORDER BY ri.id_recing ASC"
);

if (!$stmt) {
  internal_error("Prepare failed: " . $db->error);
}

$stmt->bind_param('i', $id);
$stmt->execute();

$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
  $ingredient_rows[] = $row;
}

$stmt->close();

/* 7) Load steps */
$step_rows = [];

$stmt = $db->prepare(
  "SELECT step_number_stp, instruction_stp
   FROM recipe_step_stp
   WHERE id_rec_stp = ?
   ORDER BY step_number_stp ASC"
);

if (!$stmt) {
  internal_error("Prepare failed: " . $db->error);
}

$stmt->bind_param('i', $id);
$stmt->execute();

$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
  $step_rows[] = $row;
}

$stmt->close();

/* 8) Load existing images */
$existing_images = [];

$stmt = $db->prepare(
  "SELECT id_recimg, path_recimg, alt_recimg, sort_order_recimg
   FROM recipe_image_recimg
   WHERE id_rec_recimg = ?
   ORDER BY sort_order_recimg ASC, id_recimg ASC"
);

if (!$stmt) {
  internal_error("Prepare failed: " . $db->error);
}

$stmt->bind_param('i', $id);
$stmt->execute();

$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
  $existing_images[] = $row;
}

$stmt->close();

/* 9) Build default form values from DB */
$form_title       = (string) ($recipe['title_rec'] ?? '');
$form_description = (string) ($recipe['description_rec'] ?? '');
$form_youtube_url = (string) ($recipe['youtube_url_rec'] ?? '');
$form_image_alt   = '';

$prep_total = isset($recipe['prep_minutes_rec']) && $recipe['prep_minutes_rec'] !== null
  ? (int) $recipe['prep_minutes_rec']
  : 0;

$cook_total = isset($recipe['cook_minutes_rec']) && $recipe['cook_minutes_rec'] !== null
  ? (int) $recipe['cook_minutes_rec']
  : 0;

$form_prep_hours   = intdiv($prep_total, 60);
$form_prep_minutes = $prep_total % 60;

$form_cook_hours   = intdiv($cook_total, 60);
$form_cook_minutes = $cook_total % 60;

$form_type  = $selected_categories['type'];
$form_style = $selected_categories['style'];
$form_diet  = $selected_categories['diet'];

$form_qty  = [];
$form_unit = [];
$form_ing  = [];
$form_note = [];

foreach ($ingredient_rows as $row) {
  $qty = $row['quantity_recing'];
  $unit_id = $row['id_uni_recing'];

  $form_qty[]  = ($qty !== null) ? (string) $qty : '';
  $form_unit[] = ($unit_id !== null) ? (string) $unit_id : '';
  $form_ing[]  = (string) ($row['name_ing'] ?? '');
  $form_note[] = (string) ($row['note_recing'] ?? '');
}

$form_step = [];

foreach ($step_rows as $row) {
  $form_step[] = (string) ($row['instruction_stp'] ?? '');
}

/* 10) Handle POST */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  $form_title       = trim((string) ($_POST['title'] ?? ''));
  $form_description = trim((string) ($_POST['description'] ?? ''));
  $form_youtube_url = trim((string) ($_POST['youtube_url'] ?? ''));
  $form_image_alt   = trim((string) ($_POST['image_alt'] ?? ''));

  $form_prep_hours   = (int) ($_POST['prep_hours'] ?? 0);
  $form_prep_minutes = (int) ($_POST['prep_minutes'] ?? 0);
  $form_cook_hours   = (int) ($_POST['cook_hours'] ?? 0);
  $form_cook_minutes = (int) ($_POST['cook_minutes'] ?? 0);

  $form_type  = is_array($_POST['type'] ?? null)  ? $_POST['type']  : [];
  $form_style = is_array($_POST['style'] ?? null) ? $_POST['style'] : [];
  $form_diet  = is_array($_POST['diet'] ?? null)  ? $_POST['diet']  : [];

  $form_qty  = is_array($_POST['qty'] ?? null)  ? $_POST['qty']  : [];
  $form_unit = is_array($_POST['unit'] ?? null) ? $_POST['unit'] : [];
  $form_ing  = is_array($_POST['ing'] ?? null)  ? $_POST['ing']  : [];
  $form_note = is_array($_POST['note'] ?? null) ? $_POST['note'] : [];
  $form_step = is_array($_POST['step'] ?? null) ? $_POST['step'] : [];

  $res = handle_recipe_edit($db, $id);

  if ($res['ok']) {
    header('Location: recipe.php?id=' . $id);
    exit;
  }

  $errors = $res['errors'];
}

include 'includes/header.php';
?>

<div id="container">
  <main>

    <h1>Edit Recipe</h1>

    <?php if (!empty($errors['general'])): ?>
      <p role="alert">
        <strong><?= h($errors['general']) ?></strong>
      </p>
    <?php endif; ?>

    <p>
      Editing recipe:
      <strong><?= h($recipe['title_rec']) ?></strong>
    </p>

    <?php if (!$is_admin): ?>
      <p>
        You can edit this recipe because it is still pending review.
      </p>
    <?php endif; ?>

    <style>
      .is-hidden {
        display: none !important;
      }
    </style>

    <form
      method="post"
      action="edit-recipe.php?id=<?= (int) $id ?>"
      enctype="multipart/form-data"
      class="recipe-form"
      id="recipeEditForm"
    >

      <label for="title">Recipe Title</label>

      <input
        id="title"
        name="title"
        type="text"
        maxlength="120"
        required
        value="<?= h($form_title) ?>"
      >

      <label for="description">Description (max 250 characters)</label>

      <textarea
        id="description"
        name="description"
        maxlength="250"
        rows="3"
        placeholder="Short summary of the recipe..."
      ><?= h($form_description) ?></textarea>

      <p class="field-help">
        <span id="descCount">0</span>/250
      </p>

      <fieldset class="time-fields">
        <legend>Time</legend>

        <label for="prep_hours">Prep Time</label>
        <div class="time-row">
          <div class="time-col">
            <select id="prep_hours" name="prep_hours">
              <?php for ($h = 0; $h <= 24; $h++): ?>
                <option value="<?= $h ?>" <?= ($h === (int) $form_prep_hours) ? 'selected' : '' ?>>
                  <?= $h ?> hr
                </option>
              <?php endfor; ?>
            </select>
          </div>

          <div class="time-col">
            <select id="prep_minutes" name="prep_minutes">
              <?php for ($m = 0; $m <= 59; $m++): ?>
                <option value="<?= $m ?>" <?= ($m === (int) $form_prep_minutes) ? 'selected' : '' ?>>
                  <?= $m ?> min
                </option>
              <?php endfor; ?>
            </select>
          </div>
        </div>

        <label for="cook_hours">Cook Time</label>
        <div class="time-row">
          <div class="time-col">
            <select id="cook_hours" name="cook_hours">
              <?php for ($h = 0; $h <= 24; $h++): ?>
                <option value="<?= $h ?>" <?= ($h === (int) $form_cook_hours) ? 'selected' : '' ?>>
                  <?= $h ?> hr
                </option>
              <?php endfor; ?>
            </select>
          </div>

          <div class="time-col">
            <select id="cook_minutes" name="cook_minutes">
              <?php for ($m = 0; $m <= 59; $m++): ?>
                <option value="<?= $m ?>" <?= ($m === (int) $form_cook_minutes) ? 'selected' : '' ?>>
                  <?= $m ?> min
                </option>
              <?php endfor; ?>
            </select>
          </div>
        </div>
      </fieldset>

      <label for="youtube_url">YouTube link (optional)</label>
      <input
        id="youtube_url"
        name="youtube_url"
        type="url"
        maxlength="255"
        placeholder="https://www.youtube.com/watch?v=..."
        value="<?= h($form_youtube_url) ?>"
      >

      <fieldset class="chip-set">
        <legend>Diet</legend>

        <?php foreach ($diet_options as $cat_id => $label): ?>
          <?php
            $checked = in_array((string) $cat_id, array_map('strval', $form_diet), true)
              ? 'checked'
              : '';
          ?>

          <label class="chip">
            <input
              class="chip-input"
              type="checkbox"
              name="diet[]"
              value="<?= $cat_id ?>"
              <?= $checked ?>
            >
            <span class="chip-text"><?= h($label) ?></span>
          </label>
        <?php endforeach; ?>
      </fieldset>

      <fieldset class="chip-set">
        <legend>Type</legend>

        <?php foreach ($type_options as $cat_id => $label): ?>
          <?php
            $checked = in_array((string) $cat_id, array_map('strval', $form_type), true)
              ? 'checked'
              : '';
          ?>

          <label class="chip">
            <input
              class="chip-input"
              type="checkbox"
              name="type[]"
              value="<?= $cat_id ?>"
              <?= $checked ?>
            >
            <span class="chip-text"><?= h($label) ?></span>
          </label>
        <?php endforeach; ?>
      </fieldset>

      <fieldset class="chip-set">
        <legend>Style</legend>

        <?php foreach ($style_options as $cat_id => $label): ?>
          <?php
            $checked = in_array((string) $cat_id, array_map('strval', $form_style), true)
              ? 'checked'
              : '';
          ?>

          <label class="chip">
            <input
              class="chip-input"
              type="checkbox"
              name="style[]"
              value="<?= $cat_id ?>"
              <?= $checked ?>
            >
            <span class="chip-text"><?= h($label) ?></span>
          </label>
        <?php endforeach; ?>
      </fieldset>

      <h2>Ingredients</h2>
      <p>Add as many ingredients as needed. Use “Add ingredient”.</p>

      <button type="button" id="addIngredientBtn">+ Add ingredient</button>

      <?php for ($i = 0; $i < 12; $i++): ?>
        <?php
          $qty_val  = $form_qty[$i]  ?? '';
          $unit_val = $form_unit[$i] ?? '';
          $ing_val  = $form_ing[$i]  ?? '';
          $note_val = $form_note[$i] ?? '';

          $row_has_value = (
            trim((string) $qty_val) !== '' ||
            trim((string) $unit_val) !== '' ||
            trim((string) $ing_val) !== '' ||
            trim((string) $note_val) !== ''
          );

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
      <p>Add as many steps as needed. Use “Add step”.</p>

      <button type="button" id="addStepBtn">+ Add step</button>

      <?php for ($i = 0; $i < 12; $i++): ?>
        <?php
          $step_val = $form_step[$i] ?? '';
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

      <h2>Existing Images</h2>

      <?php if (empty($existing_images)): ?>
        <p>No images uploaded yet.</p>
      <?php else: ?>
        <div class="recipe-gallery">
          <?php foreach ($existing_images as $img): ?>
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

      <h2>Add More Images</h2>

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

      <label for="image_alt">Image description for new uploads (optional)</label>

      <input
        id="image_alt"
        name="image_alt"
        type="text"
        maxlength="120"
        value="<?= h($form_image_alt) ?>"
      >

      <button type="submit">Save Changes</button>

    </form>
  </main>
</div>

<?php include 'includes/login-modal.php'; ?>
<?php include 'includes/footer.php'; ?>