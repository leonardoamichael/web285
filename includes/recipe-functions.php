<?php
// includes/recipe-functions.php

/**
 * Fetch approved recipes with a primary image, category metadata, and rating summary.
 *
 * Used by recipe listing and homepage sections to populate recipe cards with
 * full display data including category badges and rating information.
 *
 * Behavior:
 * - Only includes recipes with status 'approved'
 * - Optionally filters by recipe title when $search is provided
 * - Can return results ordered by newest or randomly
 * - Limits results (clamped for safety)
 *
 * Returns rows containing:
 * - id_rec (int)
 * - title_rec (string)
 * - created_at_rec (string/datetime)
 * - primary_image (string|null)
 * - type_cats_csv (string) comma-separated type categories
 * - style_cats_csv (string) comma-separated style categories
 * - diet_cats_csv (string) comma-separated diet categories
 * - avg_rating (float) average recipe rating
 * - rating_count (int) total number of ratings
 *
 * @param mysqli $db Active database connection
 * @param string|null $search Optional search string (title match). Pass null/empty for no search.
 * @param int $limit Max number of rows to return (clamped 1–100)
 * @param bool $random If true, results are returned in random order
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
function fetch_approved_recipes_with_primary_image(mysqli $db, ?string $search = null, int $limit = 30, bool $random = false): array
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

      COALESCE(
        GROUP_CONCAT(
          DISTINCT CASE WHEN c.group_cat = 'type' THEN c.name_cat END
          ORDER BY c.name_cat
          SEPARATOR ','
        ),
        ''
      ) AS type_cats_csv,

      COALESCE(
        GROUP_CONCAT(
          DISTINCT CASE WHEN c.group_cat = 'style' THEN c.name_cat END
          ORDER BY c.name_cat
          SEPARATOR ','
        ),
        ''
      ) AS style_cats_csv,

      COALESCE(
        GROUP_CONCAT(
          DISTINCT CASE WHEN c.group_cat = 'diet' THEN c.name_cat END
          ORDER BY c.name_cat
          SEPARATOR ','
        ),
        ''
      ) AS diet_cats_csv,

      /* Rating summary */
      COALESCE(AVG(rtg.rating_rtg), 0) AS avg_rating,
      COUNT(rtg.rating_rtg) AS rating_count

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
      ORDER BY " . ($random ? "RAND()" : "r.created_at_rec DESC") . "
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
    ORDER BY " . ($random ? "RAND()" : "r.created_at_rec DESC") . "
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

/**
 * Fetch average rating summary for a single recipe.
 *
 * Returns:
 * - avg_rating (float|null) Average rating, or null if no ratings exist
 * - rating_count (int) Total number of ratings
 *
 * @param mysqli $db Active database connection
 * @param int $recipe_id Recipe ID
 * @return array{avg_rating: float|null, rating_count: int}
 */
function fetch_recipe_rating_summary(mysqli $db, int $recipe_id): array
{
  $sql = "
    SELECT
      AVG(rating_rtg) AS avg_rating,
      COUNT(*) AS rating_count
    FROM recipe_rating_rtg
    WHERE id_rec_rtg = ?
  ";

  $stmt = $db->prepare($sql);

  if (!$stmt) {
    return [
      'avg_rating'   => null,
      'rating_count' => 0,
    ];
  }

  $stmt->bind_param('i', $recipe_id);
  $stmt->execute();

  $row = $stmt->get_result()->fetch_assoc();
  $stmt->close();

  return [
    'avg_rating'   => isset($row['avg_rating']) ? (float) $row['avg_rating'] : null,
    'rating_count' => (int) ($row['rating_count'] ?? 0),
  ];
}

/**
 * Fetch the current user's rating for a recipe.
 *
 * Returns null if the user has not rated the recipe yet.
 *
 * @param mysqli $db Active database connection
 * @param int $recipe_id Recipe ID
 * @param int $user_id User ID
 * @return int|null Rating value (1-5) or null if not found
 */
function fetch_user_recipe_rating(mysqli $db, int $recipe_id, int $user_id): ?int
{
  if ($recipe_id <= 0 || $user_id <= 0) {
    return null;
  }

  $sql = "
    SELECT rating_rtg
    FROM recipe_rating_rtg
    WHERE id_rec_rtg = ?
      AND id_usr_rtg = ?
    LIMIT 1
  ";

  $stmt = $db->prepare($sql);

  if (!$stmt) {
    return null;
  }

  $stmt->bind_param('ii', $recipe_id, $user_id);
  $stmt->execute();

  $row = $stmt->get_result()->fetch_assoc();
  $stmt->close();

  if (!$row) {
    return null;
  }

  return (int) $row['rating_rtg'];
}

/**
 * Fetch top-rated "perfect" recipes for featured display sections.
 *
 * Used by the homepage to highlight recipes that have achieved a near-perfect
 * community rating. A recipe qualifies as "perfect" when:
 * - Average rating >= 4.95
 * - Rating count > 1
 *
 * Returned rows include the same metadata required by the recipe card grid,
 * including category information and rating summary.
 *
 * Returns rows containing:
 * - id_rec (int)
 * - title_rec (string)
 * - created_at_rec (string/datetime)
 * - primary_image (string|null)
 * - type_cats_csv (string)
 * - style_cats_csv (string)
 * - diet_cats_csv (string)
 * - avg_rating (float)
 * - rating_count (int)
 *
 * Results are ordered by highest rating and rating count.
 *
 * @param mysqli $db Active database connection
 * @param int $limit Maximum number of recipes to return (clamped 1–20)
 * @return array<int, array<string, mixed>> List of top-rated recipe rows
 */

function fetch_perfect_recipes_with_primary_image(mysqli $db, int $limit = 4): array
{
  $limit = max(1, min($limit, 20));

  $sql = "
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

      COALESCE(
        GROUP_CONCAT(
          DISTINCT CASE WHEN c.group_cat = 'type' THEN c.name_cat END
          ORDER BY c.name_cat
          SEPARATOR ','
        ),
        ''
      ) AS type_cats_csv,

      COALESCE(
        GROUP_CONCAT(
          DISTINCT CASE WHEN c.group_cat = 'style' THEN c.name_cat END
          ORDER BY c.name_cat
          SEPARATOR ','
        ),
        ''
      ) AS style_cats_csv,

      COALESCE(
        GROUP_CONCAT(
          DISTINCT CASE WHEN c.group_cat = 'diet' THEN c.name_cat END
          ORDER BY c.name_cat
          SEPARATOR ','
        ),
        ''
      ) AS diet_cats_csv,

      COALESCE(AVG(rtg.rating_rtg),0) AS avg_rating,
      COUNT(rtg.rating_rtg) AS rating_count

    FROM recipe_rec r

    LEFT JOIN recipe_category_reccat rcc
      ON rcc.id_rec_reccat = r.id_rec

    LEFT JOIN category_cat c
      ON c.id_cat = rcc.id_cat_reccat

    LEFT JOIN recipe_rating_rtg rtg
      ON rtg.id_rec_rtg = r.id_rec

    WHERE r.status_rec = 'approved'

    GROUP BY r.id_rec

    HAVING
      AVG(rtg.rating_rtg) >= 4.95
      AND COUNT(rtg.rating_rtg) > 1

    ORDER BY avg_rating DESC, rating_count DESC

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