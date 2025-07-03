<?php
/**
 * Class FacilityStatus
 *
 * Represents a single status comment for an ecological facility in the ecoBuddy system.
 */
class FacilityStatus {
    /** @var int ID of the status record */
    protected $_id;

    /** @var int ID of the facility this status belongs to */
    protected $_facilityId;

    /** @var string The status comment text */
    protected $_comment;

    /** @var int ID of the user who created this status */
    protected $_userId;

    /** @var string Timestamp when this status was created/updated */
    protected $_timestamp;

    /**
     * FacilityStatus constructor.
     *
     * @param array $dbRow Associative array containing status data from database
     */
    public function __construct($dbRow) {
        $this->_id = $dbRow['id'];
        $this->_facilityId = $dbRow['facilityId'];
        $this->_comment = $dbRow['statusComment'];
        $this->_userId = isset($dbRow['userId']) ? $dbRow['userId'] : 1; // Default to 1 if not set
        $this->_timestamp = isset($dbRow['timestamp']) ? $dbRow['timestamp'] : date('Y-m-d H:i:s');
    }

    /**
     * Get the status record ID
     * @return int The status ID
     */
    public function getId() {
        return $this->_id;
    }

    /**
     * Get the facility ID this status is associated with
     * @return int The facility ID
     */
    public function getFacilityId() {
        return $this->_facilityId;
    }

    /**
     * Get the status comment text
     * @return string The comment text
     */
    public function getComment() {
        return $this->_comment;
    }

    /**
     * Get the ID of the user who created this status
     * @return int The user ID
     */
    public function getUserId() {
        return $this->_userId;
    }

    /**
     * Get the timestamp when this status was created/updated
     * @return string The timestamp
     */
    public function getTimestamp() {
        return $this->_timestamp;
    }

    /**
     * Get the formatted timestamp for display
     * @return string Formatted date/time
     */
    public function getFormattedTimestamp() {
        $datetime = new DateTime($this->_timestamp);
        return $datetime->format('M j, Y g:i A');
    }

    /**
     * Get time elapsed since this status was created/updated
     * @return string Human-readable time difference
     */
    public function getTimeElapsed() {
        $datetime = new DateTime($this->_timestamp);
        $now = new DateTime();
        $interval = $now->diff($datetime);

        if ($interval->days > 0) {
            return $interval->days . ' day' . ($interval->days > 1 ? 's' : '') . ' ago';
        } elseif ($interval->h > 0) {
            return $interval->h . ' hour' . ($interval->h > 1 ? 's' : '') . ' ago';
        } elseif ($interval->i > 0) {
            return $interval->i . ' minute' . ($interval->i > 1 ? 's' : '') . ' ago';
        } else {
            return 'Just now';
        }
    }

    /**
     * Check if this status is recent (within last 24 hours)
     * @return bool True if status is recent
     */
    public function isRecent() {
        $datetime = new DateTime($this->_timestamp);
        $now = new DateTime();
        $interval = $now->diff($datetime);

        return $interval->days === 0;
    }

    /**
     * Get a CSS class for styling based on age
     * @return string CSS class name
     */
    public function getAgeClass() {
        $datetime = new DateTime($this->_timestamp);
        $now = new DateTime();
        $interval = $now->diff($datetime);

        if ($interval->days === 0) {
            return 'status-recent';
        } elseif ($interval->days <= 7) {
            return 'status-week';
        } elseif ($interval->days <= 30) {
            return 'status-month';
        } else {
            return 'status-old';
        }
    }
}