<?php
try {
    $pdo = new PDO('mysql:host=127.0.0.1;port=3306', 'root', '');
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `inventory` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
    echo "DB_OK" . PHP_EOL;
} catch (Throwable $e) {
    echo 'ERR:' . $e->getMessage() . PHP_EOL;
    exit(1);
}
