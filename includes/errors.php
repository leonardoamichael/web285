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
];

function redirect_error($code, $return = 'index.php') {
  header("Location: error.php?code={$code}&return={$return}");
  exit;
}