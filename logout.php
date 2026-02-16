<?php
require_once 'includes/initialize.php';

$_SESSION = [];

if (ini_get("session.use_cookies")) {
  $params = session_get_cookie_params();
  setcookie(session_name(), '', time() - 42000,
    $params["path"], $params["domain"],
    $params["secure"], $params["httponly"]
  );
}

$db = db_connect();
$uid = (int)($_SESSION['user_id'] ?? 0);
if ($uid) {
  $stmt = $db->prepare("DELETE FROM active_users WHERE user_id = ?");
  $stmt->bind_param('i', $uid);
  $stmt->execute();
  $stmt->close();
}
$db->close();

session_destroy();

header('Location: index.php');
exit;