<?php
require_once __DIR__ . '/image-functions.php';

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
  $description = trim((string) ($_POST['description'] ?? ''));
  if (mb_strlen($description) > 250) {
    $errors['description'] = 'Description must be 250 characters or less.';
  }
  $type_ids  = $_POST['type']  ?? [];
  $style_ids = $_POST['style'] ?? [];
  $diet_ids  = $_POST['diet']  ?? [];

  if (!is_array($type_ids)) {
    $type_ids = [];
  }
  if (!is_array($style_ids)) {
    $style_ids = [];
  }
  if (!is_array($diet_ids)) {
    $diet_ids = [];
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

  $youtube_url = trim((string)($_POST['youtube_url'] ?? ''));

  if ($youtube_url !== '') {
    if (mb_strlen($youtube_url) > 255) {
      $errors['youtube_url'] = 'YouTube link is too long.';
    } elseif (!filter_var($youtube_url, FILTER_VALIDATE_URL)) {
      $errors['youtube_url'] = 'Please enter a valid URL.';
    }
  }
  

  // --- Time fields ---
  $prep_hours = (int)($_POST['prep_hours'] ?? 0);
  $prep_mins  = (int)($_POST['prep_minutes'] ?? 0);

  $cook_hours = (int)($_POST['cook_hours'] ?? 0);
  $cook_mins  = (int)($_POST['cook_minutes'] ?? 0);

  if ($prep_hours < 0 || $prep_hours > 24 || $prep_mins < 0 || $prep_mins > 59) {
    $errors['prep_time'] = 'Invalid prep time.';
  }

  if ($cook_hours < 0 || $cook_hours > 24 || $cook_mins < 0 || $cook_mins > 59) {
    $errors['cook_time'] = 'Invalid cook time.';
  }

  $prep_minutes_total = null;
  if ($prep_hours !== 0 || $prep_mins !== 0) {
    $prep_minutes_total = ($prep_hours * 60) + $prep_mins;
  }

  $cook_minutes_total = null;
  if ($cook_hours !== 0 || $cook_mins !== 0) {
    $cook_minutes_total = ($cook_hours * 60) + $cook_mins;
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
      "INSERT INTO recipe_rec
      (id_usr_rec, title_rec, description_rec, status_rec, prep_minutes_rec, cook_minutes_rec, youtube_url_rec)
      VALUES (?, ?, ?, ?, ?, ?, ?)"
    );

    if (!$stmt) {
      throw new Exception("Prepare failed: " . $db->error);
    }

      $desc_var = ($description !== '') ? $description : '';
      $prep_var = $prep_minutes_total;
      $cook_var = $cook_minutes_total;
      $yt_var   = ($youtube_url !== '') ? $youtube_url : null;

      $stmt->bind_param(
        'isssiis',
        $uid,
        $title,
        $desc_var,
        $status,
        $prep_var,
        $cook_var,
        $yt_var
      );

      $stmt->execute();
      $recipe_id = (int) $stmt->insert_id;

      $stmt->close();

    $stmt_cat = $db->prepare(
      "INSERT INTO recipe_category_reccat (id_rec_reccat, id_cat_reccat)
      VALUES (?, ?)"
    );

    if (!$stmt_cat) {
      throw new Exception("Prepare failed: " . $db->error);
    }

  $cat_ids = [];

  foreach ($type_ids as $t) {
    $t = (int) $t;
    if ($t > 0) {
      $cat_ids[] = $t;
    }
  }

  foreach ($style_ids as $s) {
    $s = (int) $s;
    if ($s > 0) {
      $cat_ids[] = $s;
    }
  }

  foreach ($diet_ids as $d) {
    $d = (int) $d;
    if ($d > 0) {
      $cat_ids[] = $d;
    }
  }

  $cat_ids = array_values(array_unique($cat_ids));

    foreach ($cat_ids as $cat_id) {
      $stmt_cat->bind_param('ii', $recipe_id, $cat_id);
      $stmt_cat->execute();
    }

    $stmt_cat->close();

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

        if ((int) $_FILES['images']['size'][$i] > 10_000_000) {
          continue;
        }

        $filename = "rec_{$recipe_id}_" . bin2hex(random_bytes(8)) . ".jpg";
        $dest_path = $upload_dir . $filename;

        if (save_compressed_recipe_image($tmp, $dest_path)) {
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