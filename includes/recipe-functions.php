<?php
// includes/recipe-functions.php

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

  $stmt = $db->prepare(
    "SELECT id_rec, title_rec
     FROM recipe_rec
     WHERE status_rec = 'approved'
     ORDER BY RAND()
     LIMIT ?"
  );

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
 * Fetch the most recently created recipes.
 *
 * Retrieves recipes ordered by creation timestamp (newest first).
 * The result size is safety-clamped to avoid excessive queries.
 *
 * @param mysqli $db Active database connection
 * @param int $limit Maximum number of recipes to return (clamped 1–30)
 * @return array List of recipes (associative arrays)
 */
function fetch_latest_recipes(mysqli $db, int $limit = 12): array
{
  $limit = max(1, min($limit, 30));

  $stmt = $db->prepare(
    "SELECT id_rec, title_rec
     FROM recipe_rec
     ORDER BY created_at_rec DESC
     LIMIT ?"
  );

  if (!$stmt) {
    die("Prepare failed: " . $db->error);
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