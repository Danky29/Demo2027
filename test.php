<?php
// Подключаем конфиг (БД и функции)
require_once 'config.php';

// Функция для вывода результата теста (зеленый/красный)
function testResult($name, $condition) {
    echo $condition ? " + <b>$name</b> — ПРОЙДЕН<br>" : " - <b>$name</b> — ПРОВАЛЕН<br>";
}

echo "<h2>Автотесты проекта</