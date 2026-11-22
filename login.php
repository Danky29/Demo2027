<?php
// Подключаем конфигурацию
require_once 'config.php';

// Переменная для ошибки
$error = '';

// Если форма отправлена
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Берем логин и пароль
    $login = trim($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';

    // Проверка на пустоту
    if ($login === '' || $password === '') {
        $error = 'Введите логин и пароль.';
    } else {
        // Ищем пользователя в БД
        $stmt = $pdo->prepare("SELECT id, password, full_name, role FROM users WHERE login = ?");
        $stmt->execute([$login]);
        $user = $stmt->fetch();

        // Сверяем MD5-хеш пароля
        if ($user && $user['password'] === md5($password)) {
            // Сохраняем данные в сессию
            $_SESSION['user_id'] = (int)$user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['full_name'] = $user['full_name'];
            // Перенаправляем на главную
            header('Location: index.php');
            exit;
        } else {
            // Если не совпало — ошибка
            $error = 'Неверный логин или пароль.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Вход — Конференции РФ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-dark bg-primary mb-4">
        <div class="container"><a class="navbar-brand" href="index.php">Конференции РФ</a></div>
    </nav>
    <div class="container">
        <div class="card p-4 mx-auto" style="max-width: 500px;">
            <h1 class="text-center mb-4">Вход</h1>
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= h($error) ?></div>
            <?php endif; ?>
            <form method="post">
                <div class="mb-3"><label>Логин</label><input type="text" name="login" class="form-control" required></div>
                <div class="mb-3"><label>Пароль</label><input type="password" name="password" class="form-control" required></div>
                <button type="submit" class="btn btn-primary w-100">Войти</button>
            </form>
            <div class="mt-3 text-center"><a href="register.php">Регистрация</a> | <a href="index.php">На главную</a></div>
        </div>
    </div>
</body>
</html>