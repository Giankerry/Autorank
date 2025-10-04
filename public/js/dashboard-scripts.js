document.addEventListener('DOMContentLoaded', function () {
    /*
    |--------------------------------------------------------------------------
    | SLOTS MODAL LOGIC (For Admins)
    |--------------------------------------------------------------------------
    */
    const slotsModal = document.getElementById('slots-modal');
    let currentPositionId = null;
    const pageRefreshDelay = 1250;

    if (slotsModal) {
        const closeBtn = document.getElementById('slots-modal-close-btn');
        const initialStep = document.getElementById('slots-modal-initial-step');
        const confirmationStep = document.getElementById('slots-modal-confirmation-step');
        const proceedBtn = document.getElementById('slots-proceed-to-confirmation-btn');
        const backBtn = document.getElementById('slots-back-to-selection-btn');
        const confirmBtn = document.getElementById('slots-confirm-btn');
        const slotsInput = document.getElementById('available-slots-input');
        const messages = {
            initial: document.getElementById('slots-modal-messages'),
            confirmation: document.getElementById('slots-confirmation-message-area'),
            finalStatus: document.getElementById('slots-final-status-message-area'),
        };

        const showStep = (step) => {
            initialStep.style.display = (step === 'initial') ? 'block' : 'none';
            confirmationStep.style.display = (step === 'confirmation') ? 'block' : 'none';
        };

        const showSlotsModal = (positionId) => {
            currentPositionId = positionId;
            slotsModal.style.display = 'flex';
            document.body.classList.add('modal-open');
            showStep('initial');
        };

        const hideSlotsModal = () => {
            slotsModal.style.display = 'none';
            document.body.classList.remove('modal-open');
            Object.values(messages).forEach(el => { if (el) el.innerHTML = ''; });
            slotsInput.value = 1;
            [confirmBtn, backBtn, closeBtn].forEach(btn => { if (btn) btn.disabled = false; });
        };

        closeBtn.addEventListener('click', hideSlotsModal);
        slotsModal.addEventListener('click', (e) => { if (e.target === slotsModal) hideSlotsModal(); });
        backBtn.addEventListener('click', () => {
            showStep('initial');
            if (messages.finalStatus) messages.finalStatus.innerHTML = '';
        });

        proceedBtn.addEventListener('click', () => {
            messages.confirmation.innerHTML = `You are about to set the available slots to <strong>${slotsInput.value}</strong>. Do you want to proceed?`;
            showStep('confirmation');
        });

        confirmBtn.addEventListener('click', async () => {
            [confirmBtn, backBtn, closeBtn].forEach(btn => btn.disabled = true);
            messages.finalStatus.innerHTML = '<div class="alert-info">Updating...</div>';
            const result = await updatePositionStatus(currentPositionId, slotsInput.value);
            if (result && result.success) {
                messages.finalStatus.innerHTML = `<div class="alert-success">${result.message}</div>`;
                setTimeout(hideSlotsModal, pageRefreshDelay);
            } else {
                messages.finalStatus.innerHTML = `<div class="alert-danger">${result ? result.message : 'An unknown error occurred.'}</div>`;
                [confirmBtn, backBtn, closeBtn].forEach(btn => btn.disabled = false);
            }
        });

        window.showSlotsModal = showSlotsModal;
    }

    /*
    |--------------------------------------------------------------------------
    | EVENT DELEGATION (Handles both Admin and User button clicks)
    |--------------------------------------------------------------------------
    */
    document.body.addEventListener('click', async function(event) {
        const button = event.target.closest('button');
        if (!button) return;

        // --- Logic for User Apply Button ---
        if (button.classList.contains('apply-btn')) {
            const positionId = button.dataset.positionId; // Get the position ID from the button
            const buttonText = button.querySelector('.button-text');
            const loader = button.querySelector('.button-loader');

            button.disabled = true;
            buttonText.style.display = 'none';
            loader.style.display = 'block';

            try {
                // The URL is defined in web.php
                const response = await fetch('/application/check-completeness');
                const data = await response.json();

                if (data.complete) {
                    // All documents are present, show a confirmation modal to submit
                    window.showConfirmationModal({
                        title: 'Confirm Application',
                        text: 'You have all the required documents. Are you sure you want to submit your application for this position?',
                        confirmButtonText: 'Yes, Submit',
                        actionUrl: `/application/submit/${positionId}`,
                        method: 'POST',
                        type: 'success'
                    });
                } else {
                    // Documents are missing, show an informational modal
                    let missingList = data.missing.map(item => `<li>${item}</li>`).join('');
                    window.showConfirmationModal({
                        title: 'Incomplete Requirements',
                        text: `You cannot apply yet. Please upload files for the following categories:<br><br><ul>${missingList}</ul>`,
                        confirmButtonText: 'Understood'
                        // No actionUrl means the confirm button will just close the modal
                    });
                }
            } catch (error) {
                console.error('Error checking application completeness:', error);
                window.showConfirmationModal({
                    title: 'Error',
                    text: 'Could not check your application readiness. Please try again later.',
                    confirmButtonText: 'Close'
                });
            } finally {
                button.disabled = false;
                buttonText.style.display = 'inline';
                loader.style.display = 'none';
            }
        }

        // --- Logic for Admin "Set Available" Button ---
        if (button.classList.contains('toggle-button') && button.classList.contains('set-available')) {
            const positionId = button.dataset.id;
            window.showSlotsModal(positionId);
        }
    });

    /*
    |--------------------------------------------------------------------------
    | UPDATE POSITION STATUS (AJAX Helper for Slots Modal)
    |--------------------------------------------------------------------------
    */
    async function updatePositionStatus(positionId, availableSlots) {
        const toggleButton = document.getElementById(`toggle-button-${positionId}`);
        if (!toggleButton) {
            console.error(`Button with ID toggle-button-${positionId} not found.`);
            return { success: false, message: 'UI element not found.' };
        }
        const url = toggleButton.dataset.url;
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        try {
            const response = await fetch(url, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ available_slots: availableSlots })
            });

            const data = await response.json();
            if (response.ok && data.success) {
                if (typeof window.updatePositionCard === 'function') {
                    window.updatePositionCard(data);
                }
            }
            return data;
        } catch (error) {
            console.error('Error:', error);
            return { success: false, message: 'A network error occurred.' };
        }
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE POSITION CARD UI (Global Helper)
    |--------------------------------------------------------------------------
    */
    window.updatePositionCard = function(data) {
        if (!data || !data.position || !data.position.id) return;
        
        const position = data.position;
        const badge = document.getElementById(`badge-${position.id}`);
        const toggleButton = document.getElementById(`toggle-button-${position.id}`);
        const slotsDisplay = document.getElementById(`slots-${position.id}`);

        if (badge) badge.className = `status-indicator ${position.is_available ? 'available' : 'unavailable'}`;
        if (slotsDisplay) slotsDisplay.textContent = position.available_slots;
        
        if (toggleButton) {
            if (position.is_available) {
                toggleButton.textContent = 'Set Unavailable';
                toggleButton.className = 'toggle-button set-unavailable confirm-action-btn';
                toggleButton.dataset.id = position.id;
                toggleButton.dataset.actionUrl = toggleButton.dataset.url;
                toggleButton.dataset.method = 'PATCH';
                toggleButton.dataset.modalTitle = 'Confirm Action';
                toggleButton.dataset.modalText = `Are you sure you want to set the "${position.title}" rank to unavailable?`;
                toggleButton.dataset.confirmButtonText = 'Confirm';
            } else {
                toggleButton.textContent = 'Set Available';
                toggleButton.className = 'toggle-button set-available';
                toggleButton.dataset.id = position.id;
                ['actionUrl', 'method', 'modalTitle', 'modalText', 'confirmButtonText'].forEach(attr => delete toggleButton.dataset[attr]);
            }
        }
    }
});