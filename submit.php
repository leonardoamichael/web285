<?php
require_once 'includes/initialize.php';

if (!isset($_SESSION['user_id'])) {
  redirect_error('login_required', 'recipes.php');
}

include 'includes/header.php';
?>

<div id="container">
  <main>
    <h1>Submit Recipe</h1>
    <p>(Form goes here)</p>
  </main>
</div>

<?php include 'includes/login-modal.php'; ?>
<?php include 'includes/footer.php'; ?>