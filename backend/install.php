<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/core.php';
// Cria apenas tabelas no banco já selecionado. Não cria usuários MySQL nem altera outros bancos.
$db = lab_db();
$db->exec("CREATE TABLE IF NOT EXISTS users (id INT PRIMARY KEY, name VARCHAR(80) NOT NULL, email VARCHAR(120) NOT NULL UNIQUE, password_hash VARCHAR(255) NOT NULL, role ENUM('client','admin') NOT NULL) ENGINE=InnoDB");
$db->exec('CREATE TABLE IF NOT EXISTS lab_keys (site_id INT PRIMARY KEY, secret VARCHAR(128) NOT NULL) ENGINE=InnoDB');
$reset = in_array('--reset', $argv, true);
if (!$reset && (int)$db->query('SELECT COUNT(*) FROM users')->fetchColumn() > 0) {
    echo "Contas já existentes. Use --reset apenas para restaurar este laboratório.\n";
    exit;
}
$db->beginTransaction();
try {
    if ($reset) { $db->exec('DELETE FROM users'); $db->exec('DELETE FROM lab_keys'); }
    $q = $db->prepare('INSERT INTO users (id,name,email,password_hash,role) VALUES (?,?,?,?,?)');
    foreach ([
        [1, 'Ana Lima', 'ana@example.test', 'AnaOficina!2026', 'client'],
        [2, 'Bruno Costa', 'bruno@example.test', 'BrunoOficina!2026', 'client'],
        [3, 'Admin Oficina', 'admin@example.test', 'AdminOficina!2026', 'admin'],
    ] as [$id,$name,$email,$password,$role]) $q->execute([$id,$name,$email,password_hash($password,PASSWORD_DEFAULT),$role]);
    $q = $db->prepare('INSERT INTO lab_keys (site_id,secret) VALUES (?,?)');
    for ($i=1;$i<=5;$i++) $q->execute([$i,bin2hex(random_bytes(32))]);
    $db->commit();
    echo "Criadas 2 contas de cliente, 1 de admin e 5 chaves independentes. Consulte backend/README.md.\n";
} catch (Throwable $e) { $db->rollBack(); throw $e; }
