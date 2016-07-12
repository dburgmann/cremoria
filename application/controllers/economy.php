<?php
/**
 * Controller of the economy page
 * shows data about the resource income and lets the user change
 * the rate of iron production
 */
class Economy_Controller extends ControllerIngame {
	public $template = 'main';

    /**
     * Constructor
     */
	public function __construct(){
		parent::__construct();
		$this->template->content	= new View('economyCont');
		$this->template->content->error = '';	
	}
	
	/**
	 * Retrieves the data to show all incomes
	 */
	public function index(){
		parent::index();	
		$this->template->content->goldInc 	= $this->activeTown->goldInc;
		$this->template->content->woodInc 	= $this->activeTown->woodInc;
		$this->template->content->stoneInc  = $this->activeTown->stoneInc;
		$this->template->content->ironInc 	= $this->activeTown->ironInc;
		$this->template->content->ironRate	= $this->activeTown->ironRate;
		$this->template->content->ironIncMax = $this->activeTown->ironIncMax;	
	}
	
	/**
	 * Changes the rate of the iron production
	 */
	public function adjustRate(){
		if($this->input->post('submit')){
			$newRate = $this->input->post('ironRate');
			if(!valid::numeric($newRate) || !(0 <= $newRate && $newRate <= 100)){
				$this->template->content->error = 'ecoInvalidRate';					
			}
			else{
                /*
                 *FIXME: Änderung zur Cheat verhinderung:
                 *Wood & Stone income komplett neu berechnen
                 * (Anzahl Gebs holen und mit formel income berechnen)
                 *und entsprechenden Malus durch eisen prod abziehen.
                 */
                $noWoodBld  = $this->activeTown->quantityOfBuilding(Kohana::config('crmItems.woodBld'));
                $noStoneBld = $this->activeTown->quantityOfBuilding(Kohana::config('crmItems.stoneBld'));
                
                $no         = $noWoodBld;
                $woodInc    = eval(Kohana::config('crmGame.woodIncomeFormula'));

                $no         = $noStoneBld;
                $stoneInc   = eval(Kohana::config('crmGame.stoneIncomeFormula'));
				//$oldIronInc 	 = $this->activeTown->ironInc;
				$ironWoodFactor  = Kohana::config('crmGame.ironToWood');
				$ironStoneFactor = Kohana::config('crmGame.ironToStone');
				
				//$oldWoodMalus  = $ironWoodFactor * $oldIronInc;
				//$oldStoneMalus = $ironStoneFactor * $oldIronInc;
				
				$ironInc 	   = $this->activeTown->ironIncMax * ($newRate / 100);
				$newWoodMalus  = $ironWoodFactor * $ironInc; 
				$newStoneMalus = $ironStoneFactor * $ironInc;
		
				$this->activeTown->ironRate = $newRate;
				$this->activeTown->ironInc  = $ironInc;
				$this->activeTown->woodInc	= $woodInc  - $newWoodMalus;
				$this->activeTown->stoneInc	= $stoneInc - $newStoneMalus;
				$this->activeTown->save();
			}
		}
		$this->index();		
	}
	
}
?>