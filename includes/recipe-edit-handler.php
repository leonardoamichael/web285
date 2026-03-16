<?php
// includes/recipe-edit-handler.php

/**
 * Handle the full recipe editing workflow.
 *
 * Validates edit permissions and submitted fields, updates the main recipe,
 * replaces related category / step / ingredient rows, and optionally adds
 * new uploaded images.
 *
 * Existing images are preserved in this first version. New uploads are appended.
 *
 * Expected inputs (POST):
 * - title (string)
 * - description (string, optional)
 * - prep_hours / prep_minutes
 * - cook_hours / cook_minutes
 * - youtube_url (string, optional)
 * - type[] / style[] / diet[] (category IDs)
 * - step[] (array of strings)
 * - qty[] / unit[] / ing[] / note[] (ingredient row arrays)
 * - image_alt (string, optional, used only for new uploads)
 *
 * Expected uploads (FILES):
 * - images[name|tmp_name|error|size][] (multiple files)
 *
 * Return shape:
 * - ok (bool) update status
 * - errors (array) field/general errors keyed by name
 * - recipe_id (int) edited recipe ID on success, 0 on failure
 *
 * @param mysqli $db Active database connection
 * @param int $recipe_id Recipe ID being edited
 * @return array Edit result array with ok/errors/recipe_id
 */
function handle_recipe_edit(mysqli $db, int $recipe_id): array
{
  $errors = [];

  if ($recipe_id <= 0) {
    return [
      'ok' => false,
      'errors' => ['general' => 'Invalid recipe.'],
      'recipe_id' => 0,
    ];
  }

  /* Viewer context */
  $viewer_id = (int) ($_SESSION['user_id'] ?? 0);
  $role_id   = (int) ($_SESSION['role_id'] ?? 2); // 1=admin, 2=member
  $is_admin  = ($role_id === 1);

  if ($viewer_id <= 0) {
    return [
      'ok' => false,
      'errors' => ['general' => 'You must be logged in.'],
      'recipe_id' => 0,
    ];
  }

  /* Fetch recipe for permission + status preservation */
  $stmt = $db->prepare(
    "SELECT id_rec, id_usr_rec, status_rec
     FROM recipe_rec
     WHERE id_rec = ?
     LIMIT 1"
  );

  if (!$stmt) {
    return [
      'ok' => false,
      'errors' => ['general' => 'Unable to load recipe.'],
      'recipe_id' => 0,
    ];
  }

  $stmt->bind_param('i', $recipe_id);
  $stmt->execute();

  $recipe = $stmt->get_result()->fetch_assoc();
  $stmt->close();

  if (!$recipe) {
    return [
      'ok' => false,
      'errors' => ['general' => 'Recipe not found.'],
      'recipe_id' => 0,
    ];
  }

  $is_owner   = ((int) $recipe['id_usr_rec'] === $viewer_id);
  $is_pending = ((string) $recipe['status_rec'] === 'pending');
  $can_edit   = ($is_admin || ($is_owner && $is_pending));

  if (!$can_edit) {
    return [
      'ok' => false,
      'errors' => ['general' => 'You do not have permission to edit this recipe.'],
      'recipe_id' => 0,
    ];
  }

  /* Validation */
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

  $youtube_url = trim((string) ($_POST['youtube_url'] ?? ''));

  if ($youtube_url !== '') {
    if (mb_strlen($youtube_url) > 255) {
      $errors['youtube_url'] = 'YouTube link is too long.';
    } elseif (!filter_var($youtube_url, FILTER_VALIDATE_URL)) {
      $errors['youtube_url'] = 'Please enter a valid URL.';
    }
  }

  /* Time fields */
  $prep_hours = (int) ($_POST['prep_hours'] ?? 0);
  $prep_mins  = (int) ($_POST['prep_minutes'] ?? 0);

  $cook_hours = (int) ($_POST['cook_hours'] ?? 0);
  $cook_mins  = (int) ($_POST['cook_minutes'] ?? 0);

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
      'recipe_id' => 0,
    ];
  }

  $db->begin_transaction();

  try {
    /* 1) Update main recipe record, preserving current status */
    $stmt = $db->prepare(
      "UPDATE recipe_rec
       SET title_rec = ?,
           description_rec = ?,
           prep_minutes_rec = ?,
           cook_minutes_rec = ?,
           youtube_url_rec = ?
       WHERE id_rec = ?
       LIMIT 1"
    );

    if (!$stmt) {
      throw new Exception("Prepare failed: " . $db->error);
    }

    $desc_var = ($description !== '') ? $description : '';
    $prep_var = $prep_minutes_total;
    $cook_var = $cook_minutes_total;
    $yt_var   = ($youtube_url !== '') ? $youtube_url : null;

    $stmt->bind_param(
      'ssiisi',
      $title,
      $desc_var,
      $prep_var,
      $cook_var,
      $yt_var,
      $recipe_id
    );

    $stmt->execute();
    $stmt->close();

    /* 2) Replace categories */
    $stmt = $db->prepare(
      "DELETE FROM recipe_category_reccat
       WHERE id_rec_reccat = ?"
    );

    if (!$stmt) {
      throw new Exception("Prepare failed: " . $db->error);
    }

    $stmt->bind_param('i', $recipe_id);
    $stmt->execute();
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

    /* 3) Replace steps */
    $stmt = $db->prepare(
      "DELETE FROM recipe_step_stp
       WHERE id_rec_stp = ?"
    );

    if (!$stmt) {
      throw new Exception("Prepare failed: " . $db->error);
    }

    $stmt->bind_param('i', $recipe_id);
    $stmt->execute();
    $stmt->close();

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

    /* 4) Replace ingredients */
    $stmt = $db->prepare(
      "DELETE FROM recipe_ingredient_recing
       WHERE id_rec_recing = ?"
    );

    if (!$stmt) {
      throw new Exception("Prepare failed: " . $db->error);
    }

    $stmt->bind_param('i', $recipe_id);
    $stmt->execute();
    $stmt->close();

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

      $qty_var  = $qty;
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

    /* 5) Add any new uploaded images (existing ones remain) */
    if (!empty($_FILES['images']) && is_array($_FILES['images']['name'])) {
      $allowed = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
      ];

      $upload_dir = __DIR__ . '/../uploads/recipes/';
      $web_dir    = 'uploads/recipes/';

      if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
      }

      $alt = trim((string) ($_POST['image_alt'] ?? ''));

      $sort = 1;
      $stmt_sort = $db->prepare(
        "SELECT COALESCE(MAX(sort_order_recimg), 0) AS max_sort
         FROM recipe_image_recimg
         WHERE id_rec_recimg = ?"
      );

      if (!$stmt_sort) {
        throw new Exception("Prepare failed: " . $db->error);
      }

      $stmt_sort->bind_param('i', $recipe_id);
      $stmt_sort->execute();
      $sort_row = $stmt_sort->get_result()->fetch_assoc();
      $stmt_sort->close();

      $sort = ((int) ($sort_row['max_sort'] ?? 0)) + 1;

      $stmt_img = $db->prepare(
        "INSERT INTO recipe_image_recimg (id_rec_recimg, path_recimg, alt_recimg, sort_order_recimg)
         VALUES (?, ?, ?, ?)"
      );

      if (!$stmt_img) {
        throw new Exception("Prepare failed: " . $db->error);
      }

      $count = count($_FILES['images']['name']);

      for ($i = 0; $i < $count; $i++) {
        if ($_FILES['images']['error'][$i] !== UPLOAD_ERR_OK) {
          continue;
        }

        $tmp = $_FILES['images']['tmp_name'][$i];

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $type  = $finfo->file($tmp);

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
      'recipe_id' => $recipe_id,
    ];

  } catch (Throwable $e) {
    $db->rollback();

    $errors['general'] = 'Update failed. Please try again.';
    // For local debugging, temporarily inspect $e->getMessage()

    return [
      'ok' => false,
      'errors' => $errors,
      'recipe_id' => 0,
    ];
  }
}