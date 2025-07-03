/**
 * StatusManager Class
 *
 * Manages facility status comments in the ecoBuddy system.
 * Handles adding, updating, and retrieving status comments via AJAX.
 */
class StatusManager {
    /**
     * Constructor for the StatusManager class
     * @param {boolean} isAuthenticated - Whether the current user is authenticated
     */
    constructor(isAuthenticated = false) {
        this.isAuthenticated = isAuthenticated;
        this.csrfToken = this.getCSRFToken();
        this.statusModalId = 'statusUpdateModal';
        this.currentFacilityId = null;

        // Set up event delegation FIRST - this will work even if modal doesn't exist yet
        this.setupEventDelegation();

        // Create modal element if it doesn't exist
        this.createModalElement();
    }

    /**
     * Set up event delegation for save button clicks
     * This uses event bubbling to catch clicks on the save button even if it's created later
     */
    setupEventDelegation() {
        // Use event delegation on document body to catch save button clicks
        document.body.addEventListener('click', (event) => {
            // Check if the clicked element is our save button
            if (event.target && event.target.id === 'saveStatusBtn') {
                console.log('Save button clicked via event delegation!');
                event.preventDefault();
                event.stopPropagation();
                this.saveStatus();
            }
        });
        console.log('Event delegation set up for save button');
    }

    /**
     * Get CSRF token from meta tag or other source
     * @returns {string} CSRF token
     */
    getCSRFToken() {
        // Try to get from meta tag first
        const metaToken = document.querySelector('meta[name="csrf-token"]');
        if (metaToken) {
            return metaToken.getAttribute('content');
        }

        // Try to get from a global variable
        if (typeof window.csrfToken !== 'undefined') {
            return window.csrfToken;
        }

        // Try to get token from cookies
        const cookies = document.cookie.split(';');
        for (let i = 0; i < cookies.length; i++) {
            const cookie = cookies[i].trim();
            if (cookie.startsWith('csrf_token=')) {
                return cookie.substring('csrf_token='.length, cookie.length);
            }
        }

        // Return empty string as fallback (your API accepts any token in dev mode)
        return 'dev-token';
    }

    /**
     * Create the modal element for status updates
     */
    createModalElement() {
        // Check if modal already exists
        if (document.getElementById(this.statusModalId)) {
            console.log('Modal already exists');
            return;
        }

        // Create modal structure
        const modalHtml = `
            <div class="modal fade" id="${this.statusModalId}" tabindex="-1" aria-labelledby="statusUpdateModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="statusUpdateModalLabel">Update Facility Status</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <form id="statusUpdateForm">
                                <input type="hidden" id="facilityId" name="facilityId">
                                <input type="hidden" id="csrfToken" name="csrfToken" value="${this.csrfToken}">
                                <div class="mb-3">
                                    <label for="statusComment" class="form-label">Status Comment</label>
                                    <textarea 
                                        class="form-control" 
                                        id="statusComment" 
                                        name="statusComment" 
                                        rows="3" 
                                        maxlength="100" 
                                        placeholder="e.g., 'Not working', 'Bin is full', 'Shop is closed'"
                                        required></textarea>
                                    <div class="form-text">Describe the current status of this facility (100 chars max)</div>
                                </div>
                            </form>
                            <div id="statusUpdateError" class="alert alert-danger d-none"></div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-primary" id="saveStatusBtn">Save Status</button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        // Add modal to the document
        const modalContainer = document.createElement('div');
        modalContainer.innerHTML = modalHtml;
        document.body.appendChild(modalContainer.firstElementChild);

        console.log('Modal created and added to DOM');

        // Double-check that the button exists
        setTimeout(() => {
            const saveBtn = document.getElementById('saveStatusBtn');
            console.log('Save button after creation:', saveBtn);
        }, 100);
    }

    /**
     * Open the status update modal for a facility
     * @param {number} facilityId - ID of the facility to update
     */
    openStatusModal(facilityId) {
        console.log('Opening status modal for facility:', facilityId);

        if (!this.isAuthenticated) {
            window.location.href = 'login.php';
            return;
        }

        this.currentFacilityId = facilityId;

        // Set the facility ID in the form
        const facilityIdInput = document.getElementById('facilityId');
        if (facilityIdInput) {
            facilityIdInput.value = facilityId;
            console.log('Set facility ID to:', facilityId);
        } else {
            console.error('Facility ID input not found!');
        }

        // Clear any previous error messages
        this.hideError();

        // Get current status if exists
        this.getExistingStatus(facilityId)
            .then(status => {
                const statusTextarea = document.getElementById('statusComment');
                if (statusTextarea) {
                    statusTextarea.value = status || '';
                    console.log('Set existing status:', status);
                }
            })
            .catch(error => console.error('Error fetching status:', error));

        // Open the modal using Bootstrap
        const modalElement = document.getElementById(this.statusModalId);
        if (modalElement) {
            const modal = new bootstrap.Modal(modalElement);
            modal.show();
            console.log('Modal opened');
        } else {
            console.error('Modal element not found!');
        }
    }

    /**
     * Get the existing status for a facility
     * @param {number} facilityId - ID of the facility
     * @returns {Promise<string>} Resolves with the status comment or null
     */
    getExistingStatus(facilityId) {
        return new Promise((resolve, reject) => {
            const url = `api/status.php?action=get&facilityId=${facilityId}&csrfToken=${this.csrfToken}`;

            fetch(url)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        resolve(data.statusComment);
                    } else {
                        resolve(null);
                    }
                })
                .catch(error => {
                    console.error('Error fetching status:', error);
                    reject(error);
                });
        });
    }

    /**
     * Save the status update
     */
    saveStatus() {
        console.log('saveStatus() called');

        if (!this.isAuthenticated) {
            this.showError('You must be logged in to update status');
            return;
        }

        const form = document.getElementById('statusUpdateForm');
        if (!form) {
            console.error('Form not found!');
            return;
        }

        const formData = new FormData(form);
        formData.append('action', 'save');

        // Debug output
        console.log('Form data:', {
            facilityId: formData.get('facilityId'),
            statusComment: formData.get('statusComment'),
            csrfToken: formData.get('csrfToken')
        });

        // Validate the form
        const statusComment = formData.get('statusComment');
        if (!statusComment || statusComment.trim() === '') {
            this.showError('Status comment cannot be empty');
            return;
        }

        // Security check - validate length and content
        if (statusComment.length > 100) {
            this.showError('Status comment is too long (max 100 characters)');
            return;
        }

        // Disable the save button during request
        const saveBtn = document.getElementById('saveStatusBtn');
        if (saveBtn) {
            saveBtn.disabled = true;
            saveBtn.textContent = 'Saving...';
            console.log('Save button disabled');
        }

        // Make the AJAX request
        console.log('Sending AJAX request to api/status.php');
        fetch('api/status.php', {
            method: 'POST',
            body: formData
        })
            .then(response => {
                console.log('Response status:', response.status);
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                console.log('Response data:', data);
                if (data.success) {
                    // Close the modal
                    const modalElement = document.getElementById(this.statusModalId);
                    if (modalElement) {
                        const modal = bootstrap.Modal.getInstance(modalElement);
                        if (modal) {
                            modal.hide();
                        }
                    }

                    // Show success message
                    this.showSuccessMessage('Status updated successfully!');

                    // Refresh the page or update UI
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    this.showError(data.message || 'Failed to update status');
                }
            })
            .catch(error => {
                console.error('Error saving status:', error);
                this.showError('An error occurred while saving the status. Please try again.');
            })
            .finally(() => {
                // Re-enable the save button
                if (saveBtn) {
                    saveBtn.disabled = false;
                    saveBtn.textContent = 'Save Status';
                    console.log('Save button re-enabled');
                }
            });
    }

    /**
     * Show error message in the modal
     * @param {string} message - Error message to display
     */
    showError(message) {
        const errorDiv = document.getElementById('statusUpdateError');
        if (errorDiv) {
            errorDiv.textContent = message;
            errorDiv.classList.remove('d-none');
        }
        console.log('Error shown:', message);
    }

    /**
     * Hide error message
     */
    hideError() {
        const errorDiv = document.getElementById('statusUpdateError');
        if (errorDiv) {
            errorDiv.classList.add('d-none');
        }
    }

    /**
     * Show success message
     * @param {string} message - Success message to display
     */
    showSuccessMessage(message) {
        console.log('Success:', message);

        // Simple alert for now (you can replace with a better notification system)
        if (typeof Utilities !== 'undefined' && Utilities.showNotification) {
            Utilities.showNotification(message, 'success');
        } else {
            alert(message);
        }
    }

    /**
     * Update marker popups after status change
     */
    updateMarkerPopups() {
        // Get updated facility data
        fetch(`api/facilities.php?action=get&id=${this.currentFacilityId}&csrfToken=${this.csrfToken}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && typeof window.mapManager !== 'undefined') {
                    window.mapManager.updateMarkerPopup(this.currentFacilityId, data.facility);
                }
            })
            .catch(error => console.error('Error updating marker:', error));
    }
}