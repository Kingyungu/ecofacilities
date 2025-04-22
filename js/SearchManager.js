/**
 * SearchManager Class
 *
 * Manages the live search functionality for ecoFacilities in the ecoBuddy system.
 * Implements AJAX-powered search with multiple filters and debouncing for performance.
 *
 * Features:
 * - Real-time search as user types
 * - Multiple filter options
 * - Results pagination
 * - Map integration
 * - Debounced requests for performance
 */
class SearchManager {
    /**
     * Constructor for the SearchManager class
     * @param {string} searchFormId - ID of the search form element
     * @param {string} resultsContainerId - ID of the element to display results in
     * @param {Object} mapManager - Optional reference to MapManager instance
     */
    constructor(searchFormId, resultsContainerId, mapManager = null) {
        this.searchForm = document.getElementById(searchFormId);
        this.resultsContainer = document.getElementById(resultsContainerId);
        this.mapManager = mapManager;
        this.debounceTimeout = null;
        this.debounceDelay = 300; // ms delay for debouncing
        this.csrfToken = this.getCSRFToken();
        this.lastSearchParams = null;

        // Initialize if elements exist
        if (this.searchForm && this.resultsContainer) {
            this.initialize();
        }
    }

    /**
     * Initialize the search manager
     */
    initialize() {
        // Set up event listeners
        this.setupEventListeners();
    }

    /**
     * Set up event listeners for search form
     */
    setupEventListeners() {
        // Listen for input changes in search fields
        this.searchForm.querySelectorAll('input, select').forEach(element => {
            element.addEventListener('input', () => {
                this.debounceSearch();
            });
        });

        // Listen for form submission
        this.searchForm.addEventListener('submit', (event) => {
            event.preventDefault();
            this.performSearch();
        });
    }

    /**
     * Debounce search to limit API calls
     */
    debounceSearch() {
        // Clear the existing timeout
        if (this.debounceTimeout) {
            clearTimeout(this.debounceTimeout);
        }

        // Set a new timeout
        this.debounceTimeout = setTimeout(() => {
            this.performSearch();
        }, this.debounceDelay);
    }

    /**
     * Perform the search with current form values
     */
    performSearch() {
        const formData = new FormData(this.searchForm);
        const searchParams = new URLSearchParams();

        // Add CSRF token
        searchParams.append('csrfToken', this.csrfToken);

        // Add all form fields to the params
        for (const [key, value] of formData.entries()) {
            searchParams.append(key, value);
        }

        // Add action parameter
        searchParams.append('action', 'search');

        // Check if this is the same as the last search
        const searchParamsString = searchParams.toString();
        if (this.lastSearchParams === searchParamsString) {
            return; // Skip duplicate searches
        }

        this.lastSearchParams = searchParamsString;

        // Show loading state
        this.showLoading();

        // Send AJAX request
        fetch(`api/facilities.php?${searchParams.toString()}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    this.displayResults(data.facilities, data.totalResults, data.currentPage, data.totalPages);

                    // Update map if available
                    if (this.mapManager) {
                        this.mapManager.refreshMarkers(data.facilities);
                    }

                    // Update URL to make results bookmarkable
                    this.updateURL(searchParams);
                } else {
                    this.showError(data.message || 'Search failed');
                }
            })
            .catch(error => {
                console.error('Error performing search:', error);
                this.showError('An error occurred while searching');
            })
            .finally(() => {
                this.hideLoading();
            });
    }

    /**
     * Display search results
     * @param {Array} facilities - Array of facility objects
     * @param {number} totalResults - Total number of results
     * @param {number} currentPage - Current page number
     * @param {number} totalPages - Total number of pages
     */
    displayResults(facilities, totalResults, currentPage, totalPages) {
        // Clear existing results
        this.resultsContainer.innerHTML = '';

        // Display result count
        const resultCount = document.createElement('div');
        resultCount.className = 'result-count mb-3';
        resultCount.textContent = `Showing ${facilities.length} of ${totalResults} results`;
        this.resultsContainer.appendChild(resultCount);

        // If no results
        if (facilities.length === 0) {
            const noResults = document.createElement('div');
            noResults.className = 'alert alert-info';
            noResults.textContent = 'No facilities found matching your criteria';
            this.resultsContainer.appendChild(noResults);
            return;
        }

        // Create result cards
        const resultsGrid = document.createElement('div');
        resultsGrid.className = 'row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4';

        facilities.forEach(facility => {
            const card = this.createFacilityCard(facility);
            resultsGrid.appendChild(card);
        });

        this.resultsContainer.appendChild(resultsGrid);

        // Add pagination if needed
        if (totalPages > 1) {
            const pagination = this.createPagination(currentPage, totalPages);
            this.resultsContainer.appendChild(pagination);
        }
    }

    /**
     * Create a facility card element
     * @param {Object} facility - Facility data object
     * @returns {HTMLElement} Card element
     */
    createFacilityCard(facility) {
        const colDiv = document.createElement('div');
        colDiv.className = 'col';

        const card = document.createElement('div');
        card.className = 'card h-100 facility-item';
        card.setAttribute('data-facility-id', facility.id);

        // Add click event to center map on this facility
        card.addEventListener('click', () => {
            if (this.mapManager) {
                this.mapManager.centerMapOnFacility(facility.id);
            }
        });

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
        viewBtn.addEventListener('click', (e) => {
            e.stopPropagation(); // Prevent card click event
            if (this.mapManager) {
                this.mapManager.centerMapOnFacility(facility.id);
            }
        });

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
     * Create pagination controls
     * @param {number} currentPage - Current page number
     * @param {number} totalPages - Total number of pages
     * @returns {HTMLElement} Pagination element
     */
    createPagination(currentPage, totalPages) {
        const nav = document.createElement('nav');
        nav.setAttribute('aria-label', 'Search results pagination');

        const ul = document.createElement('ul');
        ul.className = 'pagination justify-content-center mt-4';

        // Previous button
        const prevLi = document.createElement('li');
        prevLi.className = `page-item ${currentPage <= 1 ? 'disabled' : ''}`;

        const prevLink = document.createElement('a');
        prevLink.className = 'page-link';
        prevLink.href = '#';
        prevLink.setAttribute('aria-label', 'Previous');
        prevLink.innerHTML = '<span aria-hidden="true">&laquo;</span>';

        if (currentPage > 1) {
            prevLink.addEventListener('click', (e) => {
                e.preventDefault();
                this.changePage(currentPage - 1);
            });
        }

        prevLi.appendChild(prevLink);
        ul.appendChild(prevLi);

        // Page numbers
        const startPage = Math.max(1, currentPage - 2);
        const endPage = Math.min(totalPages, startPage + 4);

        for (let i = startPage; i <= endPage; i++) {
            const pageLi = document.createElement('li');
            pageLi.className = `page-item ${i === currentPage ? 'active' : ''}`;

            const pageLink = document.createElement('a');
            pageLink.className = 'page-link';
            pageLink.href = '#';
            pageLink.textContent = i;

            if (i !== currentPage) {
                pageLink.addEventListener('click', (e) => {
                    e.preventDefault();
                    this.changePage(i);
                });
            }

            pageLi.appendChild(pageLink);
            ul.appendChild(pageLi);
        }

        // Next button
        const nextLi = document.createElement('li');
        nextLi.className = `page-item ${currentPage >= totalPages ? 'disabled' : ''}`;

        const nextLink = document.createElement('a');
        nextLink.className = 'page-link';
        nextLink.href = '#';
        nextLink.setAttribute('aria-label', 'Next');
        nextLink.innerHTML = '<span aria-hidden="true">&raquo;</span>';

        if (currentPage < totalPages) {
            nextLink.addEventListener('click', (e) => {
                e.preventDefault();
                this.changePage(currentPage + 1);
            });
        }

        nextLi.appendChild(nextLink);
        ul.appendChild(nextLi);

        nav.appendChild(ul);
        return nav;
    }

    /**
     * Change to a different page of results
     * @param {number} page - Page number to navigate to
     */
    changePage(page) {
        // Update page input in form
        const pageInput = this.searchForm.querySelector('input[name="page"]');
        if (pageInput) {
            pageInput.value = page;
        } else {
            // Create page input if it doesn't exist
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'page';
            input.value = page;
            this.searchForm.appendChild(input);
        }

        // Perform search with updated page
        this.performSearch();

        // Scroll to top of results
        this.resultsContainer.scrollIntoView({ behavior: 'smooth' });
    }

    /**
     * Update browser URL with search parameters
     * @param {URLSearchParams} searchParams - Search parameters
     */
    updateURL(searchParams) {
        // Clone the params to avoid modifying the original
        const params = new URLSearchParams(searchParams);

        // Remove CSRF token from URL
        params.delete('csrfToken');

        // Update URL without reloading the page
        const newUrl = `${window.location.pathname}?${params.toString()}`;
        window.history.pushState({ path: newUrl }, '', newUrl);
    }

    /**
     * Show loading indicator
     */
    showLoading() {
        // Remove any existing loading indicator
        this.hideLoading();

        // Create loading indicator
        const loading = document.createElement('div');
        loading.className = 'text-center my-4 loading-indicator';
        loading.innerHTML = `
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2">Searching for facilities...</p>
        `;

        this.resultsContainer.appendChild(loading);
    }

    /**
     * Hide loading indicator
     */
    hideLoading() {
        const loadingIndicator = this.resultsContainer.querySelector('.loading-indicator');
        if (loadingIndicator) {
            loadingIndicator.remove();
        }
    }

    /**
     * Show error message
     * @param {string} message - Error message to display
     */
    showError(message) {
        // Clear existing content
        this.resultsContainer.innerHTML = '';

        // Create error message
        const error = document.createElement('div');
        error.className = 'alert alert-danger';
        error.textContent = message;

        this.resultsContainer.appendChild(error);
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