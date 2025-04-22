<?php
/**
 * Facilities Controller
 *
 * Handles the browsing and searching of ecological facilities in the ecoBuddy system.
 * This controller supports:
 * - Paginated facility listing
 * - Search functionality
 * - Category filtering
 * - Result counting
 *
 * @author [Your Name]
 * @version 1.0
 */

// Initialize session for user authentication
session_start();

// Include required model classes
require_once('Models/User.php');
require_once('Models/EcoFacilitySet.php');
require_once('Models/CategorySet.php');

// Initialize view object for template data
$view = new stdClass();
$view->pageTitle = 'Browse Facilities';

// Initialize user authentication
$user = new User();

/**
 * Data Access Layer Initialization
 */
// Create instances of data access objects
$facilitySet = new EcoFacilitySet();
$categorySet = new CategorySet();

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
$view->searchTerm = isset($_GET['search'])
    ? filter_var($_GET['search'], FILTER_SANITIZE_STRING)
    : '';

// Sanitize category filter
$view->selectedCategory = isset($_GET['category'])
    ? filter_var($_GET['category'], FILTER_SANITIZE_NUMBER_INT)
    : null;

/**
 * Facility Data Retrieval
 */
// Determine whether to show search results or all facilities
if (!empty($view->searchTerm) || $view->selectedCategory) {
    // Get facilities matching search criteria
    $view->facilities = $facilitySet->searchFacilities(
        $view->searchTerm,
        $view->selectedCategory,
        $page,
        $limit
    );
    // Get total count for pagination
    $view->resultCount = $facilitySet->getTotalCount(
        $view->searchTerm,
        $view->selectedCategory
    );
} else {
    // Get paginated list of all facilities
    $view->facilities = $facilitySet->fetchAllFacilities($page, $limit);
    // Get total count for pagination
    $view->resultCount = $facilitySet->getTotalCount();
}

/**
 * Additional View Data
 */
// Get categories for filter dropdown
$view->categories = $categorySet->fetchAllCategories();

// Calculate pagination values
$view->totalPages = ceil($view->resultCount / $limit);
$view->currentPage = $page;

// Load view template
require_once('Views/facilities.phtml');