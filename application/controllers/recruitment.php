<?php
/**
 * Controller of the recruitment page,
 * prepares all data concerning the recruitment
 */
class Recruitment_Controller extends ControllerIngame{
	public $template = 'main';

    /**
     * Constructor
     */
	public function __construct(){
		parent::__construct();
		$this->recrHandler 				= new RecruitmentHandler($this->user, $this->activeTown);
		$this->template->content 		= new View('recruitmentCont');
		$this->template->content->error = '';
	}
	
	/**
	 * Retrieves Data about the units the user is allowed to recruit and
	 * the units he is currently recruiting
	 */
	public function index(){
		parent::index();
		$prod = $this->recrHandler->getProduction();
        $this->template->content->production        = $prod;
        $this->template->content->freeCap           = $this->activeTown->unitCap - $this->activeTown->unitCapUsed;
        $this->template->content->totalCap          = $this->activeTown->unitCap;
        $this->template->content->units 			= $this->recrHandler->getAllowed();
        
        //Error Handling
	    if(!isset($this->template->content->error) || empty($this->template->content->error)){
        	$this->template->content->error = $this->recrHandler->getError();
        	if($this->template->content->totalCap == 0){
        		$this->template->content->error = 'prodNoBarracks';
        	}
        	elseif(empty($this->template->content->units)){
        		$this->template->content->error = 'prodNoUnitsResearched';
        	}
        }
    }

	
    /**
     * Initiates the recruiting process, using the Post- data
     * submitted by the form 
     */
	public function produce(){
        $sum    = 0;
        $no     = 0;
        $id     = 0;
        $items  = array();
        $quantities = array();

        if($this->input->post('submit')){           
            $quantities = $this->input->post('quantity');
            if(is_array($quantities)){
                foreach($quantities as $id => $no){
                    if($no != 0 && valid::numeric($id) && valid::numeric($no)){
                        $sum                   += $no;
                        $items[$id]['item']     = IniORM::factory('unit', $id);
                        $items[$id]['quantity'] = $no;
                    }
                }
                if(!empty($items)){
                    $this->recrHandler->produce($items, $sum);
                }
            }
        }
		$this->index();
	}
}

?>