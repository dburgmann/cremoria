<?php
class TickScore_Controller extends Tick{
    protected $bldPts   = array();
    protected $unitPts  = array();
    protected $techPts  = array();

    protected $userTowns = array(); //Contains the ids of all towns for each user

    protected $userRanking = array();
    protected $townRanking = array();

    public function __construct(){
        parent::__construct();
    }

    /**
     * Starts the Tick
     */
    public function start(){
        //Get Building Pts of all users
        $bldPts = $this->db->query('SELECT user_id, SUM(buildingPts) as buildingPts FROM towns GROUP BY user_id');

        foreach($bldPts as $row)
            $this->db->query("UPDATE users
                                SET buildingPts = {$row->buildingPts},
                                    totalPts = researchPts + buildingPts
                                WHERE id = {$row->user_id}");
    }

}
?>

