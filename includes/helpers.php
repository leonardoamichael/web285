<?php

/**
 * Escape output for safe HTML rendering.
 *
 * Converts special characters to HTML entities to prevent XSS.
 * Safely handles null values by casting to string.
 *
 * @param mixed $value Value to escape
 * @return string Escaped string safe for HTML output
 */
function h($value): string
{
  return htmlspecialchars(
    (string) ($value ?? ''),
    ENT_QUOTES,
    'UTF-8'
  );
}

/**
 * Retrieve a POST value with optional default.
 *
 * Convenience helper for accessing form inputs safely.
 *
 * @param string $key POST field name
 * @param mixed $default Value returned if key is not set
 * @return mixed POST value or default
 */
function post(string $key, $default = '')
{
  return $_POST[$key] ?? $default;
}
