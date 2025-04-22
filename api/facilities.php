<?php
/**
 * Facilities API
 *
 * Handles AJAX requests for facility operations including:
 * - Searching facilities with filters
 * - Listing facilities with pagination
 * - Getting facility details
 *
 * Supports both search and infinite scrolling implementations.
 *
 * Security features:
 * - CSRF protection
 * - Input validation and sanitization
 *
 * @version 1.0
 */

// Start session to access authentication data
session_start();

// Include required models
require_once('../Models/User.php');
require_once('../Models/Database.php');
require_once('../Models/EcoFacilitySet.php');
require_once('../Models/FacilityStatusSet.php');
require_once('../Models/CategorySet.php');

// Set content type to JSON
header('Content-Type: application/json');

// Verify CSRF token
$csrfToken = $_REQUEST['csrfToken'] ?? '';
if (!verifyCsrfToken($csrfToken)) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid security token'
    ]);
    exit;
}

// Initialize user
$user = new User();

// Initialize data access objects
$facilitySet = new EcoFacilitySet();
$statusSet = new FacilityStatusSet();
$categorySet = new CategorySet();

// Determine action to perform
$action = $_REQUEST['action'] ?? 'list';

// Process request based on action
switch ($action) {
    case 'search':
        handleSearch($facilitySet, $statusSet, $categorySet);
        break;

    case 'list':
        handleList($facilitySet, $statusSet, $categorySet);
        break;

    case 'get':
        handleGetFacility($facilitySet, $statusSet, $categorySet);
        break;

    default:
        echo json_encode([
            'success' => false,
            'message' => 'Invalid action specified'
        ]);
        break;
}

/**
 * Handle search request
 * @param EcoFacilitySet $facilitySet - Facility data access object
 * @param FacilityStatusSet $statusSet - Status data access object
 * @param CategorySet $categorySet - Category data access object
 */
function handleSearch($facilitySet, $statusSet, $categorySet) {
    // Get and validate parameters
    $searchTerm = filter_input(INPUT_GET, 'searchTerm', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? '';
    $category = filter_input(INPUT_GET, 'category', FILTER_VALIDATE_INT);
    $page = max(1, filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?? 1);
    $limit = min(50, max(10, filter_input(INPUT_GET, 'limit', FILTER_VALIDATE_INT) ?? 10));

    // Optional additional filters
    $town = filter_input(INPUT_GET, 'town', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? '';
    $county = filter_input(INPUT_GET, 'county', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? '';
    $postcode = filter_input(INPUT_GET, 'postcode', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? '';

    // Search facilities
    $facilities = $facilitySet->searchFacilities(
        $searchTerm,
        $category,
        $page,
        $limit,
        [
            'town' => $town,
            'county' => $county,
            'postcode' => $postcode
        ]
    );

    // Get total count for pagination
    $totalResults = $facilitySet->getTotalCount(
        $searchTerm,
        $category,
        [
            'town' => $town,
            'county' => $county,
            'postcode' => $postcode
        ]
    );

    // Calculate total pages
    $totalPages = ceil($totalResults / $limit);

    // Enhance facilities with additional data
    $enhancedFacilities = enhanceFacilities($facilities, $statusSet, $categorySet);

    // Return results
    echo json_encode([
        'success' => true,
        'facilities' => $enhancedFacilities,
        'totalResults' => $totalResults,
        'currentPage' => $page,
        'totalPages' => $totalPages,
        'limit' => $limit
    ]);
}

/**
 * Handle list request for infinite scrolling
 * @param EcoFacilitySet $facilitySet - Facility data access object
 * @param FacilityStatusSet $statusSet - Status data access object
 * @param CategorySet $categorySet - Category data access object
 */
function handleList($facilitySet, $statusSet, $categorySet) {
    // Get and validate parameters
    $page = max(1, filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?? 1);
    $limit = min(50, max(10, filter_input(INPUT_GET, 'limit', FILTER_VALIDATE_INT) ?? 20));

    // Optional filters
    $category = filter_input(INPUT_GET, 'category', FILTER_VALIDATE_INT);
    $town = filter_input(INPUT_GET, 'town', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? '';
    $county = filter_input(INPUT_GET, 'county', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? '';

    // Get facilities for page
    if ($category || !empty($town) || !empty($county)) {
        // If filters are provided, use search
        $facilities = $facilitySet->searchFacilities(
            '',  // No search term
            $category,
            $page,
            $limit,
            [
                'town' => $town,
                'county' => $county
            ]
        );

        // Get total count
        $totalResults = $facilitySet->getTotalCount(
            '',
            $category,
            [
                'town' => $town,
                'county' => $county
            ]
        );
    } else {
        // Otherwise fetch all paginated
        $facilities = $facilitySet->fetchAllFacilities($page, $limit);
        $totalResults = $facilitySet->getTotalCount();
    }

    // Calculate total pages
    $totalPages = ceil($totalResults / $limit);

    // Enhance facilities with additional data
    $enhancedFacilities = enhanceFacilities($facilities, $statusSet, $categorySet);

    // Return results
    echo json_encode([
        'success' => true,
        'facilities' => $enhancedFacilities,
        'totalResults' => $totalResults,
        'currentPage' => $page,
        'totalPages' => $totalPages,
        'limit' => $limit
    ]);
}

/**
 * Handle get facility request
 * @param EcoFacilitySet $facilitySet - Facility data access object
 * @param FacilityStatusSet $statusSet - Status data access object
 * @param CategorySet $categorySet - Category data access object
 */
function handleGetFacility($facilitySet, $statusSet, $categorySet) {
    // Validate facility ID
    $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

    if (!$id) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid facility ID'
        ]);
        return;
    }

    // Get facility
    $facility = $facilitySet->getFacilityById($id);

    if (!$facility) {
        echo json_encode([
            'success' => false,
            'message' => 'Facility not found'
        ]);
        return;
    }

    // Convert to array for JSON
    $facilityData = facilityToArray($facility);

    // Add status
    $status = $statusSet->getStatusForFacility($id);
    if ($status) {
        $facilityData['statusComment'] = $status->getComment();
        $facilityData['statusUserId'] = $status->getUserId();
        $facilityData['statusTimestamp'] = $status->getTimestamp();
    }

    // Add category name
    if ($facility->getCategory()) {
        $category = $categorySet->getCategoryById($facility->getCategory());
        if ($category) {
            $facilityData['categoryName'] = $category->getName();
        }
    }

    // Return facility
    echo json_encode([
        'success' => true,
        'facility' => $facilityData
    ]);
}

/**
 * Convert facility object to array for JSON encoding
 * @param EcoFacility $facility - Facility object
 * @return array Associative array representing facility
 */
function facilityToArray($facility) {
    return [
        'id' => $facility->getId(),
        'title' => $facility->getTitle(),
        'category' => $facility->getCategory(),
        'description' => $facility->getDescription(),
        'houseNumber' => $facility->getHouseNumber(),
        'streetName' => $facility->getStreetName(),
        'town' => $facility->getTown(),
        'county' => $facility->getCounty(),
        'postcode' => $facility->getPostcode(),
        'fullAddress' => $facility->getFullAddress(),
        'lat' => $facility->getCoordinates()['lat'],
        'lng' => $facility->getCoordinates()['lng'],
    ];
}

/**
 * Enhance facilities with additional data
 * @param array $facilities - Array of facility objects
 * @param FacilityStatusSet $statusSet - Status data access object
 * @param CategorySet $categorySet - Category data access object
 * @return array Enhanced facilities as arrays
 */
function enhanceFacilities($facilities, $statusSet, $categorySet) {
    $result = [];

    foreach ($facilities as $facility) {
        // Convert to array
        $facilityData = facilityToArray($facility);

        // Add status if available
        $status = $statusSet->getStatusForFacility($facility->getId());
        if ($status) {
            $facilityData['statusComment'] = $status->getComment();
            $facilityData['statusUserId'] = $status->getUserId();
            $facilityData['statusTimestamp'] = $status->getTimestamp();
        }

        // Add category name if available
        if ($facility->getCategory()) {
            $category = $categorySet->getCategoryById($facility->getCategory());
            if ($category) {
                $facilityData['categoryName'] = $category->getName();
            }
        }

        $result[] = $facilityData;
    }

    return $result;
}

/**
 * Verify CSRF token validity
 * @param string $token - Token to verify
 * @return bool True if token is valid
 */
function verifyCsrfToken($token) {
    // For demonstration purposes, a simple session-based CSRF protection
    // In production, implement proper CSRF protection with token generation and verification
    if (empty($token)) {
        return false;
    }

    // Check if token matches session token
    if (isset($_SESSION['csrf_token']) && $_SESSION['csrf_token'] === $token) {
        return true;
    }

    return false;
}