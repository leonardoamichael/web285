<div id="loginModal" class="modal" aria-hidden="true">

  <div class="modal-backdrop" data-close="true"></div>

  <div class="modal-panel" role="dialog" aria-modal="true" aria-labelledby="loginTitle">

    <button class="modal-close" type="button" aria-label="Close login" data-close="true">
      ×
    </button>

    <h2 id="loginTitle">Login</h2>

    <form class="login-form" method="post" action="login.php">

      <label for="username">Username</label>
      <input id="username" name="username" type="text" required />

      <label for="password">Password</label>
      <input id="password" name="password" type="password" required />

      <button type="submit">Log in</button>

      <p class="login-helper">
        New here? <a href="signup.php">Create an account</a>
      </p>

    </form>

  </div>
</div>