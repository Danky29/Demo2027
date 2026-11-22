<?php
// Подключаем конфиг, чтобы получить доступ к сессии
require_once 'config.php';

// Уничтожаем все данные сессии (выход)
session_destroy();

// Перенаправляем на главную страницу
header('Location: index.php');
exit;