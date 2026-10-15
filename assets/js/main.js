/**
 * CSV Repair Toolkit — Main JavaScript
 * Vanilla JS only. No jQuery or other frameworks.
 */

'use strict';

/* ── Drag-and-drop upload areas ─────────────────────────────────────────── */
document.querySelectorAll('.upload-area').forEach(area => {
  const input = area.querySelector('input[type="file"]');
  if (!input) return;

  area.addEventListener('click', () => input.click());

  area.addEventListener('keydown', e => {
    if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); input.click(); }
  });

  area.setAttribute('tabindex', '0');
  area.setAttribute('role', 'button');
  area.setAttribute('aria-label', 'Click or drag to upload a CSV file');

  ['dragenter', 'dragover'].forEach(evt =>
    area.addEventListener(evt, e => { e.preventDefault(); area.classList.add('dragover'); })
  );
  ['dragleave', 'drop'].forEach(evt =>
    area.addEventListener(evt, () => area.classList.remove('dragover'))
  );

  area.addEventListener('drop', e => {
    e.preventDefault();
    const files = e.dataTransfer?.files;
    if (files?.length) {
      // Use DataTransfer to assign files to input
      try {
        input.files = files;
        input.dispatchEvent(new Event('change', { bubbles: true }));
      } catch (_) {
        // Fallback: inform user to use the button
        showToast('Please use the browse button to select your file.', 'warning');
      }
    }
  });

  input.addEventListener('change', () => {
    if (input.files.length > 0) {
      const f = input.files[0];
      const nameEl = area.querySelector('.upload-filename');
      if (nameEl) {
        nameEl.textContent = f.name + ' (' + formatBytes(f.size) + ')';
        nameEl.classList.remove('d-none');
      }
      area.classList.add('has-file');
    }
  });
});

/* ── Multi-file list (CSV Merger) ─────────────────────────────────────────── */
function initMultiFileInput(inputId, listId) {
  const input = document.getElementById(inputId);
  const list  = document.getElementById(listId);
  if (!input || !list) return;

  let allFiles = [];

  input.addEventListener('change', () => {
    Array.from(input.files).forEach(f => {
      if (!allFiles.find(x => x.name === f.name && x.size === f.size)) {
        allFiles.push(f);
      }
    });
    renderFileList();
    syncFilesToInput();
  });

  function renderFileList() {
    list.innerHTML = '';
    allFiles.forEach((f, i) => {
      const item = document.createElement('div');
      item.className = 'file-list-item';
      item.innerHTML =
        '<i class="bi bi-file-earmark-spreadsheet text-success" aria-hidden="true"></i>' +
        '<span class="file-name" title="' + escHtml(f.name) + '">' + escHtml(f.name) +
        ' <span class="text-muted">(' + formatBytes(f.size) + ')</span></span>' +
        '<button type="button" class="btn btn-sm btn-outline-danger btn-remove" ' +
        'aria-label="Remove ' + escHtml(f.name) + '" data-idx="' + i + '">' +
        '<i class="bi bi-x" aria-hidden="true"></i></button>';
      list.appendChild(item);
    });
    list.querySelectorAll('.btn-remove').forEach(btn => {
      btn.addEventListener('click', () => {
        allFiles.splice(+btn.dataset.idx, 1);
        renderFileList(); syncFilesToInput();
      });
    });
    const counter = document.getElementById(inputId + '_count');
    if (counter) counter.textContent = allFiles.length + ' file(s) selected';
  }

  function syncFilesToInput() {
    const dt = new DataTransfer();
    allFiles.forEach(f => dt.items.add(f));
    input.files = dt.files;
  }
}

/* ── Processing overlay ──────────────────────────────────────────────────── */
function showProcessing(message) {
  let overlay = document.getElementById('processingOverlay');
  if (!overlay) {
    overlay = document.createElement('div');
    overlay.id = 'processingOverlay';
    overlay.className = 'processing-overlay';
    overlay.setAttribute('role', 'status');
    overlay.setAttribute('aria-live', 'assertive');
    overlay.innerHTML =
      '<div class="processing-card">' +
      '<div class="spinner-ring" aria-hidden="true"></div>' +
      '<p class="mb-0 fw-semibold" id="processingMsg">Processing…</p>' +
      '</div>';
    document.body.appendChild(overlay);
  }
  document.getElementById('processingMsg').textContent = message || 'Processing…';
  overlay.classList.add('show');
}

function hideProcessing() {
  const overlay = document.getElementById('processingOverlay');
  if (overlay) overlay.classList.remove('show');
}

/* ── Form auto-show processing overlay ─────────────────────────────────── */
document.querySelectorAll('form.tool-form').forEach(form => {
  form.addEventListener('submit', () => {
    const fileInputs = form.querySelectorAll('input[type="file"]');
    let hasFile = false;
    fileInputs.forEach(i => { if (i.files.length > 0) hasFile = true; });
    if (hasFile) showProcessing('Processing your CSV file…');
  });
});

/* ── Toast notifications ─────────────────────────────────────────────────── */
function showToast(message, type = 'info') {
  const map = { success: 'bg-success', error: 'bg-danger', warning: 'bg-warning text-dark', info: 'bg-info text-dark' };
  let container = document.getElementById('toastContainer');
  if (!container) {
    container = document.createElement('div');
    container.id = 'toastContainer';
    container.className = 'position-fixed bottom-0 end-0 p-3';
    container.style.zIndex = '11000';
    document.body.appendChild(container);
  }
  const id = 'toast_' + Date.now();
  const el = document.createElement('div');
  el.id = id;
  el.className = 'toast align-items-center text-white border-0 ' + (map[type] || map.info);
  el.setAttribute('role', 'alert');
  el.setAttribute('aria-live', 'assertive');
  el.innerHTML =
    '<div class="d-flex">' +
    '<div class="toast-body">' + escHtml(message) + '</div>' +
    '<button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>' +
    '</div>';
  container.appendChild(el);
  const toast = new bootstrap.Toast(el, { delay: 4000 });
  toast.show();
  el.addEventListener('hidden.bs.toast', () => el.remove());
}

/* ── Client-side CSV Viewer ──────────────────────────────────────────────── */
function initCsvViewer(inputId, tableContainerId, statsId) {
  const input     = document.getElementById(inputId);
  const container = document.getElementById(tableContainerId);
  const statsEl   = document.getElementById(statsId);
  if (!input || !container) return;

  const PAGE_SIZE = 500;
  let allRows = [], headers = [], currentPage = 1;

  input.addEventListener('change', () => {
    const file = input.files[0];
    if (!file) return;
    if (file.size > 10 * 1024 * 1024) {
      showToast('File too large for browser preview (max 10 MB).', 'warning');
      return;
    }
    const reader = new FileReader();
    reader.onload = e => parseCsvClient(e.target.result);
    reader.readAsText(file, 'UTF-8');
  });

  function parseCsvClient(text) {
    // Remove UTF-8 BOM
    if (text.charCodeAt(0) === 0xFEFF) text = text.slice(1);
    const rows = [];
    let i = 0;
    while (i < text.length) {
      rows.push(parseCsvRow(text, i));
      i = rows[rows.length - 1]._end;
      rows[rows.length - 1] = rows[rows.length - 1].fields;
    }
    // Remove trailing empty row
    if (rows.length && rows[rows.length - 1].every(c => c === '')) rows.pop();

    headers  = rows[0] || [];
    allRows  = rows.slice(1);
    currentPage = 1;
    renderTable();
    renderStats();
  }

  function parseCsvRow(text, start) {
    const fields = [];
    let i = start;
    while (i < text.length) {
      if (text[i] === '"') {
        let field = '';
        i++;
        while (i < text.length) {
          if (text[i] === '"') {
            if (text[i + 1] === '"') { field += '"'; i += 2; }
            else { i++; break; }
          } else { field += text[i++]; }
        }
        fields.push(field);
      } else {
        let end = i;
        while (end < text.length && text[end] !== ',' && text[end] !== '\n' && text[end] !== '\r') end++;
        fields.push(text.slice(i, end));
        i = end;
      }
      if (i >= text.length || text[i] === '\n' || text[i] === '\r') {
        if (text[i] === '\r' && text[i + 1] === '\n') i += 2;
        else if (text[i] === '\n' || text[i] === '\r') i++;
        break;
      }
      if (text[i] === ',') i++;
    }
    return { fields, _end: i };
  }

  function renderTable() {
    const start = (currentPage - 1) * PAGE_SIZE;
    const pageRows = allRows.slice(start, start + PAGE_SIZE);
    const totalPages = Math.ceil(allRows.length / PAGE_SIZE);

    let html = '<div class="csv-table-wrapper"><table class="table table-sm table-bordered csv-table"><thead><tr>';
    html += '<th>#</th>';
    headers.forEach(h => { html += '<th>' + escHtml(h) + '</th>'; });
    html += '</tr></thead><tbody>';
    pageRows.forEach((row, ri) => {
      html += '<tr><td class="text-muted font-mono">' + (start + ri + 1) + '</td>';
      for (let ci = 0; ci < headers.length; ci++) {
        html += '<td>' + escHtml(row[ci] ?? '') + '</td>';
      }
      html += '</tr>';
    });
    html += '</tbody></table></div>';

    // Pagination
    if (totalPages > 1) {
      html += '<nav aria-label="CSV page navigation" class="mt-2"><ul class="pagination pagination-sm justify-content-center mb-0">';
      if (currentPage > 1) html += '<li class="page-item"><button class="page-link" onclick="csvViewerPage(' + (currentPage - 1) + ')">‹ Prev</button></li>';
      html += '<li class="page-item disabled"><span class="page-link">Page ' + currentPage + ' of ' + totalPages + '</span></li>';
      if (currentPage < totalPages) html += '<li class="page-item"><button class="page-link" onclick="csvViewerPage(' + (currentPage + 1) + ')">Next ›</button></li>';
      html += '</ul></nav>';
    }

    container.innerHTML = html;
  }

  window.csvViewerPage = function(page) {
    currentPage = page;
    renderTable();
    container.scrollIntoView({ behavior: 'smooth', block: 'start' });
  };

  function renderStats() {
    if (!statsEl) return;
    const colCount = headers.length;
    const rowCount = allRows.length;
    const emptyCells = allRows.flat().filter(c => c === '').length;
    statsEl.innerHTML =
      '<div class="row g-2 mt-2">' +
      '<div class="col"><div class="val-stat"><div class="val-num text-primary">' + rowCount.toLocaleString() + '</div><div class="val-lbl">Rows</div></div></div>' +
      '<div class="col"><div class="val-stat"><div class="val-num text-success">' + colCount + '</div><div class="val-lbl">Columns</div></div></div>' +
      '<div class="col"><div class="val-stat"><div class="val-num text-warning">' + emptyCells + '</div><div class="val-lbl">Empty Cells</div></div></div>' +
      '</div>';
  }
}

/* ── Search filter for tool list (delegated to homepage inline script) ──── */
// The homepage handles its own search via applyToolSearch()
// This global listener handles any other pages that embed [data-tool-name] cards

/* ── Copy to clipboard ───────────────────────────────────────────────────── */
document.querySelectorAll('[data-copy]').forEach(btn => {
  btn.addEventListener('click', () => {
    const targetId = btn.dataset.copy;
    const el = document.getElementById(targetId);
    if (!el) return;
    const text = el.value !== undefined ? el.value : el.textContent;
    navigator.clipboard.writeText(text).then(() => {
      showToast('Copied to clipboard!', 'success');
    }).catch(() => {
      showToast('Could not copy — please select and copy manually.', 'warning');
    });
  });
});

/* ── Utility: escape HTML ─────────────────────────────────────────────────── */
function escHtml(str) {
  if (str == null) return '';
  return String(str)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');
}

/* ── Utility: format bytes ─────────────────────────────────────────────────── */
function formatBytes(bytes) {
  const units = ['B', 'KB', 'MB', 'GB'];
  let i = 0;
  while (bytes >= 1024 && i < units.length - 1) { bytes /= 1024; i++; }
  return bytes.toFixed(i === 0 ? 0 : 1) + ' ' + units[i];
}

/* ── Expose helpers for inline scripts ────────────────────────────────────── */
window.CsvTk = { showToast, showProcessing, hideProcessing, initMultiFileInput, initCsvViewer, escHtml, formatBytes };
