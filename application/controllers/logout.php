<?php
/**
 * Controller of the Logout page,
 * nothing is displayed there because it automatically
 * redirects the user to the login page
 */
class Logout_Controller extends ControllerCremoria{
	public $template ='main';
	
	/**
	 * Logs the current user out
	 */
	public function index(){
		$this->auth = Auth::instance();
		$this->template->info 		= '';
		$this->template->navi 		= '';
		$this->template->content	= '';	
		
		if($this->auth->logged_in()){
			$this->auth->logout();
		}
		url::redirect('login');
	}
}
?>