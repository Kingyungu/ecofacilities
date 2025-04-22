/**
 * InfiniteScrollManager Class
 *
 * Implements "infinite scrolling" or "load more" functionality for ecoFacilities in the ecoBuddy system.
 * Uses efficient AJAX loading with a sliding window approach to limit memory usage.
 *
 * Features:
 * - Dynamic loading of content as user scrolls
 * - Memory efficient with sliding window approach
 * - Loading indicators
 * - Error handling
 * - Map integration
 */
class InfiniteScrollManager {
    /**
     * Constructor for the InfiniteScrollManager class
     * @param {string} containerSelector - CSS selector for the container to add items to
     * @param {Object} mapManager - Optional reference to MapManager instance
     * @param {Object} options - Configuration options
     */
    constructor(containerSelector, mapManager = null, options = {}) {
        // Elements
        this.container = document.querySelector(containerSelector);
        this.mapManager = mapManager;

        // Configuration with defaults
        this.options = {
            itemsPerPage: 20,
            maxItemsToKeep: 100, // Max items to keep in DOM for memory efficiency
            threshold: 200, // px from bottom to trigger loading
            loadingDelay: 500, // ms simulated delay (for demonstration purposes)
            filterFormSelector: '#filterForm',
            ...options
        };

        // State
        this.page = 1;
        this.totalItems = 0;
        this.totalPages = 0;
        this.loading = false;
        this.allItemsLoaded = false;
        this.items = []; // Track all loaded items
        this.visibleItems = []; // Currently visible in DOM
        this.csrfToken = this.getCSRFToken();
        this.filterForm = document.querySelector(this.options.filterFormSelector);

        // Initialize
        if (this.container) {
            this.initialize();
        }
    }

    /**
     * Initialize the infinite scroll manager
     */
    initialize() {
        // Add loading indicator container
        this.loadingIndicator = document.createElement('div');
        this.loadingIndicator.className = 'loading-indicator text-center py-3 d-none';
        this.loadingIndicator.innerHTML = `
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2">Loading more facilities...</p>
        `;
        this.container.after(this.loadingIndicator);

        // Add scroll event listener
        window.addEventListener('scroll', this.handleScroll.bind(this));

        // Add filter form event listener
        if (this.filterForm) {
            this.filterForm.addEventListener('submit', this.handleFilterSubmit.bind(this));

            // Listen for input changes
            this.filterForm.querySelectorAll('input, select').forEach(element => {
                element.addEventListener('change', this.debounce(this.handleFilterChange.bind(this), 300));
            });
        }

        // Initial load
        this.loadItems();
    }

    /**
     * Handle scroll event
     */
    handleScroll() {
        if (this.loading || this.allItemsLoaded) return;

        const scrollPosition = window.innerHeight + window.scrollY;
        const contentHeight = document.body.offsetHeight;

        // Check if we're near the bottom
        if (contentHeight - scrollPosition < this.options.threshold) {
            this.loadItems();
        }
    }

    /**
     * Handle filter form submission
     * @param {Event} event - Form submission event
     */
    handleFilterSubmit(event) {
        event.preventDefault();
        this.resetAndLoad();
    }

    /**
     * Handle filter input change
     */
    handleFilterChange() {
        this.resetAndLoad();
    }

    /**
     * Reset state and load first page
     */
    resetAndLoad() {
        // Reset state
        this.page = 1;
        this.totalItems = 0;
        this.totalPages = 0;
        this.allItemsLoaded = false;
        this.items = [];
        this.visibleItems = [];

        // Clear container
        this.container.innerHTML = '';

        // Load first page
        this.loadItems();
    }

    /**
     * Load more items via AJAX
     */
    loadItems() {
        if (this.loading || this.allItemsLoaded) return;

        this.loading = true;
        this.showLoadingIndicator();

        // Prepare URL parameters
        const params = new URLSearchParams();
        params.append('action', 'list');
        params.append('page', this.page);
        params.append('limit', this.options.itemsPerPage);
        params.append('csrfToken', this.csrfToken);

        // Add filter parameters if form exists
        if (this.filterForm) {
            const formData = new FormData(this.filterForm);
            for (const [key, value] of formData.entries()) {
                params.append(key, value);
            }
        }

        // Make AJAX request
        fetch(`api/facilities.php?${params.toString()}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                // Simulate loading delay for demonstration
                setTimeout(() => {
                    if (data.success) {
                        this.handleLoadSuccess(data);
                    } else {
                        this.showError(data.message || 'Failed to load items');
                    }

                    this.loading = false;
                    this.hideLoadingIndicator();
                }, this.options.loadingDelay);
            })
            .catch(error => {
                console.error('Error loading items:', error);
                this.showError('An error occurred while loading items');
                this.loading = false;
                this.hideLoadingIndicator();
            });
    }

    /**
     * Handle successful item loading
     * @param {Object} data - Response data from API
     */
    handleLoadSuccess(data) {
        const { facilities, totalResults, currentPage, totalPages } = data;

        // Update state
        this.page = currentPage + 1; // Next page to load
        this.totalItems = totalResults;
        this.totalPages = totalPages;

        // Check if we've loaded all items
        if (facilities.length === 0 || currentPage >= totalPages) {
            this.allItemsLoaded = true;
            this.showEndMessage();
            return;
        }

        // Add items to tracking array
        this.items = [...this.items, ...facilities];

        // Add items to DOM
        facilities.forEach(facility => {
            const itemElement = this.createFacilityCard(facility);
            this.container.appendChild(itemElement);
            this.visibleItems.push({
                id: facility.id,
                element: itemElement
            });
        });

        // Update map if available
        if (this.mapManager) {
            facilities.forEach(facility => {
                this.mapManager.addMarker(facility);
            });
        }

        // Manage memory - implement sliding window if needed
        this.manageDOMMemory();
    }

    /**
     * Manage DOM memory by removing old items when threshold is exceeded
     */
    manageDOMMemory() {
        if (this.visibleItems.length <= this.options.maxItemsToKeep) {
            return; // No need to remove items yet
        }

        const itemsToRemove = this.visibleItems.length - this.options.maxItemsToKeep;

        // Remove oldest items from DOM
        for (let i = 0; i < itemsToRemove; i++) {
            const item = this.visibleItems.shift(); // Get oldest item
            item.element.remove(); // Remove from DOM
        }
    }

    /**
     * Create a facility card element
     * @param {Object} facility - Facility data object
     * @returns {HTMLElement} Card element
     */
    createFacilityCard(facility) {
        const colDiv = document.createElement('div');
        colDiv.className = 'col-12 col-md-6 col-lg-4 mb-4';

        const card = document.createElement('div');
        card.className = 'card h-100 facility-item';
        card.setAttribute('data-facility-id', facility.id);

        // Add click event to center map on this facility
        if (this.mapManager) {
            card.addEventListener('click', () => {
                this.mapManager.centerMapOnFacility(facility.id);
            });
        }

        const cardBody = document.createElement('div');
        cardBody.className = 'card-body';

        // Card title
        const title = document.createElement('h5');
        title.className = 'card-title text-red';
        title.textContent = facility.title;

        // Category badge
        const category = document.createElement('span');
        category.className = 'badge bg-secondary mb-2';
        category.textContent = facility.categoryName || `Category ${facility.category}`;

        // Description
        const description = document.createElement('p');
        description.className = 'card-text';
        description.textContent = facility.description;

        // Address
        const address = document.createElement('p');
        address.className = 'card-text small';
        address.innerHTML = `<strong>Address:</strong> ${facility.fullAddress || this.formatAddress(facility)}`;

        // Status if available
        let status = null;
        if (facility.statusComment) {
            status = document.createElement('p');
            status.className = 'card-text small text-muted';
            status.innerHTML = `<strong>Status:</strong> ${facility.statusComment}`;
        }

        // View on map button
        const viewBtn = document.createElement('button');
        viewBtn.className = 'btn btn-sm btn-outline-primary mt-2';
        viewBtn.textContent = 'View on Map';

        if (this.mapManager) {
            viewBtn.addEventListener('click', (e) => {
                e.stopPropagation(); // Prevent card click event
                this.mapManager.centerMapOnFacility(facility.id);
            });
        }

        // Assemble card
        cardBody.appendChild(title);
        cardBody.appendChild(category);
        cardBody.appendChild(description);
        cardBody.appendChild(address);
        if (status) cardBody.appendChild(status);
        cardBody.appendChild(viewBtn);

        card.appendChild(cardBody);
        colDiv.appendChild(card);

        return colDiv;
    }

    /**
     * Format address from facility components
     * @param {Object} facility - Facility data object
     * @returns {string} Formatted address
     */
    formatAddress(facility) {
        const parts = [
            facility.houseNumber,
            facility.streetName,
            facility.town,
            facility.county,
            facility.postcode
        ].filter(Boolean); // Remove any null or undefined values

        return parts.join(', ');
    }

    /**
     * Show loading indicator
     */
    showLoadingIndicator() {
        this.loadingIndicator.classList.remove('d-none');
    }

    /**
     * Hide loading indicator
     */
    hideLoadingIndicator() {
        this.loadingIndicator.classList.add('d-none');
    }

    /**
     * Show end of results message
     */
    showEndMessage() {
        const endMessage = document.createElement('div');
        endMessage.className = 'text-center py-3 text-muted end-message';

        if (this.totalItems > 0) {
            endMessage.textContent = `End of results. Showing ${this.items.length} of ${this.totalItems} facilities.`;
        } else {
            endMessage.textContent = 'No facilities found matching your criteria.';
        }

        // Remove any existing end message
        const existingMessage = document.querySelector('.end-message');
        if (existingMessage) {
            existingMessage.remove();
        }

        this.container.after(endMessage);
    }

    /**
     * Show error message
     * @param {string} message - Error message to display
     */
    showError(message) {
        const errorDiv = document.createElement('div');
        errorDiv.className = 'alert alert-danger my-3';
        errorDiv.textContent = message;

        // Remove any existing error
        const existingError = document.querySelector('.alert-danger');
        if (existingError) {
            existingError.remove();
        }

        this.container.before(errorDiv);

        // Hide error after 5 seconds
        setTimeout(() => {
            errorDiv.remove();
        }, 5000);
    }

    /**
     * Create a debounce function for event handlers
     * @param {Function} func - Function to debounce
     * @param {number} wait - Milliseconds to wait
     * @returns {Function} Debounced function
     */
    debounce(func, wait) {
        let timeout;
        return function(...args) {
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(this, args), wait);
        };
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

        // Fallback to a method to get from cookie
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