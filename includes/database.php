<?php
// includes/database.php

function db_connect(): mysqli {
  $db = new mysqli(DB_SERVER, DB_USER, DB_PASS, DB_NAME);

  if ($db->connect_errno) {
    die("Database connection failed: " . $db->connect_error);
  }

  $db->set_charset('utf8mb4');
  return $db;
}