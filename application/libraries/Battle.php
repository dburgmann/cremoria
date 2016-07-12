<?php

/**
 * Provides functions for calculationg battles
 */
class Battle{
    const ATTACKERWINS = 0;
    const DEFENDERWINS = 1;
    const DRAW         = 2;    

    //attackers Army
    private $attacker   = NULL;
    //defenders Army
    private $defender   = NULL;
    //number of battlerounds
    private $rounds     = 0;
    //winner of the battle
    private $winner     = 2;
    //booty of the battle
    private $booty      = array("gold"  => 0,
                                "wood"  => 0,
                                "stone" => 0,
                                "iron"  => 0);

    /**
     * Constructor
     * @param <AttackerArmy> $attacker
     * @param <DefenderArmy> $defender
     */
    public function __construct($attacker, $defender){
        $this->attacker = $attacker;
        $this->defender = $defender;
        $this->rounds   = kohana::config('crmGame.battleRounds');
    }



    /**
     * initiates a battle
     */
    public function startBattle(){
        //determine armypower in the beginning
        $startPowerAtt = $this->attacker->getPower();
        $startPowerDef = $this->defender->getPower();

        //fight rounds
        for($i = 1; $i <= $this->rounds; $i++){
            if($this->attacker->isDefeated() OR $this->defender->isDefeated())
                break;            
            $this->fightRound();
        }

        //determine armypower at the end
        $endPowerAtt = $this->attacker->getPower();
        $endPowerDef = $this->defender->getPower();

        $this->determineWinner($startPowerAtt, $endPowerAtt, $startPowerDef, $endPowerDef);   
    }

    

    /**
     * processes a battle round
     */
    private function fightRound(){
        //start new round
        $this->attacker->startNewRound();
        $this->defender->startNewRound();

        //attack & defend until round is finished
        while(!($this->attacker->roundFinished() && $this->defender->roundFinished())){
            if(!$this->attacker->roundFinished()){
                $this->defender->defend($this->attacker->attack());
            }

            if(!$this->defender->roundFinished()){
                $this->attacker->defend($this->defender->attack());
            }
        }
    }



    /**
     * determines the winner based on percentual change of army power
     * @param float $difA   percentual change of the attackers power
     * @param float $difD   üercentual change of the defenders power
     * @return int  values of class constants for the winner
     */
    private function determineWinner($startPowerAtt, $endPowerAtt, $startPowerDef, $endPowerDef){
        if($startPowerDef == 1){
            $this->winner = self::ATTACKERWINS;
            return;
        }

        $difA = ($startPowerAtt - $endPowerAtt) / $startPowerAtt;
        $difD = ($startPowerDef - $endPowerDef) / $startPowerDef;

        if($difD > 0.5)
            $this->winner = self::ATTACKERWINS;
        elseif($difD < 0.5)
            $this->winner = self::DEFENDERWINS;
        else
            $this->winner = self::DRAW;
    }




    /**
     * calculate the pillage of ressources by the attacker
     * @param <town> $town
     */
    public function pillage($town){
        $return = array();
        $iron   = 0;
        $gold   = 0;
        $wood   = 0;
        $stone  = 0;

        $cap = $this->attacker->getCargoCapacity();
        $ironPart =  mt_rand(0, $cap);
        $goldPart =  mt_rand(0, $cap - $ironPart);
        $woodPart = $stonePart = ceil(($cap - $ironPart - $goldPart) * 0.5);

        $defTown = ORM::factory('town', $town);

        //Check if defender has enough iron, otherwise distribute to other ressources
        if($defTown->iron < $ironPart){         //Has not enough =>
            $iron = $defTown->iron;             //Take all
            $goldPart += ($ironPart - $iron);   //Add difference to gold to be taken
            $defTown->iron = 0;                 //Set iron of town to zero
        }else{
            $iron = $ironPart;
            $defTown->iron -= $ironPart;
        }

        if($defTown->gold < $goldPart){
            $gold = $defTown->gold;
            $stonePart += ($goldPart - $gold);
            $defTown->gold = 0;
        }else{
            $gold = $goldPart;
            $defTown->gold -= $goldPart;
        }

        if($defTown->stone < $stonePart){
            $stone = $defTown->stone;
            $woodPart += ($stonePart - $stone);
            $defTown->stone = 0;
        }else{
            $stone = $stonePart;
            $defTown->stone -= $stonePart;
        }

        if($defTown->wood < $woodPart){
            $wood = $defTown->wood;
            $defTown->wood = 0;
        }else{
            $wood = $woodPart;
            $defTown->wood -= $woodPart;
        }

        $this->booty['gold']     = $gold;
        $this->booty['wood']     = $wood;
        $this->booty['stone']    = $stone;
        $this->booty['iron']     = $iron;
        $defTown->save();
    }

    /**
     *@return <int> No of Rounds
     */
    public function getRounds(){
        return $this->rounds;
    }
    /**
     * @return <int> Winner of Battle (see Constants)
     */
    public function getWinner(){
        return $this->winner;
    }
    /**
     * @return <int> Booty of the battle
     */
    public function getBooty(){
        return $this->booty;
    }

}
?>
