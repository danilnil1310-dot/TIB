<?php
session_start();

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'futsal_booking');
define('BASE_URL', '/futsal');

function db() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        die('Database connection failed: ' . $conn->connect_error);
    }
    $conn->set_charset('utf8');
    return $conn;
}

function escape($value) {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function check_login() {
    if (empty($_SESSION['user'])) {
        header('Location: ' . BASE_URL . '/index.php');
        exit;
    }
}

function is_admin() {
    return isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'admin';
}

function ensure_admin() {
    check_login();
    if (!is_admin()) {
        header('Location: ' . BASE_URL . '/index.php');
        exit;
    }
}

function ensure_user() {
    check_login();
    if (is_admin()) {
        header('Location: ' . BASE_URL . '/admin/index.php');
        exit;
    }
}
