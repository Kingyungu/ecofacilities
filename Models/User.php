<?php
require_once('Database.php');

/**
 * Class User
 *
 * Manages user authentication and session handling in the ecoBuddy system.
 * This class provides user authentication, role management, and session tracking
 * functionality. It follows a singleton-like pattern for session management.
 *
 * Security features:
 * - Session-based authentication
 * - Role-based access control
 * - Prepared statements for SQL queries
 */
class User {
    /** @var string Current user's username */
    protected $_username;

    /** @var int Current user's ID */
    protected $_id;

    /** @var int User type/role (1 = Manager, 2 = Regular User) */
    protected $_userType;

    /** @var object Database instance */
    protected $_db;

    /** @var bool Flag indicating if user is currently logged in */
    private $_isLoggedIn = false;

    /**
     * User constructor
     *
     * Initializes user state from session if exists
     * Starts a new session if none exists
     */
    public function __construct() {
        // Get database instance
        $this->_db = Database::getInstance();

        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Restore user state from session if exists
        if(isset($_SESSION['user_id'])) {
            $this->_id = $_SESSION['user_id'];
            $this->_username = $_SESSION['username'];
            $this->_userType = $_SESSION['userType'];
            $this->_isLoggedIn = true;
        }
    }

    /**
     * Authenticates user credentials and creates session
     *
     * @param string $username Username to authenticate
     * @param string $password Password to verify
     * @return bool True if authentication successful, false otherwise
     *
     * TODO: Implement proper password hashing using password_hash() and password_verify()
     */
    public function login($username, $password) {
        // Prepare and execute user lookup query
        $sql = "SELECT id, username, password, userType FROM ecoUser WHERE username = :username";
        $stmt = $this->_db->getdbConnection()->prepare($sql);
        $stmt->bindParam(':username', $username);
        $stmt->execute();

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // For development only - remove in production
        var_dump([
            'found_user' => $user,
            'attempted_username' => $username,
            'attempted_password' => $password
        ]);

        // Verify credentials and create session if valid
        if($user && $password === $user['password']) {  // TODO: Use password_verify() here
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['userType'] = $user['userType'];
            $this->_userType = $user['userType'];
            $this->_isLoggedIn = true;
            return true;
        }
        return false;
    }

    /**
     * Destroys current session and logs out user
     */
    public function logout() {
        session_destroy();
        $this->_isLoggedIn = false;
    }

    /**
     * Checks if user is currently logged in
     *
     * @return bool True if user is logged in, false otherwise
     */
    public function isLoggedIn() {
        return $this->_isLoggedIn;
    }

    /**
     * Checks if current user has manager role
     *
     * @return bool True if user is a manager (userType = 1), false otherwise
     */
    public function isManager() {
        return $this->_userType === 1;
    }

    /**
     * Checks if current user has regular user role
     *
     * @return bool True if user is a regular user (userType = 2), false otherwise
     */
    public function isUser() {
        return $this->_userType === 2;
    }

    /**
     * Gets current user's ID
     *
     * @return int|null User ID if logged in, null otherwise
     */
    public function getId() {
        return $this->_id;
    }

    /**
     * Gets current user's username
     *
     * @return string|null Username if logged in, null otherwise
     */
    public function getUsername() {
        return $this->_username;
    }

    /**
     * Gets current user's type/role
     *
     * @return int|null User type if logged in, null otherwise
     */
    public function getUserType() {
        return $this->_userType;
    }
}