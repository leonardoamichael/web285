# Recipe Share

Recipe Share is a PHP/MySQL web application developed as part of a Software & Web Development academic project.

The application demonstrates practical full-stack concepts including:

- PHP (procedural architecture)
- MySQL / MariaDB
- HTML5
- CSS3
- JavaScript

The project focuses on building a functional recipe sharing platform featuring authentication, role-based access control, database-driven content, and progressive UI improvements.

---

## Live Demo

A working version of the application can be viewed at:

**https://recipes.leonardomichael.com**

---

## Core Features

### User System

- User registration and authentication
- Secure password hashing
- Session management
- Role-based access control (super_admin / admin / member)

### Recipe System

- Recipe submission workflow
- Ingredient and unit management
- Step-by-step directions
- Image uploads with validation
- Optional YouTube video embedding
- Recipe editing (while pending approval)

### Community Features

- Recipe rating system
- Featured "Top Rated" recipes
- Category filtering (Type / Style / Diet)
- Dynamic recipe listings

### Administration

- Recipe moderation system
- Admin tools for managing recipes
- Super admin user role management
- Active user tracking

### UI / UX

- Homepage hero section
- Recipe card system with rating badges
- Category splash badges
- Responsive layout
- Progressive reveal UI interactions

---

## ⚠️ Database Configuration

This repository does **not** include the required database credentials file.

You must create your own configuration file:

`includes/db_credentials.php`

Example structure:

```php
<?php
// includes/db_credentials.php

// Local development
define('DB_SERVER', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'recipe_share');
define('DB_PORT', 3306);

// Live / production
define('DB_SERVER_LIVE', 'localhost');
define('DB_USER_LIVE', 'YOUR_CPANEL_DB_USER');
define('DB_PASS_LIVE', 'YOUR_CPANEL_DB_PASSWORD');
define('DB_NAME_LIVE', 'YOUR_CPANEL_DB_NAME');
define('DB_PORT_LIVE', 3306);
```
