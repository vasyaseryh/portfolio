<?php
session_start();

// Конфигурация базы данных
define('DB_HOST', 'localhost');
define('DB_NAME', 'quizmaster');
define('DB_USER', 'root');
define('DB_PASS', '');

// Подключение к БД
function getDB() {
    static $db = null;
    if ($db === null) {
        try {
            $db = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8",
                DB_USER,
                DB_PASS,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
        } catch(PDOException $e) {
            die("Ошибка подключения к БД: " . $e->getMessage());
        }
    }
    return $db;
}

// Проверка авторизации
function checkAuth() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: /auth.php');
        exit();
    }
}

// Проверка роли
function checkRole($roles) {
    checkAuth();
    if (!in_array($_SESSION['role'], (array)$roles)) {
        die('Доступ запрещен');
    }
}

// Функции безопасности
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

// Редирект
function redirect($url) {
    header("Location: $url");
    exit();
}
?>