<?php

session_start();

/* Core Includes */
require_once __DIR__ . '/db-credentials.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/errors.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/recipe-functions.php';

/**
 * Establish primary database connection.
 */
$db = db_connect();

if (!$db) {
  internal_error("Database connection failed.");
}

/**
 * Update user activity timestamp.
 *
 * Tracks active sessions by inserting or updating the user's
 * last_seen timestamp. This supports features like online users,
 * activity monitoring, or session freshness checks.
 */
if (isset($_SESSION['user_id'])) {
  $uid = (int) $_SESSION['user_id'];

  $stmt = $db->prepare(
    "INSERT INTO active_user_act (id_usr_act, last_seen_act)
     VALUES (?, NOW())
     ON DUPLICATE KEY UPDATE last_seen_act = NOW()"
  );

  $stmt->bind_param('i', $uid);
  $stmt->execute();
  $stmt->close();
}