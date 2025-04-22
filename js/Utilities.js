/**
 * Utilities Class
 *
 * Provides common utility functions for the ecoBuddy system.
 * Contains helper methods for CSRF protection, form handling, and DOM manipulation.
 */
class Utilities {
    /**
     * Generate a CSRF token and store it in a cookie
     * @returns {string} The generated CSRF token
     */
    static generateCSRFToken() {
        // Generate a random string for CSRF token
        const token = Math.random().toString(36).substring(2, 15) +
            Math.random().toString(36).substring(2, 15);

        // Store in cookie
        document.cookie = `csrf_token=${token}; path=/; SameSite=Strict`;

        return token;
    }

    /**
     * Get CSRF token from meta tag or cookie
     * @returns {string} CSRF token value
     */
    static getCSRFToken() {
        // Try to get token from meta tag
        const metaTag = document.querySelector('meta[name="csrf-token"]');
        if (metaTag) {
            return metaTag.getAttribute('content');
        }

        // Try to get token from cookie
        return Utilities.getCookie('csrf_token');
    }

    /**
     * Get a cookie value by name
     * @param {string} name - Name of the cookie to retrieve
     * @returns {string} Cookie value or empty string if not found
     */
    static getCookie(name) {
        const cookies = document.cookie.split(';');
        for (let i = 0; i < cookies.length; i++) {
            const cookie = cookies[i].trim();
            if (cookie.startsWith(name + '=')) {
                return cookie.substring(name.length + 1);
            }
        }
        return '';
    }

    /**
     * Sanitize HTML content to prevent XSS
     * @param {string} html - String that might contain HTML
     * @returns {string} Sanitized string
     */
    static sanitizeHTML(html) {
        const div = document.createElement('div');
        div.textContent = html;
        return div.innerHTML;
    }

    /**
     * Create a debounce function for event handlers
     * @param {Function} func - Function to debounce
     * @param {number} wait - Milliseconds to wait
     * @returns {Function} Debounced function
     */
    static debounce(func, wait = 300) {
        let timeout;
        return function(...args) {
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(this, args), wait);
        };
    }

    /**
     * Format an address from facility components
     * @param {Object} facility - Facility data object
     * @returns {string} Formatted address
     */
    static formatAddress(facility) {
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
     * Format a date string into a human-readable format
     * @param {string} dateString - ISO date string
     * @returns {string} Formatted date
     */
    static formatDate(dateString) {
        const date = new Date(dateString);
        const options = {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        };

        return date.toLocaleDateString('en-GB', options);
    }

    /**
     * Calculate time elapsed since a given date
     * @param {string} dateString - ISO date string
     * @returns {string} Human-readable time difference (e.g., "2 hours ago")
     */
    static timeElapsed(dateString) {
        const date = new Date(dateString);
        const now = new Date();
        const diff = Math.floor((now - date) / 1000); // Difference in seconds

        if (diff < 60) {
            return "just now";
        } else if (diff < 3600) {
            const minutes = Math.floor(diff / 60);
            return `${minutes} minute${minutes > 1 ? 's' : ''} ago`;
        } else if (diff < 86400) {
            const hours = Math.floor(diff / 3600);
            return `${hours} hour${hours > 1 ? 's' : ''} ago`;
        } else if (diff < 2592000) {
            const days = Math.floor(diff / 86400);
            return `${days} day${days > 1 ? 's' : ''} ago`;
        } else {
            return Utilities.formatDate(dateString);
        }
    }

    /**
     * Serialize form data to URL-encoded string
     * @param {HTMLFormElement} form - Form element to serialize
     * @returns {string} URL-encoded form data
     */
    static serializeForm(form) {
        const formData = new FormData(form);
        const urlParams = new URLSearchParams();

        for (const [key, value] of formData.entries()) {
            urlParams.append(key, value);
        }

        return urlParams.toString();
    }

    /**
     * Add CSRF token to a form
     * @param {HTMLFormElement} form - Form element to add token to
     */
    static addCSRFTokenToForm(form) {
        // Check if token input already exists
        let tokenInput = form.querySelector('input[name="csrfToken"]');

        if (!tokenInput) {
            // Create new input if it doesn't exist
            tokenInput = document.createElement('input');
            tokenInput.type = 'hidden';
            tokenInput.name = 'csrfToken';
            form.appendChild(tokenInput);
        }

        // Set token value
        tokenInput.value = Utilities.getCSRFToken();
    }

    /**
     * Show a temporary notification message
     * @param {string} message - Message to display
     * @param {string} type - Message type (success, error, warning, info)
     * @param {number} duration - Time in milliseconds to show message
     */
    static showNotification(message, type = 'info', duration = 3000) {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.textContent = message;

        // Apply styles
        notification.style.position = 'fixed';
        notification.style.top = '20px';
        notification.style.right = '20px';
        notification.style.zIndex = '1000';
        notification.style.padding = '15px 20px';
        notification.style.borderRadius = '4px';
        notification.style.maxWidth = '300px';
        notification.style.boxShadow = '0 4px 8px rgba(0,0,0,0.1)';
        notification.style.opacity = '0';
        notification.style.transition = 'opacity 0.3s ease';

        // Set background color based on type
        switch (type) {
            case 'success':
                notification.style.backgroundColor = '#d4edda';
                notification.style.color = '#155724';
                notification.style.borderLeft = '4px solid #155724';
                break;
            case 'error':
                notification.style.backgroundColor = '#f8d7da';
                notification.style.color = '#721c24';
                notification.style.borderLeft = '4px solid #721c24';
                break;
            case 'warning':
                notification.style.backgroundColor = '#fff3cd';
                notification.style.color = '#856404';
                notification.style.borderLeft = '4px solid #856404';
                break;
            default: // info
                notification.style.backgroundColor = '#d1ecf1';
                notification.style.color = '#0c5460';
                notification.style.borderLeft = '4px solid #0c5460';
        }

        // Add to document
        document.body.appendChild(notification);

        // Animate in
        setTimeout(() => {
            notification.style.opacity = '1';
        }, 10);

        // Remove after duration
        setTimeout(() => {
            notification.style.opacity = '0';
            setTimeout(() => {
                document.body.removeChild(notification);
            }, 300);
        }, duration);
    }
}