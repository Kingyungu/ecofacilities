<?php
require_once('Database.php');

/**
 * Class Category
 *
 * Represents a single category entity in the ecoBuddy system.
 * This class encapsulates the properties and behaviors of ecological facility categories,
 * providing a clean object-oriented interface to category data.
 */
class Category {
    /** @var int Protected category ID from database */
    protected $_id;

    /** @var string Protected category name */
    protected $_name;

    /**
     * Category constructor.
     *
     * Initializes a new Category instance from database row data.
     *
     * @param array $dbRow Associative array containing category data from database
     *                     Expected keys: 'id', 'name'
     */
    public function __construct($dbRow) {
        $this->_id = $dbRow['id'];
        $this->_name = $dbRow['name'];
    }

    /**
     * Gets the category's unique identifier
     *
     * @return int The category ID
     */
    public function getId() { return $this->_id; }

    /**
     * Gets the category's name
     *
     * @return string The category name
     */
    public function getName() { return $this->_name; }
}

/**
 * Class CategorySet
 *
 * Manages the collection of categories in the ecoBuddy system.
 * This class handles all database operations related to categories including
 * CRUD operations and data retrieval. It follows the Data Access Object (DAO) pattern.
 */
class CategorySet {
    /** @var object Database instance */
    protected $_dbInstance;

    /** @var object Database connection handle */
    protected $_dbHandle;

    /**
     * CategorySet constructor.
     *
     * Initializes database connection using singleton pattern.
     */
    public function __construct() {
        // Get database instance using singleton pattern
        $this->_dbInstance = Database::getInstance();
        $this->_dbHandle = $this->_dbInstance->getdbConnection();
    }

    /**
     * Retrieves all categories from the database
     *
     * @return array Array of Category objects sorted alphabetically by name
     */
    public function fetchAllCategories() {
        // Prepare SQL query for all categories
        $sqlQuery = 'SELECT * FROM ecoCategories ORDER BY name';
        $statement = $this->_dbHandle->prepare($sqlQuery);
        $statement->execute();

        // Build array of Category objects
        $dataSet = [];
        while ($row = $statement->fetch()) {
            $dataSet[] = new Category($row);
        }
        return $dataSet;
    }

    /**
     * Retrieves a specific category by its ID
     *
     * @param int $id The category ID to retrieve
     * @return Category|null Category object if found, null otherwise
     */
    public function getCategoryById($id) {
        // Prepare SQL query with parameter binding for security
        $sqlQuery = 'SELECT * FROM ecoCategories WHERE id = :id';
        $statement = $this->_dbHandle->prepare($sqlQuery);
        $statement->bindParam(':id', $id);
        $statement->execute();

        // Return new Category object if found, null otherwise
        if ($row = $statement->fetch()) {
            return new Category($row);
        }
        return null;
    }

    /**
     * Adds a new category to the database
     *
     * @param string $name The name of the new category
     * @return bool True if insertion successful, false otherwise
     */
    public function addCategory($name) {
        // Prepare INSERT statement with parameter binding
        $sql = "INSERT INTO ecoCategories (name) VALUES (:name)";
        $statement = $this->_dbHandle->prepare($sql);
        return $statement->execute(['name' => $name]);
    }

    /**
     * Updates an existing category's name
     *
     * @param int $id The ID of the category to update
     * @param string $name The new name for the category
     * @return bool True if update successful, false otherwise
     */
    public function updateCategory($id, $name) {
        // Prepare UPDATE statement with parameter binding
        $sql = "UPDATE ecoCategories SET name = :name WHERE id = :id";
        $statement = $this->_dbHandle->prepare($sql);
        return $statement->execute(['id' => $id, 'name' => $name]);
    }

    /**
     * Deletes a category if it's not in use by any facilities
     *
     * @param int $id The ID of the category to delete
     * @return bool True if deletion successful, false if category in use or deletion failed
     */
    public function deleteCategory($id) {
        // First check if any facilities are using this category
        $sql = "SELECT COUNT(*) FROM ecoFacilities WHERE category = :id";
        $statement = $this->_dbHandle->prepare($sql);
        $statement->execute(['id' => $id]);

        // Prevent deletion if category is in use
        if ($statement->fetchColumn() > 0) {
            return false;
        }

        // Safe to delete - prepare and execute DELETE statement
        $sql = "DELETE FROM ecoCategories WHERE id = :id";
        $statement = $this->_dbHandle->prepare($sql);
        return $statement->execute(['id' => $id]);
    }
}