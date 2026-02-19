<?php

/**
 * Centralized error definitions.
 *
 * Maps application error codes to display metadata used by error.php.
 * Each entry contains a title and user-facing message.
 */
$errors = [
  'login_required' => [
    'title'   => 'Access Restricted',
    'message' => 'You must be logged in to use this resource.'
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
    'title'   => 'Access Denied',
    'message' => 'You do not have permission to view this page.'
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