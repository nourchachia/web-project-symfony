const workshopsContainer = document.getElementById('workshops-info-container');
const workshopSelect = document.getElementById('workshop-selection');
const form = document.getElementById('unified-workshop-form');

function formatWorkshopDate(value, options = {}) {
    if (!value) {
        return 'Date to be announced';
    }

    const date = new Date(value);
    if (Number.isNaN(date.getTime())) {
        return 'Date to be announced';
    }

    return new Intl.DateTimeFormat(undefined, {
        dateStyle: options.short ? 'medium' : 'full',
        timeStyle: 'short',
    }).format(date);
}

function normalizeVideoUrl(value) {
    const videoUrl = typeof value === 'string' ? value.trim() : '';
    if (!videoUrl) {
        return '';
    }

    if (/^(https?:)?\/\//i.test(videoUrl) || videoUrl.startsWith('/')) {
        return videoUrl;
    }

    return `/${videoUrl.replace(/^\.?\//, '')}`;
}

function guessVideoType(videoUrl) {
    const cleanUrl = videoUrl.split('?')[0].toLowerCase();

    if (cleanUrl.endsWith('.webm')) {
        return 'video/webm';
    }

    if (cleanUrl.endsWith('.ogv') || cleanUrl.endsWith('.ogg')) {
        return 'video/ogg';
    }

    if (cleanUrl.endsWith('.mov')) {
        return 'video/quicktime';
    }

    return 'video/mp4';
}

function addDescription(description, container) {
    const wrapper = document.createElement('div');
    wrapper.className = 'workshop-full-description';

    const text = typeof description === 'string' ? description.trim() : '';
    const paragraphs = text ? text.split(/\n{2,}/).map((part) => part.trim()).filter(Boolean) : [];

    if (paragraphs.length === 0) {
        const paragraph = document.createElement('p');
        paragraph.textContent = 'Description coming soon.';
        wrapper.appendChild(paragraph);
    } else {
        paragraphs.forEach((part) => {
            const paragraph = document.createElement('p');
            paragraph.textContent = part;
            wrapper.appendChild(paragraph);
        });
    }

    container.appendChild(wrapper);
}

function createWorkshopSection(workshop) {
    const section = document.createElement('div');
    section.className = 'workshop-info-section';

    const title = document.createElement('h3');
    title.className = 'workshop-title';
    title.textContent = workshop.title || 'Untitled Workshop';
    section.appendChild(title);

    const date = document.createElement('time');
    date.className = 'workshop-date';
    if (workshop.date) {
        date.dateTime = workshop.date;
    }
    date.textContent = formatWorkshopDate(workshop.date);
    section.appendChild(date);

    const polaroidWrapper = document.createElement('div');
    polaroidWrapper.className = 'workshop-polaroid';

    const polaroid = document.createElement('div');
    polaroid.className = 'polaroid activity-polaroid video-polaroid';

    const imageFrame = document.createElement('div');
    imageFrame.className = 'polaroid-img';

    const videoUrl = normalizeVideoUrl(workshop.videoUrl);
    if (videoUrl) {
        const video = document.createElement('video');
        video.className = 'polaroid-video';
        video.loop = true;
        video.muted = true;
        video.playsInline = true;
        video.preload = 'metadata';

        const source = document.createElement('source');
        source.src = videoUrl;
        source.type = guessVideoType(videoUrl);
        video.appendChild(source);
        video.appendChild(document.createTextNode('Your browser does not support the video tag.'));
        imageFrame.appendChild(video);
    } else {
        const placeholder = document.createElement('div');
        placeholder.className = 'polaroid-video-placeholder';
        placeholder.textContent = 'Video coming soon';
        imageFrame.appendChild(placeholder);
    }

    const caption = document.createElement('div');
    caption.className = 'polaroid-caption';
    caption.textContent = workshop.title || 'Workshop';

    polaroid.appendChild(imageFrame);
    polaroid.appendChild(caption);
    polaroidWrapper.appendChild(polaroid);
    section.appendChild(polaroidWrapper);

    addDescription(workshop.description, section);

    return section;
}

function setWorkshopSelectState(workshops) {
    if (!workshopSelect) {
        return;
    }

    workshopSelect.innerHTML = '';

    const defaultOption = document.createElement('option');
    defaultOption.value = '';
    defaultOption.textContent = workshops.length > 0 ? 'Choose a workshop...' : 'No workshops available';
    workshopSelect.appendChild(defaultOption);

    workshops.forEach((workshop) => {
        const option = document.createElement('option');
        option.value = workshop.id || '';
        option.textContent = workshop.date
            ? `${workshop.title || 'Untitled Workshop'} - ${formatWorkshopDate(workshop.date, { short: true })}`
            : workshop.title || 'Untitled Workshop';
        option.dataset.workshopId = workshop.id || '';
        option.dataset.workshopTitle = workshop.title || '';
        workshopSelect.appendChild(option);
    });
}

function bindPolaroidVideos(scope = document) {
    scope.querySelectorAll('.video-polaroid').forEach((polaroid) => {
        if (polaroid.dataset.videoHoverBound === 'true') {
            return;
        }

        const video = polaroid.querySelector('.polaroid-video');
        if (!video) {
            return;
        }

        polaroid.dataset.videoHoverBound = 'true';
        polaroid.addEventListener('mouseenter', () => {
            video.play().catch((error) => console.log('Video play failed:', error));
        });

        polaroid.addEventListener('mouseleave', () => {
            video.pause();
            video.currentTime = 0;
        });
    });
}

function renderWorkshops(workshops) {
    if (!workshopsContainer) {
        return;
    }

    workshopsContainer.innerHTML = '';

    if (workshops.length === 0) {
        const empty = document.createElement('div');
        empty.className = 'workshops-state';
        empty.textContent = 'No workshops available yet.';
        workshopsContainer.appendChild(empty);
        setWorkshopSelectState([]);
        return;
    }

    workshops.forEach((workshop) => {
        workshopsContainer.appendChild(createWorkshopSection(workshop));
    });

    setWorkshopSelectState(workshops);
    bindPolaroidVideos(workshopsContainer);
}

async function loadWorkshops() {
    if (!workshopsContainer) {
        bindPolaroidVideos();
        return;
    }

    const workshopsUrl = workshopsContainer.dataset.workshopsUrl || '/api/workshops';

    try {
        const response = await fetch(workshopsUrl, {
            credentials: 'include',
            headers: { Accept: 'application/json' },
        });

        if (!response.ok) {
            throw new Error(`Unable to load workshops: ${response.status}`);
        }

        const workshops = await response.json();
        renderWorkshops(Array.isArray(workshops) ? workshops : []);
    } catch (error) {
        workshopsContainer.innerHTML = '';

        const errorMessage = document.createElement('div');
        errorMessage.className = 'workshops-state workshops-state--error';
        errorMessage.textContent = error.message || 'Unable to load workshops.';
        workshopsContainer.appendChild(errorMessage);
        setWorkshopSelectState([]);
    }
}

async function readApiError(response) {
    const contentType = response.headers.get('content-type') || '';

    if (contentType.includes('application/json')) {
        const payload = await response.json();
        return payload?.error || 'Unable to register for workshop.';
    }

    return 'Unable to register for workshop.';
}

if (form) {
    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        const formData = new FormData(form);
        const idWorkshop = formData.get('workshop');
        const rating = formData.get('rating');
        const selectedOption = workshopSelect?.selectedOptions[0];
        const workshopTitle = selectedOption?.dataset.workshopTitle || selectedOption?.textContent || 'Workshop';
        const submitButton = form.querySelector('button[type="submit"]');

        if (!idWorkshop) {
            alert('Please select a workshop.');
            return;
        }

        if (!rating) {
            alert('Please rate your commitment level by selecting stars.');
            return;
        }

        if (submitButton) {
            submitButton.disabled = true;
        }

        try {
            const response = await fetch(form.dataset.registrationUrl || '/api/workshop-registrations', {
                method: 'POST',
                credentials: 'include',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    idWorkshop,
                    rating: Number(rating),
                }),
            });

            if (!response.ok) {
                throw new Error(await readApiError(response));
            }

            alert(`Registration successful!\n\nWorkshop: ${workshopTitle}\nCommitment Level: ${rating} star(s)\n\nWe look forward to seeing you at the workshop!`);

            form.reset();
        } catch (error) {
            alert(error.message || 'Unable to register for workshop.');
        } finally {
            if (submitButton) {
                submitButton.disabled = false;
            }
        }
    });
}

document.addEventListener('DOMContentLoaded', loadWorkshops);
