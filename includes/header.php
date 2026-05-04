<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <title>Recipe Share</title>

  <link rel="stylesheet" href="css/app.css">
  <script type="module" src="js/app.js"></script>
</head>

<body>

<header class="site-header">
  <div class="site-header-inner">

    <!-- Site Logo / Branding -->
    <div class="site-branding">
    <a href="index.php" class="site-logo">
      <span aria-hidden="true">🍽️</span>
      Recipe Share
    </a>
    </div>

    <!-- Mobile Menu Toggle -->
    <button id="menuToggle"
            aria-label="Toggle navigation"
            aria-expanded="false"
            aria-controls="mainNav">
      ☰ Menu
    </button>

    <!-- Navigation -->
    <nav id="mainNav">

      <ul class="nav-links">
        <li><a href="index.php">Home</a></li>
        <li><a href="recipes.php">Recipes</a></li>
        <li><a href="submit.php">Submit Recipe</a></li>
        <li><a href="about.php">About</a></li>
      </ul>

      <?php if (isset($_SESSION['user_id'])): ?>
        <div class="nav-right">

          <!-- Logged-in user profile -->
          <a class="nav-user" href="profile.php">Profile</a>

          <!-- Admin tools -->
          <?php if (is_admin_access()): ?>
            <a class="nav-admin" href="profile.php#admin-tools">
              Admin Tools
            </a>
          <?php endif; ?>

          <!-- Logout -->
        <a class="nav-logout" href="logout.php">
          Logout (<?= h($_SESSION['username']) ?>)
        </a>

        </div>

      <?php else: ?>

        <!-- Guest login -->
        <button type="button" id="loginLink" class="loginLink">
          Login
        </button>

      <?php endif; ?>

    </nav>

  </div>
</header>