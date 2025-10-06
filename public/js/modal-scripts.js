/**
 * @file Manages all reusable modals for the application, including the
 * confirmation dialog and the site-wide file viewer.
 */

/**
 * Displays a confirmation modal with custom options and an action to perform.
 * @param {object} options - The configuration for the modal.
 * @param {string} options.title - The title to display.
 * @param {string} options.body - The text or HTML for the body.
 * @param {string} [options.confirmText='Confirm'] - The text for the confirm button.
 * @param {Function} options.onConfirm - The async function to execute on confirmation.
 */
function showConfirmationModal({ title, body, confirmText = 'Confirm', onConfirm }) {
    const modal = document.getElementById('confirmationModal');
    if (!modal) {
        console.error('Confirmation modal not found.');
        return;
    }

    const modalTitle = document.getElementById('confirmationModalTitle');
    const modalText = document.getElementById('confirmationModalText');
    const confirmBtn = document.getElementById('confirmActionBtn');
    const cancelBtn = document.getElementById('cancelConfirmationBtn');
    const statusMessage = document.getElementById('confirmation-final-status-message-area');

    modalTitle.textContent = title;
    modalText.innerHTML = body;
    confirmBtn.textContent = confirmText;

    statusMessage.innerHTML = '';
    confirmBtn.disabled = false;
    cancelBtn.disabled = false;

    const newConfirmBtn = confirmBtn.cloneNode(true);
    confirmBtn.parentNode.replaceChild(newConfirmBtn, confirmBtn);

    newConfirmBtn.addEventListener('click', async () => {
        newConfirmBtn.disabled = true;
        cancelBtn.disabled = true;
        statusMessage.innerHTML = `<div class="alert-info">Processing...</div>`;

        try {
            await onConfirm();
        } catch (error) {
            statusMessage.innerHTML = `<div class="alert-danger">${error.message}</div>`;
            newConfirmBtn.disabled = false;
            cancelBtn.disabled = false;
        }
    });

    document.body.classList.add('modal-open');
    modal.style.display = 'flex';
}

/**
 * Hides the main confirmation modal.
 */
function hideConfirmationModal() {
    const modal = document.getElementById('confirmationModal');
    if (modal) {
        document.body.classList.remove('modal-open');
        modal.style.display = 'none';
    }
}

document.addEventListener('DOMContentLoaded', () => {

    function initializeActionModals() {
        // --- FILE VIEWER MODAL ---
        const fileViewerModal = document.getElementById('fileViewerModal');
        if (fileViewerModal) {
            const iframe = fileViewerModal.querySelector('#fileViewerIframe');
            const loader = fileViewerModal.querySelector('.loader-container');
            const feedbackContainer = fileViewerModal.querySelector('#fileViewerFeedback');
            const downloadBtn = fileViewerModal.querySelector('#fileViewerDownloadBtn');
            const slider = fileViewerModal.querySelector('#fileViewerSlider');
            const prevBtn = fileViewerModal.querySelector('#prevFileBtn');
            const nextBtn = fileViewerModal.querySelector('#nextFileBtn');
            const counter = fileViewerModal.querySelector('#fileCounter');
            const detailsContent = fileViewerModal.querySelector('#file-details-content');
            const detailsPanel = fileViewerModal.querySelector('.file-details-panel');
            const toggleDetailsBtn = fileViewerModal.querySelector('#toggleDetailsBtn');
            const closeBtn = fileViewerModal.querySelector('#closeModalBtn');

            let files = [];
            let currentIndex = 0;
            let activeFetchController = null;
            const PREVIEWABLE_EXTENSIONS = ['pdf','png','jpg','jpeg','gif','webp','txt'];

            const escapeHtml = (str) => str.replace(/[&<>"']/g, (m) => ({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;' })[m]);
            const showLoader = (on = true) => loader && (loader.style.display = on ? 'flex' : 'none');

            const renderDetails = (details = {}) => {
                if (!detailsContent) return;
                let html = '';
                for (const [k, v] of Object.entries(details)) {
                    if (v !== null && v !== undefined && (typeof v !== 'object')) {
                        html += `<div class="file-details-content-item"><strong>${escapeHtml(k)}:</strong> <span>${escapeHtml(String(v))}</span></div>`;
                    }
                }
                detailsContent.innerHTML = html || '<p class="text-muted">No details available.</p>';
            };

            const showFile = (index) => {
                if (!files || index < 0 || index >= files.length) return;
                currentIndex = index;
                showLoader(true);
                iframe.style.display = 'none';
                feedbackContainer.style.display = 'none';

                const file = files[currentIndex];
                const isPreviewable = PREVIEWABLE_EXTENSIONS.includes(file.ext);
                
                if (isPreviewable) {
                    iframe.src = (file.ext === 'pdf') ? `${file.url}#toolbar=1` : file.url;
                    iframe.onload = () => { showLoader(false); iframe.style.display = 'block'; };
                } else {
                    showLoader(false);
                    if (downloadBtn) {
                        downloadBtn.href = file.url;
                        downloadBtn.download = file.name;
                    }
                    if (feedbackContainer) {
                        feedbackContainer.style.display = 'flex';
                    }
                }

                if (counter) counter.textContent = `${currentIndex + 1} / ${files.length}`;
                if (slider) slider.style.display = (files.length > 1) ? 'flex' : 'none';
                if (prevBtn) prevBtn.disabled = currentIndex === 0;
                if (nextBtn) nextBtn.disabled = currentIndex === files.length - 1;
            };

            const closeModal = () => {
                fileViewerModal.classList.add('modal-container--hidden');
                document.body.classList.remove('modal-open');
                if (iframe) iframe.src = 'about:blank';
                if (activeFetchController) { try { activeFetchController.abort(); } catch(e){} }
            };

            window.openFileViewerModal = async (infoUrl) => {
                fileViewerModal.classList.remove('modal-container--hidden');
                document.body.classList.add('modal-open');
                showLoader(true);
                iframe.style.display = 'none';
                feedbackContainer.style.display = 'none';
                if (detailsContent) detailsContent.innerHTML = '';
                if (slider) slider.style.display = 'none';

                if (activeFetchController) { try { activeFetchController.abort(); } catch(e){} }
                activeFetchController = new AbortController();

                try {
                    const res = await fetch(infoUrl, { headers: { 'X-Requested-With':'XMLHttpRequest' }, signal: activeFetchController.signal });
                    if (!res.ok) throw new Error(`Server responded with ${res.status}`);
                    const data = await res.json();
                    
                    const normalize = (f) => {
                        const url = f.file_url || f.viewUrl;
                        const name = f.file_name || f.filename || 'file';
                        return { url, name, ext: (name.split('.').pop() || '').toLowerCase() };
                    };
                    
                    files = (data.files || []).map(normalize).filter(f => f.url);
                    const details = data.details || data.recordData || {};
                    
                    renderDetails(details);
                    if (files.length > 0) {
                        showFile(0);
                    } else {
                        showLoader(false);
                        // Use the feedback container for the "no files" message.
                        if (feedbackContainer) {
                            feedbackContainer.innerHTML = '<p class="alert-info">No files are associated with this record.</p>';
                            feedbackContainer.style.display = 'flex';
                        }
                    }
                } catch (err) {
                    if (err.name !== 'AbortError') {
                        console.error('Error fetching files:', err);
                        showLoader(false);
                        if (feedbackContainer) {
                            feedbackContainer.innerHTML = `<p class="alert-danger">Could not load file information. Please try again.</p>`;
                            feedbackContainer.style.display = 'flex';
                        }
                    }
                } finally {
                    activeFetchController = null;
                }
            };

            prevBtn?.addEventListener('click', () => { if (currentIndex > 0) showFile(currentIndex - 1); });
            nextBtn?.addEventListener('click', () => { if (currentIndex < files.length - 1) showFile(currentIndex + 1); });
            closeBtn?.addEventListener('click', closeModal);
            fileViewerModal.addEventListener('click', (e) => { if (e.target === fileViewerModal) closeModal(); });
            toggleDetailsBtn?.addEventListener('click', () => { detailsPanel?.classList.toggle('file-details-panel--hidden'); toggleDetailsBtn.classList.toggle('active'); });
            document.addEventListener('keydown', (e) => {
                if (fileViewerModal.classList.contains('modal-container--hidden')) return;
                if (e.key === 'Escape') closeModal();
                if (e.key === 'ArrowLeft') { if (currentIndex > 0) showFile(currentIndex - 1); }
                if (e.key === 'ArrowRight') { if (currentIndex < files.length - 1) showFile(currentIndex + 1); }
            });
        }

        // --- Confirmation Modal ---
        const confirmationModal = document.getElementById('confirmationModal');
        if (confirmationModal) {
            const closeBtn = document.getElementById('closeConfirmationModalBtn');
            const cancelBtn = document.getElementById('cancelConfirmationBtn');
            closeBtn?.addEventListener('click', hideConfirmationModal);
            cancelBtn?.addEventListener('click', hideConfirmationModal);
            confirmationModal.addEventListener('click', (e) => { if (e.target === confirmationModal) hideConfirmationModal(); });
        }

        // --- Logout Confirmation ---
        const logoutButton = document.getElementById('logout-button');
        if (logoutButton) {
            logoutButton.addEventListener('click', (event) => {
                event.preventDefault();
                showConfirmationModal({
                    title: 'Confirm Logout',
                    body: 'Are you sure you want to log out of your account?',
                    confirmText: 'Logout',
                    onConfirm: () => new Promise(resolve => {
                        document.getElementById('logout-form').submit();
                        resolve();
                    })
                });
            });
        }

        // --- Delegated Click Listeners ---
        document.body.addEventListener('click', (event) => {
            const viewButton = event.target.closest('.view-file-btn');
            if (viewButton && viewButton.dataset.infoUrl) {
                event.preventDefault();
                window.openFileViewerModal(viewButton.dataset.infoUrl);
                return;
            }

            const actionButton = event.target.closest('.confirm-action-btn');
            if (actionButton) {
                event.preventDefault();
                showConfirmationModal({
                    title: actionButton.dataset.modalTitle,
                    body: actionButton.dataset.modalText,
                    confirmText: actionButton.dataset.confirmButtonText,
                    onConfirm: async () => {
                        const response = await fetch(actionButton.dataset.actionUrl, {
                            method: actionButton.dataset.method || 'DELETE',
                            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                        });
                        const data = await response.json();
                        if (!response.ok) throw new Error(data.message || 'An error occurred.');
                        
                        document.getElementById('confirmation-final-status-message-area').innerHTML = `<div class="alert-success">${data.message}</div>`;
                        if (typeof window.loadData === 'function') window.loadData(true);
                        setTimeout(hideConfirmationModal, 850);
                    }
                });
            }
        });
    }

    initializeActionModals();
    window.initializeActionModals = initializeActionModals;
});