<?php
/**
 * Army Class
 * Contains function for army actions during battles
 */
abstract class Army{
    //owner of the army
    protected $owner          = 0;
    //current fight round
    protected $round          = 0;
    //damage dealt in each round (Format $damageDealt[round] = damage)
    protected $damageDealt    = array();


    /**variables needed for battlecalculations **/
    //units in the army
    protected $units          = array();
    //number of units in the army = size of $units
    protected $initArmySize   = 0;
    //casualties in each round (Format $casualties[round][] = unit)
    protected $casualties     = array();
    //casualties counter
    protected $noCasualties   = 0;
    //attacker counter
    protected $nextAttacker   = 0;
    //shows if every unit has attacked (=> true) or not
    protected $roundFinished  = true;
    //shows if the army is defeated (= all units dead)
    protected $defeated       = false;


    public function __construct($owner, $units){
        $this->owner = $owner;        
        //reorganize unitdata
        foreach($units as $unit){
            $unitData = IniORM::factory('unit')->select(array('id', 'name', 'ap', 'hp', 'cp', 'sp'))->find($unit->id);
            for($i = 1; $i <= $unit->quantity; $i++){
                $this->units[] = clone $unitData;
                $this->initArmySize++;
            }
        } //TODO: evtl umschreiben in function addUnitFromDB oder so (bessere schnittstelle)
        usort($this->units, array('Army', 'sortUnits'));
    }

    /**
     * Sorting function for usort for sorting units
     * @param <unit> $a
     * @param <unit> $b
     * @return <int>
     */
    public static function sortUnits($a, $b){
        if($a->sp > $b->sp)
            return 1;
        elseif($a->sp < $b->sp)
            return -1;
        else
            return 0;
    }



    /**
     * determines the next attackin unit and calculates the damage it deals
     * @return int  damage
     */
    public function attack(){
        //check if attacking is allowed
        if($this->roundFinished == true OR $this->defeated)
            return;

        //check if unit chosen for attacking is alive otherwise determine next unit
        while(!isset($this->units[$this->nextAttacker])
        && ($this->initArmySize-1) > $this->nextAttacker){
             $this->nextAttacker++;
        }
        if(!isset($this->units[$this->nextAttacker])){
            $this->roundFinished = true;
            return;
        }

        //calculate Damage
        $ap     = $this->units[$this->nextAttacker]->ap;
        $damage = eval(kohana::config('crmGame.damageFormula'));

        //set next attacker, check if round is finished
        if($this->initArmySize-1 > $this->nextAttacker)
            $this->nextAttacker++;
        else
            $this->roundFinished = true;

        $this->damageDealt[$this->round] += $damage;
        return $damage;
    }



    /**
     * determines the defending unit and deals the damage to it
     * also checking if the army was defeated.
     * @param int $damage
     * @return -
     */
    public function defend($damage){
        //determine random Target
        $target = mt_rand(0, $this->initArmySize-1);

        //check if Target is alive -> otherwise determine new target
        if(!isset($this->units[$target])){
            $target = $this->initArmySize-1;
            while(!isset($this->units[$target]) && $target > 0){
                 $target--;
            }

            if(!isset($this->units[$target])){
                $this->defeated = true;
                $this->roundFinished = true;
                return;
            }
        }

        //deal damage & check death
        $this->units[$target]->hp -= $damage;
        if($this->units[$target]->hp <= 0){
            $uid = $this->units[$target]->id;
            if(!isset($this->casualties[$uid])){
                    $this->casualties[$uid]             = new stdClass ();
                    $this->casualties[$uid]->name       = $this->units[$target]->name;
                    $this->casualties[$uid]->quantity   = 0;
            }
            $this->casualties[$uid]->quantity++;
            unset($this->units[$target]); //TODO: check ob funzt (vorher unset)
            $this->noCasualties++;
        }

        //check if army was defeated
        if($this->noCasualties == $this->initArmySize){
            $this->defeated = true;
            $this->roundFinished = true;
        }
    }



    /**
     * determines the power of the army.
     * @return <type>
     */
    public function getPower(){
        $power = 0;
        foreach($this->units as $unit){
            //if(isset($unit)){
                $power += $unit->hp;
            //} //FIXME: if vermutlich nicht nötig
        }
        return $power + 1;
        //TODO: verbessern, ist zu crappy
    }



    /**
     * Resets counters to start a new attacking round.
     */
    public function startNewRound(){
        $this->roundFinished    = false;
        $this->nextAttacker     = 0;
        $this->round++;
        $this->damageDealt[$this->round] = 0;      
    }



    /**
     * calculates the cargocapacity of the army
     */
    public function getCargoCapacity(){
        
        $cap = 0;        
        foreach($this->units as $unit){
            //FIXME : evtl isset if nötig siehe getPower
            $cap += $unit->cp;
        }
        //FIXME: Momentan vorhandene Ladung abziehen
        return $cap;
    }


    /**
     * Save changes to Db
     */
    public function save(){}



    /**
     * @return int  id of the army owner
     */
    public function getOwner(){
        return $this->owner;
    }
    /**
     * @return int units of the army
     */
    public function getUnits(){
        return $this->units;
    }
    /**
     * @return ObjectArray  all casualties
     */
    public function getCasualties(){
        return $this->casualties;
    }
    /**
     * @return Array    Damage dealt each round
     */
    public function getDamage(){
        return $this->damageDealt;
    }
    /**
     * @return boolean  true when round is finished
     */
    public function roundFinished(){
        return $this->roundFinished;
    }
    /**
     * @return boolean  true when army is defeated
     */
    public function isDefeated(){
        return $this->defeated;
    }
}
?>
