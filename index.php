<?php
/**
 * Index Controller (Main Page Controller)
 *
 * Primary controller for the ecoBuddy system handling:
 * - Facility listing and search
 * - Manager operations (CRUD)
 * - Pagination
 * - Category filtering
 *
 * Security measures:
 * - Input sanitization
 * - Role-based access control
 * - POST action validation
 *
 * @author Grace Kinyungu
 * @version 1.0
 */

// Initialize session for user authentication
session_start();

// Include required model classes
require_once('Models/User.php');
require_once('Models/EcoFacilitySet.php');
require_once('Models/CategorySet.php');

/**
 * View and Authentication Setup
 */
$view = new stdClass();
$view->pageTitle = 'ecoBuddy ⚡';
$user = new User();

/**
 * Initialize Data Access Objects
 */
$facilitySet = new EcoFacilitySet();
$categorySet = new CategorySet();

/**
 * Pagination Configuration
 */
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 10;

/**
 * Manager Operations Handler
 */
if ($user->isLoggedIn() && $user->isManager()) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'delete':
                    // Handle facility deletion
                    if (isset($_POST['id'])) {
                        $facilitySet->deleteFacility(
                            filter_var($_POST['id'], FILTER_SANITIZE_NUMBER_INT)
                        );
                    }
                    break;

                case 'save':
                    // Prepare and sanitize facility data
                    $data = [
                        'title' => filter_var($_POST['title'],
                            FILTER_SANITIZE_FULL_SPECIAL_CHARS),
                        'category' => filter_var($_POST['category'],
                            FILTER_SANITIZE_NUMBER_INT),
                        'description' => filter_var($_POST['description'],
                            FILTER_SANITIZE_FULL_SPECIAL_CHARS),
                        'houseNumber' => filter_var($_POST['houseNumber'],
                            FILTER_SANITIZE_FULL_SPECIAL_CHARS),
                        'streetName' => filter_var($_POST['streetName'],
                            FILTER_SANITIZE_FULL_SPECIAL_CHARS),
                        'county' => filter_var($_POST['county'],
                            FILTER_SANITIZE_FULL_SPECIAL_CHARS),
                        'town' => filter_var($_POST['town'],
                            FILTER_SANITIZE_FULL_SPECIAL_CHARS),
                        'postcode' => filter_var($_POST['postcode'],
                            FILTER_SANITIZE_FULL_SPECIAL_CHARS),
                        'lng' => filter_var($_POST['lng'],
                            FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION),
                        'lat' => filter_var($_POST['lat'],
                            FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION),
                        'contributor' => $user->getId()
                    ];

                    // Determine whether to update or add new facility
                    if (isset($_POST['id'])) {
                        $facilitySet->updateFacility(
                            filter_var($_POST['id'], FILTER_SANITIZE_NUMBER_INT),
                            $data
                        );
                    } else {
                        $facilitySet->addFacility($data);
                    }
                    break;
            }
        }
    }

    // Load facility for editing if requested
    if (isset($_GET['edit'])) {
        $view->editFacility = $facilitySet->getFacilityById(
            filter_var($_GET['edit'], FILTER_SANITIZE_NUMBER_INT)
        );
    }
}

/**
 * Search Parameters Processing
 */
$view->searchTerm = isset($_GET['search'])
    ? filter_var($_GET['search'], FILTER_SANITIZE_STRING)
    : '';

$view->selectedCategory = isset($_GET['category'])
    ? filter_var($_GET['category'], FILTER_SANITIZE_NUMBER_INT)
    : null;

/**
 * Facility Data Retrieval
 */
// Get facilities based on search criteria
$view->facilities = $facilitySet->searchFacilities(
    $view->searchTerm,
    $view->selectedCategory,
    $page,
    $perPage
);

// Get total count for pagination
$totalItems = $facilitySet->getTotalCount(
    $view->searchTerm,
    $view->selectedCategory
);

/**
 * Pagination and Results Setup
 */
$view->currentPage = $page;
$view->totalPages = ceil($totalItems / $perPage);

// Format result message
$view->resultMessage = $totalItems > 0
    ? "Showing " . count($view->facilities) . " of " . $totalItems . " results"
    : "No facilities found";

/**
 * Additional View Data
 */
// Get categories for filter dropdown
$view->categories = $categorySet->fetchAllCategories();
$view->resultCount = count($view->facilities);

// Load view template
require_once('Views/index.phtml');
?>