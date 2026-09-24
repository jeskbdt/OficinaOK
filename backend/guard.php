<?php
declare(strict_types=1);
require_once __DIR__ . '/core.php';
lab_local_only();
header('Cache-Control: no-store');
try {
    $user = lab_identity($site);
    if (!$user) {
        header('Location: login.html', true, 302);
        exit;
    }
    if (!lab_can_admin($site, $user)) {
        http_response_code(403);
        echo '<!doctype html><html lang="pt-BR"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Acesso restrito</title><link rel="stylesheet" href="assets/bootstrap.min.css"><link rel="stylesheet" href="style.css"><body><main class="shell py-5"><h1>Acesso restrito.</h1><p>Sua conta de cliente não pode acessar a administração.</p><a href="index.html">Voltar ao site</a> · <a href="login.html">Trocar de conta</a></main></body></html>';
        exit;
    }
} catch (Throwable $e) {
    http_response_code(503);
    exit('Login indisponível. Confira a instalação do banco da oficina.');
}
