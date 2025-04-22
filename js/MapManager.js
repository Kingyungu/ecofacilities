/**
 * MapManager Class
 *
 * Manages the interactive mapping functionality for the ecoBuddy system.
 * Handles map initialization, markers, geolocation, and interactions with the facility list.
 *
 * Features:
 * - Map initialization with Leaflet.js
 * - User geolocation
 * - Facility markers with popup info
 * - Marker clustering for performance
 * - Interaction with facility list (click to center map)
 */
class MapManager {
    /**
     * Constructor for the MapManager class
     * @param {string} mapContainerId - The ID of the HTML element to contain the map
     * @param {Array} facilities - Array of facility objects with location data
     * @param {boolean} isAuthenticated - Whether the current user is authenticated
     */
    constructor(mapContainerId, facilities = [], isAuthenticated = false) {
        this.mapContainerId = mapContainerId;
        this.facilities = facilities;
        this.isAuthenticated = isAuthenticated;
        this.map = null;
        this.userMarker = null;
        this.markers = [];
        this.markerClusterGroup = null;
        this.defaultLocation = [53.4808, -2.2426]; // Default to Manchester, UK
        this.defaultZoom = 13;
    }

    /**
     * Initialize the map
     * @returns {Promise} Resolves when map is initialized
     */
    async initialize() {
        // Create map instance
        this.map = L.map(this.mapContainerId).setView(this.defaultLocation, this.defaultZoom);

        // Add OpenStreetMap tile layer
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
            maxZoom: 19
        }).addTo(this.map);

        // Initialize marker cluster group for better performance
        this.markerClusterGroup = L.markerClusterGroup();
        this.map.addLayer(this.markerClusterGroup);

        // Try to get user's location
        try {
            const position = await this.getUserLocation();
            const userLocation = [position.coords.latitude, position.coords.longitude];

            // Center map on user location
            this.map.setView(userLocation, this.defaultZoom);

            // Add user marker
            this.userMarker = L.marker(userLocation, {
                icon: L.divIcon({
                    html: '<div class="user-marker"></div>',
                    className: 'user-marker-container',
                    iconSize: [20, 20]
                })
            }).addTo(this.map);

            this.userMarker.bindPopup('<strong>Your Location</strong>').openPopup();
        } catch (error) {
            console.error('Error getting user location:', error);
            // Use default location if geolocation fails
            this.map.setView(this.defaultLocation, this.defaultZoom);
        }

        // Add facility markers
        this.addFacilityMarkers();

        // Set up event listeners for map interaction
        this.setupEventListeners();

        return this.map;
    }

    /**
     * Get user's current location
     * @returns {Promise} Resolves with Position object
     */
    getUserLocation() {
        return new Promise((resolve, reject) => {
            if (!navigator.geolocation) {
                reject(new Error('Geolocation is not supported by your browser'));
                return;
            }

            navigator.geolocation.getCurrentPosition(resolve, reject, {
                enableHighAccuracy: true,
                timeout: 5000,
                maximumAge: 0
            });
        });
    }

    /**
     * Add markers for all facilities
     */
    addFacilityMarkers() {
        if (!this.facilities || !this.facilities.length) {
            return;
        }

        this.facilities.forEach(facility => {
            if (facility.lat && facility.lng) {
                const marker = L.marker([facility.lat, facility.lng]);
                const popupContent = this.createPopupContent(facility);

                marker.bindPopup(popupContent);
                this.markerClusterGroup.addLayer(marker);

                // Store reference to the marker for later use
                this.markers.push({
                    id: facility.id,
                    marker: marker
                });
            }
        });
    }

    /**
     * Create popup content for a facility marker
     * @param {Object} facility - Facility data object
     * @returns {string} HTML content for the popup
     */
    createPopupContent(facility) {
        let content = `
            <div class="marker-popup">
                <h4>${facility.title}</h4>
                <p><strong>Category:</strong> ${facility.categoryName || facility.category}</p>
                <p><strong>Address:</strong> ${facility.fullAddress || this.formatAddress(facility)}</p>
                <p>${facility.description}</p>
        `;

        // Add status information if available
        if (facility.statusComment) {
            content += `
                <div class="facility-status">
                    <p><strong>Current Status:</strong> ${facility.statusComment}</p>
                </div>
            `;
        }

        // Add status update form for authenticated users
        if (this.isAuthenticated) {
            content += `
                <div class="status-update-form">
                    <button 
                        class="btn btn-sm btn-primary update-status-btn" 
                        data-facility-id="${facility.id}"
                        onclick="statusManager.openStatusModal(${facility.id})">
                        Update Status
                    </button>
                </div>
            `;
        }

        content += `</div>`;
        return content;
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
     * Set up event listeners for map interactions
     */
    setupEventListeners() {
        // Listen for clicks on facility list items
        document.addEventListener('DOMContentLoaded', () => {
            const facilityItems = document.querySelectorAll('.facility-item');
            facilityItems.forEach(item => {
                item.addEventListener('click', (e) => {
                    const facilityId = parseInt(item.getAttribute('data-facility-id'));
                    this.centerMapOnFacility(facilityId);
                });
            });
        });
    }

    /**
     * Center the map on a specific facility and open its popup
     * @param {number} facilityId - The ID of the facility to center on
     */
    centerMapOnFacility(facilityId) {
        const markerObj = this.markers.find(m => m.id === facilityId);
        if (markerObj) {
            const marker = markerObj.marker;
            this.map.setView(marker.getLatLng(), this.defaultZoom);
            marker.openPopup();
        }
    }

    /**
     * Refresh markers when facilities data changes
     * @param {Array} newFacilities - Updated array of facility objects
     */
    refreshMarkers(newFacilities) {
        // Clear existing markers
        this.markerClusterGroup.clearLayers();
        this.markers = [];

        // Update facilities array
        this.facilities = newFacilities;

        // Add new markers
        this.addFacilityMarkers();
    }

    /**
     * Add a single new marker to the map
     * @param {Object} facility - New facility data
     */
    addMarker(facility) {
        if (facility.lat && facility.lng) {
            const marker = L.marker([facility.lat, facility.lng]);
            const popupContent = this.createPopupContent(facility);

            marker.bindPopup(popupContent);
            this.markerClusterGroup.addLayer(marker);

            this.markers.push({
                id: facility.id,
                marker: marker
            });
        }
    }

    /**
     * Remove a marker from the map
     * @param {number} facilityId - ID of facility whose marker should be removed
     */
    removeMarker(facilityId) {
        const markerIndex = this.markers.findIndex(m => m.id === facilityId);
        if (markerIndex !== -1) {
            const markerObj = this.markers[markerIndex];
            this.markerClusterGroup.removeLayer(markerObj.marker);
            this.markers.splice(markerIndex, 1);
        }
    }

    /**
     * Update marker popup content
     * @param {number} facilityId - ID of facility to update
     * @param {Object} facilityData - Updated facility data
     */
    updateMarkerPopup(facilityId, facilityData) {
        const markerObj = this.markers.find(m => m.id === facilityId);
        if (markerObj) {
            const popupContent = this.createPopupContent(facilityData);
            markerObj.marker.setPopupContent(popupContent);
        }
    }
}