<?php
declare(strict_types=1);
require_once __DIR__ . '/core.php';
lab_local_only();
try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $user = lab_identity($site);
        lab_json(['user' => $user, 'can_admin' => lab_can_admin($site, $user), 'unsafe_error_html' => false]);
    }
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') lab_json(['message' => 'Método não permitido.'], 405);
    if (!str_starts_with(strtolower($_SERVER['CONTENT_TYPE'] ?? ''), 'application/json')) lab_json(['message' => 'Envie JSON.'], 415);
    $body = json_decode(file_get_contents('php://input', false, null, 0, 4097), true, 16, JSON_THROW_ON_ERROR);
    if (!is_array($body)) lab_json(['message' => 'Corpo inválido.'], 400);
    if (($body['action'] ?? '') === 'logout') {
        lab_cookie($site, '', true);
        lab_json(['ok' => true]);
    }
    if (($body['action'] ?? '') !== 'login') lab_json(['message' => 'Ação não encontrada.'], 400);
    if (!is_string($body['email'] ?? null) || !is_string($body['password'] ?? null)) lab_json(['message' => 'Informe e-mail e senha.'], 400);

    $email = trim($body['email']);
    $user = lab_login($site, $email, $body['password']);
    if (!$user) {
        lab_json(['message' => 'E-mail ou senha incorretos.', 'unsafe_error_html' => false], 401);
    }
    lab_cookie($site, lab_token($site, (int)$user['id']));
    lab_json(['user' => $user]);
} catch (JsonException $e) {
    lab_json(['message' => 'JSON inválido.'], 400);
} catch (Throwable $e) {
    lab_json(['message' => 'Não foi possível processar o acesso. Confira a instalação do laboratório.'], 503);
}
