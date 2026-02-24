<?php
// includes/recipe-functions.php

/**
 * Fetch a list of approved recipes, optionally filtered by a title search.
 *
 * Behavior matches the recipes index page:
 * - Only includes recipes with status 'approved'
 * - If $search is provided, filters by title using LIKE
 * - Orders newest first (created_at_rec DESC)
 * - Limits results (clamped for safety)
 *
 * Returns rows containing:
 * - id_rec (int)
 * - title_rec (string)
 *
 * @param mysqli $db Active database connection
 * @param string|null $search Optional search string (title match). Pass null/empty for no search.
 * @param int $limit Max number of rows to return (clamped 1–100)
 * @return array<int, array<string, mixed>> List of recipe rows
 */
function fetch_approved_recipes(mysqli $db, ?string $search = null, int $limit = 30): array
{
  $limit = max(1, min($limit, 100));
  $search = trim((string)($search ?? ''));

  // Search case
  if ($search !== '') {
    $like = '%' . $search . '%';

    $sql = "
      SELECT id_rec, title_rec
      FROM recipe_rec
      WHERE status_rec = 'approved'
        AND title_rec LIKE ?
      ORDER BY created_at_rec DESC
      LIMIT ?
    ";

    $stmt = $db->prepare($sql);
    if (!$stmt) {
      return [];
    }

    $stmt->bind_param('si', $like, $limit);
    $stmt->execute();

    $result = $stmt->get_result();
    $recipes = [];
    while ($row = $result->fetch_assoc()) {
      $recipes[] = $row;
    }

    $stmt->close();
    return $recipes;
  }

  // No-search case
  $sql = "
    SELECT id_rec, title_rec
    FROM recipe_rec
    WHERE status_rec = 'approved'
    ORDER BY created_at_rec DESC
    LIMIT ?
  ";

  $stmt = $db->prepare($sql);
  if (!$stmt) {
    return [];
  }

  $stmt->bind_param('i', $limit);
  $stmt->execute();

  $result = $stmt->get_result();
  $recipes = [];
  while ($row = $result->fetch_assoc()) {
    $recipes[] = $row;
  }

  $stmt->close();
  return $recipes;
}

/**
 * Fetch a list of approved recipes with a primary image (if available),
 * optionally filtered by a title search.
 *
 * Returns rows containing:
 * - id_rec (int)
 * - title_rec (string)
 * - created_at_rec (string/datetime)
 * - primary_image (string|null)
 * - type_cat (string|null)
 * - style_cat (string|null)
 * - diet_cats_csv (string) comma-separated diet categories (may be empty)
 * - avg_rating (float) average rating (0 if none)
 *
 * @param mysqli $db Active database connection
 * @param string|null $search Optional search string (title match). Pass null/empty for no search.
 * @param int $limit Max number of rows to return (clamped 1–100)
 * @return array<int, array<string, mixed>> List of recipe rows
 */
function fetch_approved_recipes_with_primary_image(mysqli $db, ?string $search = null, int $limit = 30): array
{
  $limit = max(1, min($limit, 100));
  $search = trim((string)($search ?? ''));

  $base_sql = "
    SELECT
      r.id_rec,
      r.title_rec,
      r.created_at_rec,

      (
        SELECT ri2.path_recimg
        FROM recipe_image_recimg ri2
        WHERE ri2.id_rec_recimg = r.id_rec
        ORDER BY ri2.sort_order_recimg ASC, ri2.id_recimg ASC
        LIMIT 1
      ) AS primary_image,

      /* Single-select category groups */
      MAX(CASE WHEN c.group_cat = 'type'  THEN c.name_cat END)  AS type_cat,
      MAX(CASE WHEN c.group_cat = 'style' THEN c.name_cat END)  AS style_cat,

      /* Multi-select diet group */
      COALESCE(
        GROUP_CONCAT(
          DISTINCT CASE WHEN c.group_cat = 'diet' THEN c.name_cat END
          ORDER BY c.name_cat
          SEPARATOR ','
        ),
        ''
      ) AS diet_cats_csv,

      /* Rating (placeholder-friendly) */
      COALESCE(AVG(rtg.rating_rtg), 0) AS avg_rating

    FROM recipe_rec r
    LEFT JOIN recipe_category_reccat rcc
      ON rcc.id_rec_reccat = r.id_rec
    LEFT JOIN category_cat c
      ON c.id_cat = rcc.id_cat_reccat
    LEFT JOIN recipe_rating_rtg rtg
      ON rtg.id_rec_rtg = r.id_rec

    WHERE r.status_rec = 'approved'
  ";

  // optional title search
  if ($search !== '') {
    $like = '%' . $search . '%';

    $sql = $base_sql . "
      AND r.title_rec LIKE ?
      GROUP BY r.id_rec
      ORDER BY r.created_at_rec DESC
      LIMIT ?
    ";

    $stmt = $db->prepare($sql);
    if (!$stmt) return [];

    $stmt->bind_param('si', $like, $limit);
    $stmt->execute();

    $result = $stmt->get_result();
    $recipes = [];
    while ($row = $result->fetch_assoc()) {
      $recipes[] = $row;
    }

    $stmt->close();
    return $recipes;
  }

  $sql = $base_sql . "
    GROUP BY r.id_rec
    ORDER BY r.created_at_rec DESC
    LIMIT ?
  ";

  $stmt = $db->prepare($sql);
  if (!$stmt) return [];

  $stmt->bind_param('i', $limit);
  $stmt->execute();

  $result = $stmt->get_result();
  $recipes = [];
  while ($row = $result->fetch_assoc()) {
    $recipes[] = $row;
  }

  $stmt->close();
  return $recipes;
}

/**
 * Fetch a random set of approved recipes.
 *
 * Retrieves recipes with an approved status using a randomized
 * ordering. A safety clamp prevents excessive limits.
 *
 * @param mysqli $db Active database connection
 * @param int $limit Maximum number of recipes to return (clamped 1–50)
 * @return array List of recipes (associative arrays)
 */
function fetch_random_recipes(mysqli $db, int $limit = 12): array
{
  $limit = max(1, min($limit, 50)); // safety clamp

  $sql = "
    SELECT
      r.id_rec,
      r.title_rec,
      (
        SELECT ri2.path_recimg
        FROM recipe_image_recimg ri2
        WHERE ri2.id_rec_recimg = r.id_rec
        ORDER BY ri2.sort_order_recimg ASC, ri2.id_recimg ASC
        LIMIT 1
      ) AS primary_image
    FROM recipe_rec r
    WHERE r.status_rec = 'approved'
    ORDER BY RAND()
    LIMIT ?
  ";

  $stmt = $db->prepare($sql);

  if (!$stmt) {
    return [];
  }

  $stmt->bind_param('i', $limit);
  $stmt->execute();

  $result = $stmt->get_result();
  $recipes = [];

  while ($row = $result->fetch_assoc()) {
    $recipes[] = $row;
  }

  $stmt->close();

  return $recipes;
}

/**
 * Fetch the most recently created recipes, including a primary image if available.
 *
 * Returns:
 * - id_rec
 * - title_rec
 * - primary_image (string|null)
 *
 * @param mysqli $db Active database connection
 * @param int $limit Maximum number of recipes to return (clamped 1–30)
 * @return array List of recipes (associative arrays)
 */
function fetch_latest_recipes(mysqli $db, int $limit = 12): array
{
  $limit = max(1, min($limit, 30));

  $sql = "
    SELECT
      r.id_rec,
      r.title_rec,
      r.status_rec,
      r.id_usr_rec,
      (
        SELECT ri2.path_recimg
        FROM recipe_image_recimg ri2
        WHERE ri2.id_rec_recimg = r.id_rec
        ORDER BY ri2.sort_order_recimg ASC, ri2.id_recimg ASC
        LIMIT 1
      ) AS primary_image
    FROM recipe_rec r
    ORDER BY r.created_at_rec DESC
    LIMIT ?
  ";

  $stmt = $db->prepare($sql);
  if (!$stmt) {
    internal_error("Prepare failed: " . $db->error);
  }

  $stmt->bind_param('i', $limit);
  $stmt->execute();

  $result = $stmt->get_result();
  $recipes = [];

  while ($row = $result->fetch_assoc()) {
    $recipes[] = $row;
  }

  $stmt->close();

  return $recipes;
}