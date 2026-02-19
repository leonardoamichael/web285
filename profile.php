<?php
require_once 'includes/initialize.php';

/* Authentication */
if (!isset($_SESSION['user_id'])) {
  redirect_error('login_required', 'index.php');
}

include 'includes/header.php';

/* Role / permissions */
$role_id   = (int) ($_SESSION['role_id'] ?? 2); // 1=admin, 2=member
$is_admin  = ($role_id === 1);

$admin_message = '';

/* Moderation actions (admin only) */
if ($is_admin && $_SERVER['REQUEST_METHOD'] === 'POST') {

  $action   = (string) ($_POST['action'] ?? '');
  $recipe_id = (int) ($_POST['recipe_id'] ?? 0);

  if ($recipe_id > 0 && ($action === 'approve' || $action === 'reject')) {

    $new_status = ($action === 'approve') ? 'approved' : 'rejected';
    $admin_id   = (int) ($_SESSION['user_id'] ?? 0);

    $stmt = $db->prepare(
      "UPDATE recipe_rec
       SET status_rec = ?, reviewed_by_usr = ?, reviewed_at_rec = NOW()
       WHERE id_rec = ? AND status_rec = 'pending'"
    );

    $stmt->bind_param('sii', $new_status, $admin_id, $recipe_id);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
      $admin_message = ($action === 'approve')
        ? 'Recipe approved.'
        : 'Recipe rejected.';
    } else {
      $admin_message = 'No change made (maybe already reviewed).';
    }

    $stmt->close();
  }
}

/* My recipes (owner view): includes all statuses for this user */
$my_recipes = [];

$stmt = $db->prepare(
  "SELECT id_rec, title_rec, status_rec, created_at_rec
   FROM recipe_rec
   WHERE id_usr_rec = ?
   ORDER BY created_at_rec DESC
   LIMIT 50"
);

$uid = (int) $_SESSION['user_id'];

$stmt->bind_param('i', $uid);
$stmt->execute();

$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
  $my_recipes[] = $row;
}

$stmt->close();

/* Admin data */
$active  = [];
$pending = [];

if ($is_admin) {

  /* Active users */
  $minutes = 15;

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

  while ($row = $result->fetch_assoc()) {
    $active[] = $row;
  }

  $stmt->close();

  /* Pending recipes */
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

  while ($row = $result->fetch_assoc()) {
    $pending[] = $row;
  }

  $stmt->close();
}
?>

<div id="container">
  <main>

    <h1>Your Profile</h1>

    <p>
      Welcome,
      <strong><?= h($_SESSION['username']) ?></strong>.
    </p>

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

      <?php if (empty($my_recipes)): ?>
        <p>You haven’t submitted any recipes yet.</p>

      <?php else: ?>
        <ul>
          <?php foreach ($my_recipes as $r): ?>
            <li>

              <a href="recipe.php?id=<?= (int) $r['id_rec'] ?>">
                <?= h($r['title_rec']) ?>
              </a>

              <small>
                — <?= h($r['status_rec']) ?>

                <?php if (!empty($r['created_at_rec'])): ?>
                  (<?= h($r['created_at_rec']) ?>)
                <?php endif; ?>
              </small>

            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </section>

    <section id="stats" class="tab-panel">
      <h2>My Stats</h2>
      <p>Placeholder: stats like total recipes, favorites, last login, etc.</p>
    </section>

    <?php if ($is_admin): ?>
      <section id="admin-tools" class="tab-panel">
        <h2>Admin Tools</h2>

        <?php if ($admin_message !== ''): ?>
          <p role="alert">
            <strong><?= h($admin_message) ?></strong>
          </p>
        <?php endif; ?>

        <h3>Pending Recipes: <?= count($pending) ?></h3>

        <?php if (empty($pending)): ?>
          <p>No recipes pending approval.</p>

        <?php else: ?>
          <ul>
            <?php foreach ($pending as $r): ?>
              <li style="margin-bottom: 0.75rem;">

                <a href="recipe.php?id=<?= (int) $r['id_rec'] ?>">
                  <?= h($r['title_rec']) ?>
                </a>

                — by <?= h($r['username_usr']) ?>

                <form
                  method="post"
                  action="profile.php#admin-tools"
                  style="display:inline-block; margin-left: 0.5rem;"
                >
                  <input
                    type="hidden"
                    name="recipe_id"
                    value="<?= (int) $r['id_rec'] ?>"
                  >

                  <button type="submit" name="action" value="approve">
                    Approve
                  </button>

                  <button type="submit" name="action" value="reject">
                    Reject
                  </button>
                </form>

              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>

        <hr>

        <h3>Active users (last 15 min): <?= count($active) ?></h3>

        <?php if (empty($active)): ?>
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

      </section>
    <?php endif; ?>

  </main>
</div>

<?php include 'includes/login-modal.php'; ?>
<?php include 'includes/footer.php'; ?>