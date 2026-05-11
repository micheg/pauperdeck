<?php

$user = 'admin';
$pass = '646313';

if (
    !isset($_SERVER['PHP_AUTH_USER']) ||
    !isset($_SERVER['PHP_AUTH_PW']) ||
    $_SERVER['PHP_AUTH_USER'] !== $user ||
    $_SERVER['PHP_AUTH_PW'] !== $pass
) {

    header('WWW-Authenticate: Basic realm="Admin Area"');
    header('HTTP/1.0 401 Unauthorized');

    echo 'Authentication required';

    exit;
}

require __DIR__ . '/../../protected/adminer-real.php';
