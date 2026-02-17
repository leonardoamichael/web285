<?php
require_once 'includes/initialize.php';

if (!isset($_SESSION['user_id'])) {
  redirect_error('login_required', 'index.php');
}

include 'includes/header.php';

$role_id = (int)($_SESSION['role_id'] ?? 2); // 1=admin, 2=member
$is_admin = ($role_id === 1);
?>

<div id="container">
  <main>
    <h1>Your Profile</h1>

    <p>Welcome, <strong><?= h($_SESSION['username']) ?></strong>.</p>

    <nav class="tabs" aria-label="Profile sections">
      <a class="tab" href="#recipes">My Recipes</a>
      <a class="tab" href="#stats">My Stats</a>

      <?php if ($is_admin): ?>
        <a class="tab" href="#admin-tools">Admin Tools</a>
        <a class="tab tab-link" href="admin.php">Admin Panel →</a>
      <?php endif; ?>
    </nav>

    <section id="recipes" class="tab-panel">
      <h2>My Recipes</h2>
      <p>Placeholder: this will show recipes you submitted (database-driven).</p>
    </section>

    <section id="stats" class="tab-panel">
      <h2>My Stats</h2>
      <p>Placeholder: stats like total recipes, favorites, last login, etc.</p>
    </section>

    <?php if ($is_admin): ?>
      <section id="admin-tools" class="tab-panel">
        <h2>Admin Tools</h2>
        <p>Placeholder: admin-only tools and summaries can live here.</p>
      </section>
    <?php endif; ?>

  </main>
</div>

<?php include 'includes/login-modal.php'; ?>
<?php include 'includes/footer.php'; ?>