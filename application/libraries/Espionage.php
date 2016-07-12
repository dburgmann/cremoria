<?php

/**
 * Espionage
 * Handles the espionage process
 */
class Espionage{
    private $doer = NULL;
    private $victim = NULL;
    private $destTown = NULL;


    /**
     * Constructor
     * @param <user> $doer
     * @param <user> $victim
     * @param <town> $destTown
     */
    public function __construct($doer, $victim, $destTown){
        $this->doer     = $doer;
        $this->victim   = $victim;
        $this->destTown = $destTown;
    }


    /**
     * Checks if espionage is successful
     * @return <boolean>
     */
    public function isSuccessful(){
        $doerPower    = $this->getSpioPower($this->doer);
        $victimPower  = $this->getDiscoverPower($this->destTown);
        $dif          = $victimPower - $doerPower;
        $successChance = eval(kohana::config('crmGame.spioSuccessFormula'));
        $rand = mt_rand(0, 100);
        if($rand < $successChance) return true;
        else return false;
    }


    /**
     * Generates the espionage report
     * @param <boolean> $success
     * @return <string00>
     */
    public function getReport($success){
        if($success){
            $report = new View('espionageReportSuccess');
            $report->gold  = $this->destTown->gold;
            $report->wood  = $this->destTown->wood;
            $report->stone = $this->destTown->stone;
            $report->iron  = $this->destTown->iron;
            $report->units = $this->destTown->units;

            return $report->render();
        }
        else{
            $report = new View('espionageReportNoSuccess');
            return $report->render();
        }
    }


    /**
     * Calculates the espionage power of given user
     * @param <user> $user
     * @return <int>
     */
    private function getSpioPower($user){
        $no = 0;
        $bldId = kohana::config('crmItems.spioBld');
        foreach($user->towns as $town){
            $town = $town->quantityOfBuilding($bldId);
            $no     = ($townNo > $no)? $townNo : $no;
        }
        return $no;
    }


    /**
     * Calculates the espionage discover power of the given town
     * @param <town> $town
     * @return <int>
     */
    private function getDiscoverPower($town){
        $bldId = kohana::config('crmItems.spioBld');
        return $town->quantityOfBuilding($bldId);;
    }

}
?>
