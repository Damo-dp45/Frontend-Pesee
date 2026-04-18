import './stimulus_bootstrap.js';
/*
 * Welcome to your app's main JavaScript file!
 *
 * We recommend including the built version of this JavaScript file
 * (and its CSS file) in your base layout (base.html.twig).
 */

// any CSS you import will output into a single css file (app.css in this case)
import './styles/app.css';

import * as XLSX from 'xlsx';
import jsPDF from 'jspdf';
import autoTable from 'jspdf-autotable';

document.addEventListener('turbo:load', () => {
    initTheme()
    initSidebar()
    initSidebarUserDropdown()
    initProfileDropdown()
    initAccordions()
    initBarChart()

    initFiltersToggle()
    initSiteSelect();
    initExportExcel();
    initExportPdf()

    initPasswordToggle()
    initCodeUppercase()
})

function initTheme() {
    const btn      = document.getElementById('theme-toggle');
    const iconSun  = document.getElementById('icon-sun');
    const iconMoon = document.getElementById('icon-moon');
    if (!btn) return;

    const apply = (dark) => {
        document.documentElement.classList.toggle('dark', dark);
        if (iconSun)  iconSun.style.display  = dark ? 'block' : 'none';
        if (iconMoon) iconMoon.style.display  = dark ? 'none'  : 'block';
        localStorage.setItem('theme', dark ? 'dark' : 'light');
    };

    // Appliquer au chargement
    apply(localStorage.getItem('theme') === 'dark');

    btn.addEventListener('click', () => {
        apply(!document.documentElement.classList.contains('dark'));
    });
}

function initSidebar() {
    const sidebar   = document.getElementById('sidebar');
    const toggleBtn = document.getElementById('sidebar-toggle');
    const overlay   = document.getElementById('sidebar-overlay');
    if (!sidebar) return;

    if (toggleBtn && overlay) {
        toggleBtn.addEventListener('click', () => {
            sidebar.classList.add('is-open');
            overlay.classList.add('is-open');
        });

        overlay.addEventListener('click', () => {
            sidebar.classList.remove('is-open');
            overlay.classList.remove('is-open');
        });
    }

    window.addEventListener('resize', () => {
        if (window.innerWidth >= 1024) {
            sidebar.classList.remove('is-open');
            overlay?.classList.remove('is-open');
        }
    });
}

function initSidebarUserDropdown() {
    const btn      = document.getElementById('sidebar-user-toggle');
    const menu     = document.getElementById('sidebar-user-menu');
    if (!btn || !menu) return;

    btn.addEventListener('click', (e) => {
        e.stopPropagation();
        const isOpen = menu.classList.toggle('is-open');
        btn.setAttribute('aria-expanded', isOpen);
    });

    // Fermer en cliquant en dehors
    document.addEventListener('click', (e) => {
        if (!menu.contains(e.target) && e.target !== btn) {
            menu.classList.remove('is-open');
            btn.setAttribute('aria-expanded', 'false');
        }
    });

    // Fermer avec Escape
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && menu.classList.contains('is-open')) {
            menu.classList.remove('is-open');
            btn.setAttribute('aria-expanded', 'false');
            btn.focus();
        }
    });
}

function initProfileDropdown() {
    const btn      = document.getElementById('profile-toggle');
    const dropdown = document.getElementById('profile-dropdown');
    if (!btn || !dropdown) return;

    // Ouvrir / fermer au clic sur l'avatar
    btn.addEventListener('click', (e) => {
        e.stopPropagation();
        const isOpen = dropdown.classList.toggle('is-open');
        btn.setAttribute('aria-expanded', isOpen);
    });

    // Fermer en cliquant en dehors
    document.addEventListener('click', (e) => {
        if (!dropdown.contains(e.target) && e.target !== btn) {
            dropdown.classList.remove('is-open');
            btn.setAttribute('aria-expanded', 'false');
        }
    });

    // Fermer avec Escape
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && dropdown.classList.contains('is-open')) {
            dropdown.classList.remove('is-open');
            btn.setAttribute('aria-expanded', 'false');
            btn.focus();
        }
    });
}

function initAccordions() {
    const triggers = document.querySelectorAll('.sidebar__accordion-trigger');
    if (!triggers.length) return;

    // Restaurer l'état des accordéons depuis localStorage
    triggers.forEach(trigger => {
        const key     = `accordion-${trigger.dataset.accordion}`;
        const content = document.getElementById(`accordion-${trigger.dataset.accordion}`);
        if (!content) return;

        const isOpen = localStorage.getItem(key) === 'true';
        if (isOpen) {
            content.classList.add('is-open');
            trigger.setAttribute('aria-expanded', 'true');
        }

        trigger.addEventListener('click', () => {
            const nowOpen = content.classList.toggle('is-open');
            trigger.setAttribute('aria-expanded', nowOpen);
            localStorage.setItem(key, nowOpen);
        });
    });
}

function initBarChart() {
    const wrapper = document.getElementById('chart-tonnage');
    if (!wrapper) return;

    const raw = JSON.parse(wrapper.dataset.stats ?? '[]');
    if (!raw.length) {
        wrapper.innerHTML = '<p style="color:var(--muted-foreground);font-size:.8125rem;margin:auto">Aucune donnée</p>';
        return;
    }

    // Extraire les valeurs et labels
    const values = raw.map(r => {
        const val = Number(r.total_poidsnet ?? 0);
        return Number((val / 1000).toFixed(2));
    });

    const labels = raw.map(r => {
        const d = new Date(r.jour?.date ?? r.jour);
        return d.toLocaleDateString('fr-FR', { day: '2-digit', month: 'short' });
    });

    const max = Math.max(...values, 1);

    // Construire les barres
    values.forEach((val, i) => {
        const col = document.createElement('div');
        col.className = 'chart-bar-col';

        const heightPct = Math.round((val / max) * 100);

        const bar = document.createElement('div');
        bar.className = 'chart-bar';
        bar.style.height = `${heightPct}%`;
        bar.title = `${labels[i]} : ${val.toFixed(1)} T`;

        // Label affiché tous les 5 jours pour ne pas encombrer
        const lbl = document.createElement('div');
        lbl.className = 'chart-bar-lbl';
        lbl.textContent = i % 5 === 0 ? labels[i] : '';

        col.appendChild(bar);
        col.appendChild(lbl);
        wrapper.appendChild(col);
    });
}


// ═══════════════════════════════════════════════════
// PANNEAU FILTRES
// ═══════════════════════════════════════════════════

function initFiltersToggle() {
    const trigger = document.getElementById('filters-trigger');
    const body    = document.getElementById('filters-body');
    const chevron = document.getElementById('filters-chevron');
    if (!trigger || !body || !chevron) return;

    trigger.addEventListener('click', () => {
        const isOpen = body.classList.toggle('is-open');
        chevron.classList.toggle('is-open', isOpen);
    });
}

// ═══════════════════════════════════════════════════
// SELECT SITE → RÉFÉRENTIELS SÉQUENTIELS
// ═══════════════════════════════════════════════════

function initSiteSelect() {
    const selectSite = document.getElementById('select-site');
    if (!selectSite) return;

    selectSite.addEventListener('change', async () => {
        const code = selectSite.value;
        resetRefSelects();
        if (!code) return;
        await loadAllReferentiels(code);
    });
}

function resetRefSelects() {
    document.querySelectorAll('.filter-field__select--ref').forEach(select => {
        select.innerHTML      = '<option value="">Tous</option>';
        select.disabled       = true;
        select.dataset.loaded = 'false';
    });
}

async function loadAllReferentiels(code) {
    const types = [
        { type: 'mouvement',    key: 'mouvement',       selectId: 'select-mouvement'    },
        { type: 'produit',      key: 'produit',          selectId: 'select-produit'      },
        { type: 'client',       key: 'client',           selectId: 'select-client'       },
        { type: 'fournisseur',  key: 'fournisseur',      selectId: 'select-fournisseur'  },
        { type: 'transporteur', key: 'transporteur',     selectId: 'select-transporteur' },
        { type: 'destination',  key: 'destination',      selectId: 'select-destination'  },
        { type: 'provenance',   key: 'provenance',       selectId: 'select-provenance'   },
        { type: 'vehicule',     key: 'immatriculation',  selectId: 'select-vehicule'     },
    ];

    for (const { type, key, selectId } of types) {
        const select = document.getElementById(selectId);
        if (!select) continue;

        select.innerHTML = '<option value="">Chargement…</option>';
        select.disabled  = true;

        try {
            const res = await fetch(
                `/referentiels/${encodeURIComponent(code)}/${type}`,
                { credentials: 'include' }
            );

            if (!res.ok) throw new Error(`HTTP ${res.status}`);

            const data = await res.json();

            select.innerHTML = '<option value="">Tous</option>';
            data.forEach(item => {
                const val = item[key] ?? '';
                if (!val) return;
                const opt       = document.createElement('option');
                opt.value       = val;
                opt.textContent = val;
                select.appendChild(opt);
            });

            select.disabled       = false;
            select.dataset.loaded = code;

        } catch (err) {
            console.error(`Erreur chargement ${type}:`, err);
            select.innerHTML = '<option value="">Erreur</option>';
            select.disabled  = false;
        }
    }
}

// ═══════════════════════════════════════════════════
// COLLECTE DONNÉES TABLEAU
// ═══════════════════════════════════════════════════

function collectTableData() {
    const table = document.getElementById('operations-table');
    if (!table) return { headers: [], rows: [] };

    const headers = [...table.querySelectorAll('thead th')]
        .map(th => th.textContent.trim());

    const rows = [...table.querySelectorAll('tbody tr')]
        .filter(tr => !tr.querySelector('.col-empty'))
        .map(tr =>
            [...tr.querySelectorAll('td')].map(td => {
                const clone = td.cloneNode(true);
                clone.querySelectorAll('.cell-sub').forEach(el => el.remove());
                return clone.textContent.trim().replace(/\s+/g, ' ');
            })
        );

    return { headers, rows };
}

// ═══════════════════════════════════════════════════
// EXPORT EXCEL
// ═══════════════════════════════════════════════════

function initExportExcel() {
    const btn = document.getElementById('btn-export-excel');
    if (!btn) return;

    btn.addEventListener('click', () => {
        const { headers, rows } = collectTableData();
        if (!rows.length) {
            alert('Aucune donnée à exporter.');
            return;
        }

        const wb = XLSX.utils.book_new();
        const ws = XLSX.utils.aoa_to_sheet([headers, ...rows]);

        ws['!cols'] = headers.map((h, i) => ({
            wch: Math.max(h.length, ...rows.map(r => (r[i] ?? '').length)) + 2,
        }));

        XLSX.utils.book_append_sheet(wb, ws, 'Opérations');
        XLSX.writeFile(wb, `operations_${formatDateFilename()}.xlsx`);
    });
}

// ═══════════════════════════════════════════════════
// EXPORT PDF
// ═══════════════════════════════════════════════════

function initExportPdf() {
    const btn = document.getElementById('btn-export-pdf');
    if (!btn) return;

    btn.addEventListener('click', () => {
        const { headers, rows } = collectTableData();
        if (!rows.length) {
            alert('Aucune donnée à exporter.');
            return;
        }

        const doc = new jsPDF({ orientation: 'landscape', unit: 'mm', format: 'a4' });

        doc.setFontSize(13);
        doc.setFont('helvetica', 'bold');
        doc.text('Rapport des opérations de pesée', 14, 16);

        doc.setFontSize(9);
        doc.setFont('helvetica', 'normal');
        doc.setTextColor(120);
        doc.text(
            `Généré le ${new Date().toLocaleDateString('fr-FR', {
                day: '2-digit', month: 'long', year: 'numeric',
            })}`,
            14, 22
        );
        doc.setTextColor(0);

        autoTable(doc, {
            head: [headers],
            body: rows,
            startY: 28,
            styles: {
                fontSize: 7.5,
                cellPadding: 2,
            },
            headStyles: {
                fillColor: [69, 55, 41],
                textColor: 255,
                fontStyle: 'bold',
                fontSize: 7,
            },
            alternateRowStyles: {
                fillColor: [250, 249, 247],
            },
            columnStyles: {
                8:  { halign: 'right' },
                9:  { halign: 'right' },
                10: { halign: 'right' },
                11: { halign: 'right' },
            },
        });

        doc.save(`operations_${formatDateFilename()}.pdf`);
    });
}

// ═══════════════════════════════════════════════════
// UTILITAIRE
// ═══════════════════════════════════════════════════

function formatDateFilename() {
    return new Date().toISOString().slice(0, 10);
}

function initPasswordToggle() {
    const btn     = document.getElementById('toggle-password');
    const input   = document.getElementById('password');
    const eyeShow = document.getElementById('eye-show');
    const eyeHide = document.getElementById('eye-hide');
    if (!btn || !input) return;

    btn.addEventListener('click', () => {
        const visible     = input.type === 'text';
        input.type        = visible ? 'password' : 'text';
        eyeShow.style.display = visible ? 'block' : 'none';
        eyeHide.style.display = visible ? 'none'  : 'block';
    });
}

function initCodeUppercase() {
    const input = document.getElementById('codeentreprise');
    if (!input) return;

    input.addEventListener('input', () => {
        const pos   = input.selectionStart;
        input.value = input.value.toUpperCase().replace(/[^A-Z]/g, '');
        input.setSelectionRange(pos, pos);
    });
}