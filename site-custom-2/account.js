/* Painel mock: nenhuma alteração é persistida ou enviada ao servidor. */
const adminList = document.querySelector('#admin-list');
if (adminList) {
  const rows = [...adminList.querySelectorAll('[data-record]')];
  const search = document.querySelector('#record-search');
  const status = document.querySelector('#status-filter');
  const normalize = value => value.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().trim();
  function filterRows() {
    const query = normalize(search.value);
    let visible = 0;
    rows.forEach(row => {
      const match = normalize(row.dataset.search).includes(query) && (!status.value || row.dataset.status === status.value);
      row.hidden = !match;
      if (match) visible++;
    });
    document.querySelector('#empty-message').hidden = visible !== 0;
    document.querySelector('#result-count').textContent = `${visible} de ${rows.length} registros`;
  }
  function updateMetrics() {
    document.querySelector('[data-metric="total"]').textContent = rows.length.toString().padStart(2, '0');
    document.querySelector('[data-metric="pending"]').textContent = rows.filter(row => row.dataset.status === 'pending').length.toString().padStart(2, '0');
    document.querySelector('[data-metric="confirmed"]').textContent = rows.filter(row => row.dataset.status === 'confirmed').length.toString().padStart(2, '0');
  }
  search.addEventListener('input', filterRows);
  status.addEventListener('change', filterRows);
  adminList.addEventListener('click', event => {
    const button = event.target.closest('[data-confirm]');
    if (!button || button.disabled) return;
    const row = button.closest('[data-record]');
    row.dataset.status = 'confirmed';
    const pill = row.querySelector('.status-pill');
    pill.dataset.status = 'confirmed';
    pill.textContent = 'Confirmado';
    button.disabled = true;
    button.textContent = 'Confirmado';
    document.querySelector('#admin-feedback').textContent = `${row.dataset.record} confirmado na demonstração. A alteração será desfeita ao recarregar a página.`;
    updateMetrics();
    filterRows();
    if (row.hidden) status.focus();
    else document.querySelector('#admin-feedback').focus();
  });
  updateMetrics();
  filterRows();
}
