const pageRoot = document.querySelector('.admin-main');
const registrationsUrl = pageRoot?.dataset.registrationsUrl || '/api/workshop-registrations';

let allRegistrations = [];
let filteredRegistrations = [];
let currentSort = 'date-desc';
let searchQuery = '';

const loadingEl = document.getElementById('loading-state');
const errorEl = document.getElementById('error-state');
const errorMsgEl = document.getElementById('error-message');
const emptyEl = document.getElementById('empty-state');
const wrapperEl = document.getElementById('table-wrapper');
const tableFooter = document.getElementById('table-footer');
const tableBody = document.getElementById('table-body');
const countEl = document.getElementById('result-count');
const searchInput = document.getElementById('search-input');
const sortSelect = document.getElementById('sort-select');
const exportBtn = document.getElementById('export-btn');
const retryBtn = document.getElementById('retry-btn');
const statTotalEl = document.getElementById('stat-total-val');
const statRateEl = document.getElementById('stat-rating-val');

function setState(state) {
    loadingEl?.classList.add('hidden');
    errorEl?.classList.add('hidden');
    emptyEl?.classList.add('hidden');
    wrapperEl?.classList.add('hidden');
    tableFooter?.classList.add('hidden');

    if (state === 'loading') {
        loadingEl?.classList.remove('hidden');
    }

    if (state === 'error') {
        errorEl?.classList.remove('hidden');
    }

    if (state === 'empty') {
        emptyEl?.classList.remove('hidden');
    }

    if (state === 'table') {
        wrapperEl?.classList.remove('hidden');
        tableFooter?.classList.remove('hidden');
    }
}

function textValue(value) {
    return value == null || value === '' ? '-' : String(value);
}

function escapeHtml(value) {
    return textValue(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function dateValue(registration) {
    const date = registration.createdAt ? new Date(registration.createdAt) : null;
    return date && !Number.isNaN(date.getTime()) ? date.getTime() : 0;
}

function formatDate(value) {
    if (!value) {
        return '-';
    }

    const date = new Date(value);
    if (Number.isNaN(date.getTime())) {
        return '-';
    }

    return new Intl.DateTimeFormat(undefined, {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(date);
}

function formatRating(value) {
    const rating = Math.min(5, Math.max(0, Number(value) || 0));
    let stars = '<span class="star-rating" aria-label="' + rating + ' out of 5">';

    for (let index = 1; index <= 5; index += 1) {
        stars += `<span class="star ${index <= rating ? 'filled' : ''}" aria-hidden="true">&#9733;</span>`;
    }

    return stars + '</span>';
}

function workshopBadge(value) {
    return `<span class="workshop-badge">${escapeHtml(value)}</span>`;
}

function updateStats(registrations) {
    const total = registrations.length;
    const average = total > 0
        ? registrations.reduce((sum, registration) => sum + Number(registration.rating || 0), 0) / total
        : 0;

    if (statTotalEl) {
        statTotalEl.textContent = String(total);
    }

    if (statRateEl) {
        statRateEl.textContent = total > 0 ? `${average.toFixed(1)} / 5` : '-';
    }
}

function applySearchAndSort() {
    const query = searchQuery.toLowerCase();
    let registrations = [...allRegistrations];

    if (query) {
        registrations = registrations.filter((registration) => {
            const haystack = [
                registration.workshop,
                registration.firstname,
                registration.lastname,
                registration.identifier,
                registration.rating,
                registration.createdAt,
            ].map((value) => String(value || '').toLowerCase()).join(' ');

            return haystack.includes(query);
        });
    }

    registrations.sort((first, second) => {
        switch (currentSort) {
            case 'date-asc':
                return dateValue(first) - dateValue(second);
            case 'rating-desc':
                return Number(second.rating || 0) - Number(first.rating || 0);
            case 'rating-asc':
                return Number(first.rating || 0) - Number(second.rating || 0);
            case 'name-asc':
                return textValue(first.firstname).localeCompare(textValue(second.firstname));
            case 'workshop-asc':
                return textValue(first.workshop).localeCompare(textValue(second.workshop));
            case 'date-desc':
            default:
                return dateValue(second) - dateValue(first);
        }
    });

    filteredRegistrations = registrations;
    renderTable(registrations);
}

function renderTable(registrations) {
    if (!tableBody) {
        return;
    }

    tableBody.innerHTML = '';

    if (registrations.length === 0) {
        setState('empty');
        if (countEl) {
            countEl.textContent = '0 results';
        }
        return;
    }

    setState('table');

    registrations.forEach((registration, index) => {
        const row = document.createElement('tr');
        row.style.animationDelay = `${index * 0.025}s`;
        row.innerHTML = `
            <td class="cell-id">${index + 1}</td>
            <td>${workshopBadge(registration.workshop)}</td>
            <td class="cell-name">${escapeHtml(registration.firstname)}</td>
            <td>${escapeHtml(registration.lastname)}</td>
            <td class="cell-identifier">${escapeHtml(registration.identifier)}</td>
            <td>${formatRating(registration.rating)}</td>
            <td class="cell-date">${escapeHtml(formatDate(registration.createdAt))}</td>
        `;
        tableBody.appendChild(row);
    });

    if (countEl) {
        countEl.textContent = `${registrations.length} result${registrations.length === 1 ? '' : 's'}`;
    }
}

async function readApiError(response) {
    const contentType = response.headers.get('content-type') || '';

    if (contentType.includes('application/json')) {
        const payload = await response.json();
        return payload?.error || payload?.message || `HTTP ${response.status}`;
    }

    return `HTTP ${response.status}`;
}

async function fetchRegistrations() {
    setState('loading');

    try {
        const response = await fetch(registrationsUrl, {
            credentials: 'include',
            headers: { Accept: 'application/json' },
        });

        if (!response.ok) {
            throw new Error(await readApiError(response));
        }

        const payload = await response.json();
        allRegistrations = Array.isArray(payload) ? payload : [];
        updateStats(allRegistrations);
        applySearchAndSort();
    } catch (error) {
        updateStats([]);
        setState('error');
        if (errorMsgEl) {
            errorMsgEl.textContent = `Could not load registrations: ${error.message}`;
        }
    }
}

function csvCell(value) {
    if (value == null) {
        return '';
    }

    const text = String(value).replace(/"/g, '""');
    return /[",\n\r]/.test(text) ? `"${text}"` : text;
}

function exportCsv() {
    if (filteredRegistrations.length === 0) {
        return;
    }

    const rows = filteredRegistrations.map((registration, index) => [
        index + 1,
        registration.workshop,
        registration.firstname,
        registration.lastname,
        registration.identifier,
        registration.rating,
        registration.createdAt,
    ]);

    const csv = [
        ['#', 'Workshop', 'Firstname', 'Lastname', 'Identifier', 'Rating', 'Date'],
        ...rows,
    ].map((row) => row.map(csvCell).join(',')).join('\r\n');

    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = `workshop-registrations-${new Date().toISOString().slice(0, 10)}.csv`;
    document.body.appendChild(link);
    link.click();
    link.remove();
    URL.revokeObjectURL(url);
}

let searchTimer;
searchInput?.addEventListener('input', () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
        searchQuery = searchInput.value.trim();
        applySearchAndSort();
    }, 180);
});

sortSelect?.addEventListener('change', () => {
    currentSort = sortSelect.value;
    applySearchAndSort();
});

document.querySelectorAll('.registrations-table th.sortable').forEach((header) => {
    header.addEventListener('click', () => {
        const sort = header.dataset.sort;
        const sortMap = {
            workshop: 'workshop-asc',
            name: 'name-asc',
            rating: currentSort === 'rating-desc' ? 'rating-asc' : 'rating-desc',
            createdAt: currentSort === 'date-desc' ? 'date-asc' : 'date-desc',
        };

        currentSort = sortMap[sort] || currentSort;
        if (sortSelect) {
            sortSelect.value = currentSort;
        }
        applySearchAndSort();
    });
});

exportBtn?.addEventListener('click', exportCsv);
retryBtn?.addEventListener('click', fetchRegistrations);
document.addEventListener('DOMContentLoaded', fetchRegistrations);
