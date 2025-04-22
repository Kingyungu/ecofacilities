<?php
require_once('Database.php');
require_once('EcoFacility.php');

/**
 * Class EcoFacilitySet
 *
 * Manages the collection of EcoFacility objects in the ecoBuddy system.
 * This class handles all database operations related to ecological facilities including
 * CRUD operations, search functionality, and pagination support.
 *
 * Implements the Data Access Object (DAO) pattern for the EcoFacility entity.
 *
 * Enhanced in Assignment 2 with advanced search and filtering capabilities.
 */
class EcoFacilitySet {
    /** @var object Database instance */
    protected $_dbInstance;

    /** @var object Database connection handle */
    protected $_dbHandle;

    /**
     * EcoFacilitySet constructor.
     *
     * Initializes database connection using singleton pattern.
     */
    public function __construct() {
        $this->_dbInstance = Database::getInstance();
        $this->_dbHandle = $this->_dbInstance->getdbConnection();
    }

    /**
     * Fetches a paginated list of all facilities ordered by title
     *
     * @param int $page Current page number (default: 1)
     * @param int $perPage Number of items per page (default: 10)
     * @return array Array of EcoFacility objects for the requested page
     */
    public function fetchAllFacilities($page = 1, $perPage = 10) {
        // Calculate offset for pagination
        $offset = ($page - 1) * $perPage;

        // Prepare paginated query with parameter binding
        $sqlQuery = 'SELECT * FROM ecoFacilities ORDER BY title LIMIT :limit OFFSET :offset';
        $statement = $this->_dbHandle->prepare($sqlQuery);
        $statement->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $statement->bindValue(':offset', $offset, PDO::PARAM_INT);
        $statement->execute();

        // Build array of EcoFacility objects
        $dataSet = [];
        while ($row = $statement->fetch()) {
            $dataSet[] = new EcoFacility($row);
        }
        return $dataSet;
    }

    /**
     * Gets total count of facilities matching search criteria
     *
     * Enhanced in Assignment 2 with additional search parameters
     *
     * @param string $searchTerm Optional search term for filtering
     * @param int|null $category Optional category ID for filtering
     * @param array $additionalFilters Optional additional filters (town, county, postcode)
     * @return int Total number of matching facilities
     */
    public function getTotalCount($searchTerm = '', $category = null, $additionalFilters = []) {
        $sql = 'SELECT COUNT(*) as count FROM ecoFacilities WHERE 1=1';
        $params = [];

        // Add search term condition if provided
        if (!empty($searchTerm)) {
            $sql .= ' AND (title LIKE :search OR description LIKE :search OR postcode LIKE :search)';
            $params[':search'] = "%$searchTerm%";
        }

        // Add category filter if provided
        if ($category) {
            $sql .= ' AND category = :category';
            $params[':category'] = $category;
        }

        // Add additional filters if provided
        if (!empty($additionalFilters['town'])) {
            $sql .= ' AND town LIKE :town';
            $params[':town'] = '%' . $additionalFilters['town'] . '%';
        }

        if (!empty($additionalFilters['county'])) {
            $sql .= ' AND county LIKE :county';
            $params[':county'] = '%' . $additionalFilters['county'] . '%';
        }

        if (!empty($additionalFilters['postcode'])) {
            $sql .= ' AND postcode LIKE :postcode';
            $params[':postcode'] = $additionalFilters['postcode'] . '%';
        }

        // Execute count query
        $statement = $this->_dbHandle->prepare($sql);
        foreach ($params as $key => $value) {
            $statement->bindValue($key, $value);
        }
        $statement->execute();

        $result = $statement->fetch();
        return $result['count'];
    }

    /**
     * Searches facilities with optional filtering and pagination
     *
     * Enhanced in Assignment 2 with additional search parameters and sorting
     *
     * @param string $searchTerm Search term for filtering (default: '')
     * @param int|null $category Category ID for filtering (default: null)
     * @param int $page Current page number (default: 1)
     * @param int $perPage Number of items per page (default: 10)
     * @param array $additionalFilters Optional additional filters (town, county, postcode)
     * @param string $sortBy Field to sort by (default: 'title')
     * @param string $sortDir Sort direction 'asc' or 'desc' (default: 'asc')
     * @return array Array of matching EcoFacility objects
     */
    public function searchFacilities(
        $searchTerm = '',
        $category = null,
        $page = 1,
        $perPage = 10,
        $additionalFilters = [],
        $sortBy = 'title',
        $sortDir = 'asc'
    ) {
        // Calculate pagination offset
        $offset = ($page - 1) * $perPage;

        // Validate and sanitize sort parameters
        $allowedSortFields = ['title', 'category', 'town', 'county', 'postcode'];
        if (!in_array($sortBy, $allowedSortFields)) {
            $sortBy = 'title';
        }

        $sortDir = strtolower($sortDir) === 'desc' ? 'DESC' : 'ASC';

        // Build base query
        $sql = 'SELECT * FROM ecoFacilities WHERE 1=1';
        $params = [];

        // Add search conditions if term provided
        if (!empty($searchTerm)) {
            $sql .= ' AND (title LIKE :search OR description LIKE :search OR postcode LIKE :search)';
            $params[':search'] = "%$searchTerm%";
        }

        // Add category filter if provided
        if ($category) {
            $sql .= ' AND category = :category';
            $params[':category'] = $category;
        }

        // Add additional filters if provided
        if (!empty($additionalFilters['town'])) {
            $sql .= ' AND town LIKE :town';
            $params[':town'] = '%' . $additionalFilters['town'] . '%';
        }

        if (!empty($additionalFilters['county'])) {
            $sql .= ' AND county LIKE :county';
            $params[':county'] = '%' . $additionalFilters['county'] . '%';
        }

        if (!empty($additionalFilters['postcode'])) {
            $sql .= ' AND postcode LIKE :postcode';
            $params[':postcode'] = $additionalFilters['postcode'] . '%';
        }

        // Add ordering and pagination
        $sql .= " ORDER BY $sortBy $sortDir LIMIT :limit OFFSET :offset";

        // Execute search query
        $statement = $this->_dbHandle->prepare($sql);
        foreach ($params as $key => $value) {
            $statement->bindValue($key, $value);
        }
        $statement->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $statement->bindValue(':offset', $offset, PDO::PARAM_INT);
        $statement->execute();

        // Build result set
        $dataSet = [];
        while ($row = $statement->fetch()) {
            $dataSet[] = new EcoFacility($row);
        }
        return $dataSet;
    }

    /**
     * Adds a new facility to the database
     *
     * @param array $data Facility data including title, category, description, location details
     * @return bool True if insertion successful, false otherwise
     */
    public function addFacility($data) {
        // Prepare INSERT statement with all facility fields
        $sql = "INSERT INTO ecoFacilities (
                    title, category, description, houseNumber, 
                    streetName, county, town, postcode, 
                    lng, lat, contributor
                ) VALUES (
                    :title, :category, :description, :houseNumber,
                    :streetName, :county, :town, :postcode,
                    :lng, :lat, :contributor
                )";

        $statement = $this->_dbHandle->prepare($sql);
        return $statement->execute($data);
    }

    /**
     * Updates an existing facility
     *
     * @param int $id Facility ID to update
     * @param array $data Updated facility data
     * @return bool True if update successful, false otherwise
     */
    public function updateFacility($id, $data) {
        // Prepare UPDATE statement for all facility fields
        $sql = "UPDATE ecoFacilities SET 
                title = :title,
                category = :category,
                description = :description,
                houseNumber = :houseNumber,
                streetName = :streetName,
                county = :county,
                town = :town,
                postcode = :postcode,
                lng = :lng,
                lat = :lat
                WHERE id = :id";

        $data['id'] = $id;
        $statement = $this->_dbHandle->prepare($sql);
        return $statement->execute($data);
    }

    /**
     * Deletes a facility and its related status records
     *
     * @param int $id Facility ID to delete
     * @return bool True if deletion successful, false otherwise
     */
    public function deleteFacility($id) {
        // First delete dependent status records
        $sql = "DELETE FROM ecoFacilityStatus WHERE facilityId = :id";
        $statement = $this->_dbHandle->prepare($sql);
        $statement->execute([':id' => $id]);

        // Then delete the facility itself
        $sql = "DELETE FROM ecoFacilities WHERE id = :id";
        $statement = $this->_dbHandle->prepare($sql);
        return $statement->execute([':id' => $id]);
    }

    /**
     * Retrieves a specific facility by its ID
     *
     * @param int $id Facility ID to retrieve
     * @return EcoFacility|null Facility object if found, null otherwise
     */
    public function getFacilityById($id) {
        $sql = "SELECT * FROM ecoFacilities WHERE id = :id";
        $statement = $this->_dbHandle->prepare($sql);
        $statement->execute([':id' => $id]);

        if ($row = $statement->fetch()) {
            return new EcoFacility($row);
        }
        return null;
    }

    /**
     * Retrieves facilities near a given location
     *
     * @param float $lat Latitude of the search point
     * @param float $lng Longitude of the search point
     * @param float $radius Search radius in kilometers
     * @param int $limit Maximum number of results to return
     * @return array Array of EcoFacility objects sorted by distance
     */
    public function getNearbyFacilities($lat, $lng, $radius = 5.0, $limit = 10) {
        // Calculate distance using the Haversine formula
        // Note: This is a simplified version and works best for small distances
        $sql = "SELECT *, 
                (6371 * acos(cos(radians(:lat)) * cos(radians(lat)) * cos(radians(lng) - radians(:lng)) + sin(radians(:lat)) * sin(radians(lat)))) AS distance 
                FROM ecoFacilities 
                WHERE (6371 * acos(cos(radians(:lat2)) * cos(radians(lat)) * cos(radians(lng) - radians(:lng2)) + sin(radians(:lat2)) * sin(radians(lat)))) < :radius
                ORDER BY distance 
                LIMIT :limit";

        $statement = $this->_dbHandle->prepare($sql);
        $statement->bindParam(':lat', $lat);
        $statement->bindParam(':lng', $lng);
        $statement->bindParam(':lat2', $lat); // Same as :lat but SQLite doesn't support parameter reuse
        $statement->bindParam(':lng2', $lng); // Same as :lng
        $statement->bindParam(':radius', $radius);
        $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
        $statement->execute();

        $dataSet = [];
        while ($row = $statement->fetch()) {
            $dataSet[] = new EcoFacility($row);
        }

        return $dataSet;
    }

    /**
     * Gets unique towns for filter select options
     *
     * @return array Array of unique town names
     */
    public function getUniqueTowns() {
        $sql = "SELECT DISTINCT town FROM ecoFacilities WHERE town IS NOT NULL AND town != '' ORDER BY town";
        $statement = $this->_dbHandle->prepare($sql);
        $statement->execute();

        $towns = [];
        while ($row = $statement->fetch()) {
            $towns[] = $row['town'];
        }

        return $towns;
    }

    /**
     * Gets unique counties for filter select options
     *
     * @return array Array of unique county names
     */
    public function getUniqueCounties() {
        $sql = "SELECT DISTINCT county FROM ecoFacilities WHERE county IS NOT NULL AND county != '' ORDER BY county";
        $statement = $this->_dbHandle->prepare($sql);
        $statement->execute();

        $counties = [];
        while ($row = $statement->fetch()) {
            $counties[] = $row['county'];
        }

        return $counties;
    }
}