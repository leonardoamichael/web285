# Recipe Share

Recipe Share is a PHP/MySQL web application developed as part of a Software & Web Development academic project.

The application demonstrates practical full-stack concepts including:

- PHP (procedural architecture)
- MySQL / MariaDB
- HTML5
- CSS3
- JavaScript

The project focuses on building a functional recipe sharing platform featuring user authentication, session management, role-based views, database-driven content, and progressive UI enhancements.

---

## Live Demo

A working version of the application can be viewed at:

**https://recipes.leonardomichael.com**

---

## Core Features

- User registration & authentication
- Secure password hashing
- Session management
- Role-based access control (admin / member)
- Recipe submission workflow
- Ingredient & step management
- Recipe moderation system
- Active user tracking
- Image uploads with validation
- Progressive reveal UI interactions

---

## ⚠️ Important Notice

This repository does **not** include the required database credentials file.

You must create your own configuration file:

`includes/db-credentials.php`

Example structure:

```php
<?php
define('DB_SERVER', 'localhost');
define('DB_USER', 'your_user');
define('DB_PASS', 'your_password');
define('DB_NAME', 'your_database');
```
