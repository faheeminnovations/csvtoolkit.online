<?php
$tool = getToolBySlug('csv-viewer') ?? [
    'name'              => 'CSV Online Viewer',
    'slug'              => 'csv-viewer',
    'h1'                => 'CSV Online Viewer — View CSV Files in Your Browser',
    'short_description' => 'View and browse CSV file contents in a searchable, sortable table — no upload required.',
    'meta_title'        => 'CSV Online Viewer — View CSV Files in Browser Free',
    'meta_description'  => 'Open and view any CSV file in your browser without uploading. Searchable, sortable table with column statistics and row count.',
    'instructions'      => '<ol><li>Click <strong>Browse</strong> or drag your CSV file into the upload area.</li><li>Your file is processed entirely in your browser — it is never uploaded to any server.</li><li>Use the search box to filter rows, click column headers to sort, and use pagination to navigate large files.</li></ol>',
    'content'           => '<p>The CSV Viewer is a 100% client-side tool. Your file never leaves your device — it is read directly in your browser using the FileReader API. This makes it ideal for viewing sensitive or confidential CSV files without any privacy concerns.</p>',
];
$faqs         = getToolFaqs($tool['id'] ?? 0);
$relatedTools = getRelatedTools('csv-viewer');

// No server-side POST processing — entirely browser-based
ob_start();
?>

<!-- Privacy callout -->
<div class="alert alert-success d-flex gap-2 align-items-start mb-3" role="note">
    <i class="bi bi-browser-chrome flex-shrink-0 mt-1 fs-5" aria-hidden="true"></i>
    <div>
        <strong>100% Browser-Based.</strong> Your CSV file is never uploaded to any server.
        All processing happens locally in your browser using JavaScript.
    </div>
</div>

<!-- File input -->
<div class="upload-area mb-3" id="viewerUploadArea" role="button" tabindex="0">
    <input type="file" id="viewerFile" accept=".csv,.txt,.tsv">
    <div class="upload-icon"><i class="bi bi-eye" aria-hidden="true"></i></div>
    <h3>Drag &amp; drop your CSV here</h3>
    <p>or click to browse — processed entirely in your browser</p>
    <p class="upload-filename text-primary fw-semibold d-none mt-2" id="viewerFileName"></p>
</div>

<!-- Toolbar (hidden until file loaded) -->
<div id="viewerToolbar" class="d-none mb-3">
    <div class="row g-2 align-items-center">
        <div class="col-md-5">
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-search" aria-hidden="true"></i></span>
                <input type="search" id="viewerSearch" class="form-control" placeholder="Search all cells…" aria-label="Search CSV data">
            </div>
        </div>
        <div class="col-md-3">
            <select id="viewerDelimiter" class="form-select" aria-label="Delimiter">
                <option value="auto">Auto delimiter</option>
                <option value=",">Comma ( , )</option>
                <option value=";">Semicolon ( ; )</option>
                <option value="&#9;">Tab</option>
                <option value="|">Pipe ( | )</option>
            </select>
        </div>
        <div class="col-md-4 text-md-end">
            <button id="viewerReload" class="btn btn-outline-secondary btn-sm" type="button">
                <i class="bi bi-arrow-clockwise me-1" aria-hidden="true"></i>Reload
            </button>
            <button id="viewerExportBtn" class="btn btn-outline-primary btn-sm ms-1" type="button">
                <i class="bi bi-download me-1" aria-hidden="true"></i>Export Filtered
            </button>
        </div>
    </div>
</div>

<!-- Stats (hidden until loaded) -->
<div id="viewerStats" class="mb-3"></div>

<!-- Table output -->
<div id="viewerTableContainer"></div>

<script>
(function() {
    'use strict';

    const PAGE_SIZE    = 500;
    let allRows        = [];
    let filteredRows   = [];
    let headers        = [];
    let currentPage    = 1;
    let sortCol        = -1;
    let sortAsc        = true;
    let currentFile    = null;

    const fileInput    = document.getElementById('viewerFile');
    const toolbar      = document.getElementById('viewerToolbar');
    const statsEl      = document.getElementById('viewerStats');
    const container    = document.getElementById('viewerTableContainer');
    const searchInput  = document.getElementById('viewerSearch');
    const delimSelect  = document.getElementById('viewerDelimiter');
    const reloadBtn    = document.getElementById('viewerReload');
    const exportBtn    = document.getElementById('viewerExportBtn');
    const fileNameEl   = document.getElementById('viewerFileName');

    // File input
    fileInput.addEventListener('change', function() {
        if (this.files.length) {
            currentFile = this.files[0];
            loadFile(currentFile);
        }
    });

    reloadBtn.addEventListener('click', () => { if (currentFile) loadFile(currentFile); });

    delimSelect.addEventListener('change', () => { if (currentFile) loadFile(currentFile); });

    searchInput.addEventListener('input', () => { applyFilter(); currentPage = 1; renderTable(); });

    exportBtn.addEventListener('click', exportFiltered);

    function loadFile(file) {
        if (file.size > 500 * 1024 * 1024) {
            CsvTk.showToast('File is too large. Maximum 500 MB.', 'warning');
            return;
        }
        const reader = new FileReader();
        reader.onload = e => parseContent(e.target.result);
        reader.readAsText(file, 'UTF-8');

        fileNameEl.textContent = file.name + ' (' + CsvTk.formatBytes(file.size) + ')';
        fileNameEl.classList.remove('d-none');
    }

    function parseContent(text) {
        // Strip BOM
        if (text.charCodeAt(0) === 0xFEFF) text = text.slice(1);

        // Detect delimiter
        let delim = delimSelect.value;
        if (delim === 'auto') delim = detectDelimiter(text);

        // Normalise line endings
        text = text.replace(/\r\n/g, '\n').replace(/\r/g, '\n').replace(/\n$/, '');

        const rows = parseCsvFull(text, delim);
        if (!rows.length) { container.innerHTML = '<div class="alert alert-warning">No data found in file.</div>'; return; }

        headers  = rows[0] || [];
        allRows  = rows.slice(1).filter(r => r.some(c => c !== ''));
        sortCol  = -1;
        sortAsc  = true;
        currentPage = 1;

        applyFilter();
        toolbar.classList.remove('d-none');
        renderStats();
        renderTable();
    }

    function detectDelimiter(text) {
        const lines = text.split('\n').slice(0, 5).filter(l => l.trim());
        const candidates = [',', ';', '\t', '|'];
        let best = ',', bestScore = 0;
        for (const d of candidates) {
            const score = lines.reduce((s, l) => s + countOutsideQuotes(l, d), 0);
            if (score > bestScore) { bestScore = score; best = d; }
        }
        return best;
    }

    function countOutsideQuotes(line, delim) {
        let inQ = false, count = 0;
        for (let i = 0; i < line.length; i++) {
            if (line[i] === '"') { if (inQ && line[i+1] === '"') i++; else inQ = !inQ; }
            else if (!inQ && line[i] === delim) count++;
        }
        return count;
    }

    function parseCsvFull(text, delim) {
        const rows = [];
        let i = 0;
        while (i <= text.length) {
            const { fields, end } = parseRow(text, i, delim);
            rows.push(fields);
            if (end >= text.length) break;
            i = end + 1;
        }
        return rows;
    }

    function parseRow(text, start, delim) {
        const fields = [];
        let i = start;
        while (i <= text.length) {
            if (i === text.length || text[i] === '\n') break;
            if (text[i] === '"') {
                let field = '', j = i + 1;
                while (j < text.length) {
                    if (text[j] === '"') {
                        if (text[j+1] === '"') { field += '"'; j += 2; }
                        else { j++; break; }
                    } else field += text[j++];
                }
                fields.push(field);
                i = j;
                if (i < text.length && text[i] === delim) i++;
                else break;
            } else {
                let end = i;
                while (end < text.length && text[end] !== delim && text[end] !== '\n') end++;
                fields.push(text.slice(i, end));
                i = end;
                if (i < text.length && text[i] === delim) i++;
                else break;
            }
        }
        return { fields, end: i };
    }

    function applyFilter() {
        const q = (searchInput.value || '').toLowerCase().trim();
        if (!q) {
            filteredRows = allRows;
        } else {
            filteredRows = allRows.filter(row => row.some(cell => cell.toLowerCase().includes(q)));
        }
        // Apply sort
        if (sortCol >= 0) {
            filteredRows = [...filteredRows].sort((a, b) => {
                const av = (a[sortCol] || '').toLowerCase();
                const bv = (b[sortCol] || '').toLowerCase();
                return sortAsc ? av.localeCompare(bv) : bv.localeCompare(av);
            });
        }
    }

    function renderTable() {
        const start      = (currentPage - 1) * PAGE_SIZE;
        const pageRows   = filteredRows.slice(start, start + PAGE_SIZE);
        const totalPages = Math.ceil(filteredRows.length / PAGE_SIZE);

        let html = '<div class="csv-table-wrapper"><table class="table table-sm table-bordered table-hover csv-table" id="viewerTable"><thead><tr>';
        html += '<th class="text-muted" style="cursor:default">#</th>';
        headers.forEach((h, ci) => {
            const icon = sortCol === ci ? (sortAsc ? ' ↑' : ' ↓') : '';
            html += `<th onclick="csvViewerSort(${ci})" style="cursor:pointer" title="Click to sort">${escHtml(h)}${icon}</th>`;
        });
        html += '</tr></thead><tbody>';

        if (pageRows.length === 0) {
            html += `<tr><td colspan="${headers.length + 1}" class="text-center text-muted">No rows match your search.</td></tr>`;
        } else {
            pageRows.forEach((row, ri) => {
                html += '<tr><td class="text-muted font-mono small">' + (start + ri + 1) + '</td>';
                for (let ci = 0; ci < headers.length; ci++) {
                    const val = row[ci] != null ? row[ci] : '';
                    html += '<td>' + escHtml(val) + '</td>';
                }
                html += '</tr>';
            });
        }
        html += '</tbody></table></div>';

        // Pagination
        if (totalPages > 1) {
            html += '<nav aria-label="Viewer page navigation" class="mt-2">';
            html += '<ul class="pagination pagination-sm justify-content-center mb-0">';
            if (currentPage > 1) html += `<li class="page-item"><button class="page-link" onclick="csvViewerGo(${currentPage-1})">‹</button></li>`;
            const start2 = Math.max(1, currentPage - 2);
            const end2   = Math.min(totalPages, currentPage + 2);
            if (start2 > 1) html += '<li class="page-item disabled"><span class="page-link">…</span></li>';
            for (let p = start2; p <= end2; p++) {
                html += `<li class="page-item${p===currentPage?' active':''}"><button class="page-link" onclick="csvViewerGo(${p})">${p}</button></li>`;
            }
            if (end2 < totalPages) html += '<li class="page-item disabled"><span class="page-link">…</span></li>';
            if (currentPage < totalPages) html += `<li class="page-item"><button class="page-link" onclick="csvViewerGo(${currentPage+1})">›</button></li>`;
            html += '</ul></nav>';
        }

        container.innerHTML = html;
    }

    function renderStats() {
        const empty = allRows.flat().filter(c => c === '').length;
        const total = allRows.length;
        statsEl.innerHTML =
            '<div class="validation-summary">' +
            `<div class="val-stat"><div class="val-num text-primary">${total.toLocaleString()}</div><div class="val-lbl">Rows</div></div>` +
            `<div class="val-stat"><div class="val-num text-success">${headers.length}</div><div class="val-lbl">Columns</div></div>` +
            `<div class="val-stat"><div class="val-num text-warning">${empty.toLocaleString()}</div><div class="val-lbl">Empty Cells</div></div>` +
            `<div class="val-stat"><div class="val-num text-info">${filteredRows.length.toLocaleString()}</div><div class="val-lbl">Filtered</div></div>` +
            '</div>';
    }

    window.csvViewerSort = function(ci) {
        if (sortCol === ci) sortAsc = !sortAsc;
        else { sortCol = ci; sortAsc = true; }
        applyFilter();
        currentPage = 1;
        renderTable();
    };
    window.csvViewerGo = function(page) {
        currentPage = page;
        renderTable();
        container.scrollIntoView({ behavior: 'smooth', block: 'start' });
    };

    function exportFiltered() {
        const rows  = filteredRows;
        const lines = [headers.join(',')];
        rows.forEach(r => {
            lines.push(r.map(c => /[,"\n]/.test(c) ? '"' + c.replace(/"/g, '""') + '"' : c).join(','));
        });
        const blob = new Blob([lines.join('\r\n')], { type: 'text/csv' });
        const a    = document.createElement('a');
        a.href     = URL.createObjectURL(blob);
        a.download = 'filtered_export.csv';
        a.click();
        URL.revokeObjectURL(a.href);
    }

    function escHtml(s) {
        return String(s == null ? '' : s)
            .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

})();
</script>

<?php
$toolContent = ob_get_clean();
require_once ROOT_PATH . '/includes/tool-layout.php';
