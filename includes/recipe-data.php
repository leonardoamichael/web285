<?php

function fetch_recipe_page_data(mysqli $db, int $id, int $viewer_id, bool $is_admin): array
{
  if ($id <= 0) {
    redirect_error('not_found', 'recipes.php');
  }

  /* Fetch recipe + author + status */
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

  /* Fetch ingredients */
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

  /* Fetch steps */
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

  /* Fetch images */
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

  /* Fetch categories */
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

  /* Fetch rating data */
  $rating_summary = fetch_recipe_rating_summary($db, $id);
  $avg_rating     = $rating_summary['avg_rating'];
  $rating_count   = $rating_summary['rating_count'];

  $user_rating = fetch_user_recipe_rating($db, $id, $viewer_id);

  return [
    'avg_rating'     => $avg_rating,
    'cats_by_group'  => $cats_by_group,
    'default_image'  => 'images/recipe-book.jpg',
    'images'         => $images,
    'ingredients'    => $ingredients,
    'is_approved'    => $is_approved,
    'is_owner'       => $is_owner,
    'rating_count'   => $rating_count,
    'recipe'         => $recipe,
    'steps'          => $steps,
    'user_rating'    => $user_rating,
  ];
}

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

  if (str_contains($host, 'youtu.be')) {
    $id = ltrim($path, '/');
  }

  if ($id === '' && (str_contains($host, 'youtube.com') || str_contains($host, 'youtube-nocookie.com'))) {
    parse_str($query, $qs);

    if (!empty($qs['v'])) {
      $id = (string)$qs['v'];
    }

    if ($id === '' && preg_match('~/(embed|shorts)/([^/?#]+)~', $path, $m)) {
      $id = (string)$m[2];
    }
  }

  $id = preg_replace('~[^A-Za-z0-9_-]~', '', (string)$id);

  return ($id !== '') ? "https://www.youtube-nocookie.com/embed/{$id}" : '';
}