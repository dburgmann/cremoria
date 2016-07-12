<?php
/**
 * Controller for details pages,
 * shows detailed information about users and towns
 */
class Details_Controller extends ControllerIngame {
	public $template = 'main';

    /**
     * Constructor
     */
	public function __construct(){
		parent::__construct();
			
	}


    /**
     * Generates detail pages about users
     * @param <ind> $id     user id
     */
	public function user($id){
		$user = ORM::factory('user', $id);
		$this->template->content	= new View('userDetail');			
	}


    /**
     * Generates detail pages about towns
     * @param <int> $id     town id
     */
	public function town($id){
		$town = ORM::factory('town', $id);
		$this->template->content = new View('townDetail');
		$this->template->content->user	= $town->user;  	
		$this->template->content->town	= $town;
	}


	
}
?>