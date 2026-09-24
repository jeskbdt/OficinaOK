<?php
$site = 3;
require __DIR__ . '/../backend/guard.php';
?>
<!DOCTYPE html>
<html lang="pt-BR">
  <head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1" name="viewport"/>
    <meta content="#18151f" name="theme-color"/>
    <title>
      Administração · PULSO
    </title>
    <link href="assets/disco.svg" rel="icon" type="image/svg+xml"/>
    <link href="assets/bootstrap.min.css" rel="stylesheet"/>
    <link href="style.css" rel="stylesheet"/>
    <link href="account.css" rel="stylesheet"/>
    <script defer="" src="account.js">
    </script>
    <script defer="" src="../backend/client.js">
    </script>
  </head>
  <body class="account-page admin-page">
    <a class="skip-link" href="#main-content">
      Pular para o conteúdo
    </a>
    <header class="site-header shell account-header">
      <a aria-label="Pulso, início" class="brand" href="./">
        <span aria-hidden="true" class="pulse-icon">
          ϟ
        </span>
        PULSO
        <span class="brand-year">
          /26
        </span>
      </a>
      <span class="header-note">
        Administração / PULSO
      </span>
      <nav aria-label="Navegação do site e acessos" class="nav-actions">
        <a class="header-link" href="index.html">
          Voltar ao site
          <span aria-hidden="true">
            ↗
          </span>
        </a>
        <a aria-current="page" aria-label="Acessar administração" class="admin-link" href="admin.php">
          Admin
        </a>
        <a aria-label="Login de usuários" class="profile-link" href="login.html" title="Login de usuários">
          <svg aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24">
            <circle cx="12" cy="8" r="3.5">
            </circle>
            <path d="M5 21v-2a7 7 0 0 1 14 0v2" stroke-linecap="round">
            </path>
          </svg>
        </a>
      </nav>
    </header>
    <main class="admin-main shell" id="main-content">
      <div class="admin-heading">
        <div>
          <div class="eyebrow">
            Painel de administração
          </div>
          <h1>
            Nos bastidores do PULSO.
          </h1>
          <p>
            Um olhar sobre reservas de ingressos.
          </p>
        </div>
        <span class="demo-badge">
          Painel mock · acesso por perfil
        </span>
      </div>
      <section aria-label="Resumo de todos os registros" class="metric-grid">
        <div class="metric">
          <span>
            Total de registros
          </span>
          <strong data-metric="total">
            04
          </strong>
        </div>
        <div class="metric">
          <span>
            Aguardando confirmação
          </span>
          <strong data-metric="pending">
            02
          </strong>
        </div>
        <div class="metric">
          <span>
            Confirmados
          </span>
          <strong data-metric="confirmed">
            02
          </strong>
        </div>
      </section>
      <section aria-labelledby="list-title" class="admin-list" id="admin-list">
        <div class="list-top">
          <div>
            <h2 id="list-title">
              Reservas de ingressos
            </h2>
            <p aria-live="polite" id="result-count" role="status">
              4 de 4 registros
            </p>
          </div>
          <div class="list-filters">
            <div>
              <label class="form-label" for="record-search">
                Buscar registros
              </label>
              <input class="form-control" id="record-search" placeholder="Nome, e-mail ou pedido" type="search"/>
            </div>
            <div>
              <label class="form-label" for="status-filter">
                Status
              </label>
              <select class="form-select" id="status-filter">
                <option value="">
                  Todos os status
                </option>
                <option value="pending">
                  Pendentes
                </option>
                <option value="confirmed">
                  Confirmados
                </option>
              </select>
            </div>
          </div>
        </div>
        <div aria-label="Tabela de registros; role horizontalmente em telas pequenas" class="table-responsive" role="region" tabindex="0">
          <table class="table">
            <caption class="visually-hidden">
              Reservas de ingressos — dados fictícios para demonstração.
            </caption>
            <thead>
              <tr>
                <th scope="col">
                  Cliente
                </th>
                <th scope="col">
                  Experiência
                </th>
                <th scope="col">
                  Valor
                </th>
                <th scope="col">
                  Status
                </th>
                <th scope="col">
                  Ação
                </th>
              </tr>
            </thead>
            <tbody>
              <tr data-record="#3001" data-search="Ana Lima ana@example.com Pista 3001" data-status="pending">
                <td>
                  <strong>
                    Ana Lima
                  </strong>
                  <small>
                    ana@example.com
                  </small>
                </td>
                <td>
                  Pista
                  <small>
                    #3001 · 2 ingressos · 18 out. 2026
                  </small>
                </td>
                <td>
                  R$ 240,00
                </td>
                <td>
                  <span class="status-pill" data-status="pending">
                    Pendente
                  </span>
                </td>
                <td>
                  <button aria-label="Confirmar registro 3001 de Ana Lima" class="confirm-button" data-confirm="" type="button">
                    Confirmar
                  </button>
                </td>
              </tr>
              <tr data-record="#3002" data-search="Bruno Costa bruno@example.com Lounge 3002" data-status="confirmed">
                <td>
                  <strong>
                    Bruno Costa
                  </strong>
                  <small>
                    bruno@example.com
                  </small>
                </td>
                <td>
                  Lounge
                  <small>
                    #3002 · 1 ingresso · 18 out. 2026
                  </small>
                </td>
                <td>
                  R$ 220,00
                </td>
                <td>
                  <span class="status-pill" data-status="confirmed">
                    Confirmado
                  </span>
                </td>
                <td>
                  <button aria-label="Confirmar registro 3002 de Bruno Costa" class="confirm-button" data-confirm="" disabled="" type="button">
                    Confirmado
                  </button>
                </td>
              </tr>
              <tr data-record="#3003" data-search="Clara Dias clara@example.com Pista / meia-entrada 3003" data-status="pending">
                <td>
                  <strong>
                    Clara Dias
                  </strong>
                  <small>
                    clara@example.com
                  </small>
                </td>
                <td>
                  Pista / meia-entrada
                  <small>
                    #3003 · 1 ingresso · 18 out. 2026
                  </small>
                </td>
                <td>
                  R$ 60,00
                </td>
                <td>
                  <span class="status-pill" data-status="pending">
                    Pendente
                  </span>
                </td>
                <td>
                  <button aria-label="Confirmar registro 3003 de Clara Dias" class="confirm-button" data-confirm="" type="button">
                    Confirmar
                  </button>
                </td>
              </tr>
              <tr data-record="#3004" data-search="Diego Alves diego@example.com Pista 3004" data-status="confirmed">
                <td>
                  <strong>
                    Diego Alves
                  </strong>
                  <small>
                    diego@example.com
                  </small>
                </td>
                <td>
                  Pista
                  <small>
                    #3004 · 1 ingresso · 18 out. 2026
                  </small>
                </td>
                <td>
                  R$ 120,00
                </td>
                <td>
                  <span class="status-pill" data-status="confirmed">
                    Confirmado
                  </span>
                </td>
                <td>
                  <button aria-label="Confirmar registro 3004 de Diego Alves" class="confirm-button" data-confirm="" disabled="" type="button">
                    Confirmado
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <p class="empty-message" hidden="" id="empty-message">
          Nenhum registro encontrado. Tente outro nome ou status.
        </p>
      </section>
      <p aria-live="polite" class="admin-feedback" id="admin-feedback" role="status" tabindex="-1">
      </p>
      <p class="admin-note">
        Dados fictícios. As confirmações são temporárias e voltam ao estado inicial ao recarregar. As reservas feitas na página principal ainda não aparecem neste painel.
      </p>
      <a class="account-back" href="index.html">
        ← Voltar ao site
      </a>
    </main>
    <footer class="account-footer shell">
      <span>
        PULSO · Feito para conectar.
      </span>
      <span>
        Oficina · dados fictícios
      </span>
    </footer>
  </body>
</html>
