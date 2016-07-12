<?php
/**
 * Movements Controller
 * Controlls Movement creation
 */
class Movements_Controller extends ControllerIngame {
    public $template = 'main';
    private $error = '';


    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();        
        $this->template->content = new View('movements');
        $this->template->content->error = '';
        $this->template->js[]    = "js/movements";
    }


    /**
     * Constructs standard view, showing all units for army selection and offers
     * predefined coordinates as target
     * @param <type> $x predefined coordinates if exist
     * @param <type> $y predefined coordinates if exist
     */
    public function index($x = 0, $y = 0) {
        parent::index();
        $this->validateMovementCap();

        if($this->validateMovementCap()){
            $this->template->content->units  = IniORM::factory('unit')->addObjectData(array('quantity'))->setIdAttribute('unit_id')->findAll($this->activeTown->units);
            $this->template->content->startX = $this->activeTown->x;
            $this->template->content->startY = $this->activeTown->y;
            $this->template->content->destX  = $x;
            $this->template->content->destY  = $y;
        }
        $this->template->content->error  = $this->error;
    }


    /**
     * Works on submitted Data of send army form from movements view
     * initiates validation
     * generates movement object
     */
    public function generateMovement(){        
        if($this->input->post('submit')){
            $units = $this->formatUnitPostData($this->input->post('units'));    //reading & formatting submitted data
            $startX= $this->activeTown->x;
            $startY= $this->activeTown->y;
            $destX = $this->input->post('destX');
            $destY = $this->input->post('destY');
            $cargo['gold']  = $this->input->post('gold');
            $cargo['wood']  = $this->input->post('wood');
            $cargo['stone'] = $this->input->post('stone');
            $cargo['iron']  = $this->input->post('iron');

            $action= $this->input->post('action');
            if($this->validateUnits($units))                                    //validation
                if($this->validateCoords($destX, $destY))                       //TODO: ifs durch && verknüpfen => nur noch 1 if
                    if($this->validateCargo($units, $cargo))
                        if($this->validateAction($action)){
                            $mov = ORM::factory('movement');                    //generation of movement object
                            $mov->owner     = $this->user->id;
                            $mov->startX    = $this->activeTown->x;
                            $mov->startY    = $this->activeTown->y;
                            $mov->destX     = $destX;
                            $mov->destY     = $destY;
                            $mov->action    = $action;
                            $mov->gold      = $cargo['gold'];
                            $mov->wood      = $cargo['wood'];
                            $mov->stone     = $cargo['stone'];
                            $mov->iron      = $cargo['iron'];
                            $mov->duration  = $this->getMovementTime($units, $startX, $startY, $destX, $destY);
                            $mov->arrival   = $mov->duration;
                            $mov->save();
                            
                            $this->activeTown->gold  -= $cargo['gold'];         //committing resulting changes to active town
                            $this->activeTown->wood  -= $cargo['wood'];
                            $this->activeTown->stone -= $cargo['stone'];
                            $this->activeTown->iron  -= $cargo['iron'];
                            $this->activeTown->movCapUsed += 1;
                            $this->activeTown->save();                           
                            $this->setMovementTroops($units, $mov);                            
                        }
        }
        $this->index();
    }

    /**
     * Formats the Post Data from the unit selection Form for further use.
     * Eliminates not selected Units.
     * @param <array> $units     Post Data Array from unit selection form
     * @return <array>           Array of unit objects with ini information
     */
    private function formatUnitPostData($units){
        //Remove Units with quantity = 0  & change Format
        $result = array();
        $i      = 0;
        foreach($units as $id => $quantity){
            if($quantity > 0){
                    $result[$i]              = new stdClass();
                    $result[$i]->id          = $id;
                    $result[$i]->quantity    = $quantity;
            }
            $i++;
        }
        
        //Add Ini Data
        $units = IniORM::factory('unit')->addObjectData(array('quantity'))->findAll($result);
        return $units;
    }


    /**
     * Checks if unit selection is valid
     * @param <array> $units     formatted unit object array from formatUnitPostData()
     * @return <boolean>
     */
    private function validateUnits($units){
        //Check if any units were selected
        if (empty($units)){
            $this->error = 'movNoUnits';
            return false;
        }

        foreach($units as $unit){
            $unitORM = ORM::factory('unit')->where(array('town_id'=>$this->activeTown->id, "unit_id"=>$unit->id, "quantity >="=>$unit->quantity))->find();
            if(!$unitORM->loaded OR $unit->quantity > $unitORM->quantity){
                $this->error ='movNotEnoughUnits';
                return false;
            }
        }
        return true;        
    }


    /**
     * Checks if given coordinates are valid
     * @param <int> $x
     * @param <int> $y
     * @return <boolean>
     */
    private function validateCoords($x, $y){
        $mapSize = kohana::config('crmGame.mapSize');
        if($mapSize-1 < $x  OR
           $mapSize-1 < $y  OR
           0 > $x           OR
           0 > $y           OR
           empty($x)        OR
           empty($y)        ){
            $this->error = 'movInvalidCoord';
            return false;
        }

        //check if town exists
        $town = ORM::factory('town')->where(array('x'=>$x, 'y'=>$y))->find();
        if(!$town->loaded){
            $this->error = 'movInvalidCoord';
            return false;
        }

        return true;
    }


    /**
     * Checks if there is enough movementcap to create a new movement
     * @return <boolean>
     */
    private function validateMovementCap(){
        if($this->activeTown->movCapUsed >= $this->activeTown->movCap){
            $this->error='movNotEnoughCap';
            return false;
        }
        return true;
    }

    /**
     * Checks if the choosen movement action is valid
     * @param <int> $action
     * @return <boolean>
     */
    private function validateAction($action){
        $actions = kohana::config('crmGame.movementTypes');
        if(!isset($actions[$action])){
            $this->error = 'movInvalidAction';
            return false;
        }
        return true;
    }

    /**
     * Checks if cargo selection is valid.
     * Checks the cpacity of the army and ressources of the town
     * @param <type> $units
     * @param <type> $cargo
     * @return <type>
     */
    private function validateCargo($units, $cargo){
        //Check for valid input
        foreach($cargo as $res){
            if(!is_numeric($res) OR $res < 0){
                $this->error = 'movInvalidCargoInput';
                return false;
            }
        }

        //Check for enough Capacity
        $maxCap  = $this->getArmyCapacity($units);
        $usedCap = 0;
        foreach($cargo as $amount)
            $usedCap += ($amount > 0) ? $amount : 0; //Exclude negative cargo values

        if ($usedCap > $maxCap){
            $this->error = 'movNotEnoughCapacity';
            return false;;
        }

        //Check if town has enough res
        if($this->activeTown->gold < $cargo['gold'] OR
           $this->activeTown->wood < $cargo['wood'] OR
           $this->activeTown->stone < $cargo['stone'] OR
           $this->activeTown->iron < $cargo['iron']){
            $this->error = 'movNotEnoughRes';
            return false;
        }
        return true;
    }


    /**
     * Determines the capacity of a army
     * @param <array> $units        army
     * @return <boolean>
     */
    private function getArmyCapacity($units){
        $capacity = 0;
        foreach ($units as $unit)
            $capacity += $unit->cp;
        return $capacity;
    }


    /**
     * Determines the speed of a army
     * @param <array> $units
     * @return <boolean>
     */
    private function getArmySpeed($units){
        $min = 10000;
        foreach ($units as $unit) {
            $min = ($unit->sp < $min) ? $unit->sp : $min;
        }
        return $min;
    }


    /**
     * Determines the duration of a movement
     * @param <array> $units   Army
     * @param <int> $startX    Start x-coordinate
     * @param <int> $startY    Start y-coordinate
     * @param <int> $destX     Destination x-coordinate
     * @param <int> $destY     Destination y-coordinate
     * @return <int>
     */
    private function getMovementTime($units, $startX, $startY, $destX, $destY){
        $distX = $startX - $destX;
        $distY = $startY - $destY;
        $dist  = sqrt(($distX * $distX) + ($distY * $distY));
        $sp    = $this->getArmySpeed($units);
        $calced= eval(kohana::config('crmGame.movementTimeFormula'));
        return $calced;
    }


    /**
     * Sets the troops of a movement, saves to db
     * @param <array> $units        troops
     * @param <movement> $movement
     */
    private function setMovementTroops($units, $movement){     
        foreach ($units as $unit) {
            $unitORM = ORM::factory('unit')->where(array('town_id'=>$this->activeTown->id, "unit_id"=>$unit->id))->find(); //TODO: im ganzen projekt bei unit model id zu unit_id ändern

            if($unitORM->quantity == $unit->quantity)
                $unitORM->delete();
            else{
                $unitORM->quantity -= $unit->quantity;
                $unitORM->save();
            }
           
            $troop = ORM::factory('troop');
            $troop->quantity = $unit->quantity;
            $troop->unit_id = $unit->id;
            $troop->movement_id = $movement->id;
            $troop->save();
        }
    }
}
?>