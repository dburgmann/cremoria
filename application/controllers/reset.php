<?php
class Reset_Controller extends ControllerIngame{
	public $template = 'main';
	
	public function __construct(){
		parent::__construct();
	}

	//FOR tests ONLY!!!
	public function index(){
		$db = Database::instance();
		
		$this->user->currentResearch 	= null;
		$this->user->researchTime	 	= null;
		$this->user->save(); 
		
		$db->query('UPDATE towns SET goldInc = 0, 
									 woodInc = 0,
									 stoneInc = 0,
									 ironInc = 0,
									 ironIncMax = 0,
									 ironRate = 0,
									 unitCap = 0,
									 currentBuilding = NULL,
									 buildingTime = Null,
									 gold = 300,
									 wood = 300,
									 stone= 300,
									 iron = 0');
		$db->query('TRUNCATE buildings');
		$db->query('TRUNCATE units');
		$db->query('TRUNCATE techs');
		$db->query('TRUNCATE unit_productions');
		
	}
}
?>