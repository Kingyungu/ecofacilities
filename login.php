<?php
/**
 * Login Controller
 *
 * Handles user authentication for the ecoBuddy system with role-based access control.
 * Supports both manager and regular user authentication with appropriate validation
 * and error handling.
 *
 * Security features:
 * - Input sanitization
 * - Role validation
 * - Error reporting
 * - Session management
 *
 * @author [Your Name]
 * @version 1.0
 */

// Initialize session for authentication
session_start();

// Include required model class
require_once('Models/User.php');

// Enable error reporting for development
ini_set('display_errors', 1);
error_reporting(E_ALL);

/**
 * View and Authentication Setup
 */
$view = new stdClass();
$view->pageTitle = 'Login';
$view->error = null;
$user = new User();

/**
 * Process Login Request
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate input
    $username = filter_var(
        $_POST['username'],
        FILTER_SANITIZE_FULL_SPECIAL_CHARS
    );
    $password = $_POST['password']; // Password will be hashed, no sanitization needed
    $loginType = isset($_POST['loginType']) ? $_POST['loginType'] : 'manager';

    // Validate required fields
    if (empty($username) || empty($password)) {
        $view->error = "Please provide both username and password";
    }
    // Attempt authentication
    elseif ($user->login($username, $password)) {
        // Validate manager role
        if ($loginType === 'manager' && !$user->isManager()) {
            $user->logout();
            $view->error = "Access denied. Manager privileges required.";
        }
        // Validate user role
        elseif ($loginType === 'user' && !$user->isUser()) {
            $user->logout();
            $view->error = "Access denied. User account required.";
        }
        // Successful authentication
        else {
            header('Location: index.php');
            exit();
        }
    }
    // Failed authentication
    else {
        $view->error = "Invalid credentials";
    }
}

// Load view template
require_once('Views/login.phtml');