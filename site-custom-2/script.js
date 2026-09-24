// PERSONALIZE: cores e preços. A ilustração também acompanha a cor escolhida.
const CONFIG = {
  optionField: 'color', detailField: 'size', maxQuantity: 4, success: 'Drop reservado na simulação',
  options: {
    preto: { label: 'Camiseta preto + lima', price: 89 },
    offwhite: { label: 'Camiseta off-white + preto', price: 89 },
    lima: { label: 'Camiseta lima + preto', price: 89 }
  }
};

/* OFICINA — comportamento compartilhado, com uma cópia em cada site.
   1. Edite CONFIG para mudar valores e textos.
   2. Edite validateField() para explorar mensagens de erro.
   3. Edite a confirmação no final para experimentar outros feedbacks.
   Este exercício é local: não envia nem armazena dados.
   Em um sistema real, valide os dados e recalcule preços no servidor. */

const form = document.querySelector('#interest-form');
const feedback = document.querySelector('#form-feedback');
const submitButton = form.querySelector('button[type="submit"]');
const buttonLabel = submitButton.querySelector('[data-button-label]');
const originalButtonLabel = buttonLabel.textContent.trim();
const fields = [...form.querySelectorAll('input, select, textarea')];
const currency = new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' });
let isSubmitting = false;

function getOrder() {
  const selected = form.elements.namedItem(CONFIG.optionField).value;
  const quantity = Number(form.elements.namedItem('quantity')?.value || 1);
  const item = CONFIG.options[selected];
  if (!item || !Number.isInteger(quantity) || quantity < 1 || quantity > CONFIG.maxQuantity) return null;
  return { ...item, quantity, total: item.price * quantity };
}

function updateSummary() {
  const order = getOrder();
  document.querySelectorAll('[data-total]').forEach(element => {
    element.textContent = order ? currency.format(order.total) : '—';
  });
  document.querySelectorAll('[data-selection]').forEach(element => {
    element.textContent = order ? order.label : 'Selecione uma opção';
  });
  document.querySelectorAll('[data-quantity]').forEach(element => {
    element.textContent = order ? String(order.quantity).padStart(2, '0') : '—';
  });
}

function validateField(field) {
  let message = '';
  field.setCustomValidity('');
  if (field.required && (field.type === 'checkbox' ? !field.checked : !field.value.trim())) {
    message = field.dataset.required || 'Preencha este campo para continuar.';
  } else if (field.name === 'name' && field.value.trim().length < 2) {
    message = 'Informe um nome com pelo menos 2 caracteres.';
  } else if (field.validity.typeMismatch) {
    message = 'Informe um e-mail válido, como nome@exemplo.com.';
  } else if (field.value.length > (field.maxLength > 0 ? field.maxLength : Infinity)) {
    message = `Use no máximo ${field.maxLength} caracteres.`;
  } else if (!field.validity.valid) {
    message = field.dataset.invalid || 'Confira o valor informado neste campo.';
  }
  field.setCustomValidity(message);
  field.classList.toggle('is-invalid', Boolean(message));
  if (message) field.setAttribute('aria-invalid', 'true');
  else field.removeAttribute('aria-invalid');
  const error = document.getElementById(`${field.id}-error`);
  if (error) error.textContent = message;
  return !message;
}

fields.forEach(field => {
  const handleEdit = () => {
    if (field.dataset.touched === 'true') validateField(field);
    if (!isSubmitting) {
      feedback.textContent = '';
      feedback.removeAttribute('data-state');
    }
    updateSummary();
  };
  field.addEventListener('input', handleEdit);
  field.addEventListener('change', handleEdit);
  field.addEventListener('blur', () => {
    if (field.value || field.dataset.touched === 'true') {
      field.dataset.touched = 'true';
      validateField(field);
    }
  });
});

form.addEventListener('submit', async event => {
  event.preventDefault();
  if (isSubmitting) return;
  const invalidFields = fields.filter(field => {
    field.dataset.touched = 'true';
    return !validateField(field);
  });
  if (invalidFields.length) {
    feedback.dataset.state = 'error';
    feedback.textContent = 'Quase lá. Confira os campos indicados e tente novamente.';
    invalidFields[0].focus();
    return;
  }
  const order = getOrder();
  if (!order) {
    feedback.dataset.state = 'error';
    feedback.textContent = 'Escolha uma opção e uma quantidade disponíveis.';
    return;
  }

  // Capturamos o estado antes da simulação para manter a confirmação consistente.
  const name = form.elements.namedItem('name').value.trim();
  const note = form.elements.namedItem('note')?.value.trim();
  const detail = CONFIG.detailField ? form.elements.namedItem(CONFIG.detailField) : null;
  const detailLabel = detail ? detail.options[detail.selectedIndex].textContent.trim() : '';
  isSubmitting = true;
  submitButton.disabled = true;
  form.setAttribute('aria-busy', 'true');
  fields.forEach(field => { field.disabled = true; });
  buttonLabel.textContent = 'Preparando confirmação…';
  feedback.removeAttribute('data-state');
  feedback.textContent = '';

  // Pequena espera para que o estado de carregamento possa ser observado na oficina.
  await new Promise(resolve => window.setTimeout(resolve, 500));
  feedback.dataset.state = 'success';
  const summary = `${CONFIG.success}, ${name}! ${order.quantity} × ${order.label}${detailLabel ? ` · ${detailLabel}` : ''}. Total: ${currency.format(order.total)}.`;
  // textContent exibe texto do usuário sem interpretá-lo como HTML.
  feedback.textContent = `${summary}${note ? ` Sua observação: “${note}”.` : ''} Esta é uma simulação; nenhum dado foi enviado.`;
  fields.forEach(field => { field.disabled = false; });
  submitButton.disabled = false;
  buttonLabel.textContent = originalButtonLabel;
  form.removeAttribute('aria-busy');
  isSubmitting = false;
  feedback.focus();
});

updateSummary();

// Feedback visual da variante, sem alterar arquivos nem carregar imagens externas.
const shirtImage = document.querySelector('.shirt-stage img');
const shirtCaption = document.querySelector('.shirt-caption');
const shirtColors = { preto: 'Preto + lima', offwhite: 'Off-white + preto', lima: 'Lima + preto' };
form.elements.color.addEventListener('change', () => {
  const color = form.elements.color.value;
  shirtImage.src = `assets/camiseta${color === 'preto' ? '' : `-${color}`}.svg`;
  shirtImage.alt = `Camiseta oversized ${shirtColors[color]}, com estampa de estrela e a palavra RUÍDO.`;
  shirtCaption.textContent = `PEÇA 01 / ${shirtColors[color].toUpperCase()}`;
});
