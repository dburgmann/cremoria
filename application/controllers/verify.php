<?php
/**
 * Verify Controller
 * controlls verification of user registrations
 */
class Verify_Controller extends ControllerCremoria{
	public $template ='main';
	

    /**
     * Checks if the given regKey is valid and initiates activation
     * @param <string> $regKey
     * @return <void>
     */
	public function index($regKey = null){
		$this->template->info 		= '';
		$this->template->navi 		= new View('naviOut');
		$this->template->content	= new View('verifyCont');
		$this->template->content->error = '';
		
		if($regKey == null){
			$this->template->content->error = 'verifyCodeInvalid';
			return;
		}
		
		//Check ob Code existiert
		$user = ORM::factory('user')->where('regKey', $regKey)->find();
		if(!$user->loaded){
			$this->template->content->error = 'verifyCodeInvalid';
			return;			
		}
		else{
			$this->activateUser($user);
		}			
	}


	/**
	 * Activates the user with the given key
	 * @param $user
	 * @return unknown_type
	 */
	private function activateUser($user){
		$user->regKey = null;
		
		//Change Role
		$user->remove(ORM::factory('role', 'unverified'));
		$user->add(ORM::factory('role', 'login'));
		
		//Setup new existence
		$creator = new ExistenceCreator();
		$creator->createNewExistence($user);
		$user->save();
		url::redirect('login');
	}
}
?>