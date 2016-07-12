<?php
/**
 * Controller of the login page,
 * Coordinates the login process 
 */
class Login_Controller extends ControllerCremoria{
	
	public $template ='main';
	
	public function __construct(){
		parent::__construct();
		$this->auth = Auth::instance();
		$this->template->info 		= '';
		$this->template->navi 		= new View('naviOut');
		$this->template->content	= new View('loginCont');
		$this->template->content->error = '';
	}
	
	/**
	 * Checks if the user is logged in an redirects him if necessary
	 */
	public function index(){		
		if($this->auth->logged_in()){
			url::redirect('construction');
		}
	}
	
	/**
	 * Logs a user in, using the post data submitted
	 * through the form
	 */
	public function login(){
		if($this->input->post('submit')){		
			$username 		= $this->input->post('name');
			$password 		= $this->input->post('password');
			$remember 		= (bool) $this->input->post('remember');
			
			if(empty($username) OR empty($password) OR !valid::standard_text($username)){
				$this->template->content->error	= 'loginWrongData';
			}
			else{		
				Auth::instance()->login($username, $password);				
				if(!$this->auth->logged_in()){
					$this->template->content->error	= 'loginWrongData';
				}
			}
		}
		$this->index();		
	}
}
?>