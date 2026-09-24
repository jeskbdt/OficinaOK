<?php

declare(strict_types=1);
$base = rtrim($argv[1] ?? 'http://127.0.0.1:8877', '/');
$fixed = ($argv[2] ?? '') === 'fixed';
$count = 0;
function expect(bool $ok, string $description): void
{
  global $count;
  if (!$ok) throw new RuntimeException($description);
  $count++;
}
function request(string $path, ?array $body = null, string $cookie = ''): array
{
  global $base;
  $headers = "Accept: application/json\r\n";
  if ($body !== null) $headers .= "Content-Type: application/json\r\n";
  if ($cookie) $headers .= "Cookie: $cookie\r\n";
  $raw = file_get_contents($base . $path, false, stream_context_create(['http' => [
    'method' => $body === null ? 'GET' : 'POST',
    'header' => $headers,
    'content' => $body === null ? '' : json_encode($body),
    'ignore_errors' => true,
    'follow_location' => 0,
    'timeout' => 10,
  ]]));
  $lines = http_get_last_response_headers();
  preg_match('/\s(\d{3})\s/', $lines[0], $m);
  $newCookie = '';
  foreach ($lines as $line) if (stripos($line, 'Set-Cookie: ') === 0) $newCookie = explode(';', substr($line, 12))[0];
  return ['code' => (int)$m[1], 'body' => $raw, 'json' => json_decode($raw, true), 'cookie' => $newCookie, 'headers' => implode("\n", $lines)];
}
function b64(string $s): string
{
  return rtrim(strtr(base64_encode($s), '+/', '-_'), '=');
}
foreach (range(1, 5) as $site) {
  $path = "/site-custom-$site";
  expect(request("$path/admin.php")['code'] === 302, "$site: anônimo redirecionado");
  expect(request("$path/admin.html")['code'] === 404, "$site: não existe admin HTML desprotegido");
  $login = request("$path/auth.php", ['action' => 'login', 'email' => 'ana@example.test', 'password' => 'AnaOficina!2026']);
  expect($login['code'] === 200 && $login['json']['user']['role'] === 'client', "$site: login real de cliente");
  expect(str_contains(strtolower($login['headers']), 'httponly'), "$site: cookie HttpOnly");
  $cookie = $login['cookie'];
  expect(request("$path/auth.php", null, $cookie)['json']['user']['email'] === 'ana@example.test', "$site: cookie reconhecido");
  expect(request("$path/admin.php", null, $cookie)['code'] === 403, 'cliente sem permissão continua bloqueado');
  $token = explode('=', $cookie, 2)[1];
  [$h, $p, $s] = explode('.', $token);
  $payload = json_decode(base64_decode(strtr($p, '-_', '+/')), true);
  $payload['sub'] = 3;
  $p = b64(json_encode($payload));
  $tampered = "oficina_$site=$h.$p.$s";
  expect(request("$path/admin.php", null, $tampered)['code'] === 302, 'token adulterado não é aceito quando a validação do contexto está correta');
  $key = request("$path/auth.php?action=lab-key");
  if (!$fixed) {
    preg_match('/= "([a-f0-9]+)";/', $key['body'], $m);
    expect(isset($m[1]), 'segredo do sistema não deve aparecer para o cliente');
    $signature = b64(hash_hmac('sha256', "$h.$p", $m[1], true));
    expect(request("$path/admin.php", null, "oficina_5=$h.$p.$signature")['code'] === 200, 'segredo público permite forjar identidade válida');
  } else expect(str_contains($key['body'], '= null;'), 'chave não é exposta quando a correção está ativa');
  $attack = request("$path/auth.php", ['action' => 'login', 'email' => "' UNION SELECT 3,password_hash FROM users WHERE id=1 -- -", 'password' => 'AnaOficina!2026']);
  expect($attack['code'] === 401, 'entrada maliciosa não altera a identidade do usuário');
  $xss = '<img src=x onerror="alert(1)">';
  $error = request("$path/auth.php", ['action' => 'login', 'email' => $xss, 'password' => 'invalida']);
  expect($error['code'] === 401, 'senha inválida continua sendo rejeitada');
  expect(($error['json']['unsafe_error_html'] ?? false) === false, 'resposta de erro não reutiliza entrada do usuário como HTML');
  expect(!str_contains($error['json']['message'], $xss), 'resposta de erro não repete a entrada maliciosa');
  $admin = request("$path/auth.php", ['action' => 'login', 'email' => 'admin@example.test', 'password' => 'AdminOficina!2026']);
  $panel = request("$path/admin.php", null, $admin['cookie']);
  expect($panel['code'] === 200 && substr_count($panel['body'], 'data-record=') === 4, "$site: admin acessa quatro registros mock");
  $logout = request("$path/auth.php", ['action' => 'logout'], $cookie);
  expect($logout['code'] === 200 && str_contains(strtolower($logout['headers']), 'expires='), "$site: logout limpa cookie");
  echo "Tema $site: HTTP OK\n";
}
echo "$count verificações HTTP passaram (" . ($fixed ? 'corrigido' : 'vulnerável') . ").\n";
