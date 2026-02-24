<?php
require_once __DIR__ . '/initialize.php';

if (!isset($_SESSION['user_id'])) {
  redirect_error('login_required', '../index.php');
}

$role_id  = (int) ($_SESSION['role_id'] ?? 2);
$is_admin = ($role_id === 1);

if (!$is_admin) {
  redirect_error('not_authorized', '../profile.php#admin-tools');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: ../profile.php#admin-tools');
  exit;
}

$action    = (string) ($_POST['action'] ?? '');
$recipe_id = (int) ($_POST['recipe_id'] ?? 0);

function admin_redirect_msg(string $msg): void
{
  header('Location: ../profile.php?msg=' . urlencode($msg) . '#admin-tools');
  exit;
}

function admin_redirect_err(string $msg): void
{
  header('Location: ../profile.php?err=' . urlencode($msg) . '#admin-tools');
  exit;
}

function get_recipe_image_paths(mysqli $db, int $recipe_id): array
{
  $stmt = $db->prepare(
    "SELECT path_recimg
     FROM recipe_image_recimg
     WHERE id_rec_recimg = ?"
  );
  $stmt->bind_param('i', $recipe_id);
  $stmt->execute();
  $res = $stmt->get_result();

  $paths = [];
  while ($row = $res->fetch_assoc()) {
    $val = trim((string) ($row['path_recimg'] ?? ''));
    if ($val !== '') {
      $paths[] = $val;
    }
  }

  $stmt->close();
  return array_values(array_unique($paths));
}

function unlink_images_safely(array $paths): void
{
  $root = realpath(__DIR__ . '/..');
  if ($root === false) return;

  $allowed = realpath($root . '/uploads/recipes');
  if ($allowed === false) return;

  foreach ($paths as $p) {
    $p = str_replace(["\0", '..\\', '../'], '', $p);

    $full = realpath($root . '/' . ltrim($p, '/'));
    if ($full === false) continue;

    if (strpos($full, $allowed) !== 0) continue;

    if (is_file($full)) {
      @unlink($full);
    }
  }
}

/* Approve / Reject */
if ($action === 'approve' || $action === 'reject') {

  if ($recipe_id <= 0) {
    admin_redirect_err('Invalid recipe.');
  }

  $new_status = ($action === 'approve') ? 'approved' : 'rejected';
  $admin_id   = (int) $_SESSION['user_id'];

  $stmt = $db->prepare(
    "UPDATE recipe_rec
     SET status_rec = ?, reviewed_by_usr = ?, reviewed_at_rec = NOW()
     WHERE id_rec = ? AND status_rec = 'pending'"
  );

  $stmt->bind_param('sii', $new_status, $admin_id, $recipe_id);
  $stmt->execute();

  $msg = ($stmt->affected_rows > 0)
    ? (($action === 'approve') ? 'Recipe approved.' : 'Recipe rejected.')
    : 'No change made.';

  $stmt->close();
  admin_redirect_msg($msg);
}

/* Delete recipe: request confirm */
if ($action === 'delete_recipe_request') {

  if ($recipe_id <= 0) {
    admin_redirect_err('Invalid recipe.');
  }

  $_SESSION['admin_confirm'] = [
    'type'      => 'delete_recipe',
    'recipe_id' => $recipe_id,
    'token'     => bin2hex(random_bytes(16)),
    'expires'   => time() + 600
  ];

  header('Location: ../profile.php?confirm=delete_recipe#admin-tools');
  exit;
}

/* Delete recipe: confirmed */
if ($action === 'delete_recipe_confirm') {

  $token   = (string) ($_POST['confirm_token'] ?? '');
  $confirm = $_SESSION['admin_confirm'] ?? null;

  if (!is_array($confirm)
    || ($confirm['type'] ?? '') !== 'delete_recipe'
    || (int) ($confirm['recipe_id'] ?? 0) !== $recipe_id
    || (string) ($confirm['token'] ?? '') !== $token
    || (int) ($confirm['expires'] ?? 0) < time()
  ) {
    unset($_SESSION['admin_confirm']);
    admin_redirect_err('Confirmation expired.');
  }

  unset($_SESSION['admin_confirm']);

  $paths = get_recipe_image_paths($db, $recipe_id);

  try {
    $db->begin_transaction();

    $stmt = $db->prepare("DELETE FROM recipe_step_stp WHERE id_rec_stp = ?");
    $stmt->bind_param('i', $recipe_id);
    $stmt->execute();
    $stmt->close();

    $stmt = $db->prepare("DELETE FROM recipe_ingredient_recing WHERE id_rec_recing = ?");
    $stmt->bind_param('i', $recipe_id);
    $stmt->execute();
    $stmt->close();

    $stmt = $db->prepare("DELETE FROM recipe_image_recimg WHERE id_rec_recimg = ?");
    $stmt->bind_param('i', $recipe_id);
    $stmt->execute();
    $stmt->close();

    $stmt = $db->prepare("DELETE FROM recipe_rec WHERE id_rec = ?");
    $stmt->bind_param('i', $recipe_id);
    $stmt->execute();

    if ($stmt->affected_rows < 1) {
      $stmt->close();
      $db->rollback();
      admin_redirect_err('Recipe not found.');
    }

    $stmt->close();
    $db->commit();

  } catch (Throwable $e) {
    @$db->rollback();
    admin_redirect_err('Delete failed.');
  }

  unlink_images_safely($paths);
  admin_redirect_msg('Recipe deleted.');
}

admin_redirect_err('Invalid action.');