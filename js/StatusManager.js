/**
 * StatusManager Class
 *
 * Manages facility status comments in the ecoBuddy system.
 * Handles adding, updating, and retrieving status comments via AJAX.
 *
 * Features:
 * - Status comment submission
 * - Modal interface for entering status
 * - AJAX requests for status operations
 * - Security and validation
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

        // Create modal element if it doesn't exist
        this.createModalElement();

        // Set up event listeners
        this.setupEventListeners();
    }

    /**
     * Create the modal element for status updates
     */
    createModalElement() {
        // Check if modal already exists
        if (document.getElementById(this.statusModalId)) {
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
    }

    /**
     * Set up event listeners for status operations
     */
    setupEventListeners() {
        document.addEventListener('DOMContentLoaded', () => {
            // Handle status form submission
            const saveBtn = document.getElementById('saveStatusBtn');
            if (saveBtn) {
                saveBtn.addEventListener('click', () => this.saveStatus());
            }
        });
    }

    /**
     * Open the status update modal for a facility
     * @param {number} facilityId - ID of the facility to update
     */
    openStatusModal(facilityId) {
        if (!this.isAuthenticated) {
            window.location.href = 'login.php';
            return;
        }

        this.currentFacilityId = facilityId;

        // Set the facility ID in the form
        const facilityIdInput = document.getElementById('facilityId');
        if (facilityIdInput) {
            facilityIdInput.value = facilityId;
        }

        // Get current status if exists
        this.getExistingStatus(facilityId)
            .then(status => {
                const statusTextarea = document.getElementById('statusComment');
                if (statusTextarea && status) {
                    statusTextarea.value = status;
                }
            })
            .catch(error => console.error('Error fetching status:', error));

        // Open the modal using Bootstrap
        const modal = new bootstrap.Modal(document.getElementById(this.statusModalId));
        modal.show();
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
        if (!this.isAuthenticated) {
            this.showError('You must be logged in to update status');
            return;
        }

        const form = document.getElementById('statusUpdateForm');
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
                    const modal = bootstrap.Modal.getInstance(document.getElementById(this.statusModalId));
                    modal.hide();

                    // Refresh the page or update UI
                    if (typeof mapManager !== 'undefined') {
                        // If mapManager exists, update the marker popup
                        this.updateMarkerPopups();
                    } else {
                        // Otherwise refresh the page
                        window.location.reload();
                    }
                } else {
                    this.showError(data.message || 'Failed to update status');
                }
            })
            .catch(error => {
                console.error('Error saving status:', error);
                this.showError('An error occurred while saving the status');
            });
    }

    /**
     * Update marker popups after status change
     */
    updateMarkerPopups() {
        // Get updated facility data
        fetch(`api/facilities.php?action=get&id=${this.currentFacilityId}&csrfToken=${this.csrfToken}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && typeof mapManager !== 'undefined') {
                    mapManager.updateMarkerPopup(this.currentFacilityId, data.facility);
                }
            })
            .catch(error => console.error('Error updating marker:', error));
    }

    /**
     * Display an error message in the modal
     * @param {string} message - Error message to display
     */
    showError(message) {
        const errorDiv = document.getElementById('statusUpdateError');
        if (errorDiv) {
            errorDiv.textContent = message;
            errorDiv.classList.remove('d-none');

            // Hide the error after 3 seconds
            setTimeout(() => {
                errorDiv.classList.add('d-none');
            }, 3000);
        }
    }

    /**
     * Get CSRF token from meta tag or cookie
     * @returns {string} CSRF token
     */
    getCSRFToken() {
        // Try to get token from meta tag
        const metaTag = document.querySelector('meta[name="csrf-token"]');
        if (metaTag) {
            return metaTag.getAttribute('content');
        }

        // Fallback to a method to get from cookie (implementation depends on your system)
        return this.getCSRFTokenFromCookie();
    }

    /**
     * Extract CSRF token from cookies
     * @returns {string} CSRF token or empty string if not found
     */
    getCSRFTokenFromCookie() {
        const cookies = document.cookie.split(';');
        for (let i = 0; i < cookies.length; i++) {
            const cookie = cookies[i].trim();
            if (cookie.startsWith('csrf_token=')) {
                return cookie.substring('csrf_token='.length, cookie.length);
            }
        }
        return '';
    }
}