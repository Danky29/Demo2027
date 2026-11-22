<?php
// Подключаем конфиг
require_once 'config.php';

// Если не админ — перенаправляем на главную
if (!is_admin()) {
    header('Location: index.php?msg=denied');
    exit;
}

// Переменные для сообщений
$info = '';
$error = '';

// Разрешенные типы залов
$allowed_types = ['Аудитория', 'Коворкинг', 'Кинозал'];

// ----- Обработка смены статуса заявки -----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_status'])) {
    $req_id = (int)($_POST['req_id'] ?? 0);
    $status = $_POST['status'] ?? '';
    $allowed_statuses = ['Новая', 'Мероприятие назначено', 'Завершено'];

    if ($req_id > 0 && in_array($status, $allowed_statuses, true)) {
        $stmt = $pdo->prepare("UPDATE requests SET status = ? WHERE id = ?");
        $stmt->execute([$status, $req_id]);
        $info = 'Статус обновлен.';
    } else {
        $error = 'Некорректный статус.';
    }
}

// ----- Сохранение изменений зала -----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_room'])) {
    $room_id = (int)($_POST['room_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $type = $_POST['type'] ?? '';
    $description = trim($_POST['description'] ?? '');
    $price = (float)str_replace(',', '.', $_POST['price'] ?? '0');
    $image = trim($_POST['image'] ?? '');
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    if ($room_id <= 0) $error = 'Не выбран зал.';
    elseif ($title === '') $error = 'Название не может быть пустым.';
    elseif (!in_array($type, $allowed_types, true)) $error = 'Неверный тип.';
    elseif ($price < 0) $error = 'Цена не может быть отрицательной.';
    else {
        $stmt = $pdo->prepare("UPDATE rooms SET title = ?, type = ?, description = ?, price = ?, image = ?, is_active = ? WHERE id = ?");
        $stmt->execute([$title, $type, $description, $price, $image, $is_active, $room_id]);
        $info = 'Зал обновлен.';
    }
}

// ----- Удаление зала -----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_room'])) {
    $room_id = (int)($_POST['room_id'] ?? 0);
    if ($room_id > 0) {
        $stmt = $pdo->prepare("DELETE FROM rooms WHERE id = ?");
        $stmt->execute([$room_id]);
        $info = 'Зал удален.';
    }
}

// Получаем все заявки с отзывами
$sql = "SELECT r.id, r.req_date, r.payment, r.status, rev.text AS review, rev.rating, u.login AS user_login, u.full_name AS user_name, rm.title AS room_title
        FROM requests r
        JOIN users u ON u.id = r.user_id
        JOIN rooms rm ON rm.id = r.room_id
        LEFT JOIN reviews rev ON rev.request_id = r.id
        ORDER BY r.id DESC";
$requests = $pdo->query($sql)->fetchAll();

// Получаем все залы
$rooms = $pdo->query("SELECT id, title, type, description, price, image, is_active FROM rooms ORDER BY id")->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Админ-панель — Конференции РФ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-dark bg-primary mb-4">
        <div class="container">
            <a class="navbar-brand" href="index.php">Конференции РФ</a>
            <div class="ms-auto d-flex gap-2">
                <a href="index.php" class="btn btn-light btn-sm">Главная</a>
                <a href="logout.php" class="btn btn-outline-light btn-sm">Выйти</a>
            </div>
        </div>
    </nav>

    <div class="container pb-5">
        <h1 class="text-center mb-4">Админ-панель</h1>
        <?php if ($info): ?><div class="alert alert-success"><?= h($info) ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-danger"><?= h($error) ?></div><?php endif; ?>

        <h2 class="mt-4 mb-3 text-center">Залы</h2>
        <div class="accordion mb-5" id="roomsAccordion">
            <?php foreach ($rooms as $room): ?>
                <div class="accordion-item mb-2">
                    <h3 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#room<?= $room['id'] ?>">
                            #<?= $room['id'] ?> — <?= h($room['title']) ?> (<?= h($room['type']) ?>) — <?= h($room['is_active'] ? 'Активен' : 'Скрыт') ?>
                        </button>
                    </h3>
                    <div id="room<?= $room['id'] ?>" class="accordion-collapse collapse">
                        <div class="accordion-body">
                            <form method="post" class="row g-3">
                                <input type="hidden" name="room_id" value="<?= $room['id'] ?>">
                                <div class="col-md-6"><label>Название</label><input type="text" name="title" class="form-control" value="<?= h($room['title']) ?>" required></div>
                                <div class="col-md-3"><label>Тип</label>
                                    <select name="type" class="form-select">
                                        <?php foreach ($allowed_types as $t): ?>
                                            <option value="<?= h($t) ?>" <?= $room['type'] === $t ? 'selected' : '' ?>><?= h($t) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3"><label>Цена</label><input type="number" step="0.01" min="0" name="price" class="form-control" value="<?= h($room['price']) ?>"></div>
                                <div class="col-12"><label>Картинка</label><input type="text" name="image" class="form-control" value="<?= h($room['image']) ?>"></div>
                                <div class="col-12"><label>Описание</label><textarea name="description" class="form-control" rows="3"><?= h($room['description']) ?></textarea></div>
                                <div class="col-12"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="is_active" value="1" <?= $room['is_active'] ? 'checked' : '' ?>><label class="form-check-label">Активен</label></div></div>
                                <div class="col-12"><button type="submit" name="save_room" class="btn btn-primary">Сохранить</button></div>
                            </form>
                            <form method="post" class="mt-2" onsubmit="return confirm('Удалить зал?')">
                                <input type="hidden" name="room_id" value="<?= $room['id'] ?>">
                                <button type="submit" name="delete_room" class="btn btn-danger btn-sm">Удалить зал</button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <h2 class="mt-5 mb-3 text-center">Все заявки</h2>
        <div class="table-responsive">
            <table class="table table-striped">
                <thead class="table-primary"><tr><th>ID</th><th>Пользователь</th><th>Помещение</th><th>Дата</th><th>Оплата</th><th>Статус</th><th>Отзыв</th><th>Действие</th></tr></thead>
                <tbody>
                    <?php foreach ($requests as $r): ?>
                        <tr>
                            <td><?= (int)$r['id'] ?></td>
                            <td><?= h($r['user_login']) ?> (<?= h($r['user_name']) ?>)</td>
                            <td><?= h($r['room_title']) ?></td>
                            <td><?= h($r['req_date']) ?></td>
                            <td><?= h($r['payment']) ?></td>
                            <td><?= h($r['status']) ?></td>
                            <td><?= $r['review'] ? h($r['review']) . ' (Оценка: ' . (int)$r['rating'] . ')' : 'Нет отзыва' ?></td>
                            <td>
                                <form method="post" class="d-flex gap-2">
                                    <input type="hidden" name="req_id" value="<?= $r['id'] ?>">
                                    <select name="status" class="form-select form-select-sm">
                                        <option value="Новая" <?= $r['status'] === 'Новая' ? 'selected' : '' ?>>Новая</option>
                                        <option value="Мероприятие назначено" <?= $r['status'] === 'Мероприятие назначено' ? 'selected' : '' ?>>Мероприятие назначено</option>
                                        <option value="Завершено" <?= $r['status'] === 'Завершено' ? 'selected' : '' ?>>Завершено</option>
                                    </select>
                                    <button type="submit" name="save_status" class="btn btn-primary btn-sm">Сохранить</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>