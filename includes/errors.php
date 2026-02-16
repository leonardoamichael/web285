<?php
$errors = [
  'login_required' => [
    'title' => 'Access Restricted',
    'message' => 'You must be logged in to use this resource.'
  ],
  'not_found' => [
    'title' => 'Not Found',
    'message' => 'The requested page could not be found.'
  ],
  'login_failed' => [
  'title' => 'Login Failed',
  'message' => 'Incorrect username/email or password. Please try again.'
],

'access_denied' => [
  'title' => 'Access Denied',
  'message' => 'You do not have permission to view this page.'
],
];

function redirect_error($code, $return = 'index.php') {
  header("Location: error.php?code={$code}&return={$return}");
  exit;
}