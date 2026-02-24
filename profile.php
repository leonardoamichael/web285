<?php
require_once 'includes/initialize.php';

/* Authentication */
if (!isset($_SESSION['user_id'])) {
    redirect_error('login_required', 'index.php');
}

include 'includes/header.php';

/* Role / permissions */
$role_id  = (int) ($_SESSION['role_id'] ?? 2); // 1=admin, 2=member
$is_admin = ($role_id === 1);

/* Messages from handler */
$admin_message = '';
$admin_error   = '';

if (!empty($_GET['msg'])) {
    $admin_message = (string) $_GET['msg'];
}
if (!empty($_GET['err'])) {
    $admin_error = (string) $_GET['err'];
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
$active   = [];
$pending  = [];
$approved = [];

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

  /* Approved recipes */
  $stmt = $db->prepare(
    "SELECT r.id_rec, r.title_rec, r.created_at_rec, u.username_usr
     FROM recipe_rec r
     JOIN user_usr u ON u.id_usr = r.id_usr_rec
     WHERE r.status_rec = 'approved'
     ORDER BY r.created_at_rec DESC
     LIMIT 50"
  );

  $stmt->execute();
  $result = $stmt->get_result();

  while ($row = $result->fetch_assoc()) {
    $approved[] = $row;
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
/* Clear confirmation if cancelled */
if ($is_admin && isset($_GET['confirm_cancel'])) {
  unset($_SESSION['admin_confirm']);
}

/* Confirmation state for delete recipe */
$confirm_type        = (string) ($_GET['confirm'] ?? '');
$confirm             = $_SESSION['admin_confirm'] ?? null;
$show_delete_confirm = false;

if (
    $is_admin
    && $confirm_type === 'delete_recipe'
    && is_array($confirm)
    && ($confirm['type'] ?? '') === 'delete_recipe'
    && (int) ($confirm['expires'] ?? 0) >= time()
) {
    $show_delete_confirm = true;
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

                <?php if ($admin_error !== ''): ?>
                    <p role="alert">
                        <strong><?= h($admin_error) ?></strong>
                    </p>
                <?php endif; ?>

                <?php if ($admin_message !== ''): ?>
                    <p role="status">
                        <strong><?= h($admin_message) ?></strong>
                    </p>
                <?php endif; ?>

                <?php if ($show_delete_confirm): ?>
                    <div class="admin-confirm">
                        <h3 class="admin-confirm-title">Confirm delete recipe</h3>

                        <p>
                            You are about to permanently delete recipe ID
                            <strong><?= (int) $confirm['recipe_id'] ?></strong>.
                            This will remove its steps, ingredients, images, and uploaded files.
                        </p>

                        <form
                            method="post"
                            action="includes/admin-actions-handler.php"
                            class="admin-confirm-form"
                        >
                            <input type="hidden" name="action" value="delete_recipe_confirm">
                            <input type="hidden" name="recipe_id" value="<?= (int) $confirm['recipe_id'] ?>">
                            <input type="hidden" name="confirm_token" value="<?= h((string) $confirm['token']) ?>">
                            <button type="submit">Yes — delete</button>
                        </form>

                        <a href="profile.php?confirm_cancel=1#admin-tools">Cancel</a>
                    </div>
                <?php endif; ?>

                <h3>Pending Recipes: <?= count($pending) ?></h3>

                <?php if (empty($pending)): ?>
                    <p>No recipes pending approval.</p>
                <?php else: ?>
                    <ul>
                        <?php foreach ($pending as $r): ?>
                            <li class="admin-recipe-row">

                                <a href="recipe.php?id=<?= (int) $r['id_rec'] ?>">
                                    <?= h($r['title_rec']) ?>
                                </a>

                                — by <?= h($r['username_usr']) ?>

                                <form
                                    method="post"
                                    action="includes/admin-actions-handler.php"
                                    class="admin-inline-form"
                                >
                                    <input type="hidden" name="recipe_id" value="<?= (int) $r['id_rec'] ?>">

                                    <button type="submit" name="action" value="approve">Approve</button>
                                    <button type="submit" name="action" value="reject">Reject</button>
                                    <button type="submit" name="action" value="delete_recipe_request">Delete…</button>
                                </form>

                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
                <hr>

                <h3>Approved Recipes: <?= count($approved) ?></h3>

                <?php if (empty($approved)): ?>
                  <p>No approved recipes found.</p>
                <?php else: ?>
                  <ul>
                    <?php foreach ($approved as $r): ?>
                      <li class="admin-recipe-row">

                        <a href="recipe.php?id=<?= (int) $r['id_rec'] ?>">
                          <?= h($r['title_rec']) ?>
                        </a>

                        — by <?= h($r['username_usr']) ?>

                        <form
                          method="post"
                          action="includes/admin-actions-handler.php"
                          class="admin-inline-form"
                        >
                          <input type="hidden" name="recipe_id" value="<?= (int) $r['id_rec'] ?>">
                          <button type="submit" name="action" value="delete_recipe_request">Delete…</button>
                        </form>

                      </li>
                    <?php endforeach; ?>
                  </ul>
                <?php endif; ?>

                <hr>
                <?php
                /* Categories for admin tools */
                $cats = ['type' => [], 'style' => [], 'diet' => []];

                $res = $db->query(
                "SELECT id_cat, group_cat, name_cat
                FROM category_cat
                ORDER BY group_cat ASC, name_cat ASC"
                );

                if ($res) {
                while ($row = $res->fetch_assoc()) {
                    $g = (string) ($row['group_cat'] ?? '');
                    if (isset($cats[$g])) {
                    $cats[$g][] = $row;
                    }
                }
                $res->free();
                }
                ?>

                <hr>

                <h3>Manage Categories</h3>

                <form method="post" action="includes/admin-actions-handler.php" class="admin-inline-form">
                <input type="hidden" name="action" value="category_create">

                <label>
                    Group
                    <select name="group_cat" required>
                    <option value="type">Type</option>
                    <option value="style">Style</option>
                    <option value="diet">Diet</option>
                    </select>
                </label>

                <label>
                    Name
                    <input type="text" name="name_cat" maxlength="60" required>
                </label>

                <button type="submit">Add</button>
                </form>

                <?php foreach ($cats as $group => $items): ?>
                <h4><?= h(ucfirst($group)) ?></h4>

                <?php if (empty($items)): ?>
                    <p>No categories.</p>
                <?php else: ?>
                    <ul>
                    <?php foreach ($items as $c): ?>
                        <li class="admin-recipe-row">
                        <form method="post" action="includes/admin-actions-handler.php" class="admin-inline-form">
                            <input type="hidden" name="action" value="category_rename">
                            <input type="hidden" name="id_cat" value="<?= (int) $c['id_cat'] ?>">

                            <input
                            type="text"
                            name="name_cat"
                            maxlength="60"
                            value="<?= h($c['name_cat']) ?>"
                            required
                            >
                            <button type="submit">Save</button>
                        </form>

                        <form method="post" action="includes/admin-actions-handler.php" class="admin-inline-form" onsubmit="return confirm('Delete this category?');">
                            <input type="hidden" name="action" value="category_delete">
                            <input type="hidden" name="id_cat" value="<?= (int) $c['id_cat'] ?>">
                            <button type="submit">Delete</button>
                        </form>
                        </li>
                    <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
                <?php endforeach; ?>

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