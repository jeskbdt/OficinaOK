<?php

return array_replace([
    'dsn' => getenv('OFICINA_DSN') ?: 'mysql:host=127.0.0.1;port=3306;dbname=oficina_login;charset=utf8mb4',
    'db_user' => getenv('OFICINA_DB_USER') ?: 'oficina',
    'db_password' => getenv('OFICINA_DB_PASSWORD') ?: '',
    'fixed' => getenv('OFICINA_FIXED') === '1',
    'allow_lan' => getenv('OFICINA_ALLOW_LAN') === '1',
], is_file(__DIR__ . '/config.local.php') ? require __DIR__ . '/config.local.php' : []);
