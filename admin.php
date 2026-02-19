<?php
require_once 'includes/initialize.php';

/* Authentication & Authorization */

// Must be logged in
if (!isset($_SESSION['user_id'])) {
  redirect_error('login_required', 'index.php');
}

// Must be admin (role_id = 1)
if ((int) ($_SESSION['role_id'] ?? 0) !== 1) {
  redirect_error('access_denied', 'index.php');
}

include 'includes/header.php';

$minutes = 15;

/* Active Users Query */
$stmt = $db->prepare(
  "SELECT u.username_usr, a.last_seen_act
   FROM active_user_act a
   JOIN user_usr u ON u.id_usr = a.id_usr_act
   WHERE a.last_seen_act >= (NOW() - INTERVAL ? MINUTE)
   ORDER BY a.last_seen_act DESC"
);

$stmt->bind_param('i', $minutes);
$stmt->execute();

$result = $stmt->get_result();

$active = [];

while ($row = $result->fetch_assoc()) {
  $active[] = $row;
}

$stmt->close();

/* Pending Recipes Query */
$stmt = $db->prepare(
  "SELECT r.id_rec, r.title_rec, r.created_at_rec, u.username_usr
   FROM recipe_rec r
   JOIN user_usr u ON u.id_usr = r.id_usr_rec
   WHERE r.status_rec = 'pending'
   ORDER BY r.created_at_rec DESC
   LIMIT 50"
);

$stmt->execute();

$result = $stmt->get_result();

$pending = [];

while ($row = $result->fetch_assoc()) {
  $pending[] = $row;
}

$stmt->close();
?>

<div id="container">
  <main>

    <h1>Admin Panel</h1>

    <h2>
      Active users (last <?= (int) $minutes ?> min):
      <?= count($active) ?>
    </h2>

    <?php if (count($active) === 0): ?>
      <p>No active users right now.</p>

    <?php else: ?>
      <ul>
        <?php foreach ($active as $row): ?>
          <li>
            <?= h($row['username_usr']) ?>
            (last seen: <?= h($row['last_seen_act']) ?>)
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>

    <hr>

    <h2>Pending Recipes: <?= count($pending) ?></h2>

    <?php if (empty($pending)): ?>
      <p>No recipes pending approval.</p>

    <?php else: ?>
      <ul>
        <?php foreach ($pending as $r): ?>
          <li>

            <a href="recipe.php?id=<?= (int) $r['id_rec'] ?>">
              <?= h($r['title_rec']) ?>
            </a>

            — by <?= h($r['username_usr']) ?>

            <?php if (!empty($r['created_at_rec'])): ?>
              (<?= h($r['created_at_rec']) ?>)
            <?php endif; ?>

          </li>
        <?php endforeach; ?>
      </ul>

      <p>
        Approve/Reject happens inside
        <a href="profile.php#admin-tools">Profile → Admin Tools</a>.
      </p>
    <?php endif; ?>

    <p>Welcome, <?= h($_SESSION['username']) ?>.</p>

  </main>
</div>

<?php include 'includes/login-modal.php'; ?>
<?php include 'includes/footer.php'; ?>