<?php
// Подключаем конфигурацию (БД, сессии, функции)
require_once 'config.php';

// Переменная для хранения текста ошибки
$error = '';

// Проверяем, была ли отправлена форма методом POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Получаем и очищаем от пробелов логин
    $login = trim($_POST['login'] ?? '');
    // Получаем пароль (не очищаем от пробелов, так как они могут быть в пароле)
    $password = $_POST['password'] ?? '';
    // Получаем и очищаем ФИО
    $full_name = trim($_POST['full_name'] ?? '');
    // Получаем и очищаем телефон
    $phone = trim($_POST['phone'] ?? '');
    // Получаем и очищаем email
    $email = trim($_POST['email'] ?? '');

    // ПРОВЕРКА 1: Все ли поля заполнены
    if ($login === '' || $password === '' || $full_name === '' || $phone === '' || $email === '') {
        $error = 'Пожалуйста, заполните все поля.';
    }
    // ПРОВЕРКА 2: Логин (только латиница и цифры, минимум 6 символов)
    elseif (!preg_match('/^[a-zA-Z0-9]{6,}$/', $login)) {
        $error = 'Логин должен содержать только латиницу и цифры (минимум 6 символов).';
    }
    // ПРОВЕРКА 3: Пароль (минимум 8 символов)
    elseif (strlen($password) < 8) {
        $error = 'Пароль должен быть минимум 8 символов.';
    }
    // ПРОВЕРКА 4: ФИО (только кириллица и пробелы)
    elseif (!preg_match('/^[а-яА-ЯёЁ\s]+$/u', $full_name)) {
        $error = 'ФИО должно содержать только кириллицу и пробелы.';
    }
    // ПРОВЕРКА 5: Телефон (формат 8XXXXXXXXXX или +7XXXXXXXXXX)
    elseif (!preg_match('/^(\+7|8)[0-9]{10}$/', $phone)) {
        $error = 'Телефон должен быть в формате 8XXXXXXXXXX.';
    }
    // ПРОВЕРКА 6: Email (стандартная проверка)
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Введите корректный адрес электронной почты.';
    }
    // Если все проверки пройдены, проверяем уникальность логина
    else {
        // Запрос на поиск пользователя с таким же логином
        $stmt = $pdo->prepare("SELECT id FROM users WHERE login = ?");
        $stmt->execute([$login]);

        // Если такой пользователь найден, выводим ошибку
        if ($stmt->fetch()) {
            $error = 'Пользователь с таким логином уже существует.';
        } else {
            // Хешируем пароль с помощью MD5 (для совместимости с текущей БД)
            $hash = md5($password);
            // Запрос на добавление нового пользователя
            $stmt2 = $pdo->prepare(
                "INSERT INTO users (login, password, full_name, phone, email, role)
                 VALUES (?, ?, ?, ?, ?, 'user')"
            );
            // Выполняем запрос с полученными данными
            $stmt2->execute([$login, $hash, $full_name, $phone, $email]);

            // После успешной регистрации перенаправляем на страницу входа
            header('Location: login.php');
            exit; // Останавливаем выполнение скрипта
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Регистрация — Конференции РФ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-dark bg-primary mb-4">
        <div class="container"><a class="navbar-brand" href="index.php">Конференции РФ</a></div>
    </nav>
    <div class="container">
        <div class="card p-4 mx-auto" style="max-width: 500px;">
            <h1 class="text-center mb-4">Регистрация</h1>
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= h($error) ?></div>
            <?php endif; ?>
            <form method="post">
                <div class="mb-3"><label>Логин</label><input type="text" name="login" class="form-control" pattern="^[a-zA-Z0-9]{6,}$" required title="Минимум 6 символов, только латиница и цифры"></div>
                <div class="mb-3"><label>Пароль</label><input type="password" name="password" class="form-control" minlength="8" required></div>
                <div class="mb-3"><label>ФИО</label><input type="text" name="full_name" class="form-control" pattern="^[а-яА-ЯёЁ\s]+$" required title="Только кириллица и пробелы"></div>
                <div class="mb-3"><label>Телефон</label><input type="text" name="phone" class="form-control" pattern="^(\+7|8)[0-9]{10}$" required title="Формат: 8XXXXXXXXXX или +7XXXXXXXXXX"></div>
                <div class="mb-3"><label>Email</label><input type="email" name="email" class="form-control" required></div>
                <button type="submit" class="btn btn-primary w-100">Зарегистрироваться</button>
            </form>
            <div class="mt-3 text-center"><a href="login.php">Уже есть аккаунт? Войти</a></div>
        </div>
    </div>
</body>
</html>