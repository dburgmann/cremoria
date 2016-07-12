<?php
/**
 * Movement Tick - called via cronjob
 * decreasing time for arrival & responsible for handleing movements
 * @author Daniel
 */
class TickMovement_Controller extends Tick {

    //Handled movements which are ready for deletion
    private $delMovs = array();
    //Troops lost during movements which are ready for deletion
    private $delTroops = array();


    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();
    }


    /**
     * Bootstrap method initiating the tick
     */
    public function start() {
        $this->updateMovements();
        $this->handleMovements();
        $this->deleteFinishedMovements();
    }


    /**
     * Updates all movements, decreasing their time for arrival
     */
    protected function updateMovements() {
        $this->db->query('UPDATE movements SET arrival = arrival -1');
    }


    /**
     * Bootstrap method for movement handling
     * Selects all finished movements and calls the needed methods to handle them
     */
    protected function handleMovements() {
        $actions = kohana::config('crmGame.movementTypes');
        $res = $this->db->query('SELECT * FROM movements WHERE arrival = 0');               //get finished movements
        foreach ($res as $mov) {
            $action = (isset($actions[$mov->action])) ? $actions[$mov->action] : null;      //check if movementaction is valid
            switch ($action) {                                                              //check movementaction to determine method to use
                case 'Transfer':
                    $this->handleTransfer($mov);
                    break;
                case 'Transport':
                    $this->handleTransport($mov);
                    break;
                case 'Attack':
                    $this->handleAttack($mov);
                    break;
                case 'Espionage':
                    $this->handleEspionage($mov);
                    break;
                case 'Return':
                    $this->handleReturn($mov);
            }
        }
    }


    /**
     * Deletes all movements which are ready for deletion / whoose handling is finished
     */
    protected function deleteFinishedMovements() {
        if(!empty($this->delMovs))
            $this->db->query('DELETE FROM movements WHERE id IN(' . implode(', ', $this->delMovs) . ')');
        if(!empty($this->delTroops))
            $this->db->query('DELETE FROM troops WHERE id IN(' . implode(', ', $this->delTroops) . ')');
    }


    /**
     * Handles Transfermovements
     * delivering cargo & transfering troops
     * @param <type> $mov
     */
    protected function handleTransfer($mov){
        
        $gold = $mov->gold;
        $wood = $mov->wood;
        $stone = $mov->stone;
        $iron = $mov->iron;
        $destX = $mov->destX;
        $destY = $mov->destY;

        //deliver cargo
        if(!empty($gold) OR !empty($wood) OR !empty($stone) OR !empty($iron))
            $this->db->query("UPDATE towns
                              SET gold = gold + {$gold},
                                  wood = wood + {$wood},
                                  stone = stone + {$stone},
                                  iron = iron + {$iron}
                              WHERE x = {$destX}
                                AND y = {$destY}");

        //deliver troops
        $troops = $this->db->query("SELECT * FROM troops WHERE movement_id = {$mov->id}");      //get troops
        $town   = $this->db->query("SELECT id FROM towns WHERE x = $destX AND y = $destY");
        $town   = $town[0]->id;
        
        foreach($troops as $troop){
            //add troops to town
            $this->db->query("INSERT INTO units (unit_id, town_id, quantity) VALUES ($troop->unit_id, $town, $troop->quantity)
                                ON DUPLICATE KEY UPDATE quantity = quantity + $troop->quantity");
            //FIXME: Bei Einheiten transfer wird überschrieben statt geadded
        }
        $this->delMovs[] = $mov->id;
    }


    /**
     * Handles transport movements
     * delivering cargo & requesting return
     * @param <type> $mov
     */
    protected function handleTransport($mov) {
        $gold   = $mov->gold;
        $wood   = $mov->wood;
        $stone  = $mov->stone;
        $iron   = $mov->iron;
        $destX  = $mov->destX;
        $destY  = $mov->destY;

        //deliver Cargo
        if(!empty($gold) OR !empty($wood) OR !empty($stone) OR !empty($iron))
            $this->db->query("UPDATE towns
                              SET gold = gold + $gold,
                                  wood = wood + $wood,
                                  stone = stone + $stone,
                                  iron = iron + $iron
                              WHERE x = $destX
                                AND y = $destY");

        //create return
        $mov->gold  = 0;
        $mov->wood  = 0;
        $mov->stone = 0;
        $mov->iron  = 0;
        $this->makeReturnMovement($mov);
        $this->delMovs[] = $mov->id;
    }


    /**
     * Handles attack movements
     * uses combatsystem classes to initate the fight
     * @param <type> $mov
     */
    protected function handleAttack($mov){
        //TODO: getDestinationtown benutzen...

        //Get Destination Town
        $res  = $this->db->query("SELECT user_id, id
                                  FROM towns
                                  WHERE x = {$mov->destX} and y = {$mov->destY}");
        $atter      = $mov->owner;
        $deffer     = $res[0]->user_id;
        $location   = $res[0]->id;

        //get atter & deffer army
        $attUnits   = $this->db->query("SELECT unit_id as id, quantity 
                                        FROM troops
                                        WHERE movement_id = {$mov->id}");
        $defUnits   = $this->db->query("SELECT unit_id as id, quantity
                                        FROM units 
                                        WHERE town_id = {$location}");
        //init classes for combatsystem
        $attArmy    = new AttackerArmy($atter, $attUnits);
        $defArmy    = new DefenderArmy($deffer, $defUnits);
        $battle     = new Battle($attArmy, $defArmy);
        $report     = new BattleReport($battle, $attArmy, $defArmy);
        $report->initReport($attUnits, $defUnits);
        $battle->startBattle();

        //add Booty to movement if attacker won
        if($battle->getWinner() == Battle::ATTACKERWINS){
            $battle->pillage($location);
            $booty = $battle->getBooty();
            $mov->gold += $booty['gold'];
            $mov->wood += $booty['wood'];
            $mov->stone += $booty['stone'];
            $mov->iron += $booty['iron'];
        }

        //Battle report
        $report->finishReport();
        $report->sendReport();      

        //Save changes to db
        $attArmy->save($mov->id);
        $defArmy->save($location);

        //Request Return if attacker was not defeated
        if(!$attArmy->isDefeated())
            $this->makeReturnMovement($mov);
        $this->delMovs[] = $mov->id;
    }


    /**
     * Handles espionage movements
     * uses espionage classes for success & information calculations
     * @param <type> $mov
     */
    protected function handleEspionage($mov){
        //get informations abaout destination & victim
        $town   = $this->getDestinationTown($mov->destX, $mov->destY);
        $doer   = ORM::factory('user', $mov->owner);
        $victim = ORM::factory('user', $town->user_id);

        //init espionage
        $espionage = new Espionage($doer, $victim, $town);
        $report = $espionage->getReport($espionage->isSuccessful());

        //create message for report
        $msg = ORM::factory('message');
        $msg->title    = 'Spiobericht';
        $msg->text     = $report;
        $msg->sender   = 0;
        $msg->receiver = $mov->owner;
        $msg->save();
        //TODO: evtl als standart Nachricht auslagern

        //TODO: Check, kein return wenn espionage entdeckt wurde
        //Request return
        $this->makeReturnMovement($mov);
        $this->delMovs[] = $mov->id;
        
    }


    /**
     * Handles return movements
     * uses the handleTransfer method as return mostly equals transfer
     * @param <type> $mov
     */
    protected function handleReturn($mov){
        $this->handleTransfer($mov);
        $this->db->query("UPDATE towns
                              SET movCapUsed = movCapUsed +1
                              WHERE x = {$mov->destX}
                                AND y = {$mov->destY}");
    }



    /**
     * returns the town with given coordinates
     * @param <type> $x
     * @param <type> $y
     * @return <type>
     */
    protected function getDestinationTown($x, $y){
        $res = ORM::factory('town')->where(array('x'=>$x, 'y'=>$y))->find();
        return $res;
    }


    /**
     * Generates a return movement for given movement
     * @param <type> $mov
     */
    protected function makeReturnMovement($mov){
        $actions= kohana::config('crmGame.movementTypes');
        $return = ORM::factory('movement');
        $return->gold   = $mov->gold;
        $return->wood   = $mov->wood;
        $return->stone  = $mov->stone;
        $return->iron   = $mov->iron;
        $return->action = array_search("Return", $actions);
        $return->startX = $mov->destX;
        $return->startY = $mov->destY;
        $return->destX  = $mov->startX;
        $return->destY  = $mov->startY;
        $return->duration   = $mov->duration;
        $return->arrival    = $mov->duration;
        $return->owner      = $mov->owner;
        $return->save();

        $id     = $mov->id;
        $newId  = $return->id;
        $this->db->query("UPDATE troops SET movement_id = $newId WHERE movement_id = $id");
    }

}
?>
