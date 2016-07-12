<?php
/**
 * Controller of the research page.
 * Prepares all data concerning the research.
 */
class Research_Controller extends ControllerIngame {
	public $template = 'main';
			
	public function __construct(){
		parent::__construct();
		$this->rscHandler 				= new ResearchHandler($this->user, $this->activeTown);
		$this->template->content 		= new View('researchCont');
		$this->template->content->error = '';
	}
	
	
	/**
	 * Retrieves data about allowed researches and the current research
	 */
	public function index(){
		parent::index();
		$this->template->content->techs 			= $this->rscHandler->getAllowed();
        $this->template->content->currentTech		= IniORM::factory('tech',  $this->user->currentResearch);
        $this->template->content->currentTech->time = $this->user->researchTime;
		
        //Error Handling
        if(!isset($this->template->content->error) || empty($this->template->content->error)){
        	$this->template->content->error = $this->rscHandler->getError();
        	if($this->rscHandler->getLabs() == 0){
        		$this->template->content->error = 'prodNoLab';
        	}
        }       
	}

	
	/**
	 * Initiates a research process
	 * @param $techId
	 * @return unknown_type
	 */
	public function produce($techId){
		if(valid::numeric($techId)){
			$tech = IniORM::factory('tech', $techId);
            $this->rscHandler->produce($tech);
		}
		$this->index();
	}
}
?>