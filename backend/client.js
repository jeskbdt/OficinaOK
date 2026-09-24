
(() => {
  const form = document.querySelector("#login-form");
  const status = document.createElement("div");
  status.className = "shell account-session";
  status.setAttribute("role", "status");
  document.querySelector(".site-header").after(status);
  async function api(body) {
    const response = await fetch("auth.php", {
      method: body ? "POST" : "GET",
      credentials: "same-origin",
      cache: "no-store",
      ...(body
        ? {
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(body),
          }
        : {}),
    });
    const data = await response.json();
    return { response, data };
  }
  function showUser(user, canAdmin) {
    document.querySelectorAll("[data-admin-only]").forEach((link) => {
      link.hidden = canAdmin !== true;
    });
    status.replaceChildren();
    const text = document.createElement("span");
    text.textContent = user
      ? `Conectado como ${user.name} · ${user.role === "admin" ? "administrador" : "cliente"}`
      : "Você não está conectado.";
    status.append(text);
    if (user) {
      document.querySelectorAll("#interest-form [data-account-field]").forEach((group) => {
        const field = group.querySelector("input");
        field.value = user[field.name];
        field.defaultValue = field.value;
        field.readOnly = true;
        field.dispatchEvent(new Event("input", { bubbles: true }));
        group.hidden = true;
      });
      const logout = document.createElement("button");
      logout.type = "button";
      logout.className = "confirm-button";
      logout.textContent = "Sair";
      logout.addEventListener("click", async () => {
        logout.disabled = true;
        try {
          const { response } = await api({ action: "logout" });
          if (!response.ok) throw new Error();
          location.assign("login.html");
        } catch {
          text.textContent = "Não foi possível sair. Tente novamente.";
          logout.disabled = false;
        }
      });
      status.append(logout);
    }
  }
  api()
    .then(({ response, data }) => {
      if (!response.ok) throw new Error(data.message);
      showUser(data.user, data.can_admin);
    })
    .catch(() => {
      status.textContent =
        "Login indisponível. Abra pelo Apache e confira a configuração do MySQL.";
    });
  if (!form) return;
  const password = document.querySelector("#login-password");
  const toggle = document.querySelector("#toggle-password");
  toggle.addEventListener("click", () => {
    const show = password.type === "password";
    password.type = show ? "text" : "password";
    toggle.textContent = show ? "Ocultar" : "Mostrar";
    toggle.setAttribute("aria-pressed", String(show));
  });
  const feedback = document.querySelector("#login-feedback");
  const button = form.querySelector('button[type="submit"]');
  form.addEventListener("submit", async (event) => {
    event.preventDefault();
    if (button.disabled) return;
    feedback.textContent = "";
    const email = document.querySelector("#login-email").value.trim();
    if (!email || !password.value) {
      feedback.textContent = "Preencha e-mail e senha.";
      return;
    }
    button.disabled = true;
    form.setAttribute("aria-busy", "true");
    try {
      const { response, data } = await api({
        action: "login",
        email,
        password: password.value,
      });
      if (!response.ok) {
        feedback.textContent = data.message || "Não foi possível entrar.";
        feedback.focus();
        return;
      }
      form.reset();
      location.assign("index.html");
    } catch {
      feedback.textContent =
        "Não foi possível conectar ao login. Tente novamente.";
    } finally {
      button.disabled = false;
      form.removeAttribute("aria-busy");
    }
  });
})();
