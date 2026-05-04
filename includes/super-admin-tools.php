<?php
if (!isset($db) || !is_super_admin()) {
  return;
}

$users = [];

$stmt = $db->prepare(
  "SELECT u.id_usr,
          u.username_usr,
          u.email_usr,
          u.id_rol_usr,
          u.admin_active_usr,
          r.name_rol
   FROM user_usr u
   JOIN role_rol r ON r.id_rol = u.id_rol_usr
   ORDER BY u.username_usr ASC"
);

$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
  $users[] = $row;
}

$stmt->close();

$current_user_id = (int) ($_SESSION['user_id'] ?? 0);
?>

<hr>

<section id="super-admin-tools" class="tab-panel">
  <h3>User Management</h3>

  <p>
    Super admins can promote members to admin and demote admins back to member.
    Super admin role changes must be done directly in the database.
  </p>

  <?php if (empty($users)): ?>
    <p>No users found.</p>
  <?php else: ?>
    <ul>
      <?php foreach ($users as $user): ?>
        <?php
        $target_user_id = (int) $user['id_usr'];
        $target_role_id = (int) $user['id_rol_usr'];
        $is_self = ($target_user_id === $current_user_id);
        $admin_active = (int) ($user['admin_active_usr'] ?? 1);
        ?>

        <li class="admin-recipe-row">
          <div>
            <strong><?= h($user['username_usr']) ?></strong>
            — <?= h($user['email_usr']) ?>
            <small>
              (<?= h($user['name_rol']) ?><?php if ($target_role_id === ROLE_ADMIN): ?>,
                <?= $admin_active === 1 ? 'active' : 'inactive' ?>
              <?php endif; ?>)
            </small>
          </div>

          <div>
            <?php if ($is_self): ?>
              <span>Current account</span>

            <?php elseif ($target_role_id === ROLE_MEMBER): ?>
              <form
                method="post"
                action="includes/admin-actions-handler.php"
                class="admin-inline-form"
              >
                <input type="hidden" name="action" value="promote_user">
                <input type="hidden" name="user_id" value="<?= $target_user_id ?>">
                <button type="submit">Promote to Admin</button>
              </form>

          <?php elseif ($target_role_id === ROLE_ADMIN): ?>

            <?php if ($admin_active === 1): ?>
              <form
                method="post"
                action="includes/admin-actions-handler.php"
                class="admin-inline-form"
              >
                <input type="hidden" name="action" value="deactivate_admin">
                <input type="hidden" name="user_id" value="<?= $target_user_id ?>">
                <button type="submit">Deactivate Admin</button>
              </form>
            <?php else: ?>
              <form
                method="post"
                action="includes/admin-actions-handler.php"
                class="admin-inline-form"
              >
                <input type="hidden" name="action" value="reactivate_admin">
                <input type="hidden" name="user_id" value="<?= $target_user_id ?>">
                <button type="submit">Reactivate Admin</button>
              </form>
            <?php endif; ?>

            <form
              method="post"
              action="includes/admin-actions-handler.php"
              class="admin-inline-form"
            >
              <input type="hidden" name="action" value="demote_user">
              <input type="hidden" name="user_id" value="<?= $target_user_id ?>">
              <button type="submit">Demote to Member</button>
            </form>

            <?php elseif ($target_role_id === ROLE_SUPER_ADMIN): ?>
              <span>Super Admin (DB only)</span>

            <?php else: ?>
              <span>No action available</span>
            <?php endif; ?>
          </div>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>
</section>