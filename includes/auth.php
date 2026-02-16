<?php

function username_or_email_exists(mysqli $db, string $username, string $email): bool {
  $sql = "SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1";
  $stmt = $db->prepare($sql);
  if (!$stmt) { die("Prepare failed: " . $db->error); }

  $stmt->bind_param('ss', $username, $email);
  $stmt->execute();
  $stmt->store_result();
  $exists = ($stmt->num_rows > 0);
  $stmt->close();

  return $exists;
}

function create_user(mysqli $db, string $username, string $email, string $password, string $role = 'member'): int {
  $hash = password_hash($password, PASSWORD_DEFAULT);

  $sql = "INSERT INTO users (username, email, password_hash, role)
          VALUES (?, ?, ?, ?)";
  $stmt = $db->prepare($sql);
  if (!$stmt) { die("Prepare failed: " . $db->error); }

  $stmt->bind_param('ssss', $username, $email, $hash, $role);
  $stmt->execute();
  $new_id = (int)$stmt->insert_id;
  $stmt->close();

  return $new_id;
}

function login_user(int $id, string $username, string $role): void {
  $_SESSION['user_id'] = $id;
  $_SESSION['username'] = $username;
  $_SESSION['role'] = $role;
}