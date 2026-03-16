<?php
require_once 'includes/initialize.php';

/* Must be logged in */
if (!isset($_SESSION['user_id'])) {
  redirect_error('login_required', 'recipes.php');
}

/* Only allow POST */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  redirect_error('invalid_request', 'recipes.php');
}

$viewer_id = (int) $_SESSION['user_id'];
$recipe_id = (int) ($_POST['recipe_id'] ?? 0);
$rating    = (int) ($_POST['rating'] ?? 0);

if ($recipe_id <= 0) {
  redirect_error('not_found', 'recipes.php');
}

/* Validate rating range */
if ($rating < 1 || $rating > 5) {
  header("Location: recipe.php?id=" . $recipe_id);
  exit;
}

/* Insert or update rating */
$stmt = $db->prepare(
  "INSERT INTO recipe_rating_rtg
   (id_rec_rtg, id_usr_rtg, rating_rtg)
   VALUES (?, ?, ?)
   ON DUPLICATE KEY UPDATE
     rating_rtg = VALUES(rating_rtg)"
);

if (!$stmt) {
  internal_error("Prepare failed: " . $db->error);
}

$stmt->bind_param('iii', $recipe_id, $viewer_id, $rating);
$stmt->execute();
$stmt->close();

/* Redirect back to recipe */
header("Location: recipe.php?id=" . $recipe_id);
exit;