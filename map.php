<?php
/**
 * Map Controller
 *
 * Handles the mapping page for ecological facilities in the ecoBuddy system.
 * Provides facilities data for the map, filter options, and search functionality.
 *
 * @author Your Name
 * @version 1.0
 */

// Start session for authentication
session_start();

// Include required model classes
require_once('Models/User.php');
require_once('Models/EcoFacilitySet.php');
require_once('Models/CategorySet.php');
require_once('Models/FacilityStatusSet.php');

// Initialize view object for template data
$view = new stdClass();
$view->pageTitle = 'Facility Map';

// Initialize user authentication
$user = new User();

// Generate CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/**
 * Data Access Layer Initialization
 */
// Create instances of data access objects
$facilitySet = new EcoFacilitySet();
$categorySet = new CategorySet();
$statusSet = new FacilityStatusSet();

/**
 * Pagination Configuration
 */
// Get and validate current page number
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 10; // Number of items per page

/**
 * Search and Filter Parameters
 */
// Sanitize search input
$view->searchTerm = isset($_GET['searchTerm'])
    ? filter_var($_GET['searchTerm'], FILTER_SANITIZE_FULL_SPECIAL_CHARS)
    : '';

// Sanitize category filter
$view->selectedCategory = isset($_GET['category'])
    ? filter_var($_GET['category'], FILTER_SANITIZE_NUMBER_INT)
    : null;

// Sanitize location filters
$view->selectedTown = isset($_GET['town'])
    ? filter_var($_GET['town'], FILTER_SANITIZE_FULL_SPECIAL_CHARS)
    : '';

$view->selectedCounty = isset($_GET['county'])
    ? filter_var($_GET['county'], FILTER_SANITIZE_FULL_SPECIAL_CHARS)
    : '';

/**
 * Facility Data Retrieval
 */
// Get facilities based on search criteria
$additionalFilters = [
    'town' => $view->selectedTown,
    'county' => $view->selectedCounty
];

$view->facilities = $facilitySet->searchFacilities(
    $view->searchTerm,
    $view->selectedCategory,
    $page,
    $limit,
    $additionalFilters
);

// Get total count for pagination
$view->totalResults = $facilitySet->getTotalCount(
    $view->searchTerm,
    $view->selectedCategory,
    $additionalFilters
);

/**
 * Pagination and Results Setup
 */
$view->currentPage = $page;
$view->totalPages = ceil($view->totalResults / $limit);

/**
 * Status Data Retrieval
 */
// Get status information for each facility
$view->statuses = [];
foreach ($view->facilities as $facility) {
    $status = $statusSet->getStatusForFacility($facility->getId());
    if ($status) {
        $view->statuses[$facility->getId()] = $status;
    }
}

/**
 * Filter Options Data
 */
// Get categories for filter dropdown
$view->categories = $categorySet->fetchAllCategories();

// Get towns and counties for filter dropdowns
$view->towns = $facilitySet->getUniqueTowns();
$view->counties = $facilitySet->getUniqueCounties();

// Load view template
require_once('Views/map.phtml');