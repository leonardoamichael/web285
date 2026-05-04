<?php
require_once 'includes/initialize.php';
require_once 'includes/recipe-data.php';

include 'includes/header.php';

/* Input validation */
$id = (int) ($_GET['id'] ?? 0);

/* Viewer context */
$viewer_id = (int) ($_SESSION['user_id'] ?? 0);
$role_id   = (int) ($_SESSION['role_id'] ?? 2); // 1=admin, 2=member
$is_admin  = ($role_id === 1);

$data = fetch_recipe_page_data($db, $id, $viewer_id, $is_admin);

$recipe        = $data['recipe'];
$ingredients   = $data['ingredients'];
$steps         = $data['steps'];
$images        = $data['images'];
$cats_by_group = $data['cats_by_group'];
$default_image = $data['default_image'];

$prep = isset($recipe['prep_minutes_rec']) ? (int)$recipe['prep_minutes_rec'] : 0;
$cook = isset($recipe['cook_minutes_rec']) ? (int)$recipe['cook_minutes_rec'] : 0;

$has_prep = !empty($recipe['prep_minutes_rec']);
$has_cook = !empty($recipe['cook_minutes_rec']);
?>

<div id="container">
  <main class="recipe-print-page">

    <section class="recipe-print-controls no-print" aria-label="Print preview controls">
      <a class="recipe-print-back" href="recipe.php?id=<?= (int)$id ?>">
        ← Back to Recipe
      </a>

      <div class="recipe-print-options">
        <label>
          <input type="checkbox" id="printShowPhotos">
          Include photos
        </label>

        <label>
          <input type="checkbox" id="printShowDescription" checked>
          Include description
        </label>

        <label>
          <input type="checkbox" id="printShowCategories" checked>
          Include categories
        </label>

        <label>
          <input type="checkbox" id="printShowNotes">
          Include notes box
        </label>
      </div>

      <button type="button" id="printPageButton">
        Print Recipe
      </button>
    </section>

    <article class="recipe-print-sheet">

      <header class="recipe-print-header">
        <h1><?= h($recipe['title_rec']) ?></h1>

        <p class="recipe-print-meta">
          By <?= h($recipe['username_usr']) ?>

          <?php if ($has_prep): ?>
            • Prep: <?= h(format_minutes_to_hr_min($prep)) ?>
          <?php endif; ?>

          <?php if ($has_cook): ?>
            • Cook: <?= h(format_minutes_to_hr_min($cook)) ?>
          <?php endif; ?>

          <?php if ($has_prep && $has_cook): ?>
            • Total: <?= h(format_minutes_to_hr_min($prep + $cook)) ?>
          <?php endif; ?>
        </p>

        <?php if (!empty($recipe['description_rec'])): ?>
          <p class="recipe-print-description" data-print-section="description">
            <?= h($recipe['description_rec']) ?>
          </p>
        <?php endif; ?>

        <?php if (!empty($cats_by_group['type']) || !empty($cats_by_group['style']) || !empty($cats_by_group['diet'])): ?>
          <div class="recipe-print-categories" data-print-section="categories">
            <?php foreach (['type' => 'Type', 'style' => 'Style', 'diet' => 'Diet'] as $key => $label): ?>
              <?php if (!empty($cats_by_group[$key])): ?>
                <p>
                  <strong><?= h($label) ?>:</strong>
                  <?= h(implode(', ', $cats_by_group[$key])) ?>
                </p>
              <?php endif; ?>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </header>

      <section class="recipe-print-photos" data-print-section="photos">
        <h2>Photos</h2>

        <?php if (empty($images)): ?>
          <figure>
            <img src="<?= h($default_image) ?>" alt="Default recipe image">
          </figure>
        <?php else: ?>
          <div class="recipe-print-gallery">
            <?php foreach ($images as $img): ?>
              <figure>
                <img
                  src="<?= h($img['path_recimg']) ?>"
                  alt="<?= h($img['alt_recimg'] ?? '') ?>"
                >

                <?php if (!empty($img['alt_recimg'])): ?>
                  <figcaption><?= h($img['alt_recimg']) ?></figcaption>
                <?php endif; ?>
              </figure>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </section>

      <div class="recipe-print-columns">

        <section class="recipe-print-section recipe-print-ingredients">
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

                  <?php if ($qty !== null || $unit): ?>
                    <strong>
                      <?= $qty !== null ? h($qty) : '' ?>
                      <?= $unit ? ' ' . h($unit) : '' ?>
                    </strong>
                  <?php endif; ?>

                  <?= h($name) ?>

                  <?php if ($note): ?>
                    <span>(<?= h($note) ?>)</span>
                  <?php endif; ?>
                </li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>
        </section>

        <section class="recipe-print-section recipe-print-directions">
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

      </div>

      <section class="recipe-print-notes" data-print-section="notes">
        <h2>Notes</h2>
        <div class="recipe-print-note-box"></div>
      </section>

    </article>

  </main>
</div>

<?php include 'includes/footer.php'; ?>