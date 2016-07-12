<?php
/**
 * Base class of all controllers whoose pages should be only
 * visible to logged in users 
 */
abstract class ControllerProtection extends ControllerCremoria{

	/**
	 * Checks if the user is logged in
	 */
	public function __construct(){
		parent::__construct();
		
		$this->session 	= Session::instance();
        $this->auth 	= Auth::instance();
        $this->auth->auto_login();
             
        if (!$this->auth->logged_in()){
	    	url::redirect('/login/');
        }
	}
}
?>