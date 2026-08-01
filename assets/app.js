const REQUIRED = ['to_email', 'company', 'industry', 'demo_url', 'scheduled_at'];
const FIELD_META = {
  to_email: { type: 'email', placeholder: 'kontakt@example.com' },
  company: { type: 'text', placeholder: 'Example AS' },
  industry: { type: 'text', placeholder: 'bygg og anlegg' },
  demo_url: { type: 'url', placeholder: 'https://example.com/demo' },
  scheduled_at: { type: 'datetime-local', placeholder: '' },
};

const els = {
  authBox: document.getElementById('authBox'),
  drop: document.getElementById('drop'),
  file: document.getElementById('file'),
  formTableWrap: document.getElementById('formTableWrap'),
  csvTableWrap: document.getElementById('csvTableWrap'),
  emailPreview: document.getElementById('emailPreview'),
  scheduleBtn: document.getElementById('scheduleBtn'),
  scheduleCsvBtn: document.getElementById('scheduleCsvBtn'),
  refreshScheduledBtn: document.getElementById('refreshScheduledBtn'),
  refreshSentBtn: document.getElementById('refreshSentBtn'),
  statusMsg: document.getElementById('statusMsg'),
  scheduledList: document.getElementById('scheduledList'),
  sentList: document.getElementById('sentList'),
  tabCompose: document.getElementById('tabCompose'),
  tabScheduled: document.getElementById('tabScheduled'),
  tabSent: document.getElementById('tabSent'),
  composePage: document.getElementById('composePage'),
  scheduledPage: document.getElementById('scheduledPage'),
  sentPage: document.getElementById('sentPage'),
  tabForm: document.getElementById('tabForm'),
  tabCsv: document.getElementById('tabCsv'),
  formPanel: document.getElementById('formPanel'),
  csvPanel: document.getElementById('csvPanel'),
};

let formRows = [emptyRow()];
let csvRows = [];
let loggedIn = false;
let activeTab = 'form';

function emptyRow() {
  return {
    to_email: '',
    company: '',
    industry: '',
    demo_url: '',
    scheduled_at: defaultScheduleValue(),
  };
}

function showStatus(message, type = '') {
  els.statusMsg.textContent = message;
  els.statusMsg.className = 'status show' + (type ? ` ${type}` : '');
}

function clearStatus() {
  els.statusMsg.className = 'status';
  els.statusMsg.textContent = '';
}

function defaultScheduleValue() {
  const d = new Date(Date.now() + 60 * 60 * 1000);
  d.setMinutes(Math.ceil(d.getMinutes() / 5) * 5, 0, 0);
  const pad = (n) => String(n).padStart(2, '0');
  return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
}

function datetimeLocalToSchedule(value) {
  return String(value || '').replace('T', ' ').trim();
}

function scheduleToDatetimeLocal(value) {
  const trimmed = String(value || '').trim();
  if (!trimmed) return defaultScheduleValue();
  if (trimmed.includes('T')) return trimmed.slice(0, 16);
  return trimmed.replace(' ', 'T').slice(0, 16);
}

function parseCsv(text) {
  const lines = text.replace(/^\uFEFF/, '').trim().split(/\r?\n/);
  if (lines.length < 2) {
    throw new Error('CSV needs a header row and at least one data row.');
  }

  const headers = splitCsvLine(lines[0]).map((h) => h.trim().toLowerCase());
  for (const key of REQUIRED) {
    if (!headers.includes(key)) {
      throw new Error(`Missing column: ${key}`);
    }
  }

  return lines.slice(1).filter(Boolean).map((line) => {
    const values = splitCsvLine(line);
    const row = {};
    headers.forEach((header, idx) => {
      row[header] = (values[idx] ?? '').trim();
    });
    row.scheduled_at = scheduleToDatetimeLocal(row.scheduled_at);
    return row;
  });
}

function splitCsvLine(line) {
  const out = [];
  let current = '';
  let inQuotes = false;

  for (let i = 0; i < line.length; i += 1) {
    const ch = line[i];
    if (ch === '"') {
      if (inQuotes && line[i + 1] === '"') {
        current += '"';
        i += 1;
      } else {
        inQuotes = !inQuotes;
      }
    } else if (ch === ',' && !inQuotes) {
      out.push(current);
      current = '';
    } else {
      current += ch;
    }
  }

  out.push(current);
  return out;
}

function escapeHtml(value) {
  return String(value)
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;');
}

function normalizeUrl(value) {
  const url = String(value || '').trim();
  if (!url) return '';
  if (/^https?:\/\//i.test(url)) return url;
  if (/^https?:\//i.test(url)) return url.replace(/^https?:\//i, (m) => m.startsWith('https') ? 'https://' : 'http://');
  return `https://${url}`;
}

function renderEmail(row) {
  const company = row.company || '…';
  const industry = row.industry || '…';

  return `
    <p>Hei, ${escapeHtml(company)} 😊</p>
    <p>Jeg heter Jonas og utvikler programvare innen ${escapeHtml(industry)}. Jeg lurer på om dere i ${escapeHtml(company)} er interessert i et samarbeid hvor jeg lager programvare for dere gratis, imot at dere hjelper meg ved å fortelle hva dere trenger og hvilke problemer dere har som kanskje kan løses med software.</p>
    <p>På denne måten kan jeg utvikle et produkt som tilsvarende bedrifter virkelig har behov for, og dere får skreddersydd programvare gratis.</p>
    <p><strong>Link til gratis skreddersydd demo:</strong> <a href="${escapeHtml(normalizeUrl(row.demo_url) || '#')}">${escapeHtml(normalizeUrl(row.demo_url) || '…')}</a><br>
    Jeg har også laget en gratis skreddersydd demo av en løsning jeg tror kan være nyttig for dere.<br>
    Hvis dere er interessert, kan jeg videreutvikle løsningen eller utvikle noe annet dere har behov for.</p>
    <p><strong>Hvilke programvarer bruker dere mest nå?</strong><br>
    <strong>Kan jeg sende noen mer detaljerte spørsmål for å finne ut om deres behov?</strong></p>
    <p>Ser frem til å høre fra dere,<br>
    Jonas Wingan</p>
    <p>
      <img src="https://raw.githubusercontent.com/jonaswing/email-sender/main/images/image.png" alt="Jonas Wingan" width="240" style="width:240px; max-width:100%; height:auto; border:0;">
    </p>
    <p>
      LinkedIn<br>
      <a href="https://www.linkedin.com/in/jonas-wingan-8a35051b2">www.linkedin.com/in/jonas-wingan-8a35051b2</a><br>
      Github<br>
      <a href="https://github.com/jonaswing">https://github.com/jonaswing</a>
    </p>
    <p>
      Jonas Wingan &amp; Co<br>
      Startuplab, Gaustadalléen 21<br>
      0349 Oslo
    </p>
  `;
}

function normalizeRows(rows) {
  return rows.map((row) => ({
    to_email: (row.to_email || '').trim(),
    company: (row.company || '').trim(),
    industry: (row.industry || '').trim(),
    demo_url: normalizeUrl(row.demo_url),
    scheduled_at: datetimeLocalToSchedule(row.scheduled_at),
  }));
}

function completeRows(rows) {
  return normalizeRows(rows).filter((row) => REQUIRED.every((key) => row[key]));
}

function renderEditableTable(target, rows, { removable }) {
  const head = [
    ...REQUIRED.map((h) => `<th>${h}</th>`),
    removable ? '<th></th>' : '',
  ].join('');

  const body = rows.map((row, index) => `
    <tr data-row="${index}">
      ${REQUIRED.map((field) => {
        const meta = FIELD_META[field];
        const value = field === 'scheduled_at'
          ? scheduleToDatetimeLocal(row[field])
          : (row[field] || '');
        return `
          <td>
            <input
              class="table-input"
              type="${meta.type}"
              data-field="${field}"
              data-index="${index}"
              value="${escapeHtml(value)}"
              placeholder="${escapeHtml(meta.placeholder)}"
            >
          </td>
        `;
      }).join('')}
      ${removable ? `
        <td>
          <button type="button" class="linkish" data-remove="${index}" ${rows.length === 1 ? 'disabled' : ''}>Remove</button>
        </td>
      ` : ''}
    </tr>
  `).join('');

  target.innerHTML = `<table class="input-table"><thead><tr>${head}</tr></thead><tbody>${body}</tbody></table>`;

  target.querySelectorAll('.table-input').forEach((input) => {
    input.addEventListener('input', () => {
      const index = Number(input.dataset.index);
      const field = input.dataset.field;
      rows[index][field] = input.value;
      updatePreview();
      updateScheduleButtons();
    });
  });

  target.querySelectorAll('[data-remove]').forEach((btn) => {
    btn.addEventListener('click', () => {
      if (rows.length <= 1) return;
      rows.splice(Number(btn.dataset.remove), 1);
      renderFormTable();
      updatePreview();
      updateScheduleButtons();
    });
  });
}

function renderFormTable() {
  renderEditableTable(els.formTableWrap, formRows, { removable: true });
}

function renderCsvTable() {
  if (!csvRows.length) {
    els.csvTableWrap.hidden = true;
    els.csvTableWrap.innerHTML = '';
    return;
  }

  els.csvTableWrap.hidden = false;
  renderEditableTable(els.csvTableWrap, csvRows, { removable: true });
}

function renderPreviews(rows) {
  if (!rows.length) {
    els.emailPreview.innerHTML = '';
    return;
  }

  els.emailPreview.innerHTML = rows.map((row) => {
    const normalized = {
      ...row,
      scheduled_at: datetimeLocalToSchedule(row.scheduled_at),
    };
    return `<div class="email-preview">${renderEmail(normalized)}</div>`;
  }).join('');
}

function updatePreview() {
  const rows = activeTab === 'form' ? formRows : csvRows;
  renderPreviews(rows);
}

function updateScheduleButtons() {
  els.scheduleBtn.disabled = !(loggedIn && completeRows(formRows).length > 0);
  els.scheduleCsvBtn.disabled = !(loggedIn && completeRows(csvRows).length > 0);
}

async function loadMe() {
  const res = await fetch('api/me.php');
  const data = await res.json();

  if (!data.configured) {
    els.authBox.innerHTML = '';
    loggedIn = false;
    return;
  }

  if (!data.logged_in) {
    els.authBox.innerHTML = `
      <div class="user">Not connected to Outlook</div>
      <a class="btn btn-primary" href="api/login.php">Connect Microsoft</a>
    `;
    loggedIn = false;
    updateScheduleButtons();
    return;
  }

  loggedIn = true;
  els.authBox.innerHTML = `
    <div class="user">${escapeHtml(data.user.email || data.user.name || '')}</div>
    <a class="btn btn-danger" href="api/logout.php">Disconnect</a>
  `;
  updateScheduleButtons();
  await loadStatus();
}

function formatWhen(value) {
  if (!value) return '—';
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return value;
  return date.toLocaleString('nb-NO', {
    dateStyle: 'medium',
    timeStyle: 'short',
  });
}

function renderStatusTable(target, items, mode) {
  const rows = items.length
    ? items.map((item) => `
      <tr>
        <td>${escapeHtml(item.to_email || '')}</td>
        <td>${escapeHtml(item.company || '')}</td>
        <td>${escapeHtml(item.industry || '')}</td>
        <td>${escapeHtml(item.demo_url || '')}</td>
        <td>${escapeHtml(item.scheduled_at || '')}</td>
      </tr>
    `).join('')
    : `<tr><td colspan="5" class="muted">No ${mode} emails found.</td></tr>`;

  target.innerHTML = `
    <table>
      <thead>
        <tr>
          <th>to_email</th>
          <th>company</th>
          <th>industry</th>
          <th>demo_url</th>
          <th>scheduled_at</th>
        </tr>
      </thead>
      <tbody>${rows}</tbody>
    </table>
  `;
}

async function loadStatus() {
  if (!loggedIn) return;

  const res = await fetch('api/status.php');
  const data = await res.json();
  if (!data.ok) {
    showStatus(data.error || 'Could not load status', 'error');
    return;
  }

  renderStatusTable(els.scheduledList, data.scheduled || [], 'scheduled');
  renderStatusTable(els.sentList, data.sent || [], 'sent');
}

async function scheduleRows(rows, button) {
  const ready = completeRows(rows);
  if (!ready.length || !loggedIn) {
    showStatus('Fill in all fields for at least one row.', 'error');
    return;
  }

  button.disabled = true;
  showStatus('Scheduling emails in Outlook…');

  const res = await fetch('api/schedule.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ rows: ready }),
  });

  const data = await res.json();
  if (!res.ok && !data.results) {
    showStatus(data.error || 'Scheduling failed', 'error');
    button.disabled = false;
    updateScheduleButtons();
    return;
  }

  const failed = (data.results || []).filter((r) => !r.ok);
  if (failed.length) {
    const details = failed.map((f) => `Row ${f.row}: ${f.error}`).join(' | ');
    showStatus(`Scheduled ${data.scheduled || 0}, failed ${data.failed || 0}. ${details}`, data.scheduled ? 'ok' : 'error');
  } else {
    showStatus(`Scheduled ${data.scheduled} email(s) in Outlook.`, 'ok');
  }

  await loadStatus();
  button.disabled = false;
  updateScheduleButtons();
}

function handleFile(file) {
  if (!file) return;
  const reader = new FileReader();
  reader.onload = () => {
    try {
      csvRows = parseCsv(String(reader.result || ''));
      clearStatus();
      renderCsvTable();
      updatePreview();
      updateScheduleButtons();
      showStatus(`Loaded ${csvRows.length} row(s) from CSV.`, 'ok');
    } catch (err) {
      csvRows = [];
      renderCsvTable();
      updatePreview();
      updateScheduleButtons();
      showStatus(err.message || 'Could not parse CSV', 'error');
    }
  };
  reader.readAsText(file);
}

function showMainTab(whichTab) {
  const pages = {
    compose: els.composePage,
    scheduled: els.scheduledPage,
    sent: els.sentPage,
  };
  const tabs = {
    compose: els.tabCompose,
    scheduled: els.tabScheduled,
    sent: els.tabSent,
  };

  Object.entries(pages).forEach(([key, page]) => {
    page.hidden = key !== whichTab;
    tabs[key].classList.toggle('active', key === whichTab);
  });

  if (whichTab === 'scheduled' || whichTab === 'sent') {
    loadStatus();
  }
}

function showInputTab(whichTab) {
  activeTab = whichTab;
  const isForm = whichTab === 'form';
  els.tabForm.classList.toggle('active', isForm);
  els.tabCsv.classList.toggle('active', !isForm);
  els.formPanel.hidden = !isForm;
  els.csvPanel.hidden = isForm;
  updatePreview();
  updateScheduleButtons();
}

els.drop.addEventListener('click', () => els.file.click());
els.file.addEventListener('change', () => handleFile(els.file.files?.[0]));

['dragenter', 'dragover'].forEach((eventName) => {
  els.drop.addEventListener(eventName, (e) => {
    e.preventDefault();
    els.drop.classList.add('dragover');
  });
});

['dragleave', 'drop'].forEach((eventName) => {
  els.drop.addEventListener(eventName, (e) => {
    e.preventDefault();
    els.drop.classList.remove('dragover');
  });
});

els.drop.addEventListener('drop', (e) => {
  handleFile(e.dataTransfer?.files?.[0]);
});

els.scheduleBtn.addEventListener('click', () => scheduleRows(formRows, els.scheduleBtn));
els.scheduleCsvBtn.addEventListener('click', () => scheduleRows(csvRows, els.scheduleCsvBtn));
els.refreshScheduledBtn.addEventListener('click', loadStatus);
els.refreshSentBtn.addEventListener('click', loadStatus);

els.tabCompose.addEventListener('click', () => showMainTab('compose'));
els.tabScheduled.addEventListener('click', () => showMainTab('scheduled'));
els.tabSent.addEventListener('click', () => showMainTab('sent'));
els.tabForm.addEventListener('click', () => showInputTab('form'));
els.tabCsv.addEventListener('click', () => showInputTab('csv'));

const params = new URLSearchParams(window.location.search);
if (params.get('auth') === 'ok') {
  showStatus('Connected to Microsoft.', 'ok');
  history.replaceState({}, '', window.location.pathname);
}
if (params.get('auth_error')) {
  showStatus(`Login failed: ${params.get('auth_error')}`, 'error');
  history.replaceState({}, '', window.location.pathname);
}

renderFormTable();
renderStatusTable(els.scheduledList, [], 'scheduled');
renderStatusTable(els.sentList, [], 'sent');
updatePreview();
updateScheduleButtons();
loadMe();
