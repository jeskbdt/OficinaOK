<?php
return [
    'dsn' => 'mysql:host=127.0.0.1;port=3306;dbname=oficina_login;charset=utf8mb4',
    'db_user' => 'root',
    'db_password' => '',
    'fixed' => false, // modo seguro; desativa as falhas didáticas do laboratório.
    'allow_lan' => false, // mantenha local; habilite apenas em rede isolada da oficina.
];
