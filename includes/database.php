<?php
// includes/database.php

/**
 * Establish a database connection.
 *
 * Creates a new MySQLi connection using configured constants and
 * applies the utf8mb4 character set for full Unicode support.
 *
 * @return mysqli Active database connection
 */
function db_connect(): mysqli
{
  $db = new mysqli(DB_SERVER, DB_USER, DB_PASS, DB_NAME);

  if ($db->connect_errno) {
    die("Database connection failed: " . $db->connect_error);
  }

  $db->set_charset('utf8mb4');

  return $db;
}