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

    /** @var int Default user ID since this column doesn't exist in database */
    protected $_userId = 1;

    /** @var string Default timestamp since this column doesn't exist in database */
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

        // Set defaults for missing columns
        $this->_userId = 1; // Default to user ID 1 (usually admin)
        $this->_timestamp = date('Y-m-d H:i:s'); // Current timestamp
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
     * @return int The user ID (always 1 in this implementation)
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
        return 'Recent update';
    }

    /**
     * Get time elapsed since this status was created/updated
     * @return string Human-readable time difference
     */
    public function getTimeElapsed() {
        return 'Recently';
    }
}