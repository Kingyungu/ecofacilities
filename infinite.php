<?php
/**
 * Infinite Scroll Controller
 *
 * Handles the infinite scrolling page for displaying ecological facilities.
 * Provides initial page data and filter options for the client-side infinite scroll.
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
$view->pageTitle = 'Browse Facilities';

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

/**
 * Filter Options Data
 */
// Get categories for filter dropdown
$view->categories = $categorySet->fetchAllCategories();

// Get towns and counties for filter dropdowns
$view->towns = $facilitySet->getUniqueTowns();
$view->counties = $facilitySet->getUniqueCounties();

/**
 * Total Count for Initial Display
 */
$view->totalFacilities = $facilitySet->getTotalCount();

// Load view template
require_once('Views/infinite.phtml');