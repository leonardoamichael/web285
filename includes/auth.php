<?php

/**
 * Check whether a username or email is already in use.
 *
 * Looks up a user record by matching either the provided username or email.
 * Used to prevent duplicate accounts during registration.
 *
 * @param mysqli $db Active database connection
 * @param string $username Username to check
 * @param string $email Email address to check
 * @return bool True if a matching user exists; otherwise false
 */
function username_or_email_exists(mysqli $db, string $username, string $email): bool
{
  $sql = "SELECT id_usr
          FROM user_usr
          WHERE username_usr = ? OR email_usr = ?
          LIMIT 1";

  $stmt = $db->prepare($sql);
    if (!$stmt) {
      internal_error("Prepare failed: " . $db->error);
    }

  $stmt->bind_param('ss', $username, $email);
  $stmt->execute();
  $stmt->store_result();

  $exists = ($stmt->num_rows > 0);

  $stmt->close();

  return $exists;
}

/**
 * Create a new user account.
 *
 * Hashes the password and inserts a new record into the user table.
 * Defaults the new user to a standard member role and Level 1.
 *
 * @param mysqli $db Active database connection
 * @param string $username New user's username
 * @param string $email New user's email address
 * @param string $password Plain-text password (will be hashed before insert)
 * @return int The new user's ID (id_usr) on success
 */
function create_user(mysqli $db, string $username, string $email, string $password): int
{
  $hash = password_hash($password, PASSWORD_DEFAULT);

  $role_id = 2;  // member
  $level_id = 1; // Level 1

  $sql = "INSERT INTO user_usr
          (username_usr, email_usr, password_hash_usr, id_rol_usr, id_lev_usr)
          VALUES (?, ?, ?, ?, ?)";

  $stmt = $db->prepare($sql);
  if (!$stmt) {
    internal_error("Prepare failed: " . $db->error);
  }

  $stmt->bind_param('sssii', $username, $email, $hash, $role_id, $level_id);
  $stmt->execute();

  $new_id = (int) $stmt->insert_id;

  $stmt->close();

  return $new_id;
}

/**
 * Log a user in by storing session values.
 *
 * Sets core session variables used to identify the user and their role
 * across page requests.
 *
 * @param int $id User ID (id_usr)
 * @param string $username Username to display / reference in session
 * @param int $role_id Role ID used for access control checks
 * @return void
 */
function login_user(int $id, string $username, int $role_id): void
{
  $_SESSION['user_id'] = $id;
  $_SESSION['username'] = $username;
  $_SESSION['role_id'] = $role_id;
}