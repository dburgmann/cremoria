<?php
/**
 * Base class of all controllers of ingame Pages
 */
abstract class ControllerIngame extends ControllerProtection{
	protected $user;
	protected $activeTown;
	
	/**
	 * Constructor, initialises basic data needed on every page:
	 * the user and the active town
	 */
	public function __construct(){
		parent::__construct();
        //check for ajax request
        if (request::is_ajax()) {
            $this->auto_render = FALSE;
            header('content-type: application/json');
        }
		
		//netcup bugfix
		include_once('/var/www/web289/html/bugfix.php');		
		
        $this->template->js[] = 'js/messages';
        $this->user       = $this->auth->get_user();
      	$this->activeTown = ORM::factory('town', $this->session->get('town', $this->user->towns[0]->id));
	}


	/**
	 * Sets the active Town to the town of the given id
	 * @param $townId
	 * @return unknown_type
	 */
	public function setTown($townId = null){
        if($townId == null)
            $townId = $this->input->post('townId', null);       

        if(!empty($townId)){
            foreach($this->user->towns as $town){
                if($town->id == $townId){
                    $this->activeTown = ORM::factory('town', $townId);
                    $this->session->set('town', $this->activeTown->id);
                    url::redirect(substr(url::current(), 0, -8));
                }
            }
        }
  		$this->index();
	}
	
	/**
	 * Retrives Data which is used by the view of every Page
	 */
	public function index(){
		$this->template->info 				= new View('resBar');
		$this->template->navi 				= new View('naviIn');
        
		$this->template->info->gold 	= $this->activeTown->gold;
		$this->template->info->wood 	= $this->activeTown->wood;
		$this->template->info->stone 	= $this->activeTown->stone;
		$this->template->info->iron 	= $this->activeTown->iron;
        $this->checkMessages();
        
        $selection = array();
        foreach($this->user->towns as $town){
            $selection[$this->activeTown->id] = $this->activeTown->name.' ['.$this->activeTown->x.'|'.$this->activeTown->y.']';
            if($town->id != $this->activeTown->id){
                $selection[$town->id] = $town->name.' ['.$town->x.'|'.$town->y.']';
            }
        }
        $this->template->navi->townSelect    = $selection;
	}

    /**
     * Checks if new messages are received
     */
    private function checkMessages(){
        $noUnread  = ORM::factory('message')->where(array('receiver' => $this->user->id, 'unread' => true))->count_all();
        $this->template->info->unreadMessages = ($noUnread > 0)? true : false;
    }
}
?>