<?php
// includes/recipe-submit-handler.php

/**
 * Handle the full recipe submission workflow.
 *
 * Validates required fields, inserts the recipe as "pending", and then
 * inserts related steps, ingredients, and optional image uploads.
 * Uses a database transaction to prevent partial inserts.
 *
 * Expected inputs (POST):
 * - title (string)
 * - step[] (array of strings)
 * - qty[] / unit[] / ing[] / note[] (ingredient row arrays)
 * - image_alt (string, optional)
 *
 * Expected uploads (FILES):
 * - images[name|tmp_name|error|size][] (multiple files)
 *
 * Return shape:
 * - ok (bool) submission status
 * - errors (array) field/general errors keyed by name
 * - recipe_id (int) new recipe ID on success, 0 on failure
 *
 * @param mysqli $db Active database connection
 * @return array Submission result array with ok/errors/recipe_id
 */
function handle_recipe_submit(mysqli $db): array
{
  $errors = [];
  $recipe_id = 0;

  $title = trim((string) ($_POST['title'] ?? ''));
  if ($title === '' || mb_strlen($title) > 120) {
    $errors['title'] = 'Title is required (max 120 characters).';
  }

  $steps = $_POST['step'] ?? [];
  $has_step = false;

  if (is_array($steps)) {
    foreach ($steps as $s) {
      if (trim((string) $s) !== '') {
        $has_step = true;
        break;
      }
    }
  }

  if (!$has_step) {
    $errors['steps'] = 'Please enter at least one step.';
  }

  $uid = (int) ($_SESSION['user_id'] ?? 0);
  if ($uid <= 0) {
    $errors['general'] = 'You must be logged in.';
  }

  if (!empty($errors)) {
    return [
      'ok' => false,
      'errors' => $errors,
      'recipe_id' => 0
    ];
  }

  // Transaction so we don't end up with partial inserts
  $db->begin_transaction();

  try {

    // 1) Insert recipe as PENDING
    $status = 'pending';

    $stmt = $db->prepare(
      "INSERT INTO recipe_rec (id_usr_rec, title_rec, status_rec)
       VALUES (?, ?, ?)"
    );

    if (!$stmt) {
      throw new Exception("Prepare failed: " . $db->error);
    }

    $stmt->bind_param('iss', $uid, $title, $status);
    $stmt->execute();

    $recipe_id = (int) $stmt->insert_id;

    $stmt->close();

    // 2) Insert steps
    $stmt = $db->prepare(
      "INSERT INTO recipe_step_stp (id_rec_stp, step_number_stp, instruction_stp)
       VALUES (?, ?, ?)"
    );

    if (!$stmt) {
      throw new Exception("Prepare failed: " . $db->error);
    }

    $step_num = 1;

    foreach ((array) $steps as $s) {
      $s = trim((string) $s);
      if ($s === '') {
        continue;
      }

      $stmt->bind_param('iis', $recipe_id, $step_num, $s);
      $stmt->execute();

      $step_num++;
    }

    $stmt->close();

    // 3) Insert ingredients
    $qtys  = $_POST['qty']  ?? [];
    $units = $_POST['unit'] ?? [];
    $ings  = $_POST['ing']  ?? [];
    $notes = $_POST['note'] ?? [];

    $stmt_find_ing = $db->prepare(
      "SELECT id_ing
       FROM ingredient_ing
       WHERE name_ing = ?
       LIMIT 1"
    );

    if (!$stmt_find_ing) {
      throw new Exception("Prepare failed: " . $db->error);
    }

    $stmt_ins_ing = $db->prepare(
      "INSERT INTO ingredient_ing (name_ing)
       VALUES (?)"
    );

    if (!$stmt_ins_ing) {
      throw new Exception("Prepare failed: " . $db->error);
    }

    $stmt_ins_join = $db->prepare(
      "INSERT INTO recipe_ingredient_recing
       (id_rec_recing, id_ing_recing, quantity_recing, id_uni_recing, note_recing)
       VALUES (?, ?, ?, ?, ?)"
    );

    if (!$stmt_ins_join) {
      throw new Exception("Prepare failed: " . $db->error);
    }

    $row_count = max(
      count((array) $ings),
      count((array) $qtys),
      count((array) $units),
      count((array) $notes)
    );

    for ($i = 0; $i < $row_count; $i++) {
      $name = trim((string) ($ings[$i] ?? ''));
      if ($name === '') {
        continue;
      }

      $name = mb_strtolower($name);

      $note = trim((string) ($notes[$i] ?? ''));

      $qty_raw = trim((string) ($qtys[$i] ?? ''));
      $qty = ($qty_raw === '') ? null : (float) $qty_raw;

      $unit_raw = (string) ($units[$i] ?? '');
      $unit_id = ($unit_raw === '') ? null : (int) $unit_raw;

      $stmt_find_ing->bind_param('s', $name);
      $stmt_find_ing->execute();

      $res = $stmt_find_ing->get_result();
      $found = $res->fetch_assoc();
      $res->free();

      if ($found) {
        $ing_id = (int) $found['id_ing'];
      } else {
        $stmt_ins_ing->bind_param('s', $name);
        $stmt_ins_ing->execute();
        $ing_id = (int) $stmt_ins_ing->insert_id;
      }

      $qty_var = $qty;
      $unit_var = $unit_id;
      $note_var = ($note === '') ? null : $note;

      $stmt_ins_join->bind_param(
        'iidis',
        $recipe_id,
        $ing_id,
        $qty_var,
        $unit_var,
        $note_var
      );

      $stmt_ins_join->execute();
    }

    $stmt_find_ing->close();
    $stmt_ins_ing->close();
    $stmt_ins_join->close();

    // 4) Image uploads (stored, but recipe is not public until approved)
    if (!empty($_FILES['images']) && is_array($_FILES['images']['name'])) {

      $allowed = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
      ];

      $upload_dir = __DIR__ . '/../uploads/recipes/';
      $web_dir = 'uploads/recipes/';

      if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
      }

      $alt = trim((string) ($_POST['image_alt'] ?? ''));

      $stmt_img = $db->prepare(
        "INSERT INTO recipe_image_recimg (id_rec_recimg, path_recimg, alt_recimg, sort_order_recimg)
         VALUES (?, ?, ?, ?)"
      );

      if (!$stmt_img) {
        throw new Exception("Prepare failed: " . $db->error);
      }

      $sort = 1;
      $count = count($_FILES['images']['name']);

      for ($i = 0; $i < $count; $i++) {
        if ($_FILES['images']['error'][$i] !== UPLOAD_ERR_OK) {
          continue;
        }

        $tmp = $_FILES['images']['tmp_name'][$i];

        // More reliable MIME check than trusting extension
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $type = $finfo->file($tmp);

        if (!isset($allowed[$type])) {
          continue;
        }

        if ((int) $_FILES['images']['size'][$i] > 2_000_000) {
          continue;
        }

        $ext = $allowed[$type];
        $filename = "rec_{$recipe_id}_" . bin2hex(random_bytes(8)) . ".{$ext}";
        $dest_path = $upload_dir . $filename;

        if (move_uploaded_file($tmp, $dest_path)) {
          $path = $web_dir . $filename;
          $alt_to_use = ($alt !== '') ? $alt : $title;

          $stmt_img->bind_param('issi', $recipe_id, $path, $alt_to_use, $sort);
          $stmt_img->execute();

          $sort++;
        }
      }

      $stmt_img->close();
    }

    $db->commit();

    return [
      'ok' => true,
      'errors' => [],
      'recipe_id' => $recipe_id
    ];

  } catch (Throwable $e) {

    $db->rollback();

    $errors['general'] = 'Submission failed. Please try again.';
    // For debugging locally you can temporarily echo $e->getMessage()

    return [
      'ok' => false,
      'errors' => $errors,
      'recipe_id' => 0
    ];
  }
}