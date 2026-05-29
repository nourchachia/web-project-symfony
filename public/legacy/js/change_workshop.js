const adminSection = document.querySelector('.admin-section');
const workshopsEndpoint = adminSection?.dataset.workshopsUrl || '/api/workshops';
const videoUploadEndpoint = adminSection?.dataset.uploadUrl || '/api/upload/video';

let editVideoRemoved = false;
let pendingDeleteId = null;

function getElement(id) {
    return document.getElementById(id);
}

async function parseJsonSafe(response, fallback = {}) {
    const contentType = response.headers.get('content-type') || '';
    if (!contentType.includes('application/json')) {
        return fallback;
    }

    return response.json().catch(() => fallback);
}

async function readApiError(response, fallbackMessage) {
    const payload = await parseJsonSafe(response, {});
    return payload.error || payload.message || fallbackMessage;
}

function showToast(message, type = 'success') {
    const toast = getElement('toast');
    if (!toast) {
        return;
    }

    toast.textContent = message;
    toast.className = `toast ${type}`;

    window.clearTimeout(showToast.timeoutId);
    showToast.timeoutId = window.setTimeout(() => {
        toast.classList.add('hidden');
    }, 3500);
}

function formatDate(value) {
    if (!value) {
        return '-';
    }

    const date = new Date(value);
    if (Number.isNaN(date.getTime())) {
        return '-';
    }

    return new Intl.DateTimeFormat(undefined, { dateStyle: 'medium' }).format(date);
}

function toInputDate(value) {
    if (!value) {
        return '';
    }

    const datePart = String(value).slice(0, 10);
    return /^\d{4}-\d{2}-\d{2}$/.test(datePart) ? datePart : '';
}

function setLoading(buttonId, loading) {
    const button = getElement(buttonId);
    if (!button) {
        return;
    }

    const text = button.querySelector('.btn-text');
    const spinner = button.querySelector('.btn-spinner');

    button.disabled = loading;
    text?.classList.toggle('hidden', loading);
    spinner?.classList.toggle('hidden', !loading);
}

function setProgress(progressId, visible) {
    getElement(progressId)?.classList.toggle('hidden', !visible);
}

function appendText(parent, tagName, className, text) {
    const element = document.createElement(tagName);
    element.className = className;
    element.textContent = text || '-';
    parent.appendChild(element);
    return element;
}

function uploadVideo(file, progressId) {
    setProgress(progressId, true);

    return new Promise((resolve, reject) => {
        const formData = new FormData();
        formData.append('video', file);

        const xhr = new XMLHttpRequest();
        xhr.open('POST', videoUploadEndpoint);
        xhr.withCredentials = true;

        xhr.upload.addEventListener('progress', (event) => {
            if (!event.lengthComputable) {
                return;
            }

            const percent = Math.round((event.loaded / event.total) * 100);
            const text = document.querySelector(`#${progressId} .upload-progress-text`);
            if (text) {
                text.textContent = `Uploading... ${percent}%`;
            }
        });

        xhr.addEventListener('load', () => {
            setProgress(progressId, false);

            let payload = {};
            try {
                payload = JSON.parse(xhr.responseText);
            } catch {
                payload = {};
            }

            if (xhr.status >= 200 && xhr.status < 300 && payload.url) {
                resolve(payload.url);
                return;
            }

            reject(new Error(payload.error || `Upload failed (HTTP ${xhr.status}).`));
        });

        xhr.addEventListener('error', () => {
            setProgress(progressId, false);
            reject(new Error('Network error during upload. Please try again.'));
        });

        xhr.send(formData);
    });
}

async function loadWorkshops() {
    const list = getElement('workshops-list');
    const loading = getElement('workshops-loading');
    const error = getElement('workshops-error');
    const count = getElement('workshops-count');

    if (!list || !loading || !error || !count) {
        return;
    }

    list.innerHTML = '';
    loading.classList.remove('hidden');
    error.classList.add('hidden');
    count.textContent = '';

    try {
        const response = await fetch(workshopsEndpoint, {
            credentials: 'include',
            headers: { Accept: 'application/json' },
        });

        if (!response.ok) {
            throw new Error(await readApiError(response, 'Failed to load workshops.'));
        }

        const workshops = await response.json();
        if (!Array.isArray(workshops)) {
            throw new Error('Invalid workshops payload.');
        }

        loading.classList.add('hidden');
        count.textContent = `${workshops.length} workshop${workshops.length === 1 ? '' : 's'}`;

        if (workshops.length === 0) {
            appendText(list, 'p', 'state-message', 'No workshops yet. Add one above.');
            return;
        }

        workshops.forEach((workshop) => {
            list.appendChild(buildWorkshopRow(workshop));
        });
    } catch (errorThrown) {
        loading.classList.add('hidden');
        error.classList.remove('hidden');
        error.textContent = errorThrown.message || 'Could not load workshops.';
    }
}

function buildWorkshopRow(workshop) {
    const row = document.createElement('article');
    row.className = 'ws-row';
    row.dataset.id = workshop.id || '';

    const info = document.createElement('div');
    info.className = 'ws-row-info';

    appendText(info, 'h4', 'ws-row-title', workshop.title);
    appendText(info, 'span', 'ws-row-meta', `${workshop.departement || '-'} | ${formatDate(workshop.date)}`);
    appendText(info, 'p', 'ws-row-desc', workshop.description || 'No description yet.');

    if (workshop.videoUrl) {
        const videoWrap = document.createElement('div');
        videoWrap.className = 'ws-row-video';

        const video = document.createElement('video');
        video.src = workshop.videoUrl;
        video.controls = true;
        video.preload = 'none';
        video.className = 'ws-video-player';
        videoWrap.appendChild(video);
        info.appendChild(videoWrap);
    }

    const actions = document.createElement('div');
    actions.className = 'ws-row-actions';

    const editButton = document.createElement('button');
    editButton.type = 'button';
    editButton.className = 'btn-icon btn-edit';
    editButton.textContent = 'Edit';
    editButton.addEventListener('click', () => openEditModal(workshop.id));

    const deleteButton = document.createElement('button');
    deleteButton.type = 'button';
    deleteButton.className = 'btn-icon btn-del';
    deleteButton.textContent = 'Delete';
    deleteButton.addEventListener('click', () => openDeleteModal(workshop));

    actions.append(editButton, deleteButton);
    row.append(info, actions);

    return row;
}

function validateWorkshopForm(title, date, departement, description) {
    if (!title || !date || !departement || !description) {
        showToast('Please fill in all required fields.', 'error');
        return false;
    }

    return true;
}

function bindAddForm() {
    getElement('add-form')?.addEventListener('submit', async (event) => {
        event.preventDefault();

        const title = getElement('new-title')?.value.trim() || '';
        const date = getElement('new-date')?.value || '';
        const departement = getElement('new-departement')?.value || '';
        const description = getElement('new-description')?.value.trim() || '';
        const videoFile = getElement('new-video')?.files[0] || null;

        if (!validateWorkshopForm(title, date, departement, description)) {
            return;
        }

        setLoading('add-btn', true);

        try {
            const videoUrl = videoFile ? await uploadVideo(videoFile, 'new-video-progress') : null;
            const response = await fetch(workshopsEndpoint, {
                method: 'POST',
                credentials: 'include',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ title, date, departement, description, videoUrl }),
            });

            if (!response.ok) {
                throw new Error(await readApiError(response, 'Failed to create workshop.'));
            }

            showToast(`"${title}" created successfully.`);
            getElement('add-form')?.reset();
            await loadWorkshops();
        } catch (error) {
            showToast(error.message || 'Failed to create workshop.', 'error');
        } finally {
            setLoading('add-btn', false);
        }
    });
}

async function fetchWorkshop(id) {
    const response = await fetch(`${workshopsEndpoint}/${encodeURIComponent(id)}`, {
        credentials: 'include',
        headers: { Accept: 'application/json' },
    });

    if (!response.ok) {
        throw new Error(await readApiError(response, 'Failed to load workshop.'));
    }

    return response.json();
}

async function openEditModal(id) {
    if (!id) {
        showToast('Workshop id is missing.', 'error');
        return;
    }

    try {
        const workshop = await fetchWorkshop(id);
        editVideoRemoved = false;

        getElement('edit-id').value = workshop.id || '';
        getElement('edit-title').value = workshop.title || '';
        getElement('edit-date').value = toInputDate(workshop.date);
        getElement('edit-departement').value = workshop.departement || '';
        getElement('edit-description').value = workshop.description || '';

        const previewWrap = getElement('edit-current-video');
        const previewVideo = getElement('edit-video-preview');

        if (workshop.videoUrl) {
            previewVideo.src = workshop.videoUrl;
            previewWrap.classList.remove('hidden');
        } else {
            previewVideo.removeAttribute('src');
            previewWrap.classList.add('hidden');
        }

        getElement('edit-video').value = '';
        getElement('edit-modal').classList.remove('hidden');
        getElement('edit-title').focus();
    } catch (error) {
        showToast(error.message || 'Failed to load workshop.', 'error');
    }
}

function closeEditModal() {
    const modal = getElement('edit-modal');
    const previewVideo = getElement('edit-video-preview');

    modal?.classList.add('hidden');
    previewVideo?.removeAttribute('src');
}

function bindEditForm() {
    getElement('modal-close-btn')?.addEventListener('click', closeEditModal);
    getElement('modal-cancel-btn')?.addEventListener('click', closeEditModal);

    getElement('edit-modal')?.addEventListener('click', (event) => {
        if (event.target === event.currentTarget) {
            closeEditModal();
        }
    });

    getElement('edit-remove-video-btn')?.addEventListener('click', () => {
        editVideoRemoved = true;
        getElement('edit-current-video')?.classList.add('hidden');
        getElement('edit-video-preview')?.removeAttribute('src');
    });

    getElement('edit-form')?.addEventListener('submit', async (event) => {
        event.preventDefault();

        const id = getElement('edit-id')?.value || '';
        const title = getElement('edit-title')?.value.trim() || '';
        const date = getElement('edit-date')?.value || '';
        const departement = getElement('edit-departement')?.value || '';
        const description = getElement('edit-description')?.value.trim() || '';
        const videoFile = getElement('edit-video')?.files[0] || null;

        if (!validateWorkshopForm(title, date, departement, description)) {
            return;
        }

        setLoading('edit-save-btn', true);

        try {
            let videoUrl;
            if (videoFile) {
                videoUrl = await uploadVideo(videoFile, 'edit-video-progress');
            } else if (editVideoRemoved) {
                videoUrl = null;
            }

            const body = { title, date, departement, description };
            if (videoUrl !== undefined) {
                body.videoUrl = videoUrl;
            }

            const response = await fetch(`${workshopsEndpoint}/${encodeURIComponent(id)}`, {
                method: 'PUT',
                credentials: 'include',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(body),
            });

            if (!response.ok) {
                throw new Error(await readApiError(response, 'Failed to update workshop.'));
            }

            showToast(`"${title}" updated successfully.`);
            closeEditModal();
            await loadWorkshops();
        } catch (error) {
            showToast(error.message || 'Failed to update workshop.', 'error');
        } finally {
            setLoading('edit-save-btn', false);
        }
    });
}

function openDeleteModal(workshop) {
    pendingDeleteId = workshop.id || null;
    getElement('delete-confirm-text').textContent = `Are you sure you want to permanently delete "${workshop.title || 'this workshop'}"? This cannot be undone.`;
    getElement('delete-modal').classList.remove('hidden');
}

function closeDeleteModal() {
    pendingDeleteId = null;
    getElement('delete-modal')?.classList.add('hidden');

    const button = getElement('delete-confirm-btn');
    if (button) {
        button.disabled = false;
        button.textContent = 'Delete';
    }
}

function bindDeleteModal() {
    getElement('delete-cancel-btn')?.addEventListener('click', closeDeleteModal);

    getElement('delete-modal')?.addEventListener('click', (event) => {
        if (event.target === event.currentTarget) {
            closeDeleteModal();
        }
    });

    getElement('delete-confirm-btn')?.addEventListener('click', async () => {
        if (!pendingDeleteId) {
            return;
        }

        const button = getElement('delete-confirm-btn');
        if (button) {
            button.disabled = true;
            button.textContent = 'Deleting...';
        }

        try {
            const response = await fetch(`${workshopsEndpoint}/${encodeURIComponent(pendingDeleteId)}`, {
                method: 'DELETE',
                credentials: 'include',
                headers: { Accept: 'application/json' },
            });

            if (!response.ok) {
                throw new Error(await readApiError(response, 'Failed to delete workshop.'));
            }

            showToast('Workshop deleted.');
            closeDeleteModal();
            await loadWorkshops();
        } catch (error) {
            showToast(error.message || 'Failed to delete workshop.', 'error');
            if (button) {
                button.disabled = false;
                button.textContent = 'Delete';
            }
        }
    });
}

document.addEventListener('DOMContentLoaded', () => {
    loadWorkshops();
    bindAddForm();
    bindEditForm();
    bindDeleteModal();
});
