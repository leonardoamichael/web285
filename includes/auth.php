<?php

function username_or_email_exists(mysqli $db, string $username, string $email): bool {
  $sql = "SELECT id_usr
          FROM user_usr
          WHERE username_usr = ? OR email_usr = ?
          LIMIT 1";
  $stmt = $db->prepare($sql);
  if (!$stmt) { die("Prepare failed: " . $db->error); }

  $stmt->bind_param('ss', $username, $email);
  $stmt->execute();
  $stmt->store_result();
  $exists = ($stmt->num_rows > 0);
  $stmt->close();

  return $exists;
}


function create_user(mysqli $db, string $username, string $email, string $password): int {
  $hash = password_hash($password, PASSWORD_DEFAULT);

  $role_id = 2;  // member
  $level_id = 1; // Level 1

  $sql = "INSERT INTO user_usr
          (username_usr, email_usr, password_hash_usr, id_rol_usr, id_lev_usr)
          VALUES (?, ?, ?, ?, ?)";
  $stmt = $db->prepare($sql);
  if (!$stmt) { die("Prepare failed: " . $db->error); }

  $stmt->bind_param('sssii', $username, $email, $hash, $role_id, $level_id);
  $stmt->execute();

  $new_id = (int)$stmt->insert_id;
  $stmt->close();

  return $new_id;
}

function login_user(int $id, string $username, int $role_id): void {
  $_SESSION['user_id'] = $id;
  $_SESSION['username'] = $username;
  $_SESSION['role_id'] = $role_id;
}