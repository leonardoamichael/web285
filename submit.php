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

/*Diet Array */
$type_options  = [];
$style_options = [];
$diet_options  = [];

$res = $db->query(
  "SELECT id_cat, group_cat, name_cat
   FROM category_cat
   ORDER BY name_cat ASC"
);

if ($res) {
  while ($row = $res->fetch_assoc()) {

    $id    = (int) $row['id_cat'];
    $group = $row['group_cat'];
    $name  = $row['name_cat'];

    if ($group === 'type') {
      $type_options[$id] = $name;
    }

    if ($group === 'style') {
      $style_options[$id] = $name;
    }

    if ($group === 'diet') {
      $diet_options[$id] = $name;
    }
  }

  $res->free();
}

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

    <p class="submit-intro">
      Share a recipe with the community. Fields marked <strong>*</strong> are required.
    </p>

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

      <label for="title">Recipe Title <span class="required-mark">*</span></label>

      <input
        id="title"
        name="title"
        type="text"
        maxlength="120"
        required
        value="<?= h($title ?? '') ?>"
      >

      <label for="description">
        Description <span class="optional-mark">(optional)</span>
      </label>

      <textarea
        id="description"
        name="description"
        maxlength="250"
        rows="3"
        placeholder="Short summary of the recipe..."
      ><?= h($_POST['description'] ?? '') ?></textarea>

      <p class="field-help">
        <span id="descCount">0</span>/250
      </p>

      <fieldset class="time-fields">
        <legend>Time <span class="optional-mark">(optional)</span></legend>

        <?php
        $prep_hours_val = (int)($_POST['prep_hours'] ?? 0);
        $prep_mins_val  = (int)($_POST['prep_minutes'] ?? 0);

        $cook_hours_val = (int)($_POST['cook_hours'] ?? 0);
        $cook_mins_val  = (int)($_POST['cook_minutes'] ?? 0);
        ?>

        <p class="time-label">Prep Time</p>
        <div class="time-row">
          <div class="time-col">
            <select id="prep_hours" name="prep_hours">
              <?php for ($h = 0; $h <= 24; $h++): ?>
                <option value="<?= $h ?>" <?= ($h === $prep_hours_val) ? 'selected' : '' ?>>
                  <?= $h ?> hr
                </option>
              <?php endfor; ?>
            </select>
          </div>

          <div class="time-col">
            <select id="prep_minutes" name="prep_minutes">
              <?php for ($m = 0; $m <= 59; $m++): ?>
                <option value="<?= $m ?>" <?= ($m === $prep_mins_val) ? 'selected' : '' ?>>
                  <?= $m ?> min
                </option>
              <?php endfor; ?>
            </select>
          </div>
        </div>

        <p class="time-label">Cook Time</p>
        <div class="time-row">
          <div class="time-col">
            <select id="cook_hours" name="cook_hours">
              <?php for ($h = 0; $h <= 24; $h++): ?>
                <option value="<?= $h ?>" <?= ($h === $cook_hours_val) ? 'selected' : '' ?>>
                  <?= $h ?> hr
                </option>
              <?php endfor; ?>
            </select>
          </div>

          <div class="time-col">
            <select id="cook_minutes" name="cook_minutes">
              <?php for ($m = 0; $m <= 59; $m++): ?>
                <option value="<?= $m ?>" <?= ($m === $cook_mins_val) ? 'selected' : '' ?>>
                  <?= $m ?> min
                </option>
              <?php endfor; ?>
            </select>
          </div>
        </div>
      </fieldset>

      <fieldset>
        <legend>YouTube Video <span class="optional-mark">(optional)</span></legend>

        <label for="youtube_url">YouTube link</label>

        <input
          type="text"
          id="youtube_url"
          name="youtube_url"
          placeholder="https://www.youtube.com/watch?v=..."
          value="<?= h($_POST['youtube_url'] ?? '') ?>"
        >
      </fieldset>

      <fieldset class="chip-set filter-chipset filter-group">
        <legend>Diet <span class="optional-mark">(optional)</span></legend>

        <input
          class="filter-group-search"
          type="search"
          placeholder="Search diet"
          aria-label="Search diet"
        >

        <div class="filter-group-body">

          <?php foreach ($diet_options as $id => $label): ?>

            <?php
              $checked = in_array(
                $id,
                (array)($_POST['diet'] ?? []),
                true
              ) ? 'checked' : '';
            ?>

            <label class="chip">
              <input
                class="chip-input"
                type="checkbox"
                name="diet[]"
                value="<?= $id ?>"
                <?= $checked ?>
              >
              <span class="chip-text"><?= h($label) ?></span>
            </label>

          <?php endforeach; ?>

        </div>

        <button type="button" class="filter-group-toggle">Show All</button>
      </fieldset>

      <fieldset class="chip-set filter-chipset filter-group">
        <legend>Type <span class="optional-mark">(optional)</span></legend>

        <input
          class="filter-group-search"
          type="search"
          placeholder="Search type"
          aria-label="Search type"
        >

        <div class="filter-group-body">

          <?php foreach ($type_options as $id => $label): ?>

            <?php
              $checked = in_array(
                $id,
                (array)($_POST['type'] ?? []),
                true
              ) ? 'checked' : '';
            ?>

            <label class="chip">
              <input
                class="chip-input"
                type="checkbox"
                name="type[]"
                value="<?= $id ?>"
                <?= $checked ?>
              >
              <span class="chip-text"><?= h($label) ?></span>
            </label>

          <?php endforeach; ?>

        </div>

        <button type="button" class="filter-group-toggle">Show All</button>
      </fieldset>

      <fieldset class="chip-set filter-chipset filter-group">
        <legend>Style <span class="optional-mark">(optional)</span></legend>

        <input
          class="filter-group-search"
          type="search"
          placeholder="Search style"
          aria-label="Search style"
        >

        <div class="filter-group-body">

          <?php foreach ($style_options as $id => $label): ?>

            <?php
              $checked = in_array(
                $id,
                (array)($_POST['style'] ?? []),
                true
              ) ? 'checked' : '';
            ?>

            <label class="chip">
              <input
                class="chip-input"
                type="checkbox"
                name="style[]"
                value="<?= $id ?>"
                <?= $checked ?>
              >
              <span class="chip-text"><?= h($label) ?></span>
            </label>

          <?php endforeach; ?>

        </div>

        <button type="button" class="filter-group-toggle">Show All</button>
      </fieldset>

      <h2>Ingredients <span class="required-mark">*</span></h2>
      <p>Add at least one ingredient. Use “Add ingredient” to add more.</p>

      <button type="button" id="addIngredientBtn">+ Add ingredient</button>

      <?php for ($i = 0; $i < 12; $i++): ?>
        <?php
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

          <label for="note<?= $i ?>">
            Note <span class="optional-mark">(optional)</span>
          </label>
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

      <h2>Directions <span class="required-mark">*</span></h2>
      <p>Add at least one step. Use “Add step” to add more.</p>

      <button type="button" id="addStepBtn">+ Add step</button>

      <?php for ($i = 0; $i < 12; $i++): ?>
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

      <h2>Images <span class="optional-mark">(optional)</span></h2>

      <label for="images">
        Recipe Images <span class="optional-mark">(optional — you can select multiple)</span>
      </label>

      <input
      id="images"
      name="images[]"
      type="file"
      accept=".jpg,.jpeg,.png,.webp"
      multiple
      class="file-input"
      >


      <label for="image_alt">
        Image description <span class="optional-mark">(optional)</span>
      </label>

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