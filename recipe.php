<?php
require_once 'includes/initialize.php';
require_once 'includes/recipe-data.php';

include 'includes/header.php';

/* Input validation */
$id = (int) ($_GET['id'] ?? 0);

/* Viewer context */
$viewer_id  = (int) ($_SESSION['user_id'] ?? 0);
$role_id    = (int) ($_SESSION['role_id'] ?? 2); // 1=admin, 2=member
$is_admin   = ($role_id === 1);

$data = fetch_recipe_page_data($db, $id, $viewer_id, $is_admin);

$recipe        = $data['recipe'];
$ingredients   = $data['ingredients'];
$steps         = $data['steps'];
$images        = $data['images'];
$cats_by_group = $data['cats_by_group'];
$avg_rating    = $data['avg_rating'];
$rating_count  = $data['rating_count'];
$user_rating   = $data['user_rating'];
$default_image = $data['default_image'];
$is_owner      = $data['is_owner'];
$is_approved   = $data['is_approved'];
?>

<div id="container">
  <main class="recipe-page">

    <header class="recipe-header">
      <h1 class="recipe-title"><?= h($recipe['title_rec']) ?></h1>

      <?php
      $can_edit = $is_admin || ($is_owner && $recipe['status_rec'] === 'pending');
      ?>

      <?php if ($can_edit): ?>
        <div class="recipe-edit-note">

          <p>
            <a href="edit-recipe.php?id=<?= (int)$id ?>">Edit Recipe</a>
          </p>

          <?php if (!$is_admin): ?>
            <p class="recipe-edit-help">
              You can edit this recipe while it is pending review. Once approved, editing is disabled.
            </p>
          <?php endif; ?>

        </div>
      <?php endif; ?>

      <?php if (!empty($recipe['description_rec'])): ?>
        <p class="recipe-description">
          <?= h($recipe['description_rec']) ?>
        </p>
      <?php endif; ?>

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

      <?php if ($rating_count > 0): ?>
        <?php
          $display_rating = round($avg_rating * 2) / 2;
          $star_percent = ($display_rating / 5) * 100;
          $is_perfect_rating = ($avg_rating >= 4.95);
        ?>

        <div class="recipe-meta recipe-rating recipe-rating-summary">
          <strong>Rating:</strong>

          <span class="rating-stars<?= $is_perfect_rating ? ' rating-stars--perfect' : '' ?>" aria-hidden="true">
            <span class="rating-stars-base">★★★★★</span>
            <span class="rating-stars-fill" style="width: <?= h((string)$star_percent) ?>%;">★★★★★</span>
          </span>

          <span class="rating-text">
            <?= h(number_format($avg_rating, 1)) ?> / 5
            (<?= (int) $rating_count ?> <?= $rating_count === 1 ? 'rating' : 'ratings' ?>)

            <?php if ($is_perfect_rating): ?>
              <span class="rating-badge" title="Near-perfect rating">⭐</span>
            <?php endif; ?>
          </span>
        </div>
      <?php else: ?>
        <p class="recipe-meta recipe-rating">
          <strong>Rating:</strong> No ratings yet
        </p>
      <?php endif; ?>

      <?php
      $prep = isset($recipe['prep_minutes_rec']) ? (int)$recipe['prep_minutes_rec'] : 0;
      $cook = isset($recipe['cook_minutes_rec']) ? (int)$recipe['cook_minutes_rec'] : 0;

      $has_prep = !empty($recipe['prep_minutes_rec']);
      $has_cook = !empty($recipe['cook_minutes_rec']);
      ?>

      <?php if ($has_prep || $has_cook): ?>
        <p class="recipe-meta">
          <?php if ($has_prep): ?>
            <strong>Prep:</strong> <?= h(format_minutes_to_hr_min($prep)) ?>
          <?php endif; ?>

          <?php if ($has_prep && $has_cook): ?>
            •
          <?php endif; ?>

          <?php if ($has_cook): ?>
            <strong>Cook:</strong> <?= h(format_minutes_to_hr_min($cook)) ?>
          <?php endif; ?>

          <?php
          $total = 0;
          $has_total = false;

          if ($has_prep) {
            $total += $prep;
            $has_total = true;
          }

          if ($has_cook) {
            $total += $cook;
            $has_total = true;
          }
          ?>

          <?php if ($has_total && ($has_prep && $has_cook)): ?>
            • <strong>Total:</strong> <?= h(format_minutes_to_hr_min($total)) ?>
          <?php endif; ?>
        </p>
      <?php endif; ?>

      <?php if ($viewer_id > 0): ?>
        <form class="recipe-rating-form" method="post" action="rate-recipe.php">

          <input type="hidden" name="recipe_id" value="<?= (int)$id ?>">

          <span class="recipe-rating-label"><strong>Your rating:</strong></span>

          <div class="star-rating-input">

            <?php for ($i = 5; $i >= 1; $i--): ?>
              <input
                type="radio"
                id="star<?= $i ?>"
                name="rating"
                value="<?= $i ?>"
                <?= ($user_rating === $i) ? 'checked' : '' ?>
              >

              <label for="star<?= $i ?>" title="<?= $i ?> star<?= $i > 1 ? 's' : '' ?>">★</label>
            <?php endfor; ?>

          </div>

          <button type="submit">
            <?= $user_rating ? 'Update Rating' : 'Submit Rating' ?>
          </button>

        </form>
      <?php endif; ?>
    </header>

    <div class="recipe-actions no-print">
      <a class="recipe-print-button" href="recipe-print.php?id=<?= (int)$id ?>">
        Print-Friendly View
      </a>
    </div>

    <?php
      $max_per_group = 4;
    ?>

    <?php if (!empty($cats_by_group['type']) || !empty($cats_by_group['style']) || !empty($cats_by_group['diet'])): ?>
      <div class="recipe-tags" aria-label="Recipe categories">
        <?php
          $groups = [
            'type'  => 'Type',
            'style' => 'Style',
            'diet'  => 'Diet',
          ];
        ?>

        <?php foreach ($groups as $key => $label): ?>
          <?php if (!empty($cats_by_group[$key])): ?>
            <?php
              $all = $cats_by_group[$key];
              $visible = array_slice($all, 0, $max_per_group);
              $overflow = max(0, count($all) - count($visible));
            ?>

            <div class="tag-group">
              <span class="tag-label"><?= h($label) ?>:</span>

              <div class="tag-pills" role="list">
                <?php foreach ($visible as $pill): ?>
                  <span class="tag-pill" role="listitem"><?= h($pill) ?></span>
                <?php endforeach; ?>

                <?php if ($overflow > 0): ?>
                  <span class="tag-pill tag-pill--more" aria-label="<?= $overflow ?> more <?= h($label) ?> categories">
                    +<?= (int) $overflow ?> more
                  </span>
                <?php endif; ?>
              </div>
            </div>
          <?php endif; ?>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

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

      <?php
      $embed = youtube_embed_url($recipe['youtube_url_rec'] ?? '');
      ?>

      <?php if ($embed !== ''): ?>
        <section class="recipe-card recipe-video">
          <h2>Video</h2>

          <div class="video-embed">
            <iframe
              src="<?= h($embed) ?>"
              title="YouTube video"
              allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
              allowfullscreen
              loading="lazy"
              referrerpolicy="strict-origin-when-cross-origin"
            ></iframe>
          </div>
        </section>
      <?php endif; ?>

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