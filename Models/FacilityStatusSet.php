<?php
require_once('Database.php');
require_once('FacilityStatus.php');

/**
 * Class FacilityStatusSet
 *
 * Manages the collection of FacilityStatus objects in the ecoBuddy system.
 */
class FacilityStatusSet {
    /** @var object Database instance */
    protected $_dbInstance;

    /** @var object Database connection handle */
    protected $_dbHandle;

    /**
     * FacilityStatusSet constructor.
     */
    public function __construct() {
        $this->_dbInstance = Database::getInstance();
        $this->_dbHandle = $this->_dbInstance->getdbConnection();
    }

    /**
     * Get the current status for a facility
     */
    public function getStatusForFacility($facilityId) {
        $sql = "SELECT * FROM ecoFacilityStatus 
                WHERE facilityId = :facilityId 
                ORDER BY id DESC 
                LIMIT 1";

        $statement = $this->_dbHandle->prepare($sql);
        $statement->bindParam(':facilityId', $facilityId, PDO::PARAM_INT);
        $statement->execute();

        $row = $statement->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            return new FacilityStatus($row);
        }

        return null;
    }

    /**
     * Get all statuses for a facility with pagination
     */
    public function getStatusHistoryForFacility($facilityId, $page = 1, $perPage = 10) {
        $offset = ($page - 1) * $perPage;

        $sql = "SELECT * FROM ecoFacilityStatus 
                WHERE facilityId = :facilityId 
                ORDER BY id DESC 
                LIMIT :limit OFFSET :offset";

        $statement = $this->_dbHandle->prepare($sql);
        $statement->bindParam(':facilityId', $facilityId, PDO::PARAM_INT);
        $statement->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $statement->bindValue(':offset', $offset, PDO::PARAM_INT);
        $statement->execute();

        $statuses = [];
        while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
            $statuses[] = new FacilityStatus($row);
        }

        return $statuses;
    }

    /**
     * Update or create a status for a facility
     */
    public function updateStatus($facilityId, $userId, $comment) {
        // Validate input
        if (empty($facilityId) || empty($comment)) {
            return false;
        }

        // Sanitize and truncate comment
        $sanitizedComment = substr(trim($comment), 0, 100);

        // Check if status already exists for this facility
        $currentStatus = $this->getStatusForFacility($facilityId);

        if ($currentStatus) {
            // Update existing status
            $sql = "UPDATE ecoFacilityStatus 
                    SET statusComment = :comment 
                    WHERE id = :id";

            $statement = $this->_dbHandle->prepare($sql);
            $statement->bindParam(':comment', $sanitizedComment);
            $statement->bindParam(':id', $currentStatus->getId(), PDO::PARAM_INT);
        } else {
            // Insert new status
            $sql = "INSERT INTO ecoFacilityStatus 
                    (facilityId, statusComment) 
                    VALUES 
                    (:facilityId, :comment)";

            $statement = $this->_dbHandle->prepare($sql);
            $statement->bindParam(':facilityId', $facilityId, PDO::PARAM_INT);
            $statement->bindParam(':comment', $sanitizedComment);
        }

        return $statement->execute();
    }

    /**
     * Get total number of status updates for a facility
     */
    public function getStatusCountForFacility($facilityId) {
        $sql = "SELECT COUNT(*) as count FROM ecoFacilityStatus 
                WHERE facilityId = :facilityId";

        $statement = $this->_dbHandle->prepare($sql);
        $statement->bindParam(':facilityId', $facilityId, PDO::PARAM_INT);
        $statement->execute();

        $result = $statement->fetch(PDO::FETCH_ASSOC);
        return $result['count'];
    }

    /**
     * Delete all statuses for a facility
     */
    public function deleteStatusesForFacility($facilityId) {
        $sql = "DELETE FROM ecoFacilityStatus 
                WHERE facilityId = :facilityId";

        $statement = $this->_dbHandle->prepare($sql);
        $statement->bindParam(':facilityId', $facilityId, PDO::PARAM_INT);

        return $statement->execute();
    }
}