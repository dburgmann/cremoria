<?php
/**
 * Base class for all ticks
 */
class Tick {
    protected $db;
	
    /**
     * Constructor.
     * Initialises the Database.
     */
    public function __construct(){
        $this->db = Database::instance();
    }
}
?>
