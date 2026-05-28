const rowLabels = ['O', 'N', 'M', 'L', 'K', 'J', 'I', 'H', 'G', 'F', 'E', 'D', 'C', 'B', 'A'];

const sectionConfig = {
    left: {
        rows: 15,
        seatsPerRow: [6, 7, 8, 9, 10, 11, 12, 14, 15, 16, 16, 16, 16, 16, 16],
        alignment: 'right',
    },
    center: {
        rows: 15,
        seatsPerRow: [8, 11, 13, 14, 15, 16, 16, 16, 16, 16, 16, 16, 16, 16, 16],
        alignment: 'center',
    },
    right: {
        rows: 15,
        seatsPerRow: [6, 7, 8, 9, 10, 11, 12, 14, 15, 16, 16, 16, 16, 16, 16],
        alignment: 'left',
    },
};

let currentShowId = null;
let releaseMode = false;
const selectedSeats = new Map();
const releaseSeats = new Map();

const fetchOptions = { credentials: 'include', headers: { Accept: 'application/json' } };

function seatKey(section, row, number) {
    return `${section}:${row}:${number}`;
}

function getSeatElement(section, row, number) {
    return document.querySelector(
        `.seat[data-section="${section}"][data-row="${row}"][data-seat="${number}"]`,
    );
}

function setFeedback(message, isError = false) {
    const el = document.getElementById('booking-feedback');
    if (!el) {
        return;
    }
    el.textContent = message;
    el.classList.toggle('booking-feedback--error', isError);
}

async function apiFetch(url, options = {}) {
    const response = await fetch(url, {
        ...fetchOptions,
        ...options,
        headers: {
            ...fetchOptions.headers,
            ...(options.headers || {}),
            ...(options.body ? { 'Content-Type': 'application/json' } : {}),
        },
    });

    let payload = null;
    const contentType = response.headers.get('content-type') || '';
    if (contentType.includes('application/json')) {
        payload = await response.json();
    }

    return { response, payload };
}

function updateSelectedSeatsSummary() {
    const summary = document.getElementById('booking-selected-seats');
    if (!summary) {
        return;
    }

    if (releaseMode) {
        const labels = Array.from(releaseSeats.values()).map(
            (s) => `${s.section.toUpperCase()} ${s.row}${s.number}`,
        );
        summary.textContent = labels.length > 0 ? `Release: ${labels.join(', ')}` : 'Release mode: select occupied seats';
        return;
    }

    const labels = Array.from(selectedSeats.values()).map(
        (s) => `${s.section.toUpperCase()} ${s.row}${s.number}`,
    );
    summary.textContent = labels.length > 0 ? labels.join(', ') : 'None';
}

function createSection(sectionId, config) {
    const container = document.getElementById(`${sectionId}-section`);
    const leftLabels = document.getElementById(`${sectionId}-labels-left`);
    const rightLabels = document.getElementById(`${sectionId}-labels-right`);
    const numbersContainer = document.getElementById(`${sectionId}-numbers`);

    if (!container || !leftLabels || !rightLabels || !numbersContainer || container.children.length > 0) {
        return;
    }

    const maxSeats = Math.max(...config.seatsPerRow);
    const seatWidth = 16;
    const gapWidth = 2.5;

    for (let i = 1; i <= maxSeats; i++) {
        const numDiv = document.createElement('div');
        numDiv.className = 'seat-number';
        numDiv.textContent = i;
        numbersContainer.appendChild(numDiv);
    }

    config.seatsPerRow.forEach((seatCount, rowIndex) => {
        const row = document.createElement('div');
        row.className = 'seat-row';

        let paddingLeft = 0;
        let startSeatNumber = 1;

        if (config.alignment === 'center') {
            const currentRowWidth = seatCount * seatWidth + (seatCount - 1) * gapWidth;
            const maxRowWidth = maxSeats * seatWidth + (maxSeats - 1) * gapWidth;
            paddingLeft = (maxRowWidth - currentRowWidth) / 2;
            startSeatNumber = Math.round(paddingLeft / (seatWidth + gapWidth)) + 1;
        } else if (config.alignment === 'right') {
            const currentRowWidth = seatCount * seatWidth + (seatCount - 1) * gapWidth;
            const maxRowWidth = maxSeats * seatWidth + (maxSeats - 1) * gapWidth;
            paddingLeft = maxRowWidth - currentRowWidth;
            startSeatNumber = Math.round(paddingLeft / (seatWidth + gapWidth)) + 1;
        }

        if (paddingLeft > 0) {
            row.style.paddingLeft = `${paddingLeft}px`;
        }

        for (let i = 0; i < seatCount; i++) {
            const seatNumber = startSeatNumber + i;
            const seat = document.createElement('button');
            seat.type = 'button';
            seat.className = 'seat';
            seat.dataset.row = rowLabels[rowIndex];
            seat.dataset.seat = String(seatNumber);
            seat.dataset.section = sectionId;
            seat.setAttribute(
                'aria-label',
                `${sectionId} section, row ${rowLabels[rowIndex]}, seat ${seatNumber}`,
            );
            seat.addEventListener('click', onSeatClick);
            row.appendChild(seat);
        }

        container.appendChild(row);

        const leftLabel = document.createElement('div');
        leftLabel.className = 'row-label';
        leftLabel.textContent = rowLabels[rowIndex];
        leftLabels.appendChild(leftLabel);

        const rightLabel = document.createElement('div');
        rightLabel.className = 'row-label';
        rightLabel.textContent = rowLabels[rowIndex];
        rightLabels.appendChild(rightLabel);
    });
}

function onSeatClick(event) {
    const seat = event.currentTarget;
    const section = seat.dataset.section;
    const row = seat.dataset.row;
    const number = Number(seat.dataset.seat);
    const key = seatKey(section, row, number);

    if (releaseMode) {
        if (!seat.classList.contains('occupied')) {
            return;
        }

        if (releaseSeats.has(key)) {
            releaseSeats.delete(key);
            seat.classList.remove('release-selected');
        } else {
            releaseSeats.set(key, { section, row, number });
            seat.classList.add('release-selected');
        }
        updateSelectedSeatsSummary();
        return;
    }

    if (seat.classList.contains('occupied')) {
        return;
    }

    if (selectedSeats.has(key)) {
        selectedSeats.delete(key);
        seat.classList.remove('selected');
        seat.setAttribute('aria-pressed', 'false');
    } else {
        selectedSeats.set(key, { section, row, number });
        seat.classList.add('selected');
        seat.setAttribute('aria-pressed', 'true');
    }

    updateSelectedSeatsSummary();
}

function markSeatOccupied(section, row, number) {
    const seat = getSeatElement(section, row, number);
    if (!seat) {
        return;
    }
    seat.classList.add('occupied');
    seat.classList.remove('selected', 'release-selected');
    seat.setAttribute('aria-disabled', 'true');
    seat.setAttribute('aria-pressed', 'false');
    selectedSeats.delete(seatKey(section, row, number));
    releaseSeats.delete(seatKey(section, row, number));
}

function markSeatAvailable(section, row, number) {
    const seat = getSeatElement(section, row, number);
    if (!seat) {
        return;
    }
    seat.classList.remove('occupied', 'selected', 'release-selected');
    seat.removeAttribute('aria-disabled');
    seat.setAttribute('aria-pressed', 'false');
    selectedSeats.delete(seatKey(section, row, number));
    releaseSeats.delete(seatKey(section, row, number));
}

async function resolveShow() {
    const params = new URLSearchParams(window.location.search);
    const showIdParam = params.get('show_id');
    const showNameParam = params.get('show_name');

    if (showIdParam) {
        currentShowId = showIdParam;
        return;
    }

    if (showNameParam) {
        const encoded = encodeURIComponent(showNameParam);
        const { response, payload } = await apiFetch(`/api/shows/name/${encoded}`);
        if (response.ok && payload?.id) {
            currentShowId = payload.id;
            replaceShowIdInUrl(currentShowId);
            return;
        }

        if (response.status === 404) {
            const ensure = await apiFetch('/api/shows/ensure', {
                method: 'POST',
                body: JSON.stringify({ name: showNameParam }),
            });
            if (!ensure.response.ok) {
                throw new Error(ensure.payload?.error || 'Unable to create show.');
            }
            currentShowId = ensure.payload.id;
            replaceShowIdInUrl(currentShowId);
            return;
        }

        throw new Error(payload?.error || 'Unable to resolve show by name.');
    }

    const { response, payload } = await apiFetch('/api/shows');
    if (!response.ok || !Array.isArray(payload) || payload.length === 0) {
        throw new Error('No shows available.');
    }

    currentShowId = payload[0].id;
    replaceShowIdInUrl(currentShowId);
}

function replaceShowIdInUrl(showId) {
    const url = new URL(window.location.href);
    url.searchParams.delete('show_name');
    url.searchParams.set('show_id', showId);
    window.history.replaceState({}, '', url);
}

async function hydrateSeats() {
    const { response, payload } = await apiFetch(`/api/shows/${currentShowId}/seats`);
    if (!response.ok) {
        throw new Error(payload?.error || 'Unable to load seat occupancy.');
    }

    document.querySelectorAll('.seat').forEach((seat) => {
        seat.classList.remove('occupied', 'selected', 'release-selected');
        seat.removeAttribute('aria-disabled');
    });
    selectedSeats.clear();
    releaseSeats.clear();

    payload.forEach((seat) => {
        if (seat.is_occupied) {
            markSeatOccupied(seat.section, seat.row, seat.number);
        }
    });

    updateSelectedSeatsSummary();
}

async function confirmBooking() {
    if (selectedSeats.size === 0) {
        setFeedback('Select at least one available seat.', true);
        return;
    }

    setFeedback('Booking seats…');
    const entries = Array.from(selectedSeats.values());
    const results = await Promise.all(
        entries.map(async (seat) => {
            const { response, payload } = await apiFetch('/api/tickets', {
                method: 'POST',
                body: JSON.stringify({
                    show_id: currentShowId,
                    section: seat.section,
                    row: seat.row,
                    number: seat.number,
                    status: 'reserved',
                }),
            });
            return { seat, response, payload };
        }),
    );

    let booked = 0;
    let conflicts = 0;

    results.forEach(({ seat, response }) => {
        const key = seatKey(seat.section, seat.row, seat.number);
        const element = getSeatElement(seat.section, seat.row, seat.number);

        if (response.status === 201) {
            booked += 1;
            selectedSeats.delete(key);
            if (element) {
                element.classList.remove('selected');
            }
            markSeatOccupied(seat.section, seat.row, seat.number);
            return;
        }

        if (response.status === 409) {
            conflicts += 1;
            selectedSeats.delete(key);
            if (element) {
                element.classList.remove('selected');
            }
            markSeatOccupied(seat.section, seat.row, seat.number);
        }
    });

    updateSelectedSeatsSummary();

    if (booked > 0 && conflicts === 0) {
        setFeedback(`Booked ${booked} seat(s).`);
        return;
    }

    if (booked > 0 && conflicts > 0) {
        setFeedback(`Booked ${booked} seat(s). ${conflicts} seat(s) were already taken.`, true);
        return;
    }

    if (conflicts > 0) {
        setFeedback('Selected seat(s) are already booked.', true);
        return;
    }

    setFeedback('Booking failed. Please try again.', true);
}

async function confirmRelease() {
    if (releaseSeats.size === 0) {
        setFeedback('Select occupied seats to release.', true);
        return;
    }

    setFeedback('Releasing seats…');
    const entries = Array.from(releaseSeats.values());
    const results = await Promise.all(
        entries.map(async (seat) => {
            const { response, payload } = await apiFetch('/api/tickets/release', {
                method: 'POST',
                body: JSON.stringify({
                    show_id: currentShowId,
                    section: seat.section,
                    row: seat.row,
                    number: seat.number,
                }),
            });
            return { seat, response, payload };
        }),
    );

    let released = 0;
    results.forEach(({ seat, response }) => {
        if (response.ok) {
            released += 1;
            releaseSeats.delete(seatKey(seat.section, seat.row, seat.number));
            markSeatAvailable(seat.section, seat.row, seat.number);
        }
    });

    releaseMode = false;
    document.body.classList.remove('booking-release-mode');
    const releaseBtn = document.getElementById('release-booking-btn');
    if (releaseBtn) {
        releaseBtn.classList.remove('booking-btn--active');
        releaseBtn.textContent = 'Release selected seats';
    }

    updateSelectedSeatsSummary();

    if (released > 0) {
        setFeedback(`Released ${released} seat(s).`);
        return;
    }

    setFeedback('Release failed. Please try again.', true);
}

function wireActions() {
    const confirmBtn = document.getElementById('confirm-booking-btn');
    const releaseBtn = document.getElementById('release-booking-btn');

    confirmBtn?.addEventListener('click', () => {
        if (releaseMode) {
            return;
        }
        confirmBooking().catch((error) => setFeedback(error.message, true));
    });

    releaseBtn?.addEventListener('click', () => {
        if (!releaseMode) {
            releaseMode = true;
            document.body.classList.add('booking-release-mode');
            selectedSeats.clear();
            document.querySelectorAll('.seat.selected').forEach((seat) => {
                seat.classList.remove('selected');
                seat.setAttribute('aria-pressed', 'false');
            });
            releaseBtn.classList.add('booking-btn--active');
            releaseBtn.textContent = 'Confirm release';
            updateSelectedSeatsSummary();
            setFeedback('Release mode: click occupied seats, then confirm release.');
            return;
        }

        confirmRelease().catch((error) => setFeedback(error.message, true));
    });
}

async function initBookingPage() {
    if (!document.getElementById('left-section')) {
        return;
    }

    createSection('left', sectionConfig.left);
    createSection('center', sectionConfig.center);
    createSection('right', sectionConfig.right);
    wireActions();

    try {
        await resolveShow();
        await hydrateSeats();
        setFeedback('');
    } catch (error) {
        setFeedback(error.message || 'Unable to initialize booking.', true);
    }
}

document.addEventListener('DOMContentLoaded', initBookingPage);
document.addEventListener('turbo:load', initBookingPage);
