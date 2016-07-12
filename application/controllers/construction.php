<?php
/**
 * Controller of the construction page
 * shows the buildings the user is allowed to build and
 * initiates building processes
 */
class Construction_Controller extends ControllerIngame {
	public $template = 'main';
		
	public function __construct(){
		parent::__construct();
		$this->consHandler 				= new ConstructionHandler($this->user, $this->activeTown);
		$this->template->content 		= new View('constructionCont');
		$this->template->content->error = '';
	}
	
	/**
	 * Retrieves data for displaying the current building
	 * and the buildings the user is allowed to build
	 */
	public function index(){
		parent::index();				
		$this->template->content->buildings 	= $this->consHandler->getAllowed();
        $this->template->content->currentBld	= IniORM::factory('building',  $this->activeTown->currentBuilding);
        $this->template->content->currentBld->time = $this->activeTown->buildingTime;
        
        if(!isset($this->template->content->error) || empty($this->template->content->error)){
        	$this->template->content->error = $this->consHandler->getError();
        }

	}

	/**
	 * Initiates the production of the given building
	 * 
	 * @param $buildingId
	 * @return unknown_type
	 */
	public function produce($buildingId){
		if(valid::numeric($buildingId)){
			$building = IniORM::factory('building', $buildingId);
            $this->consHandler->produce($building);
		}
		else{
			$this->template->content->error = 'prodInvalidItem';
		}
		$this->index();
	}
	
}
?>