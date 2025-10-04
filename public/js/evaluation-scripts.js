/**
 * @file Manages all dynamic functionality for the evaluator pages.
 * This script handles both the applications dashboard and the
 * KRA-specific evaluation page.
 */
document.addEventListener('DOMContentLoaded', () => {
    // --- PART 1: PAGE DETECTION & CONFIG ---
    let pageConfig = {};
    const table = document.querySelector('.performance-metric-container table');
    const tableBody = table ? table.querySelector('tbody') : null;
    const loadMoreButton = document.getElementById('load-more-btn');
    const searchForm = document.getElementById('search-form');
    const searchInput = searchForm ? searchForm.querySelector('input[name="search"]') : null;
    const searchBtn = document.getElementById('search-btn');
    const clearSearchBtn = document.getElementById('clear-search-btn');

    if (document.getElementById('status-filter')) {
        pageConfig = { page: 'applications', filterElement: document.getElementById('status-filter'), filterParamName: 'status' };
    } else if (document.getElementById('filter-select')) {
        pageConfig = { page: 'kra-evaluation', filterElement: document.getElementById('filter-select'), filterParamName: 'filter' };
    } else if (document.getElementById('details-button')) {
        pageConfig = { page: 'application-details'};
    } else { return; }

    if (pageConfig.page !== 'application-details') {
        if (!tableBody || !loadMoreButton || !searchForm || !table || !searchBtn || !clearSearchBtn || !pageConfig.filterElement) {
            return;
        }

        // --- PART 2: SHARED UI & DATA LOADING ---
        const toggleSearchButtons = () => {
            if (searchInput.value.length > 0) {
                searchBtn.style.display = 'none';
                clearSearchBtn.style.display = 'inline-block';
            } else {
                searchBtn.style.display = 'inline-block';
                clearSearchBtn.style.display = 'none';
            }
        };

        window.loadData = async (isNewQuery = false) => {
            if (isNewQuery) {
                loadMoreButton.dataset.currentOffset = '0';
                const colspan = table.querySelectorAll('thead th').length;
                tableBody.innerHTML = `<tr><td colspan="${colspan}" style="text-align: center;">Loading...</td></tr>`;
            }
            const offset = parseInt(loadMoreButton.dataset.currentOffset, 10);
            const searchTerm = searchInput.value;
            const selectedFilter = pageConfig.filterElement.value;
            loadMoreButton.disabled = true;
            loadMoreButton.textContent = 'Loading...';
            try {
                const url = new URL(window.location.href);
                url.searchParams.set(pageConfig.filterParamName, selectedFilter);
                url.searchParams.set('search', searchTerm);
                url.searchParams.set('offset', offset);
                const response = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
                const data = await response.json();
                if (isNewQuery) tableBody.innerHTML = '';
                if (data.html) tableBody.insertAdjacentHTML('beforeend', data.html);
                loadMoreButton.dataset.currentOffset = data.nextOffset;
                loadMoreButton.style.display = data.hasMore ? 'inline-block' : 'none';
                if (tableBody.children.length === 0) {
                    const colspan = table.querySelectorAll('thead th').length;
                    const message = pageConfig.page === 'applications' ? 'No applications found.' : 'No submissions found.';
                    tableBody.innerHTML = `<tr id="no-results-row"><td colspan="${colspan}" style="text-align: center;">${message}</td></tr>`;
                    loadMoreButton.style.display = 'none';
                }
            } catch (error) {
                console.error('Error loading data:', error);
                const colspan = table.querySelectorAll('thead th').length;
                tableBody.innerHTML = `<tr id="no-results-row"><td colspan="${colspan}" style="text-align: center;">An error occurred. Please try again.</td></tr>`;
            } finally {
                loadMoreButton.disabled = false;
                loadMoreButton.textContent = 'Load More +';
            }
        };

        searchInput.addEventListener('input', toggleSearchButtons);
        clearSearchBtn.addEventListener('click', () => { searchInput.value = ''; toggleSearchButtons(); window.loadData(true); });
        loadMoreButton.addEventListener('click', () => window.loadData(false));
        searchForm.addEventListener('submit', (e) => { e.preventDefault(); window.loadData(true); });
        pageConfig.filterElement.addEventListener('change', () => window.loadData(true));
        toggleSearchButtons();

        // --- PART 3: KRA PAGE-SPECIFIC LOGIC ---
        if (pageConfig.page === 'kra-evaluation') {

            function initializeScoreModal(modal) {
                const form = modal.querySelector('form');
                if (!form) return;
                const initialStep = modal.querySelector('.initial-step');
                const confirmationStep = modal.querySelector('.confirmation-step');
                const closeBtn = modal.querySelector('.close-modal-btn');
                const proceedBtn = modal.querySelector('.proceed-btn');
                const backBtn = modal.querySelector('.back-btn');
                const confirmBtn = modal.querySelector('.confirm-btn');
                const messages = { initial: modal.querySelector('.modal-messages'), confirmation: modal.querySelector('.confirmation-message-area'), finalStatus: modal.querySelector('.final-status-message-area'), };
                
                const showStep = (step) => {
                    initialStep.style.display = (step === 'initial') ? 'block' : 'none';
                    confirmationStep.style.display = (step === 'confirmation') ? 'block' : 'none';
                };
                
                const hideModal = () => {
                    modal.style.display = 'none';
                    document.body.classList.remove('modal-open');
                    Object.values(messages).forEach(el => el && (el.innerHTML = ''));
                    form.reset();
                    [confirmBtn, backBtn, closeBtn].forEach(btn => btn && (btn.disabled = false));
                    showStep('initial');
                };
                
                closeBtn.addEventListener('click', hideModal);
                modal.addEventListener('click', (e) => (e.target === modal) && hideModal());
                backBtn.addEventListener('click', () => { showStep('initial'); messages.finalStatus.innerHTML = ''; });

                proceedBtn.addEventListener('click', () => {
                    if (messages.initial) messages.initial.innerHTML = '';
                    if (!form.checkValidity()) {
                        if (messages.initial) messages.initial.innerHTML = '<div class="alert-danger">Please enter a valid score.</div>';
                        return;
                    }
                    const scoreValue = parseFloat(form.querySelector('input[name="score"]').value).toFixed(2);
                    messages.confirmation.innerHTML = `Please confirm you want to set the score to <strong>${scoreValue}</strong>. This action cannot be undone.`;
                    showStep('confirmation');
                });

                confirmBtn.addEventListener('click', async () => {
                    const id = form.querySelector('input[name="submission_id"]').value;
                    const kra = form.querySelector('input[name="kra_slug"]').value;
                    const score = form.querySelector('input[name="score"]').value;
                    const csrf = form.querySelector('input[name="_token"]').value;
                    [confirmBtn, backBtn, closeBtn].forEach(btn => btn.disabled = true);
                    messages.finalStatus.innerHTML = '<div class="alert-info">Saving... Please wait.</div>';
                    try {
                        const response = await fetch(`/evaluator/application/score/${kra}/${id}`, { method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf }, body: JSON.stringify({ score: score }) });
                        const data = await response.json();
                        if (!response.ok) throw new Error(data.message || 'An unknown error occurred.');
                        messages.finalStatus.innerHTML = `<div class="alert-success">${data.message}</div>`;
                        setTimeout(() => { hideModal(); window.loadData(true); }, 1500);
                    } catch (error) {
                        messages.finalStatus.innerHTML = `<div class="alert-danger">${error.message}</div>`;
                        [confirmBtn, backBtn, closeBtn].forEach(btn => btn.disabled = false);
                    }
                });
            }

            // --- INITIALIZE & ATTACH LISTENERS ---
            const scoreModal = document.getElementById('scoring-modal');

            if (scoreModal) { initializeScoreModal(scoreModal); }

            tableBody.addEventListener('click', (e) => {
                const scoreBtn = e.target.closest('.set-score-btn');
                if (scoreBtn) {
                    const form = scoreModal.querySelector('form');
                    form.querySelector('input[name="submission_id"]').value = scoreBtn.dataset.submissionId;
                    form.querySelector('input[name="kra_slug"]').value = scoreBtn.dataset.kraSlug;
                    scoreModal.querySelector('#scoring-modal-title').textContent = `Set Score for "${scoreBtn.dataset.submissionTitle}"`;
                    scoreModal.style.display = 'flex';
                    document.body.classList.add('modal-open');
                    return;
                }
            });
        }
    }

    /*
    |--------------------------------------------------------------------------
    | TOOLTIP LOGIC
    |--------------------------------------------------------------------------
    */
    const detailsButton = document.getElementById('details-button');
    const dropdownContent = document.querySelector('.dropdown-content');

    if (!detailsButton || !dropdownContent) return; 

    detailsButton.addEventListener('click', () => {
        dropdownContent.classList.toggle('show');
    });

    // Hide when clicking outside
    document.addEventListener('click', (event) => {
        if (!dropdownContent.contains(event.target) && event.target !== detailsButton) {
            dropdownContent.classList.remove('show');
        }
    });
});
