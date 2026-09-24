# Backend de login — laboratório da oficina

PHP + Apache + MySQL, sem framework, Composer, Docker ou serviços externos. O backend atende **somente login, identificação da conta, logout e permissão de abrir o painel**. Os formulários de reserva continuam simulados; as tabelas e os botões do admin continuam mock, sem persistência.

Este laboratório contém falhas intencionais. Por padrão, as rotas PHP aceitam apenas conexões locais. Use dados fictícios e não publique o projeto abertamente. `allow_lan` serve apenas para uma rede isolada de aula.

## Preparação

É necessário Apache com PHP habilitado, PHP com `pdo_mysql`, MySQL e um banco exclusivo. O navegador deve abrir o projeto por HTTP, por exemplo `http://localhost/oficina/`, não como arquivo local.

Com uma conta de administração **do MySQL**, crie um banco e um usuário exclusivo. Substitua a senha do exemplo antes de executar:

```sql
CREATE DATABASE oficina_login CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'oficina'@'localhost' IDENTIFIED BY 'ESCOLHA_UMA_SENHA_LOCAL';
GRANT SELECT, INSERT, UPDATE, DELETE, CREATE ON oficina_login.* TO 'oficina'@'localhost';
```

Copie `backend/config.example.php` para `backend/config.local.php` e informe host/porta ou socket, banco, usuário e senha. O arquivo local está no `.gitignore`; é um arquivo PHP que retorna configurações, não um arquivo de texto público.

Execute na raiz do projeto:

```sh
php backend/install.php
```

O instalador cria `users` e `lab_keys` no banco configurado e insere as três contas. Ele não cria nem modifica usuários do servidor MySQL. Se já houver contas, não as sobrescreve. Para restaurar deliberadamente os dados do laboratório:

```sh
php backend/install.php --reset
```

O reset substitui as contas e gera novas chaves, invalidando os tokens antigos. Não altera os dados mock dos painéis. A conta MySQL usada pelo instalador precisa de CREATE; após preparar o ambiente, essa permissão pode ser retirada do usuário da aplicação.

Também é possível configurar `OFICINA_DSN`, `OFICINA_DB_USER`, `OFICINA_DB_PASSWORD`, `OFICINA_FIXED` e `OFICINA_ALLOW_LAN` no ambiente do processo PHP. No Apache, configure o ambiente dos processos que executam PHP; exportar variáveis em outro terminal não altera um Apache já iniciado. `config.local.php`, quando existe, tem precedência.

## Contas fictícias

| ID | Nome | E-mail | Senha | Perfil |
| --- | --- | --- | --- | --- |
| 1 | Ana Lima | ana@example.test | AnaOficina!2026 | Cliente |
| 2 | Bruno Costa | bruno@example.test | BrunoOficina!2026 | Cliente |
| 3 | Admin Oficina | admin@example.test | AdminOficina!2026 | Admin |

As mesmas três contas funcionam em todos os temas. O banco guarda hashes gerados por `password_hash`, nunca as senhas em texto puro. Cookies e chaves de assinatura são separados por tema. Entrar no tema 1 não autentica automaticamente o tema 2.

## Fluxo

- Perfil → `login.html` → `auth.php` → página inicial, exibindo nome e perfil.
- Nas páginas iniciais e de login dos temas 1, 3, 4 e 5, Admin começa oculto e só aparece quando `auth.php` confirma `can_admin: true`. Sem JavaScript ou com falha na consulta, continua oculto. Na RUÍDO, o botão fica visível para todos, preservando o exercício didático, inclusive para comparar com o bloqueio do modo corrigido.
- Admin → `admin.php`. Sem login, redireciona ao login. Cliente recebe HTTP 403, exceto no tema 2 vulnerável. Administrador entra normalmente.
- “Sair” apaga o cookie daquele tema e volta ao login.
- Não existem mais páginas `admin.html` desprotegidas contendo uma cópia do painel.
- O servidor consulta o perfil no MySQL. Papéis enviados no corpo do login ou inseridos no JWT não determinam o perfil.

`auth.php` é um adaptador de poucas linhas que fixa o tema no servidor. `backend/controller.php` e `backend/core.php` implementam a lógica compartilhada. `backend/guard.php` protege cada admin antes de emitir HTML. `backend/client.js` conecta as telas existentes ao login.

## Uma falha principal por site

| Tema | Padrão de falha | Como observar | Correção implementada pelo modo corrigido |
| --- | --- | --- | --- |
| 1 · Margem | **Manipulação da consulta de autenticação** | A entrada do login altera a consulta e o resultado devolvido pelo servidor, permitindo confirmar uma identidade diferente da esperada. | Consultas preparadas com entrada tratada como dado e validação da senha no servidor. |
| 2 · RUÍDO | **Condição de acesso baseada em contexto incompleto** | O painel é entregue mesmo quando o usuário não possui a permissão correta para administrá-lo. | Verificar a autorização no servidor antes de emitir qualquer conteúdo de administração. |
| 3 · PULSO | **Validação insuficiente de credenciais e tokens** | Um token com conteúdo alterado ainda é aceito pelo servidor, mostrando que a integridade e o contexto do token não foram checados. | Validar assinatura, algoritmo, emissor, destinatário, expiração e identidade do usuário. |
| 4 · Vereda | **Reuso de entrada em resposta sem neutralização** | Mensagens de erro retornam conteúdo informado pelo usuário e esse valor é interpretado como HTML no navegador. | Usar respostas genéricas e tratar a saída como texto, não como markup. |
| 5 · Grão | **Segredo de autenticação exposto ao cliente** | O servidor entrega ao frontend um material que deveria existir apenas no backend, permitindo a criação de credenciais válidas. | Manter a chave exclusivamente no servidor e rotacionar o segredo comprometido. |

As falhas foram escolhidas para caber no escopo de login. Não foram acrescentados upload, banco de pedidos ou API de administração. O frontend não altera registros reais.

## Demonstrações com Burp Suite Community

Utilize o navegador integrado do Burp e somente o endereço local do laboratório. Proxy e Repeater são suficientes para observar as solicitações; Decoder ajuda com Base64. Cookies são HttpOnly, mas continuam visíveis nas solicitações interceptadas pelo Burp.

### Tema 1: consulta de login

Envie o login de Ana ao Repeater. Mantenha `password` como `AnaOficina!2026` e substitua o campo `email` por:

```text
' UNION SELECT 3,password_hash FROM users WHERE id=1 -- -
```

Use o sufixo `-- -` exatamente como mostrado; o espaço interno inicia o comentário mesmo depois de remover espaços nas extremidades do campo. A primeira parte procura e-mail vazio; a UNION devolve ID 3 associado ao hash de Ana. Na versão vulnerável, a resposta identifica o administrador. A versão corrigida trata todo o conteúdo como um e-mail literal e responde 401.

Isso demonstra a manipulação do resultado SQL sem armazenar senhas em texto puro. O alvo é exclusivamente a tabela fictícia deste laboratório.

### Tema 2: permissão

Entre como Ana, abra o botão Admin e observe o painel. Na versão corrigida, repita com a mesma conta: HTTP 403. A conta administrativa continua entrando normalmente.

### Tema 3: token

1. Entre como Ana no PULSO.
2. Capture `GET /oficina/site-custom-3/auth.php` e envie ao Repeater.
3. Localize o cookie `oficina_3`. Ele contém três partes separadas por pontos: cabeçalho, conteúdo e assinatura.
4. Decodifique a segunda parte com Base64 URL-safe. Altere somente `"sub":1` para `"sub":3`.
5. Recodifique como Base64 URL-safe, sem `=` no final. Preserve cabeçalho e assinatura originais. Substitua o cookie na solicitação do Repeater.
6. A resposta vulnerável passa a identificar Admin Oficina. Envie o mesmo cookie a `admin.php` para confirmar o acesso ao painel mock.
7. No modo corrigido, `auth.php` não reconhece esse token e retorna `user: null`; `admin.php` redireciona ao login.

Não basta decodificar o token: a evidência da falha é o servidor aceitar o conteúdo adulterado. A expiração e o destinatário ainda precisam ser válidos; faça o teste com um login recente do próprio tema 3.

### Tema 4: mensagem interpretada como HTML

No campo e-mail da Vereda, use este marcador inofensivo e uma senha qualquer:

```html
<img src=x onerror="alert('XSS na oficina')">
```

O formulário usa `novalidate` para permitir que esse caso didático chegue ao servidor. A falha de login retorna o texto e a página vulnerável executa o alerta. No modo corrigido aparece apenas “E-mail ou senha incorretos”. No Burp é possível inspecionar o JSON retornado; a execução de JavaScript acontece quando a página do navegador insere esse texto no DOM.

### Tema 5: segredo exposto

Abra o login do Grão e inspecione a resposta do script `auth.php?action=lab-key`. O valor de `window.OFICINA_SIGNING_KEY` é o segredo que deveria ficar no servidor. Diferentemente do tema 3, a assinatura **é** verificada; o problema é o atacante ter material para criar uma assinatura válida.

Para demonstrar o impacto, copie um JWT recente de Ana no Burp e, usando PHP localmente, altere `sub` para 3 e recalcule HMAC-SHA256 sobre `cabeçalho.conteúdo` com a chave exposta. Substitua o cookie `oficina_5` pelo token resultante. Os testes automatizados executam essa mesma comparação, incluindo a necessidade de rotacionar chaves comprometidas.

## Comparar com as correções

Altere `'fixed' => true` em `config.local.php` ou inicie o PHP com `OFICINA_FIXED=1`. As cinco exceções didáticas deixam de ser usadas. Para o tema 5, execute também `php backend/install.php --reset`: só esconder a chave não revoga tokens assinados com ela.

O modo corrigido resolve as cinco falhas delimitadas, mas não é um produto de autenticação pronto para produção. O JWT deste exercício usa uma implementação pequena em PHP para respeitar as ferramentas da oficina; não implementa recuperação de senha, MFA, limitação de tentativas ou revogação individual. “Sair” remove o cookie do navegador, mas uma cópia do token ainda vale até expirar ou até a rotação da chave. Em um sistema real, use uma implementação de autenticação revisada e uma política de sessões apropriada.

Referências para a discussão: [SQL Injection](https://cheatsheetseries.owasp.org/cheatsheets/SQL_Injection_Prevention_Cheat_Sheet.html), [autorização](https://cheatsheetseries.owasp.org/cheatsheets/Authorization_Cheat_Sheet.html), [JWT](https://portswigger.net/web-security/jwt), [XSS](https://cheatsheetseries.owasp.org/cheatsheets/Cross_Site_Scripting_Prevention_Cheat_Sheet.html) e [segredos](https://cheatsheetseries.owasp.org/cheatsheets/Secrets_Management_Cheat_Sheet.html).


## Executar as verificações

Depois de configurar e popular o banco exclusivo, execute:

```sh
php tests/login.php
php tests/http.php http://localhost/oficina
```

Para comparar as correções, habilite `fixed` na configuração e execute novamente:

```sh
php tests/login.php
php tests/http.php http://localhost/oficina fixed
```

Os testes de lógica leem as três contas e fazem uma rotação de chave dentro de uma transação desfeita ao final. Os testes HTTP exercitam login, cookies, autorização, SQLi, reflexão de erro e exposição de chave. Não criam pedidos nem alteram os painéis mock. A execução do JavaScript de XSS deve ser observada no navegador, seguindo o roteiro.

Na preparação desta entrega, esses testes foram executados com PHP 8.5, Apache e MySQL 8.4 em instâncias temporárias, nos dois modos. O MySQL definitivo ainda precisa ser configurado; nenhum serviço permanente do sistema foi alterado.
