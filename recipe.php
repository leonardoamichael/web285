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
  "SELECT r.id_rec,
          r.title_rec,
          r.description_rec,
          r.created_at_rec,
          r.status_rec,
          r.id_usr_rec,
          r.prep_minutes_rec,
          r.cook_minutes_rec,
          r.youtube_url_rec,
          u.username_usr
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

/* 5) Fetch categories (type/style/diet) */
$stmt = $db->prepare(
  "SELECT c.group_cat, c.name_cat
   FROM recipe_category_reccat rc
   JOIN category_cat c ON c.id_cat = rc.id_cat_reccat
   WHERE rc.id_rec_reccat = ?
   ORDER BY
     CASE c.group_cat
       WHEN 'type' THEN 1
       WHEN 'style' THEN 2
       WHEN 'diet' THEN 3
       ELSE 9
     END,
     c.name_cat ASC"
);

$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();

$cats_by_group = [
  'type'  => [],
  'style' => [],
  'diet'  => [],
];

while ($row = $result->fetch_assoc()) {
  $grp = (string) ($row['group_cat'] ?? '');
  $name = trim((string) ($row['name_cat'] ?? ''));

  if ($name !== '' && isset($cats_by_group[$grp])) {
    $cats_by_group[$grp][] = $name;
  }
}

$stmt->close();

/* 6) Fetch rating data */
$rating_summary = fetch_recipe_rating_summary($db, $id);
$avg_rating     = $rating_summary['avg_rating'];
$rating_count   = $rating_summary['rating_count'];

$user_rating = fetch_user_recipe_rating($db, $id, $viewer_id);

/* Fallback image */
$default_image = 'images/recipe-book.png';

function youtube_embed_url(?string $url): string
{
  $url = trim((string)$url);
  if ($url === '') {
    return '';
  }

  $parts = parse_url($url);
  if (!$parts || empty($parts['host'])) {
    return '';
  }

  $host = strtolower($parts['host']);
  $path = (string)($parts['path'] ?? '');
  $query = (string)($parts['query'] ?? '');

  $id = '';

  // youtu.be/<id>
  if (str_contains($host, 'youtu.be')) {
    $id = ltrim($path, '/');
  }

  // youtube.com/watch?v=<id>
  if ($id === '' && (str_contains($host, 'youtube.com') || str_contains($host, 'youtube-nocookie.com'))) {
    parse_str($query, $qs);
    if (!empty($qs['v'])) {
      $id = (string)$qs['v'];
    }

    // /embed/<id> or /shorts/<id>
    if ($id === '') {
      if (preg_match('~/(embed|shorts)/([^/?#]+)~', $path, $m)) {
        $id = (string)$m[2];
      }
    }
  }

  // sanitize id to safe chars
  $id = preg_replace('~[^A-Za-z0-9_-]~', '', (string)$id);

  return ($id !== '') ? "https://www.youtube-nocookie.com/embed/{$id}" : '';
}
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

      function format_minutes_to_hr_min(int $total): string
      {
        $h = intdiv($total, 60);
        $m = $total % 60;

        if ($h > 0 && $m > 0) {
          return $h . ' hr ' . $m . ' min';
        }
        if ($h > 0) {
          return $h . ' hr';
        }
        return $m . ' min';
      }
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

    <?php
      // Presentation limits (tweak anytime)
      $max_per_group = 4;  // e.g., show up to 4 pills per group
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