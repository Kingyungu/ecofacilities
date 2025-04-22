<?php
/**
 * Manage Facilities Controller
 *
 * Handles administrative operations for ecological facilities including:
 * - Create, Read, Update, Delete (CRUD) operations
 * - Manager authentication
 * - Form processing
 * - Data validation
 *
 * Security features:
 * - Role-based access control
 * - Input sanitization
 * - Authentication verification
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

/**
 * View and Authentication Setup
 */
$view = new stdClass();
$view->pageTitle = 'Manage Facilities';
$user = new User();

// Verify manager authentication
if (!$user->isLoggedIn() || !$user->isManager()) {
    header('Location: login.php');
    exit();
}

/**
 * Initialize Data Access Objects
 */
$facilitySet = new EcoFacilitySet();
$categorySet = new CategorySet();
$view->categories = $categorySet->fetchAllCategories();
$view->message = '';

/**
 * Form Submission Handler
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'delete':
                // Handle facility deletion
                if (isset($_POST['id'])) {
                    $id = filter_var($_POST['id'], FILTER_SANITIZE_NUMBER_INT);
                    $facilitySet->deleteFacility($id);
                    $view->message = "Facility deleted successfully";
                }
                break;

            case 'save':
                // Prepare and sanitize facility data
                $data = [
                    'title' => filter_var(
                        $_POST['title'],
                        FILTER_SANITIZE_STRING
                    ),
                    'category' => filter_var(
                        $_POST['category'],
                        FILTER_SANITIZE_NUMBER_INT
                    ),
                    'description' => filter_var(
                        $_POST['description'],
                        FILTER_SANITIZE_STRING
                    ),
                    'houseNumber' => filter_var(
                        $_POST['houseNumber'],
                        FILTER_SANITIZE_STRING
                    ),
                    'streetName' => filter_var(
                        $_POST['streetName'],
                        FILTER_SANITIZE_STRING
                    ),
                    'county' => filter_var(
                        $_POST['county'],
                        FILTER_SANITIZE_STRING
                    ),
                    'town' => filter_var(
                        $_POST['town'],
                        FILTER_SANITIZE_STRING
                    ),
                    'postcode' => filter_var(
                        $_POST['postcode'],
                        FILTER_SANITIZE_STRING
                    ),
                    'lng' => filter_var(
                        $_POST['lng'],
                        FILTER_SANITIZE_NUMBER_FLOAT,
                        FILTER_FLAG_ALLOW_FRACTION
                    ),
                    'lat' => filter_var(
                        $_POST['lat'],
                        FILTER_SANITIZE_NUMBER_FLOAT,
                        FILTER_FLAG_ALLOW_FRACTION
                    ),
                    'contributor' => $user->getId()
                ];

                // Determine whether to update or add
                if (isset($_POST['id'])) {
                    $id = filter_var($_POST['id'], FILTER_SANITIZE_NUMBER_INT);
                    $facilitySet->updateFacility($id, $data);
                    $view->message = "Facility updated successfully";
                } else {
                    $facilitySet->addFacility($data);
                    $view->message = "Facility added successfully";
                }
                break;
        }
    }
}

/**
 * Load Edit Data
 */
$view->editFacility = null;
if (isset($_GET['edit'])) {
    $id = filter_var($_GET['edit'], FILTER_SANITIZE_NUMBER_INT);
    $view->editFacility = $facilitySet->getFacilityById($id);
}

/**
 * Load Facility List
 */
$view->facilities = $facilitySet->fetchAllFacilities();

// Load view template
require_once('Views/manage.phtml');