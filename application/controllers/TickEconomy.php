<?php
class TickEconomy_Controller extends Tick{
    protected $clean = array();

    public function __construct(){
        parent::__construct();
    }
	
    /**
     * Starts the Tick
     */
    public function start(){
        $this->updateTowns();
        $this->updateUsers();
        $this->updateProduction();
        $this->updateBuildings();
        $this->updateUnits();
        $this->updateTechs();
        $this->cleanTables();
        //TODO: ErrorLogging & Exceptions
    }


    /**#####TABLES CONTAINING PRODUCTION#####**/
    
    /**
     * Updates the User Table 
     */
    protected function updateUsers(){
        $this->db->query('UPDATE users SET researchTime = researchTime -1');
        $this->db->query('UPDATE users SET researchPts = researchPts + currentRscPts WHERE researchTime <= 0');
    }
	
    
    /**
     * Updates the Towns Table 
     */
    protected function updateTowns(){
        $this->db->query('UPDATE towns SET buildingTime = buildingTime - 1,
                                           gold = gold + goldInc,
                                           wood = wood + woodInc,
                                           stone = stone + stoneInc,
                                           iron = iron + ironInc');
        
        $this->db->query('UPDATE towns SET buildingPts = buildingPts + currentBldPts WHERE buildingTime <= 0');
        //TODO: Überlegen ob reduzierung auf ein Query möglich!

    }

    
    /**
     * Updates the Productions Table
     */
    protected function updateProduction(){
        $this->db->query('UPDATE unit_productions SET time = time-1');
    }



    
    
    /**#####BUILDINGS#####**/
    
    /**
     * Coordinates the updating of the Building Table,
     * When Buildings where finished
     */
    protected function updateBuildings(){
        $towns = $this->db->query('SELECT id, currentBuilding FROM  towns WHERE buildingTime <= 0');

        foreach($towns as $town){
            //Save ids to clean the table afterwards
            $this->clean['building'][] = $town->id;
            
            //update building table
            $this->db->query('INSERT INTO buildings (id, town_id, quantity)
                                      VALUES ('.$town->currentBuilding.', '.$town->id.', 1)
                                      ON DUPLICATE KEY
                                      UPDATE quantity = quantity + 1');

            //If the finished building was a ressource building, update the ressource increase / tick
            $this->checkBldOperations($town);
        }
    }
    
    
    /**
     * Checks if finished buildings unleash further actions
     * @param $town
     */
    protected function checkBldOperations($town){
    	if(in_array($town->currentBuilding, Kohana::config('crmItems.tickRelevantBlds'))){
        	$res = $this->db->query('SELECT quantity
                                     FROM buildings
                                     WHERE town_id = '.$town->id.' AND id = '.$town->currentBuilding);
            $quantity = ($res->count() == 0)? 0 : $res[0]->quantity;

            //Check which building was build
            Switch($town->currentBuilding){
            	case Kohana::config('crmItems.goldBld'):
                	$this->updateGoldIncome($town->id, $quantity);
                    break;

                case Kohana::config('crmItems.woodBld'):
                    $this->updateWoodIncome($town->id, $quantity);
                    break;

                case Kohana::config('crmItems.stoneBld'):
                    $this->updateStoneIncome($town->id, $quantity);
                    break;
                        
                case Kohana::config('crmItems.ironBld'):
                  	$this->updateIronIncomeMax($town->id, $quantity);
                  	break;
                    	                   
                case Kohana::config('crmItems.unitBld'):
                   	$this->updateUnitCap($town->id, $quantity);
                   	break;

                case Kohana::config('crmItems.movCapBld'):
                    $this->updateMovCap($town->id, $quantity);
                    break;
            }
        }
    }
    
    
    /**
     * Updates the gold Income
     */
    protected function updateGoldIncome($townId, $no){
        $calced = eval(Kohana::config('crmGame.goldIncomeFormula'));
        $this->db->query("UPDATE towns
                          SET goldInc = $calced
                          WHERE id = $townId");
    }
    
    
    /**
     * Updates the wood Income
     */
    protected function updateWoodIncome($townId, $no){
        $calced = eval(Kohana::config('crmGame.woodIncomeFormula'));
        $factor = Kohana::config('crmGame.ironToWood');
        $this->db->query("UPDATE towns
                          SET woodInc = $calced - ($factor * ironInc)
                          WHERE id = $townId");
    }
        
    
    /**
     * Updates the stone Income
     */
    protected function updateStoneIncome($townId, $no){
        $calced = eval(Kohana::config('crmGame.stoneIncomeFormula'));
        $factor = Kohana::config('crmGame.ironToStone');
        $this->db->query("UPDATE towns
                          SET stoneInc = $calced - ($factor * ironInc)
                          WHERE id = $townId");
        
    }
        
    
    /**
     * Updates the iron Max Income
     */
    protected function updateIronIncomeMax($townId, $no){
		$calced = eval(Kohana::config('crmGame.ironIncomeFormula'));
		$this->db->query("UPDATE towns
                          SET ironIncMax = $calced
                          WHERE id = $townId");
    }

	        
    /**
     * Updates the unitCap
     */
    protected function updateUnitCap($townId, $no){
        $calced = eval(Kohana::config('crmGame.unitCapFormula'));
        $this->db->query("UPDATE towns
                          SET unitCap = $calced
                          WHERE id = $townId");
    }


    /**
     * Update the movement Cap
     */
    protected function updateMovCap($townId, $quantity){
        $calced = $quantity; //eval(Kohana::config('crmGame.movCapFormula'));
        $this->db->query("UPDATE towns
                          SET movCap = $calced
                          WHERE id = $townId");
    }

    
    
    /**#####UNITS#####**/
    
    /**
     * Updates the Units Table when units where finished
     */
    protected function updateUnits(){
        $units = $this->db->query('SELECT id, unit_id, town_id, quantity FROM unit_productions WHERE time <= 0');
        foreach($units as $unit){
            $id   	= $unit->unit_id;
            $town   = $unit->town_id;
            $no     = $unit->quantity;
            $this->clean['unit'][] = $unit->id;
            $this->db->query("INSERT INTO units (unit_id, town_id, quantity) VALUES ($id, $town, $no)
                              ON DUPLICATE KEY UPDATE quantity = quantity + $no");
            $this->db->query("UPDATE towns SET unitCapUsed = unitCapUsed - $no WHERE id = $town");

        }
    }



    
    
    /**#####TECHS#####**/
    
    /**
     * Updates the Tech Table when Researchs where finished
     */
    protected function updateTechs(){
        $users = $this->db->query('SELECT id, currentResearch FROM  users WHERE researchTime <= 0');

        foreach($users as $user){
            //Save ids to clean the table afterwards
            $this->clean['tech'][] = $user->id;
            
            //update tech table
            $this->db->query('INSERT INTO techs (id, user_id, quantity)
                                      VALUES ('.$user->currentResearch.', '.$user->id.', 1)
                                      ON DUPLICATE KEY
                                      UPDATE quantity = quantity + 1');
        }    
        
    }



    /**
     * Resets the values in the the tables if eg. a buildign was finished
     */
    protected function cleanTables(){
        if(isset($this->clean['building']))
            $this->db->query('UPDATE towns SET currentBuilding = NULL, buildingTime = NULL WHERE id IN('.implode(', ', $this->clean['building']).')');
        if(isset($this->clean['tech']))
            $this->db->query('UPDATE users SET currentResearch = NULL, researchTime = NULL WHERE id IN('.implode(', ', $this->clean['tech']).')');
        if(isset($this->clean['unit']))
            $this->db->query('DELETE FROM unit_productions WHERE id IN('.implode(', ', $this->clean['unit']).')');
    }
}

?>
