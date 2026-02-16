<?php


require_once 'includes/initialize.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: index.php');
  exit;
}

$username_or_email = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

if ($username_or_email === '' || $password === '') {
  redirect_error('login_required', 'index.php'); // or make a new error code later
}

$db = db_connect();

$sql = "SELECT id, username, password_hash, role
        FROM users
        WHERE username = ? OR email = ?
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
$db->close();

if (!$user || !password_verify($password, $user['password_hash'])) {
  // Add a nicer error code later like 'login_failed'
  header('Location: error.php?code=login_failed&return=index.php');
exit;
}

// ✅ Logged in
$_SESSION['user_id'] = (int)$user['id'];
$_SESSION['username'] = $user['username'];
$_SESSION['role'] = $user['role'];

// Redirect by role (simple)
if ($user['role'] === 'admin') {
  header('Location: admin.php'); // create later, or change to index.php for now
  exit;
}

header('Location: submit.php');
exit;