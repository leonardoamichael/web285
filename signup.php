<?php
require_once 'includes/initialize.php';
include 'includes/header.php';

/* Form state */
$form_errors = [];

$username = trim((string) post('username'));
$email    = strtolower(trim((string) post('email')));

/* Form submission */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  $password = (string) post('password');
  $confirm  = (string) post('confirm_password');

  /* Validation */

  if ($username === '' || !preg_match('/^[A-Za-z0-9_]{3,50}$/', $username)) {
    $form_errors['username'] =
      'Username must be 3–50 characters (letters, numbers, underscore).';
  }

  if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $form_errors['email'] = 'Please enter a valid email address.';
  }

  if ($password === '' || strlen($password) < 8) {
    $form_errors['password'] = 'Password must be at least 8 characters.';
  }

  if ($confirm === '' || $password !== $confirm) {
    $form_errors['confirm_password'] = 'Passwords do not match.';
  }

  /* Uniqueness checks */
  if (
    empty($form_errors) &&
    username_or_email_exists($db, $username, $email)
  ) {
    $form_errors['general'] =
      'That username or email is already in use.';
  }

/* Verify Cloudflare Turnstile */
if (empty($form_errors)) {
  $turnstile_token = $_POST['cf-turnstile-response'] ?? '';

  $turnstile_secret = 'secret-key-goes-here';

  $verify_response = file_get_contents(
    'https://challenges.cloudflare.com/turnstile/v0/siteverify',
    false,
    stream_context_create([
      'http' => [
        'method'  => 'POST',
        'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
        'content' => http_build_query([
          'secret'   => $turnstile_secret,
          'response' => $turnstile_token,
          'remoteip' => $_SERVER['REMOTE_ADDR'] ?? '',
        ]),
      ],
    ])
  );

  $turnstile_ok = false;

  if ($verify_response !== false) {
    $verify_data = json_decode($verify_response, true);
    $turnstile_ok = !empty($verify_data['success']);
  }

  if (!$turnstile_ok) {
    $form_errors['general'] = 'Please complete the security check and try again.';
  }
}

/* Create account + auto-login */
if (empty($form_errors)) {

  $new_id = create_user($db, $username, $email, $password);

  $role_id = 2; // member
  login_user($new_id, $username, $role_id);

  header('Location: profile.php');
  exit; }
}
?>

<div id="container">
  <main>

    <h1>Create an account</h1>

    <?php if (!empty($form_errors['general'])): ?>
      <p role="alert">
        <strong><?= h($form_errors['general']) ?></strong>
      </p>
    <?php endif; ?>

    <form
      method="post"
      action="signup.php"
      class="signup-form"
      novalidate
    >

      <label for="username">Username</label>

      <input
        id="username"
        name="username"
        type="text"
        required
        minlength="3"
        maxlength="50"
        pattern="[A-Za-z0-9_]{3,50}"
        value="<?= h($username) ?>"
      >

      <?php if (!empty($form_errors['username'])): ?>
        <p role="alert"><?= h($form_errors['username']) ?></p>
      <?php endif; ?>

      <label for="email">Email</label>

      <input
        id="email"
        name="email"
        type="email"
        required
        maxlength="255"
        value="<?= h($email) ?>"
      >

      <?php if (!empty($form_errors['email'])): ?>
        <p role="alert"><?= h($form_errors['email']) ?></p>
      <?php endif; ?>

      <label for="password">Password</label>

      <input
        id="password"
        name="password"
        type="password"
        required
        minlength="8"
      >

      <?php if (!empty($form_errors['password'])): ?>
        <p role="alert"><?= h($form_errors['password']) ?></p>
      <?php endif; ?>

      <label for="confirm_password">Confirm Password</label>

      <input
        id="confirm_password"
        name="confirm_password"
        type="password"
        required
        minlength="8"
      >

      <?php if (!empty($form_errors['confirm_password'])): ?>
        <p role="alert"><?= h($form_errors['confirm_password']) ?></p>
      <?php endif; ?>

    <div
      class="cf-turnstile"
      data-sitekey="0x4AAAAAADJEQWNI4j0C89aq"
    ></div>
      <button type="submit">Sign up</button>
    </form>

    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>   

    <p class="login-helper">
      Already have an account?
      <button
        type="button"
        id="loginLink"
        class="loginLink"
      >
        Log in
      </button>
    </p>

  </main>
</div>

<?php include 'includes/login-modal.php'; ?>
<?php include 'includes/footer.php'; ?>