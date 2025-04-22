<?php

/**
 * Class EcoFacility
 *
 * Represents a single ecological facility entity in the ecoBuddy system.
 * This class encapsulates all the data and behavior related to an ecological facility,
 * including its location, description, and category information.
 */
class EcoFacility {
    /** @var int Unique identifier for the facility */
    protected $_id;

    /** @var string Title/name of the facility */
    protected $_title;

    /** @var int Category ID the facility belongs to */
    protected $_category;

    /** @var string Detailed description of the facility */
    protected $_description;

    /** @var string|null House/building number of the facility location */
    protected $_houseNumber;

    /** @var string|null Street name of the facility location */
    protected $_streetName;

    /** @var string|null County where the facility is located */
    protected $_county;

    /** @var string|null Town where the facility is located */
    protected $_town;

    /** @var string Postal code of the facility location */
    protected $_postcode;

    /** @var float Longitude coordinate of the facility */
    protected $_lng;

    /** @var float Latitude coordinate of the facility */
    protected $_lat;

    /** @var int ID of the user who contributed this facility */
    protected $_contributor;

    /** @var string|null Name of the category this facility belongs to */
    protected $_categoryName;

    /**
     * EcoFacility constructor.
     *
     * Initializes a new EcoFacility instance from database row data.
     *
     * @param array $dbRow Associative array containing facility data from database
     *                     Expected keys: id, title, category, description, houseNumber,
     *                     streetName, county, town, postcode, lng, lat, contributor,
     *                     and optionally categoryName
     */
    public function __construct($dbRow) {
        $this->_id = $dbRow['id'];
        $this->_title = $dbRow['title'];
        $this->_category = $dbRow['category'];
        $this->_description = $dbRow['description'];
        $this->_houseNumber = $dbRow['houseNumber'];
        $this->_streetName = $dbRow['streetName'];
        $this->_county = $dbRow['county'];
        $this->_town = $dbRow['town'];
        $this->_postcode = $dbRow['postcode'];
        $this->_lng = $dbRow['lng'];
        $this->_lat = $dbRow['lat'];
        $this->_contributor = $dbRow['contributor'];
        $this->_categoryName = $dbRow['categoryName'] ?? null; // Null coalescing operator for optional field
    }

    /**
     * Gets the facility's unique identifier
     * @return int
     */
    public function getId() { return $this->_id; }

    /**
     * Gets the facility's title
     * @return string
     */
    public function getTitle() { return $this->_title; }

    /**
     * Gets the facility's category ID
     * @return int
     */
    public function getCategory() { return $this->_category; }

    /**
     * Gets the facility's description
     * @return string
     */
    public function getDescription() { return $this->_description; }

    /**
     * Gets the facility's category name if available
     * @return string|null
     */
    public function getCategoryName() { return $this->_categoryName; }

    /**
     * Gets the facility's house number
     * @return string|null
     */
    public function getHouseNumber() { return $this->_houseNumber; }

    /**
     * Gets the facility's street name
     * @return string|null
     */
    public function getStreetName() { return $this->_streetName; }

    /**
     * Gets the facility's county
     * @return string|null
     */
    public function getCounty() { return $this->_county; }

    /**
     * Gets the facility's town
     * @return string|null
     */
    public function getTown() { return $this->_town; }

    /**
     * Gets the facility's postcode
     * @return string
     */
    public function getPostcode() { return $this->_postcode; }

    /**
     * Gets the facility's geographical coordinates
     * @return array Array containing 'lat' and 'lng' keys with corresponding values
     */
    public function getCoordinates() {
        return ['lat' => $this->_lat, 'lng' => $this->_lng];
    }

    /**
     * Generates a formatted full address string for the facility
     *
     * Combines all address components into a comma-separated string,
     * excluding any null or empty components.
     *
     * @return string The complete formatted address
     */
    public function getFullAddress() {
        // Filter out any null or empty address components before joining
        return implode(", ", array_filter([
            $this->_houseNumber,
            $this->_streetName,
            $this->_town,
            $this->_county,
            $this->_postcode
        ]));
    }
}