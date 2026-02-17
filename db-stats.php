<?php
require_once 'includes/initialize.php';
include 'includes/header.php';

// Simple queries that prove DB works (safe, read-only)
$role_rows = [];
$level_rows = [];
$user_count = 0;
$recipe_count = 0;
$category_count = 0;

if ($result = $db->query("SELECT id_rol, name_rol FROM role_rol ORDER BY id_rol")) {
  while ($row = $result->fetch_assoc()) { $role_rows[] = $row; }
  $result->free();
}

if ($result = $db->query("SELECT id_lev, level_number_lev, name_lev FROM level_lev ORDER BY level_number_lev")) {
  while ($row = $result->fetch_assoc()) { $level_rows[] = $row; }
  $result->free();
}

if ($result = $db->query("SELECT COUNT(*) AS c FROM user_usr")) {
  $user_count = (int)($result->fetch_assoc()['c'] ?? 0);
  $result->free();
}

if ($result = $db->query("SELECT COUNT(*) AS c FROM recipe_rec")) {
  $recipe_count = (int)($result->fetch_assoc()['c'] ?? 0);
  $result->free();
}

if ($result = $db->query("SELECT COUNT(*) AS c FROM category_cat")) {
  $category_count = (int)($result->fetch_assoc()['c'] ?? 0);
  $result->free();
}
?>

<div id="container">
  <main>
    <h1>Database Proof Page</h1>
    <p>
      This page confirms that PHP is successfully connecting to the MySQL database and displaying live data.
    </p>

    <section class="tab-panel">
      <h2>Quick Counts</h2>
      <ul>
        <li>Users: <strong><?= h($user_count) ?></strong></li>
        <li>Recipes: <strong><?= h($recipe_count) ?></strong></li>
        <li>Categories: <strong><?= h($category_count) ?></strong></li>
      </ul>
    </section>

    <section class="tab-panel">
      <h2>Roles (role_rol)</h2>
      <?php if (empty($role_rows)): ?>
        <p>No roles found.</p>
      <?php else: ?>
        <ul>
          <?php foreach ($role_rows as $r): ?>
            <li><?= h($r['id_rol']) ?> — <?= h($r['name_rol']) ?></li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </section>

    <section class="tab-panel">
      <h2>User Levels (level_lev)</h2>
      <?php if (empty($level_rows)): ?>
        <p>No levels found.</p>
      <?php else: ?>
        <ul>
          <?php foreach ($level_rows as $l): ?>
            <li>
              Level <?= h($l['level_number_lev']) ?> — <?= h($l['name_lev']) ?>
              (id_lev: <?= h($l['id_lev']) ?>)
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </section>

  </main>
</div>

<?php include 'includes/footer.php'; ?>