<div class="recipe-grid">

<?php
$viewer_id   = (int) ($_SESSION['user_id'] ?? 0);
$viewer_role = (string) ($_SESSION['role'] ?? '');
$is_admin    = ($viewer_role === 'admin');

$visible_recipes = [];

if (!empty($recipes) && is_array($recipes)) {
  foreach ($recipes as $r) {
    if (!isset($r['status_rec']) && !isset($r['id_usr_rec'])) {
      $visible_recipes[] = $r;
      continue;
    }

    $status   = (string) ($r['status_rec'] ?? 'approved');
    $owner_id = (int) ($r['id_usr_rec'] ?? 0);
    $is_owner = ($viewer_id > 0 && $owner_id === $viewer_id);

    if ($status === 'approved') {
      $visible_recipes[] = $r;
      continue;
    }

    if ($is_admin || $is_owner) {
      $visible_recipes[] = $r;
    }
  }
}
?>

<?php if (!empty($visible_recipes)): ?>

  <?php foreach ($visible_recipes as $recipe): ?>
    <?php
      $avg_rating   = isset($recipe['avg_rating']) ? (float) $recipe['avg_rating'] : 0;
      $rating_count = isset($recipe['rating_count']) ? (int) $recipe['rating_count'] : 0;

      $show_rating_badge = ($rating_count > 0);
      $is_perfect_card   = ($avg_rating >= 4.95 && $rating_count > 1);

      $category_options = [];

      foreach (['type_cats_csv', 'style_cats_csv', 'diet_cats_csv'] as $field) {
        $csv = trim((string) ($recipe[$field] ?? ''));
        if ($csv === '') {
          continue;
        }

        $parts = array_filter(array_map('trim', explode(',', $csv)));
        foreach ($parts as $part) {
          if ($part !== '') {
            $category_options[] = $part;
          }
        }
      }

      $splash_badge = '';
      if (!empty($category_options)) {
        $splash_badge = $category_options[array_rand($category_options)];
      }
    ?>

    <div
      class="tile recipe-tile<?= $is_perfect_card ? ' recipe-tile--perfect' : '' ?>"
      data-title="<?= h($recipe['title_rec'] ?? '') ?>"
      data-created="<?= h($recipe['created_at_rec'] ?? '') ?>"
      data-rating="<?= h($recipe['avg_rating'] ?? 0) ?>"
      data-type="<?= h($recipe['type_cats_csv'] ?? '') ?>"
      data-style="<?= h($recipe['style_cats_csv'] ?? '') ?>"
      data-diet="<?= h($recipe['diet_cats_csv'] ?? '') ?>"
    >
      <a class="recipe-tile-link" href="recipe.php?id=<?= (int) $recipe['id_rec'] ?>">

        <div class="recipe-tile-image">
          <img
            src="<?= h($recipe['primary_image'] ?? 'images/recipe-book.png') ?>"
            alt="<?= h($recipe['title_rec']) ?>"
            loading="lazy"
          >

          <?php if ($show_rating_badge): ?>
            <span class="recipe-tile-badge recipe-tile-badge-rating">
              ⭐ <?= h(number_format($avg_rating, 1)) ?>
            </span>
          <?php endif; ?>

          <?php if ($splash_badge !== ''): ?>
            <span class="recipe-tile-badge recipe-tile-badge-splash">
              <?= h($splash_badge) ?>
            </span>
          <?php endif; ?>
        </div>

        <div class="recipe-tile-title">
          <?= h($recipe['title_rec']) ?>
        </div>

      </a>

      <?php if (isset($recipe['status_rec']) && (string) $recipe['status_rec'] !== 'approved'): ?>
        <div class="recipe-status">
          <small>Status: <?= h($recipe['status_rec']) ?></small>
        </div>
      <?php endif; ?>
    </div>
  <?php endforeach; ?>

<?php else: ?>

  <div class="tile">Recipe Placeholder</div>
  <div class="tile">Recipe Placeholder</div>
  <div class="tile">Recipe Placeholder</div>
  <div class="tile">Recipe Placeholder</div>

<?php endif; ?>

</div>