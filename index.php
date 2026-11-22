<?php
// Подключаем конфигурацию
require_once 'config.php';

// Переменные для сообщений
$info = '';
$error = '';

// Если пользователь вошел, берем его ID
$user_id = is_logged() ? (int)$_SESSION['user_id'] : 0;

// Обработка POST-запросов
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // ----- Создание заявки -----
    if (isset($_POST['create_request'])) {
        // Проверка на авторизацию
        if (!is_logged()) {
            $error = 'Войдите, чтобы оставить заявку.';
        } else {
            // Получаем данные из формы
            $room_id = (int)($_POST['room_id'] ?? 0);
            $req_date = $_POST['req_date'] ?? '';
            $payment = $_POST['payment'] ?? '';
            $allowed_payments = ['Очное посещение', 'СБП'];

            // ПРОВЕРКА: все поля заполнены корректно
            if ($room_id <= 0 || $req_date === '' || !in_array($payment, $allowed_payments, true)) {
                $error = 'Заполните все поля корректно.';
            } else {
                // Проверяем, что зал существует и активен
                $check = $pdo->prepare("SELECT id FROM rooms WHERE id = ? AND is_active = 1");
                $check->execute([$room_id]);

                if (!$check->fetch()) {
                    $error = 'Этот зал недоступен для бронирования.';
                } else {
                    // Создаем заявку
                    $stmt = $pdo->prepare("INSERT INTO requests (user_id, room_id, req_date, payment, status) VALUES (?, ?, ?, ?, 'Новая')");
                    $stmt->execute([$user_id, $room_id, $req_date, $payment]);
                    $info = 'Заявка успешно отправлена.';
                }
            }
        }
    }

    // ----- Сохранение отзыва -----
    if (isset($_POST['save_review']) && is_logged()) {
        // Получаем данные
        $req_id = (int)($_POST['req_id'] ?? 0);
        $review = trim($_POST['review'] ?? '');
        $rating = (int)($_POST['rating'] ?? 5);

        // Проверяем, что заявка принадлежит пользователю
        $stmt = $pdo->prepare("SELECT id FROM requests WHERE id = ? AND user_id = ?");
        $stmt->execute([$req_id, $user_id]);

        if ($stmt->fetch()) {
            // Проверяем, есть ли уже отзыв
            $stmt2 = $pdo->prepare("SELECT id FROM reviews WHERE request_id = ?");
            $stmt2->execute([$req_id]);

            if ($stmt2->fetch()) {
                // Если отзыв есть — обновляем
                $stmt3 = $pdo->prepare("UPDATE reviews SET text = ?, rating = ? WHERE request_id = ? AND user_id = ?");
                $stmt3->execute([$review, $rating, $req_id, $user_id]);
            } else {
                // Если отзыва нет — создаем
                $stmt3 = $pdo->prepare("INSERT INTO reviews (request_id, user_id, text, rating) VALUES (?, ?, ?, ?)");
                $stmt3->execute([$req_id, $user_id, $review, $rating]);
            }
            $info = 'Отзыв сохранен.';
        } else {
            $error = 'Заявка не найдена.';
        }
    }
}

// Получаем список активных залов
$stmt = $pdo->query("SELECT id, title, type, description, price, image FROM rooms WHERE is_active = 1 ORDER BY id");
$rooms = $stmt->fetchAll();

// Получаем заявки текущего пользователя
$my_requests = [];
if (is_logged()) {
    $stmt = $pdo->prepare(
        "SELECT r.id, r.req_date, r.payment, r.status, rev.text AS review, rev.rating, rm.title AS room_title
         FROM requests r
         JOIN rooms rm ON rm.id = r.room_id
         LEFT JOIN reviews rev ON rev.request_id = r.id
         WHERE r.user_id = ?
         ORDER BY r.id DESC"
    );
    $stmt->execute([$user_id]);
    $my_requests = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Конференции РФ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
        <div class="container">
            <a class="navbar-brand" href="index.php">Конференции РФ</a>
            <div class="ms-auto d-flex align-items-center gap-3 text-white">
                <?php if (is_logged()): ?>
                    <span>Вы вошли как <strong><?= h($_SESSION['full_name']) ?></strong></span>
                    <?php if (is_admin()): ?>
                        <a href="admin.php" class="btn btn-light btn-sm">Админ-панель</a>
                    <?php endif; ?>
                    <a href="logout.php" class="btn btn-outline-light btn-sm">Выйти</a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-outline-light btn-sm">Войти</a>
                    <a href="register.php" class="btn btn-light btn-sm">Регистрация</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <div class="container pb-5">
        <?php if ($info): ?><div class="alert alert-success"><?= h($info) ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-danger"><?= h($error) ?></div><?php endif; ?>

        <h2 class="mb-4 text-center">Наши помещения</h2>
        <div class="row g-4">
            <?php foreach ($rooms as $room): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 shadow-sm">
                        <img src="<?= h($room['image']) ?>" class="card-img-top" alt="<?= h($room['title']) ?>">
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title"><?= h($room['title']) ?></h5>
                            <p class="mb-1">
                                <span class="badge bg-secondary"><?= h($room['type']) ?></span>
                                <span class="text-success fw-bold ms-2"><?= number_format((float)$room['price'], 0, '.', ' ') ?> ₽</span>
                            </p>
                            <p class="card-text text-muted flex-grow-1"><?= h($room['description']) ?></p>
                            <?php if (is_logged()): ?>
                                <form method="post" class="mt-3">
                                    <input type="hidden" name="room_id" value="<?= (int)$room['id'] ?>">
                                    <div class="mb-2">
                                        <label class="form-label small">Дата</label>
                                        <input type="date" name="req_date" class="form-control form-control-sm" required>
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label small">Оплата</label>
                                        <select name="payment" class="form-select form-select-sm">
                                            <option value="Очное посещение">Очное посещение</option>
                                            <option value="СБП">СБП</option>
                                        </select>
                                    </div>
                                    <button type="submit" name="create_request" class="btn btn-primary btn-sm w-100">Отправить заявку</button>
                                </form>
                            <?php else: ?>
                                <a href="login.php" class="btn btn-outline-primary btn-sm mt-3">Войдите, чтобы оставить заявку</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if (is_logged()): ?>
            <h2 class="mt-5 mb-3 text-center">Мои заявки</h2>
            <?php if (!$my_requests): ?>
                <div class="alert alert-info text-center">У вас пока нет заявок.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover bg-white mb-0">
                        <thead class="table-primary">
                            <tr><th>Помещение</th><th>Дата</th><th>Оплата</th><th>Статус</th><th>Отзыв</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($my_requests as $r): ?>
                                <tr>
                                    <td><?= h($r['room_title']) ?></td>
                                    <td><?= h($r['req_date']) ?></td>
                                    <td><?= h($r['payment']) ?></td>
                                    <td>
                                        <?php
                                        $badge = 'secondary';
                                        if ($r['status'] === 'Новая') $badge = 'primary';
                                        elseif ($r['status'] === 'Мероприятие назначено') $badge = 'warning text-dark';
                                        elseif ($r['status'] === 'Завершено') $badge = 'success';
                                        ?>
                                        <span class="badge bg-<?= $badge ?>"><?= h($r['status']) ?></span>
                                    </td>
                                    <td>
                                        <?php if ($r['status'] === 'Завершено'): ?>
                                            <form method="post">
                                                <input type="hidden" name="req_id" value="<?= (int)$r['id'] ?>">
                                                <textarea name="review" class="form-control form-control-sm mb-2" rows="2" placeholder="Ваш отзыв"><?= h($r['review'] ?? '') ?></textarea>
                                                <div class="input-group input-group-sm mb-2">
                                                    <span class="input-group-text">Оценка</span>
                                                    <select name="rating" class="form-select">
                                                        <?php for ($i = 5; $i >= 1; $i--): ?>
                                                            <option value="<?= $i ?>" <?= ($r['rating'] ?? 5) == $i ? 'selected' : '' ?>><?= $i ?></option>
                                                        <?php endfor; ?>
                                                    </select>
                                                </div>
                                                <button type="submit" name="save_review" class="btn btn-outline-secondary btn-sm">Сохранить отзыв</button>
                                            </form>
                                        <?php else: ?>
                                            <span class="text-muted small">После завершения</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>