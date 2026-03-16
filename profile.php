<?php
require_once 'includes/initialize.php';

/* Authentication */
if (!isset($_SESSION['user_id'])) {
    redirect_error('login_required', 'index.php');
}

include 'includes/header.php';

/* Role / permissions */
$is_admin = is_admin_access();
$is_super_admin = is_super_admin();

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

/* Categories for admin tools */
$cats = ['type' => [], 'style' => [], 'diet' => []];

if ($is_admin) {
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
}
?>

<div id="container">
    <main class="profile-page">

        <header class="profile-hero">
            <div class="profile-hero-copy">
                <h1>Your Profile</h1>
                <p class="profile-welcome">
                    Welcome, <strong><?= h($_SESSION['username']) ?></strong>.
                </p>
                <p class="profile-intro">
                    Manage your recipes, review account activity, and access tools available to your role.
                </p>
            </div>

            <nav class="profile-nav" aria-label="Profile sections">
                <a class="profile-nav-link" href="#recipes">My Recipes</a>
                <a class="profile-nav-link" href="#stats">My Stats</a>

                <?php if ($is_admin): ?>
                    <a class="profile-nav-link" href="#admin-tools">Admin Tools</a>
                    <a class="profile-nav-link profile-nav-link--accent" href="admin.php">Open Admin Panel →</a>
                <?php endif; ?>
            </nav>
        </header>

        <section id="recipes" class="profile-section">
            <div class="profile-section-heading">
                <h2>My Recipes</h2>
                <p>View the recipes you’ve submitted and check their current status.</p>
            </div>

            <?php if (empty($my_recipes)): ?>
                <div class="profile-empty-state">
                    <p>You haven’t submitted any recipes yet.</p>
                </div>
            <?php else: ?>
                <ul class="profile-list profile-list--recipes">
                    <?php foreach ($my_recipes as $r): ?>
                        <li class="profile-item profile-item--recipe">
                            <div class="profile-item-main">
                                <a class="profile-item-title" href="recipe.php?id=<?= (int) $r['id_rec'] ?>">
                                    <?= h($r['title_rec']) ?>
                                </a>

                                <div class="profile-item-meta">
                                    <span class="profile-status profile-status--<?= h($r['status_rec']) ?>">
                                        <?= h(ucfirst($r['status_rec'])) ?>
                                    </span>

                                    <?php if (!empty($r['created_at_rec'])): ?>
                                        <span class="profile-item-date">
                                            Submitted <?= h($r['created_at_rec']) ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="profile-item-actions">
                                <a class="profile-item-link" href="recipe.php?id=<?= (int) $r['id_rec'] ?>">
                                    View Recipe
                                </a>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>

        <section id="stats" class="profile-section">
            <div class="profile-section-heading">
                <h2>My Stats</h2>
                <p>A quick overview of your account activity and recipe participation.</p>
            </div>

            <div class="profile-empty-state">
                <p>Placeholder: stats like total recipes, favorites, last login, and ratings can go here later.</p>
            </div>
        </section>

       <?php if ($is_admin): ?>
    <section id="admin-tools" class="profile-section profile-section--admin">
        <div class="profile-section-heading">
            <h2>Admin Tools</h2>
            <p>Moderate submitted content, manage categories, and review site activity.</p>
        </div>

        <?php if ($admin_error !== ''): ?>
            <div class="profile-message profile-message--error" role="alert">
                <strong><?= h($admin_error) ?></strong>
            </div>
        <?php endif; ?>

        <?php if ($admin_message !== ''): ?>
            <div class="profile-message profile-message--success" role="status">
                <strong><?= h($admin_message) ?></strong>
            </div>
        <?php endif; ?>

        <?php if ($show_delete_confirm): ?>
            <div class="admin-confirm">
                <h3 class="admin-confirm-title">Confirm delete recipe</h3>

                <p>
                    You are about to permanently delete recipe ID
                    <strong><?= (int) $confirm['recipe_id'] ?></strong>.
                    This will remove its steps, ingredients, images, and uploaded files.
                </p>

                <div class="profile-action-row">
                    <form
                        method="post"
                        action="includes/admin-actions-handler.php"
                        class="admin-confirm-form"
                    >
                        <input type="hidden" name="action" value="delete_recipe_confirm">
                        <input type="hidden" name="recipe_id" value="<?= (int) $confirm['recipe_id'] ?>">
                        <input type="hidden" name="confirm_token" value="<?= h((string) $confirm['token']) ?>">
                        <button type="submit" class="profile-btn profile-btn--danger">Yes — delete</button>
                    </form>

                    <a href="profile.php?confirm_cancel=1#admin-tools">Cancel</a>
                </div>
            </div>
        <?php endif; ?>

        <details class="profile-subsection profile-subsection--accordion" open>
            <summary class="profile-subsection-summary">
                <div>
                    <h3>Pending Recipes</h3>
                    <p><?= count($pending) ?> awaiting review.</p>
                </div>
            </summary>

            <div class="profile-subsection-body">
                <?php if (empty($pending)): ?>
                    <p>No recipes pending approval.</p>
                <?php else: ?>
                    <ul class="profile-list profile-list--admin">
                        <?php foreach ($pending as $r): ?>
                            <li class="profile-item profile-item--admin admin-recipe-row">
                                <div class="profile-item-main">
                                    <a class="profile-item-title" href="recipe.php?id=<?= (int) $r['id_rec'] ?>">
                                        <?= h($r['title_rec']) ?>
                                    </a>

                                    <div class="profile-item-meta">
                                        <span>By <?= h($r['username_usr']) ?></span>

                                        <?php if (!empty($r['created_at_rec'])): ?>
                                            <span>Submitted <?= h($r['created_at_rec']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <form
                                    method="post"
                                    action="includes/admin-actions-handler.php"
                                    class="profile-item-actions admin-inline-form"
                                >
                                    <input type="hidden" name="recipe_id" value="<?= (int) $r['id_rec'] ?>">

                                    <button type="submit" name="action" value="approve" class="profile-btn profile-btn--success">Approve</button>
                                    <button type="submit" name="action" value="reject" class="profile-btn profile-btn--secondary">Reject</button>
                                    <button type="submit" name="action" value="delete_recipe_request" class="profile-btn profile-btn--danger">Delete…</button>
                                </form>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </details>

        <details class="profile-subsection profile-subsection--accordion">
            <summary class="profile-subsection-summary">
                <div>
                    <h3>Approved Recipes</h3>
                    <p><?= count($approved) ?> currently approved.</p>
                </div>
            </summary>

            <div class="profile-subsection-body">
                <?php if (empty($approved)): ?>
                    <p>No approved recipes found.</p>
                <?php else: ?>
                    <ul class="profile-list profile-list--admin">
                        <?php foreach ($approved as $r): ?>
                            <li class="profile-item profile-item--admin admin-recipe-row">
                                <div class="profile-item-main">
                                    <a class="profile-item-title" href="recipe.php?id=<?= (int) $r['id_rec'] ?>">
                                        <?= h($r['title_rec']) ?>
                                    </a>

                                    <div class="profile-item-meta">
                                        <span>By <?= h($r['username_usr']) ?></span>

                                        <?php if (!empty($r['created_at_rec'])): ?>
                                            <span>Submitted <?= h($r['created_at_rec']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <form
                                    method="post"
                                    action="includes/admin-actions-handler.php"
                                    class="profile-item-actions admin-inline-form"
                                >
                                    <input type="hidden" name="recipe_id" value="<?= (int) $r['id_rec'] ?>">
                                    <button type="submit" name="action" value="delete_recipe_request" class="profile-btn profile-btn--danger">Delete…</button>
                                </form>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </details>

        <details class="profile-subsection profile-subsection--accordion">
            <summary class="profile-subsection-summary">
                <div>
                    <h3>Manage Categories</h3>
                    <p>Create, rename, or remove recipe categories.</p>
                </div>
            </summary>

            <div class="profile-subsection-body">
                <form method="post" action="includes/admin-actions-handler.php" class="profile-form-row">
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

                    <button type="submit" class="profile-btn profile-btn--accent">Add</button>
                </form>

                <?php foreach ($cats as $group => $items): ?>
                    <details class="profile-category-group" <?= $group === 'type' ? 'open' : '' ?>>
                        <summary class="profile-category-summary">
                            <span class="profile-category-title"><?= h(ucfirst($group)) ?></span>
                            <span class="profile-category-count"><?= count($items) ?></span>
                        </summary>

                        <div class="profile-category-body">
                            <?php if (empty($items)): ?>
                                <p>No categories.</p>
                            <?php else: ?>
                                <ul class="profile-list profile-list--categories">
                                    <?php foreach ($items as $c): ?>
                                        <li class="profile-item profile-item--category admin-recipe-row">
                                            <form method="post" action="includes/admin-actions-handler.php" class="profile-item-main admin-inline-form">
                                                <input type="hidden" name="action" value="category_rename">
                                                <input type="hidden" name="id_cat" value="<?= (int) $c['id_cat'] ?>">

                                                <input
                                                    type="text"
                                                    name="name_cat"
                                                    maxlength="60"
                                                    value="<?= h($c['name_cat']) ?>"
                                                    required
                                                >
                                                <button type="submit" class="profile-btn profile-btn--secondary">Save</button>
                                            </form>

                                            <form
                                                method="post"
                                                action="includes/admin-actions-handler.php"
                                                class="profile-item-actions admin-inline-form"
                                                onsubmit="return confirm('Delete this category?');"
                                            >
                                                <input type="hidden" name="action" value="category_delete">
                                                <input type="hidden" name="id_cat" value="<?= (int) $c['id_cat'] ?>">
                                                <button type="submit" class="profile-btn profile-btn--danger">Delete</button>
                                            </form>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </div>
                    </details>
                <?php endforeach; ?>
            </div>
        </details>

        <details class="profile-subsection profile-subsection--accordion">
            <summary class="profile-subsection-summary">
                <div>
                    <h3>Active Users</h3>
                    <p><?= count($active) ?> active in the last 15 minutes.</p>
                </div>
            </summary>

            <div class="profile-subsection-body">
                <?php if (empty($active)): ?>
                    <p>No active users right now.</p>
                <?php else: ?>
                    <ul class="profile-list profile-list--activity">
                        <?php foreach ($active as $row): ?>
                            <li class="profile-item profile-item--activity">
                                <div class="profile-item-main">
                                    <span class="profile-item-title">
                                        <?= h($row['username_usr']) ?>
                                    </span>

                                    <div class="profile-item-meta">
                                        <span>Last seen: <?= h($row['last_seen_act']) ?></span>
                                    </div>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </details>

        <?php if ($is_super_admin): ?>
            <details class="profile-subsection profile-subsection--accordion profile-subsection--super-admin">
                <summary class="profile-subsection-summary">
                    <div>
                        <h3>Super Admin Tools</h3>
                        <p>User management and elevated admin controls.</p>
                    </div>
                </summary>

                <div class="profile-subsection-body">
                    <?php include 'includes/super-admin-tools.php'; ?>
                </div>
            </details>
        <?php endif; ?>
    </section>
<?php endif; ?>

    </main>
</div>

<?php include 'includes/login-modal.php'; ?>
<?php include 'includes/footer.php'; ?>