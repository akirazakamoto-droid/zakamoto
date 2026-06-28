<?php
// Copia questo file in config.php e inserisci i valori reali (config.php è in .gitignore).
define('IS_LOCAL', is_file(__DIR__ . '/.local'));

define('MAT_BASE', IS_LOCAL ? 'https://zakamoto.com/new/MAT/' : '/new/MAT/');
define('NEW_BASE', IS_LOCAL ? 'https://zakamoto.com/new/' : '/new/');

// Database principale
define('DB_DSN',  'mysql:host=YOUR_DB_HOST;dbname=YOUR_DB;charset=utf8mb4');
define('DB_USER', 'YOUR_DB_USER');
define('DB_PASS', 'YOUR_DB_PASSWORD');

// Database STUDIO/ATELIER
define('STUDIO_DSN',  'mysql:host=YOUR_HOST;port=6052;dbname=YOUR_STUDIO_DB;charset=utf8mb4');
define('STUDIO_USER', 'YOUR_STUDIO_USER');
define('STUDIO_PASS', 'YOUR_STUDIO_PASSWORD');
define('ATELIER_BASE', IS_LOCAL ? 'https://zakamoto.com/new/studio/imm/' : '/new/studio/imm/');

// SMTP (form Contact, inviti collezionisti)
define('SMTP_HOST', 'mail.example.com');
define('SMTP_USER', 'user@example.com');
define('SMTP_PASS', 'YOUR_SMTP_PASSWORD');
define('SMTP_PORT', 465);
define('SMTP_SECURE', 'ssl');
define('SMTP_FROM', 'user@example.com');
define('SMTP_FROM_NAME', 'zakamoto.com');
define('CONTACT_TO', 'you@example.com');

// Password area Galleries & Collectors
define('GALLERIES_PASS', 'CHANGE_ME');

// hCaptcha (form Contact)
define('HCAPTCHA_SITE',   '');
define('HCAPTCHA_SECRET', '');

// Cache-busting asset
define('ASSET_VER', '1');

date_default_timezone_set('Europe/Rome');
