<?php
declare(strict_types=1);

function lab_config(): array {
    static $config;
    return $config ??= require __DIR__ . '/config.php';
}
function lab_db(): PDO {
    static $db;
    $c = lab_config();
    return $db ??= new PDO($c['dsn'], $c['db_user'], $c['db_password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
}

function lab_local_only(): void {
    if (!lab_config()['allow_lan'] && !in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true)) {
        http_response_code(403);
        exit('Laboratório disponível somente no computador local.');
    }
}
function lab_json(array $data, int $status = 200): never {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    header('X-Content-Type-Options: nosniff');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    exit;
}
function lab_key(int $site): string {
    $q = lab_db()->prepare('SELECT secret FROM lab_keys WHERE site_id = ?');
    $q->execute([$site]);
    $key = $q->fetchColumn();
    if (!is_string($key)) throw new RuntimeException('Execute o instalador.');
    return $key;
}
function lab_b64(string $value): string {
    return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
}
function lab_unb64(string $value): string {
    $decoded = base64_decode(strtr($value, '-_', '+/'), true);
    if ($decoded === false) throw new InvalidArgumentException('Token inválido.');
    return $decoded;
}
function lab_token(int $site, int $userId): string {
    $header = lab_b64('{"alg":"HS256","typ":"JWT"}');
    $payload = lab_b64(json_encode(['sub' => $userId, 'aud' => $site, 'iss' => 'oficina-local', 'exp' => time() + 3600], JSON_THROW_ON_ERROR));
    return "$header.$payload." . lab_b64(hash_hmac('sha256', "$header.$payload", lab_key($site), true));
}
function lab_user(int $id): ?array {
    $q = lab_db()->prepare('SELECT id, name, email, role FROM users WHERE id = ?');
    $q->execute([$id]);
    return $q->fetch() ?: null;
}

function lab_identity(int $site, ?string $token = null): ?array {
    $token ??= $_COOKIE['oficina_' . $site] ?? '';
    if (!$token || strlen($token) > 4096) return null;
    try {
        $parts = explode('.', $token);
        if (count($parts) !== 3) return null;
        [$h, $p, $s] = $parts;
        $header = json_decode(lab_unb64($h), true, 16, JSON_THROW_ON_ERROR);
        $payload = json_decode(lab_unb64($p), true, 16, JSON_THROW_ON_ERROR);
        if (!is_array($header) || !is_array($payload) || ($header['alg'] ?? '') !== 'HS256' || ($header['typ'] ?? '') !== 'JWT') return null;
        if (($payload['aud'] ?? null) !== $site || ($payload['iss'] ?? '') !== 'oficina-local' || !is_int($payload['exp'] ?? null) || $payload['exp'] <= time() || !is_int($payload['sub'] ?? null)) return null;

        $expected = lab_b64(hash_hmac('sha256', "$h.$p", lab_key($site), true));
        if (!hash_equals($expected, $s)) return null;
        return lab_user($payload['sub']);
    } catch (JsonException | InvalidArgumentException $e) {
        return null;
    }
}

function lab_login(int $site, string $email, string $password): ?array {
    if ($email === '' || $password === '' || strlen($email) > 500 || strlen($password) > 200) return null;
    $q = lab_db()->prepare('SELECT id, password_hash FROM users WHERE email = ? LIMIT 1');
    $q->execute([$email]);
    $row = $q->fetch();
    return $row && password_verify($password, $row['password_hash']) ? lab_user((int)$row['id']) : null;
}
function lab_can_admin(int $site, ?array $user): bool {
    if (!$user) return false;
    return $user['role'] === 'admin';
}
function lab_cookie(int $site, string $token, bool $clear = false): void {
    $path = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/') . '/';
    setcookie('oficina_' . $site, $token, [
        'expires' => $clear ? time() - 3600 : time() + 3600,
        'path' => $path,
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}
