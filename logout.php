<?php
require_once 'includes/initialize.php';

// Grab user id BEFORE clearing session
$uid = (int)($_SESSION['user_id'] ?? 0);

// Remove from active users table (new schema)
if ($uid) {
  $stmt = $db->prepare("DELETE FROM active_user_act WHERE id_usr_act = ?");
  if ($stmt) {
    $stmt->bind_param('i', $uid);
    $stmt->execute();
    $stmt->close();
  }
}

// Clear session array
$_SESSION = [];

// Clear session cookie
if (ini_get("session.use_cookies")) {
  $params = session_get_cookie_params();
  setcookie(
    session_name(),
    '',
    time() - 42000,
    $params["path"],
    $params["domain"],
    $params["secure"],
    $params["httponly"]
  );
}

// Destroy session
session_destroy();

// Redirect home
header('Location: index.php');
exit;