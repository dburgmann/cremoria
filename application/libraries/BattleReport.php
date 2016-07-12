<?php
/**
 * Provides function for generation of Battlereports
 */
class BattleReport{
    private $attacker = null;
    private $defender = null;
    private $battle   = null;

    private $report   = '';
    private $title    = '';


    /**
     * Constructor
     * @param <battle> $battle
     * @param <AttackerArmy> $attacker
     * @param <DefenderArmy> $defender
     */
    public function __construct($battle, $attacker, $defender){
        $this->battle   = $battle;
        $this->attacker = $attacker;
        $this->defender = $defender;
    }


    /**
     * Starts the report with initial Data
     * @param <type> $attUnits
     * @param <type> $defUnits
     */
    public function initReport($attUnits, $defUnits){
        $attUser = ORM::factory('user', $this->attacker->getOwner())->username;
        $defUser = ORM::factory('user', $this->defender->getOwner())->username;
        $this->title   = "Kampfbericht {$attUser} VS {$defUser}";
        $this->report  = "<h2>Kampfbericht</h3>
                          <p>{$attUser} VS {$defUser} </p>";
        $attTable      = $this->generateStartTable($attUnits);
        $defTable      = $this->generateStartTable($defUnits);
        $this->report .= "<p>{$attUser}s Armee:</p> $attTable <br />";
        $this->report .= "<p>{$defUser}s Armee:</p> $defTable <br />";
    }
    
    /**
     * Generates the first tables showing the fighting units
     * @param <type> $units
     * @return <type>
     */
    private function generateStartTable($units){
        $th = $td = $table ='';
        foreach($units as $unit){
            $unitData = IniORM::factory('unit')->select(array('name'))->find($unit->id);
            $no       = $unit->quantity;
            $th .= "<th>{$unitData->name}</th>";
            $td .= "<td>{$no}</td>";
        }
        $table = "<table class=\"reportTable\">
                    <tr>{$th}</tr>
                    <tr>{$td}</tr>
                  </table>";
        return $table;
    }


    


    /**
     * Generates the table showing the casualties
     */
    private function generateCasualtiesTable($units){
        $th = $td = $table ='';
        foreach($units as $unit){           
            $th .= "<th>{$unit->name}</th>";
            $td .= "<td>{$unit->quantity}</td>";
        }
        $table = "<table class=\"reportTable\">
                    <tr>{$th}</tr>
                    <tr>{$td}</tr>
                  </table>";
        return $table;
    }

    /**
     * Closes the report with Data from the battle
     */
    public function finishReport(){
        $attCasualties  = $this->attacker->getCasualties();
        $defCasualties  = $this->defender->getCasualties();
        $attDamage      = $this->attacker->getDamage();
        $defDamage      = $this->defender->getDamage();
        $winner         = $this->battle->getWinner();
        $rounds         = $this->battle->getRounds();
        $booty          = $this->battle->getBooty();
        $attTable = $this->generateCasualtiesTable($attCasualties);
        $defTable = $this->generateCasualtiesTable($defCasualties);
        if($winner == Battle::ATTACKERWINS)
            $winner = "Angreifer";
        elseif($winner == Battle::DEFENDERWINS)
            $winner = "Verteidiger";
        else
            $winner = "Unentschieden";

        $this->report .= "Verluste: <br />";
        $this->report .= "<p>Angreifer:</p> $attTable <br />";
        $this->report .= "<p>Verteidiger:</p> $defTable <br />";
        $this->report .= "Gewinner: $winner <br />";
        $this->report .= "Beute: <br />";
        $this->report .= "Gold: {$booty['gold']}, Holz: {$booty['wood']}, Stein: {$booty['stone']}, Eisen: {$booty['iron']}";
    }





    /**
     * Sends the report
     */
    public function sendReport(){
        $msgAtt = ORM::factory('message');
        $msgAtt->title     = $this->title;
        $msgAtt->text      = $this->report;
        $msgAtt->sender    = 0;

        $msgDef  = clone $msgAtt;
        $msgDef->receiver   = $this->defender->getOwner();
        $msgDef->save();

        $msgAtt->receiver   = $this->attacker->getOwner();
        $msgAtt->save();

    }
}
?>
