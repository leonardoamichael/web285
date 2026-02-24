<div class="recipe-grid">

<?php
/**
 * Recipe visibility filtering
 *
 * This grid assumes the calling page already filtered recipes correctly.
 * As a safety net, if status/owner fields are present, non-approved
 * recipes are hidden from public viewers.
 */

$viewer_id   = (int) ($_SESSION['user_id'] ?? 0);
$viewer_role = (string) ($_SESSION['role'] ?? '');
$is_admin    = ($viewer_role === 'admin');

$visible_recipes = [];

if (!empty($recipes) && is_array($recipes)) {

  foreach ($recipes as $r) {

    /*
     * If caller did not provide moderation/ownership fields,
     * treat dataset as already safe.
     */
    if (!isset($r['status_rec']) && !isset($r['id_usr_rec'])) {
      $visible_recipes[] = $r;
      continue;
    }

    $status    = (string) ($r['status_rec'] ?? 'approved');
    $owner_id  = (int) ($r['id_usr_rec'] ?? 0);
    $is_owner  = ($viewer_id > 0 && $owner_id === $viewer_id);

    /* Public visibility */
    if ($status === 'approved') {
      $visible_recipes[] = $r;
      continue;
    }

    /* Owner / admin visibility */
    if ($is_admin || $is_owner) {
      $visible_recipes[] = $r;
    }
  }
}
?>

<?php if (!empty($visible_recipes)): ?>

  <?php foreach ($visible_recipes as $recipe): ?>
    <div
      class="tile recipe-tile"
      data-title="<?= h($recipe['title_rec'] ?? '') ?>"
      data-created="<?= h($recipe['created_at_rec'] ?? '') ?>"
      data-rating="<?= h($recipe['avg_rating'] ?? 0) ?>"
      data-type="<?= h($recipe['type_cat'] ?? '') ?>"
      data-style="<?= h($recipe['style_cat'] ?? '') ?>"
      data-diet="<?= h($recipe['diet_cats_csv'] ?? '') ?>"
    >


      <a class="recipe-tile-link" href="recipe.php?id=<?= (int) $recipe['id_rec'] ?>">

        <div class="recipe-tile-image">
          <img
            src="<?= h($recipe['primary_image'] ?? 'images/recipe-book.png') ?>"
            alt="<?= h($recipe['title_rec']) ?>"
            loading="lazy"
          >
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

  <!-- Empty-state placeholders -->
  <div class="tile">Recipe Placeholder</div>
  <div class="tile">Recipe Placeholder</div>
  <div class="tile">Recipe Placeholder</div>
  <div class="tile">Recipe Placeholder</div>

<?php endif; ?>

</div>