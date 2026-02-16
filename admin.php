<?php require_once 'includes/initialize.php'; ?>
<?php include 'includes/header.php'; ?>

<div id="container">
  <main>
    <h1>Admin Panel</h1>

    <?php if (!isset($_SESSION['user_id'])): ?>
      <?php header('Location: error.php?code=login_required'); exit; ?>
    <?php endif; ?>

    <?php if ($_SESSION['role'] !== 'admin'): ?>
      <?php header('Location: error.php?code=access_denied'); exit; ?>
    <?php endif; ?>

    <?php
    if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    redirect_error('access_denied', 'index.php');
    }
    // after admin check
    $minutes = 15;

    $stmt = $db->prepare(
    "SELECT u.username, a.last_seen
    FROM active_users a
    JOIN users u ON u.id = a.user_id
    WHERE a.last_seen >= (NOW() - INTERVAL ? MINUTE)
    ORDER BY a.last_seen DESC"
    );
    $stmt->bind_param('i', $minutes);
    $stmt->execute();
    $result = $stmt->get_result();

    $active = [];
    while ($row = $result->fetch_assoc()) {
    $active[] = $row;
    }
    $count = count($active);
    ?>

    

    <h2>Active users (last <?= (int)$minutes ?> min): <?= $count ?></h2>
    <ul>
    <?php foreach ($active as $row): ?>
    <li><?= htmlspecialchars($row['username']) ?> (last seen: <?= htmlspecialchars($row['last_seen']) ?>)</li>
    <?php endforeach; ?>
    </ul>

    <?php $stmt->close(); ?>

    <p>Welcome, <?= htmlspecialchars($_SESSION['username']) ?>.</p>
    <p>Admin functionality coming soon.</p>
  </main>
</div>

<?php include 'includes/footer.php'; ?>