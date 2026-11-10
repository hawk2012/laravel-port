<?php

// Мини-роутер для `php -S` при локальной разработке на Windows.
// Запуск: php -c php.ini -S 127.0.0.1:8000 -t public router.php (из корня проекта)

$publicPath = __DIR__.'/public';
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '');

if ($uri !== '/' && file_exists($publicPath.$uri)) {
    return false;
}

require_once $publicPath.'/index.php';
