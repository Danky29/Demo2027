<?php
// Стартуем сессию для авторизации пользователей
session_start();

// Параметры подключения к базе данных (стандартные для OpenServer)
$DB_HOST = 'localhost'; // Хост базы данных
$DB_NAME = 'conf2027';  // Имя базы данных
$DB_USER = 'root';      // Логин пользователя БД
$DB_PASS = '';          // Пароль (пустой по умолчанию)

// Блок подключения к БД с обработкой ошибок
try {
    // Создаем объект PDO для подключения к MySQL
    $pdo = new PDO(
        "mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4", // Строка подключения (DSN)
        $DB_USER, // Имя пользователя
        $DB_PASS, // Пароль
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,      // Включаем режим исключений для ошибок
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, // Данные возвращаются в виде ассоциативного массива
            PDO::ATTR_EMULATE_PREPARES => false,              // Отключаем эмуляцию запросов (безопасность)
        ]
    );
} catch (PDOException $e) {
    // Если подключиться не удалось, останавливаем скрипт и выводим ошибку
    die('Ошибка подключения к БД: ' . $e->getMessage());
}

// Функция проверки, авторизован ли пользователь
function is_logged() {
    // Возвращает true, если в сессии есть user_id
    return isset($_SESSION['user_id']);
}

// Функция проверки, является ли пользователь администратором
function is_admin() {
    // Проверяем, что он вошел, и его роль равна 'admin'
    return is_logged() && isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// Функция безопасного вывода (защита от XSS-атак)
function h($text) {
    // Преобразуем опасные HTML-символы в безопасные сущности
    return htmlspecialchars((string)$text, ENT_QUOTES, 'UTF-8');
}
?>