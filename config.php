<?php
/**
 * config.php — database connection, session start, and API key loading.
 * Every page includes this file first.
 */

session_start();

// ---- Database settings --------------------------------------------------
define('DB_HOST', 'localhost');
define('DB_NAME', 'haatkatha');
define('DB_USER', 'root');
define('DB_PASS', '');        // set your MySQL password here for XAMPP

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    die("Database connection failed. Make sure XAMPP's MySQL is running and "
        . "you've imported database/schema.sql. (" . $e->getMessage() . ")");
}

// ---- AI API key -----------------------------------------------------------
// Put your key in a file called `api_key.txt` in the project root (git-ignored),
// or set the GEMINI_API_KEY environment variable. Never commit a real key.
define('GEMINI_API_KEY', trim(
    getenv('GEMINI_API_KEY')
    ?: (file_exists(__DIR__ . '/api_key.txt') ? file_get_contents(__DIR__ . '/api_key.txt') : '')
));
define('GEMINI_MODEL_URL', 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent');

define('SITE_NAME', 'HaatKatha');
define('BASE_URL', '/haatkatha'); // change if you deploy to a different folder
