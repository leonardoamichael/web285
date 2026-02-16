<?php
function h($value): string {
  return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function post(string $key, $default = '') {
  return $_POST[$key] ?? $default;
}