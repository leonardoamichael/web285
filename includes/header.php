<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <title>Recipe Share</title>

  <link rel="stylesheet" href="css/styles.css">
  <script src="js/scripts.js" defer></script>
</head>

<body>

<header>
</header>

<button id="menuToggle" aria-label="Toggle navigation">
  ☰ Menu
</button>

<nav id="mainNav">
  <ul>
    <li><a href="index.php">Home</a></li>
    <li><a href="recipes.php">Recipes</a></li>
    <li><a href="submit.php">Submit Recipe</a></li>
    <li><a href="about.php">About</a></li>
  </ul>

  <?php if (isset($_SESSION['user_id'])): ?>
    <div class="nav-right">

      <!-- Logged-in user → Profile -->
      <a class="nav-user" href="profile.php">
        <?= h($_SESSION['username']) ?>
      </a>

      <!-- Admin tools (role_id = 1) -->
      <?php if ((int) ($_SESSION['role_id'] ?? 0) === 1): ?>
        <a class="nav-admin" href="profile.php#admin-tools">
          Admin Tools
        </a>
      <?php endif; ?>

      <!-- Logout -->
      <a class="nav-logout" href="logout.php">Logout</a>
    </div>

  <?php else: ?>

    <!-- Guest navigation -->
    <button type="button" id="loginLink" class="loginLink">
      Login
    </button>

  <?php endif; ?>
</nav>