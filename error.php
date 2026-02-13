<?php
require_once 'includes/initialize.php';
include 'includes/header.php';

$code = $_GET['code'] ?? 'not_found';
$return = $_GET['return'] ?? 'index.php';

if (!isset($errors[$code])) {
  $code = 'not_found';
}

$title = $errors[$code]['title'];
$message = $errors[$code]['message'];
?>

<div id="container">
  <main>
    <h1><?= htmlspecialchars($title) ?></h1>
    <p><?= htmlspecialchars($message) ?></p>
    <p><a href="<?= htmlspecialchars($return) ?>">Go back</a></p>
  </main>
</div>

<?php include 'includes/login-modal.php'; ?>
<?php include 'includes/footer.php'; ?>