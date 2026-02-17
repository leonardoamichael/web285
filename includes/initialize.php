<?php

session_start();

/* Core Includes */
require_once 'db-credentials.php';
require_once 'database.php';
require_once 'errors.php';
require_once 'helpers.php';
require_once 'auth.php';

$db = db_connect();

if (!$db) {
  die("Database connection failed.");
}

/* Update last_seen timestamp for logged-in users */
if (isset($_SESSION['user_id'])) {
  $uid = (int)$_SESSION['user_id'];

  $stmt = $db->prepare(
    "INSERT INTO active_user_act (id_usr_act, last_seen_act)
     VALUES (?, NOW())
     ON DUPLICATE KEY UPDATE last_seen_act = NOW()"
  );

  $stmt->bind_param('i', $uid);
  $stmt->execute();
  $stmt->close();
}