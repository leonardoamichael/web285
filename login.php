<?php

require_once 'includes/initialize.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: index.php');
  exit;
}

$username_or_email = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

if ($username_or_email === '' || $password === '') {
  redirect_error('login_required', 'index.php');
}

// NOTE: initialize.php already created $db = db_connect();
// so do NOT call db_connect() again here.

$sql = "SELECT id_usr, username_usr, password_hash_usr, id_rol_usr
        FROM user_usr
        WHERE username_usr = ? OR email_usr = ?
        LIMIT 1";

$stmt = $db->prepare($sql);
if (!$stmt) {
  die("Prepare failed: " . $db->error);
}

$stmt->bind_param('ss', $username_or_email, $username_or_email);
$stmt->execute();

$result = $stmt->get_result();
$user = $result->fetch_assoc();

$stmt->close();

// Validate login
if (!$user || !password_verify($password, $user['password_hash_usr'])) {
  header('Location: error.php?code=login_failed&return=index.php');
  exit;
}

// ✅ Logged in
$_SESSION['user_id'] = (int)$user['id_usr'];
$_SESSION['username'] = $user['username_usr'];
$_SESSION['role_id'] = (int)$user['id_rol_usr'];

// Redirect by role_id (admin = 1, member = 2)
if ($_SESSION['role_id'] === 1) {
  header('Location: admin.php');
  exit;
}

header('Location: profile.php');
exit;