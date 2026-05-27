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

function createSection(sectionId, config) {
    const container = document.getElementById(`${sectionId}-section`);
    const leftLabels = document.getElementById(`${sectionId}-labels-left`);
    const rightLabels = document.getElementById(`${sectionId}-labels-right`);
    const numbersContainer = document.getElementById(`${sectionId}-numbers`);

    if (!container || !leftLabels || !rightLabels || !numbersContainer) {
        return;
    }

    if (container.children.length > 0) {
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
            const currentRowWidth = (seatCount * seatWidth) + ((seatCount - 1) * gapWidth);
            const maxRowWidth = (maxSeats * seatWidth) + ((maxSeats - 1) * gapWidth);
            paddingLeft = (maxRowWidth - currentRowWidth) / 2;
            startSeatNumber = Math.round(paddingLeft / (seatWidth + gapWidth)) + 1;
        } else if (config.alignment === 'right') {
            const currentRowWidth = (seatCount * seatWidth) + ((seatCount - 1) * gapWidth);
            const maxRowWidth = (maxSeats * seatWidth) + ((maxSeats - 1) * gapWidth);
            paddingLeft = maxRowWidth - currentRowWidth;
            startSeatNumber = Math.round(paddingLeft / (seatWidth + gapWidth)) + 1;
        }

        if (paddingLeft > 0) {
            row.style.paddingLeft = `${paddingLeft}px`;
        }

        for (let i = 0; i < seatCount; i++) {
            const seat = document.createElement('button');
            seat.type = 'button';
            seat.className = 'seat';
            seat.dataset.row = rowLabels[rowIndex];
            seat.dataset.seat = String(startSeatNumber + i);
            seat.dataset.section = sectionId;
            seat.setAttribute('aria-label', `${sectionId} section, row ${rowLabels[rowIndex]}, seat ${startSeatNumber + i}`);
            seat.addEventListener('click', toggleSeat);
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

function toggleSeat(event) {
    const seat = event.currentTarget;

    if (seat.classList.contains('occupied')) {
        return;
    }

    seat.classList.toggle('selected');
    seat.setAttribute('aria-pressed', seat.classList.contains('selected') ? 'true' : 'false');
    updateSelectedSeats();
}

function updateSelectedSeats() {
    const summary = document.getElementById('booking-selected-seats');

    if (!summary) {
        return;
    }

    const selectedSeats = Array.from(document.querySelectorAll('.seat.selected'))
        .map((seat) => `${seat.dataset.section.toUpperCase()} ${seat.dataset.row}${seat.dataset.seat}`);

    summary.textContent = selectedSeats.length > 0 ? selectedSeats.join(', ') : 'None';
}

function markSeatAsOccupied(section, row, seatNum) {
    const seat = document.querySelector(
        `.seat[data-section="${section}"][data-row="${row}"][data-seat="${seatNum}"]`
    );

    if (seat) {
        seat.classList.add('occupied');
        seat.setAttribute('aria-disabled', 'true');
    }
}

function initBookingPage() {
    if (!document.getElementById('left-section')) {
        return;
    }

    createSection('left', sectionConfig.left);
    createSection('center', sectionConfig.center);
    createSection('right', sectionConfig.right);

    markSeatAsOccupied('center', 'A', 5);
    markSeatAsOccupied('left', 'B', 3);
    updateSelectedSeats();
}

document.addEventListener('DOMContentLoaded', initBookingPage);
document.addEventListener('turbo:load', initBookingPage);
