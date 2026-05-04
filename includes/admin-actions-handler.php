<?php
require_once __DIR__ . '/initialize.php';

if (!isset($_SESSION['user_id'])) {
  redirect_error('login_required', '../index.php');
}

$is_admin = is_admin_access();

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

    $stmt = $db->prepare("DELETE FROM recipe_category_reccat WHERE id_rec_reccat = ?");
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

/**
 * Create a new category.
 *
 * Validates group and name, prevents duplicates within group.
 */
if ($action === 'category_create') {

  $group = trim((string) ($_POST['group_cat'] ?? ''));
  $name  = trim((string) ($_POST['name_cat'] ?? ''));

  $allowed_groups = ['type', 'style', 'diet'];

  if (!in_array($group, $allowed_groups, true)) {
    admin_redirect_err('Invalid category group.');
  }

  if ($name === '' || mb_strlen($name) > 60) {
    admin_redirect_err('Category name required (max 60).');
  }

  $name_norm = mb_strtolower($name);

  $stmt = $db->prepare(
    "SELECT id_cat
     FROM category_cat
     WHERE group_cat = ? AND LOWER(name_cat) = ?
     LIMIT 1"
  );

  $stmt->bind_param('ss', $group, $name_norm);
  $stmt->execute();

  $res = $stmt->get_result();
  $exists = (bool) $res->fetch_assoc();

  $res->free();
  $stmt->close();

  if ($exists) {
    admin_redirect_err('Category already exists.');
  }

  $stmt = $db->prepare(
    "INSERT INTO category_cat (group_cat, name_cat)
     VALUES (?, ?)"
  );

  $stmt->bind_param('ss', $group, $name);
  $stmt->execute();
  $stmt->close();

  admin_redirect_msg('Category added.');
}

/**
 * Rename an existing category.
 *
 * Validates category ID and name, prevents duplicates within group.
 */
if ($action === 'category_rename') {

  $id   = (int) ($_POST['id_cat'] ?? 0);
  $name = trim((string) ($_POST['name_cat'] ?? ''));

  if ($id <= 0) {
    admin_redirect_err('Invalid category.');
  }

  if ($name === '' || mb_strlen($name) > 60) {
    admin_redirect_err('Category name required (max 60).');
  }

  $stmt = $db->prepare(
    "SELECT group_cat
     FROM category_cat
     WHERE id_cat = ?
     LIMIT 1"
  );

  $stmt->bind_param('i', $id);
  $stmt->execute();

  $res = $stmt->get_result();
  $row = $res->fetch_assoc();

  $res->free();
  $stmt->close();

  if (!$row) {
    admin_redirect_err('Category not found.');
  }

  $group = (string) $row['group_cat'];
  $name_norm = mb_strtolower($name);

  $stmt = $db->prepare(
    "SELECT id_cat
     FROM category_cat
     WHERE group_cat = ?
       AND LOWER(name_cat) = ?
       AND id_cat <> ?
     LIMIT 1"
  );

  $stmt->bind_param('ssi', $group, $name_norm, $id);
  $stmt->execute();

  $res = $stmt->get_result();
  $dupe = (bool) $res->fetch_assoc();

  $res->free();
  $stmt->close();

  if ($dupe) {
    admin_redirect_err('Duplicate category name.');
  }

  $stmt = $db->prepare(
    "UPDATE category_cat
     SET name_cat = ?
     WHERE id_cat = ?"
  );

  $stmt->bind_param('si', $name, $id);
  $stmt->execute();
  $stmt->close();

  admin_redirect_msg('Category updated.');
}

/**
 * Delete a category.
 *
 * Refuses deletion if category is in use by recipes.
 */
if ($action === 'category_delete') {

  $id = (int) ($_POST['id_cat'] ?? 0);

  if ($id <= 0) {
    admin_redirect_err('Invalid category.');
  }

  $stmt = $db->prepare(
    "SELECT 1
     FROM recipe_category_reccat
     WHERE id_cat_reccat = ?
     LIMIT 1"
  );

  $stmt->bind_param('i', $id);
  $stmt->execute();

  $res = $stmt->get_result();
  $in_use = (bool) $res->fetch_assoc();

  $res->free();
  $stmt->close();

  if ($in_use) {
    admin_redirect_err('Category is in use.');
  }

  $stmt = $db->prepare("DELETE FROM category_cat WHERE id_cat = ?");
  $stmt->bind_param('i', $id);
  $stmt->execute();
  $stmt->close();

  admin_redirect_msg('Category deleted.');
}

/* Promote member to admin */
if ($action === 'promote_user') {

  if (!is_super_admin()) {
    admin_redirect_err('Only super admins can manage user roles.');
  }

  $user_id = (int) ($_POST['user_id'] ?? 0);
  $current_user_id = (int) ($_SESSION['user_id'] ?? 0);

  if ($user_id <= 0) {
    admin_redirect_err('Invalid user.');
  }

  if ($user_id === $current_user_id) {
    admin_redirect_err('You cannot change your own role.');
  }

  $stmt = $db->prepare(
    "SELECT id_rol_usr
     FROM user_usr
     WHERE id_usr = ?
     LIMIT 1"
  );

  $stmt->bind_param('i', $user_id);
  $stmt->execute();
  $result = $stmt->get_result();
  $user = $result->fetch_assoc();
  $stmt->close();

  if (!$user) {
    admin_redirect_err('User not found.');
  }

  $current_role = (int) $user['id_rol_usr'];

  if ($current_role === ROLE_SUPER_ADMIN) {
    admin_redirect_err('Super admin roles must be managed in the database.');
  }

  if ($current_role !== ROLE_MEMBER) {
    admin_redirect_err('Only members can be promoted to admin.');
  }

  $new_role = ROLE_ADMIN;

  $stmt = $db->prepare(
    "UPDATE user_usr
     SET id_rol_usr = ?
     WHERE id_usr = ?"
  );

  $stmt->bind_param('ii', $new_role, $user_id);
  $stmt->execute();
  $stmt->close();

  admin_redirect_msg('User promoted to admin.');
}

/* Activate / deactivate admin privileges */
if ($action === 'deactivate_admin' || $action === 'reactivate_admin') {

  if (!is_super_admin()) {
    admin_redirect_err('Only super admins can manage admin status.');
  }

  $user_id = (int) ($_POST['user_id'] ?? 0);
  $current_user_id = (int) ($_SESSION['user_id'] ?? 0);

  if ($user_id <= 0) {
    admin_redirect_err('Invalid user.');
  }

  if ($user_id === $current_user_id) {
    admin_redirect_err('You cannot change your own admin status.');
  }

  $stmt = $db->prepare(
    "SELECT id_rol_usr
     FROM user_usr
     WHERE id_usr = ?
     LIMIT 1"
  );

  $stmt->bind_param('i', $user_id);
  $stmt->execute();
  $result = $stmt->get_result();
  $user = $result->fetch_assoc();
  $stmt->close();

  if (!$user) {
    admin_redirect_err('User not found.');
  }

  $current_role = (int) $user['id_rol_usr'];

  if ($current_role === ROLE_SUPER_ADMIN) {
    admin_redirect_err('Super admin status must be managed in the database.');
  }

  if ($current_role !== ROLE_ADMIN) {
    admin_redirect_err('Only admins can be activated or deactivated.');
  }

  $admin_active = ($action === 'reactivate_admin') ? 1 : 0;

  $stmt = $db->prepare(
    "UPDATE user_usr
     SET admin_active_usr = ?
     WHERE id_usr = ?"
  );

  $stmt->bind_param('ii', $admin_active, $user_id);
  $stmt->execute();
  $stmt->close();

  $msg = ($admin_active === 1)
    ? 'Admin reactivated.'
    : 'Admin deactivated.';

  admin_redirect_msg($msg);
}

/* Demote admin to member */
if ($action === 'demote_user') {

  if (!is_super_admin()) {
    admin_redirect_err('Only super admins can manage user roles.');
  }

  $user_id = (int) ($_POST['user_id'] ?? 0);
  $current_user_id = (int) ($_SESSION['user_id'] ?? 0);

  if ($user_id <= 0) {
    admin_redirect_err('Invalid user.');
  }

  if ($user_id === $current_user_id) {
    admin_redirect_err('You cannot change your own role.');
  }

  $stmt = $db->prepare(
    "SELECT id_rol_usr
     FROM user_usr
     WHERE id_usr = ?
     LIMIT 1"
  );

  $stmt->bind_param('i', $user_id);
  $stmt->execute();
  $result = $stmt->get_result();
  $user = $result->fetch_assoc();
  $stmt->close();

  if (!$user) {
    admin_redirect_err('User not found.');
  }

  $current_role = (int) $user['id_rol_usr'];

  if ($current_role === ROLE_SUPER_ADMIN) {
    admin_redirect_err('Super admin roles must be managed in the database.');
  }

  if ($current_role !== ROLE_ADMIN) {
    admin_redirect_err('Only admins can be demoted to member.');
  }

  $new_role = ROLE_MEMBER;

  $stmt = $db->prepare(
    "UPDATE user_usr
     SET id_rol_usr = ?
     WHERE id_usr = ?"
  );

  $stmt->bind_param('ii', $new_role, $user_id);
  $stmt->execute();
  $stmt->close();

  admin_redirect_msg('Admin demoted to member.');
}

admin_redirect_err('Invalid action.');