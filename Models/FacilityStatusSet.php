<?php
require_once('Database.php');
require_once('FacilityStatus.php');

/**
 * Class FacilityStatusSet
 *
 * Manages the collection of FacilityStatus objects in the ecoBuddy system.
 */
class FacilityStatusSet {
    /** @var Database Database instance */
    protected $_dbInstance;

    /** @var PDO Database connection handle */
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
     *
     * @param int $facilityId The facility ID
     * @return FacilityStatus|null The facility status or null if not found
     */
    public function getStatusForFacility(int $facilityId): ?FacilityStatus {
        $sql = "SELECT * FROM ecoFacilityStatus 
                WHERE facilityId = :facilityId 
                ORDER BY timestamp DESC, id DESC 
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
     *
     * @param int $facilityId The facility ID
     * @param int $page Page number (1-based)
     * @param int $perPage Number of items per page
     * @return array Array of FacilityStatus objects
     */
    public function getStatusHistoryForFacility(int $facilityId, int $page = 1, int $perPage = 10): array {
        $offset = ($page - 1) * $perPage;

        $sql = "SELECT s.*, u.username 
                FROM ecoFacilityStatus s 
                LEFT JOIN ecoUser u ON s.userId = u.id 
                WHERE s.facilityId = :facilityId 
                ORDER BY s.timestamp DESC, s.id DESC 
                LIMIT :limit OFFSET :offset";

        $statement = $this->_dbHandle->prepare($sql);
        $statement->bindParam(':facilityId', $facilityId, PDO::PARAM_INT);

        // Fix: Use variables for bindValue to avoid "only variables can be passed by reference"
        $limitValue = $perPage;
        $offsetValue = $offset;
        $statement->bindValue(':limit', $limitValue, PDO::PARAM_INT);
        $statement->bindValue(':offset', $offsetValue, PDO::PARAM_INT);
        $statement->execute();

        $statuses = [];
        while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
            $statuses[] = new FacilityStatus($row);
        }

        return $statuses;
    }

    /**
     * Update or create a status for a facility
     *
     * @param int $facilityId The facility ID
     * @param int $userId The user ID making the update
     * @param string $comment The status comment
     * @return bool True if successful, false otherwise
     */
    public function updateStatus(int $facilityId, int $userId, string $comment): bool {
        // Validate input
        if (empty($facilityId) || empty($comment)) {
            return false;
        }

        // Sanitize and truncate comment
        $sanitizedComment = substr(trim($comment), 0, 100);

        // Check if status already exists for this facility
        $currentStatus = $this->getStatusForFacility($facilityId);

        if ($currentStatus) {
            // Update existing status - update comment, user, and timestamp
            $sql = "UPDATE ecoFacilityStatus 
                    SET statusComment = :comment, 
                        userId = :userId,
                        timestamp = CURRENT_TIMESTAMP
                    WHERE id = :id";

            $statement = $this->_dbHandle->prepare($sql);
            $statement->bindParam(':comment', $sanitizedComment);
            $statement->bindParam(':userId', $userId, PDO::PARAM_INT);
            $statement->bindParam(':id', $currentStatus->getId(), PDO::PARAM_INT);
        } else {
            // Insert new status with all fields
            $sql = "INSERT INTO ecoFacilityStatus 
                    (facilityId, userId, statusComment, timestamp) 
                    VALUES 
                    (:facilityId, :userId, :comment, CURRENT_TIMESTAMP)";

            $statement = $this->_dbHandle->prepare($sql);
            $statement->bindParam(':facilityId', $facilityId, PDO::PARAM_INT);
            $statement->bindParam(':userId', $userId, PDO::PARAM_INT);
            $statement->bindParam(':comment', $sanitizedComment);
        }

        return $statement->execute();
    }

    /**
     * Add a new status (always creates a new record instead of updating)
     * Useful for keeping history of all status changes
     *
     * @param int $facilityId The facility ID
     * @param int $userId The user ID making the update
     * @param string $comment The status comment
     * @return bool True if successful, false otherwise
     */
    public function addStatus(int $facilityId, int $userId, string $comment): bool {
        // Validate input
        if (empty($facilityId) || empty($comment)) {
            return false;
        }

        // Sanitize and truncate comment
        $sanitizedComment = substr(trim($comment), 0, 100);

        // Always insert new status (keeps history)
        $sql = "INSERT INTO ecoFacilityStatus 
                (facilityId, userId, statusComment, timestamp) 
                VALUES 
                (:facilityId, :userId, :comment, CURRENT_TIMESTAMP)";

        $statement = $this->_dbHandle->prepare($sql);
        $statement->bindParam(':facilityId', $facilityId, PDO::PARAM_INT);
        $statement->bindParam(':userId', $userId, PDO::PARAM_INT);
        $statement->bindParam(':comment', $sanitizedComment);

        return $statement->execute();
    }

    /**
     * Get total number of status updates for a facility
     *
     * @param int $facilityId The facility ID
     * @return int The count of status updates
     */
    public function getStatusCountForFacility(int $facilityId): int {
        $sql = "SELECT COUNT(*) as count FROM ecoFacilityStatus 
                WHERE facilityId = :facilityId";

        $statement = $this->_dbHandle->prepare($sql);
        $statement->bindParam(':facilityId', $facilityId, PDO::PARAM_INT);
        $statement->execute();

        $result = $statement->fetch(PDO::FETCH_ASSOC);
        return (int)$result['count'];
    }

    /**
     * Get recent status updates across all facilities
     *
     * @param int $limit Number of recent updates to return
     * @return array Array of FacilityStatus objects
     */
    public function getRecentStatusUpdates(int $limit = 10): array {
        $sql = "SELECT s.*, f.title as facilityTitle, u.username 
                FROM ecoFacilityStatus s 
                LEFT JOIN ecoFacilities f ON s.facilityId = f.id 
                LEFT JOIN ecoUser u ON s.userId = u.id 
                ORDER BY s.timestamp DESC, s.id DESC 
                LIMIT :limit";

        $statement = $this->_dbHandle->prepare($sql);
        $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
        $statement->execute();

        $statuses = [];
        while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
            $statuses[] = new FacilityStatus($row);
        }

        return $statuses;
    }

    /**
     * Get status updates by a specific user
     *
     * @param int $userId The user ID
     * @param int $limit Number of updates to return
     * @return array Array of FacilityStatus objects
     */
    public function getStatusUpdatesByUser(int $userId, int $limit = 20): array {
        $sql = "SELECT s.*, f.title as facilityTitle 
                FROM ecoFacilityStatus s 
                LEFT JOIN ecoFacilities f ON s.facilityId = f.id 
                WHERE s.userId = :userId 
                ORDER BY s.timestamp DESC, s.id DESC 
                LIMIT :limit";

        $statement = $this->_dbHandle->prepare($sql);
        $statement->bindParam(':userId', $userId, PDO::PARAM_INT);
        $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
        $statement->execute();

        $statuses = [];
        while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
            $statuses[] = new FacilityStatus($row);
        }

        return $statuses;
    }

    /**
     * Delete all statuses for a facility
     *
     * @param int $facilityId The facility ID
     * @return bool True if successful, false otherwise
     */
    public function deleteStatusesForFacility(int $facilityId): bool {
        $sql = "DELETE FROM ecoFacilityStatus 
                WHERE facilityId = :facilityId";

        $statement = $this->_dbHandle->prepare($sql);
        $statement->bindParam(':facilityId', $facilityId, PDO::PARAM_INT);

        return $statement->execute();
    }

    /**
     * Delete a specific status by ID
     *
     * @param int $statusId The status ID to delete
     * @return bool True if successful, false otherwise
     */
    public function deleteStatus(int $statusId): bool {
        $sql = "DELETE FROM ecoFacilityStatus 
                WHERE id = :id";

        $statement = $this->_dbHandle->prepare($sql);
        $statement->bindParam(':id', $statusId, PDO::PARAM_INT);

        return $statement->execute();
    }
}