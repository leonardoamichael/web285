<?php

/**
 * Centralized error definitions.
 *
 * Maps application error codes to display metadata used by error.php.
 * Each entry contains a title and user-facing message.
 */
$errors = [
  'login_required' => [
    'title'   => 'Please Log In',
    'message' => 'Log in or create an account to submit a recipe.'
  ],

  'not_found' => [
    'title'   => 'Not Found',
    'message' => 'The requested page could not be found.'
  ],

  'login_failed' => [
    'title'   => 'Login Failed',
    'message' => 'Incorrect username/email or password. Please try again.'
  ],

  'access_denied' => [
    'title'   => 'Sorry, This Page Is Not Available',
    'message' => 'Your account does not have access to this page.'
  ],

];

/**
 * Redirect to the error handler.
 *
 * Sends the user to error.php with the provided error code and
 * optional return location.
 *
 * @param string $code Error code key from the $errors array
 * @param string $return Page to return to after error display
 * @return void
 */
function redirect_error($code, $return = 'index.php')
{
  header("Location: error.php?code={$code}&return={$return}");
  exit;
}

/**
 * Stop execution with a generic user-facing message and log details.
 *
 * Use this for unexpected/internal failures (DB connection, prepare errors, etc.)
 * to avoid leaking sensitive system information to the browser.
 *
 * @param string $log_message Detailed message for server logs
 * @param string $user_message Safe message shown to the user
 * @return void
 */
function internal_error(string $log_message, string $user_message = 'An internal error occurred. Please try again later.'): void
{
  error_log($log_message);
  http_response_code(500);
  die($user_message);
}