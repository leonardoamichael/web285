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

  /* Create account + auto-login */
  if (empty($form_errors)) {

    $new_id = create_user($db, $username, $email, $password);

    $role_id = 2; // member
    login_user($new_id, $username, $role_id);

    header('Location: profile.php');
    exit;
  }
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

      <button type="submit">Sign up</button>
    </form>

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