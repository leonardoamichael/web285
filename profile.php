<?php
require_once 'includes/initialize.php';
include 'includes/header.php';
?>

<div id="container">
  <main>
    <h1>Your Profile</h1>

    <?php if (!isset($_SESSION['user_id'])): ?>
      <?php redirect_error('login_required', 'index.php'); ?>
    <?php endif; ?>

    <p>Welcome, <strong><?= h($_SESSION['username']) ?></strong>.</p>

    <p>
      This is your profile page. Member-specific features will appear here as the
      application continues to develop.
    </p>
  </main>
</div>

<?php include 'includes/login-modal.php'; ?>
<?php include 'includes/footer.php'; ?>