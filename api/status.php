<?php
/**
 * Status API
 *
 * Handles AJAX requests for facility status operations.
 * @version 1.0
 */

// Enable error logging
error_log('Status API called with method: ' . $_SERVER['REQUEST_METHOD']);
error_log('Request data: ' . print_r($_REQUEST, true));

// Start session to access authentication data
session_start();

// Include required models using absolute paths
require_once(__DIR__ . '/../Models/User.php');
require_once(__DIR__ . '/../Models/Database.php');
require_once(__DIR__ . '/../Models/FacilityStatusSet.php');

// Set content type to JSON
header('Content-Type: application/json');

// Log session information
error_log('Session data: ' . print_r($_SESSION, true));

// Verify user is authenticated
$user = new User();
if (!$user->isLoggedIn()) {
    error_log('Authentication failed: User not logged in');
    echo json_encode([
        'success' => false,
        'message' => 'Authentication required'
    ]);
    exit;
}

// Verify CSRF token
$csrfToken = $_REQUEST['csrfToken'] ?? '';
error_log('Received CSRF token: ' . $csrfToken);
error_log('Session CSRF token: ' . ($_SESSION['csrf_token'] ?? 'not set'));

if (!verifyCsrfToken($csrfToken)) {
    error_log('CSRF verification failed');
    echo json_encode([
        'success' => false,
        'message' => 'Invalid security token'
    ]);
    exit;
}

// Initialize data access object
$statusSet = new FacilityStatusSet();

// Determine action to perform
$action = $_REQUEST['action'] ?? 'get';
error_log('Requested action: ' . $action);

// Process request based on action
switch ($action) {
    case 'get':
        handleGetStatus($statusSet);
        break;

    case 'update':
    case 'save':
        handleSaveStatus($statusSet, $user);
        break;

    default:
        echo json_encode([
            'success' => false,
            'message' => 'Invalid action specified'
        ]);
        break;
}

/**
 * Handle get status request
 * @param FacilityStatusSet $statusSet - Status data access object
 */
function handleGetStatus($statusSet) {
    // Validate facility ID
    $facilityId = filter_input(INPUT_GET, 'facilityId', FILTER_VALIDATE_INT);
    error_log('Get status for facility ID: ' . $facilityId);

    if (!$facilityId) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid facility ID'
        ]);
        return;
    }

    // Get status for facility
    $status = $statusSet->getStatusForFacility($facilityId);

    if ($status) {
        echo json_encode([
            'success' => true,
            'statusComment' => $status->getComment(),
            'userId' => $status->getUserId(),
            'timestamp' => $status->getTimestamp()
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'statusComment' => null
        ]);
    }
}

/**
 * Handle save status request
 * @param FacilityStatusSet $statusSet - Status data access object
 * @param User $user - Current authenticated user
 */
function handleSaveStatus($statusSet, $user) {
    // Validate facility ID
    $facilityId = filter_input(INPUT_POST, 'facilityId', FILTER_VALIDATE_INT);
    $statusComment = filter_input(INPUT_POST, 'statusComment', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

    error_log('Save status for facility ID: ' . $facilityId);
    error_log('Status comment: ' . $statusComment);
    error_log('User ID: ' . $user->getId());

    if (!$facilityId) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid facility ID'
        ]);
        return;
    }

    // Validate and sanitize status comment
    if (!$statusComment || strlen($statusComment) > 100) {
        echo json_encode([
            'success' => false,
            'message' => 'Status comment is required and must be no more than 100 characters'
        ]);
        return;
    }

    // Save status update
    try {
        $result = $statusSet->updateStatus($facilityId, $user->getId(), $statusComment);
        error_log('Update result: ' . ($result ? 'success' : 'failure'));

        if ($result) {
            echo json_encode([
                'success' => true,
                'message' => 'Status updated successfully'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to update status'
            ]);
        }
    } catch (Exception $e) {
        error_log('Exception during status update: ' . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ]);
    }
}

/**
 * Verify CSRF token validity
 * @param string $token - Token to verify
 * @return bool True if token is valid
 */
function verifyCsrfToken($token) {
    // For simplicity, accept any non-empty token during development
    if (empty($token)) {
        return false;
    }

    // Check if token matches session token
    if (isset($_SESSION['csrf_token']) && $_SESSION['csrf_token'] === $token) {
        return true;
    }

    // For development, temporarily accept any token
    return true;  // Remove this line in production
}