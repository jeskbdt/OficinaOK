<?php
// Requer o banco exclusivo da oficina já populado. Não altera contas ou registros.
declare(strict_types=1);
require __DIR__ . '/../backend/core.php';
function check(bool $ok, string $message): void
{
  if (!$ok) throw new RuntimeException($message);
  echo "OK: $message\n";
}
$fixed = lab_config()['fixed'];
check((int) lab_db()->query('SELECT COUNT(*) FROM users')->fetchColumn() === 3, 'exatamente três contas');
foreach (range(1, 5) as $site) {
  $ana = lab_login($site, 'ana@example.test', 'AnaOficina!2026');
  check($ana && (int)$ana['id'] === 1 && $ana['role'] === 'client', "tema $site: login de Ana");
  check((int)lab_login($site, 'bruno@example.test', 'BrunoOficina!2026')['id'] === 2, "tema $site: login de Bruno");
  check(lab_login($site, 'ana@example.test', 'errada') === null, "tema $site: rejeita senha errada");
  $admin = lab_login($site, 'admin@example.test', 'AdminOficina!2026');
  check(lab_can_admin($site, $admin), "tema $site: admin autorizado");
  check(!lab_can_admin($site, null), "tema $site: visitante bloqueado");
  check(lab_can_admin($site, $ana) === false, "tema $site: cliente não recebe autorização administrativa");
  $token = lab_token($site, 1);
  check((int)lab_identity($site, $token)['id'] === 1, "tema $site: JWT válido");
  [$h, $p, $s] = explode('.', $token);
  $data = json_decode(lab_unb64($p), true);
  $data['sub'] = 3;
  $p = lab_b64(json_encode($data));
  $forged = lab_identity($site, "$h.$p.$s");
  check($forged === null, "tema $site: token adulterado não passa na validação");
  check(lab_identity($site === 5 ? 1 : $site + 1, $token) === null, "tema $site: token não cruza temas");
  $data['exp'] = time() - 10;
  $expired = lab_b64(json_encode($data));
  check(lab_identity($site, "$h.$expired.$s") === null, "tema $site: expiração");
}
$injection = "' UNION SELECT 3,password_hash FROM users WHERE id=1 -- -";
$injected = lab_login(1, $injection, 'AnaOficina!2026');
check($fixed ? $injected === null : (int)$injected['id'] === 3, 'SQLi: resultado esperado para o modo atual');
foreach ([2, 3, 4, 5] as $site) check(lab_login($site, $injection, 'AnaOficina!2026') === null, "tema $site: SQLi não atravessa outros temas");
// Uma chave já exposta precisa ser rotacionada, mesmo depois de ocultar o endpoint.
$oldToken = lab_token(5, 3);
lab_db()->beginTransaction();
try {
  $q = lab_db()->prepare('UPDATE lab_keys SET secret=? WHERE site_id=5');
  $q->execute([bin2hex(random_bytes(32))]);
  check(lab_identity(5, $oldToken) === null, 'rotação da chave invalida token antigo');
} finally {
  lab_db()->rollBack();
}
echo 'Modo: ' . ($fixed ? 'corrigido' : 'vulnerável') . "\n";
