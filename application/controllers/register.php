<?php
/**
 * Controller of the register page.
 * Prepares view data and coordinates the register process.
 */
class Register_Controller extends ControllerCremoria{
	public $template ='main';

    /**
     * Constructor
     * Sets views and initilises Captcha system
     */
	public function __construct(){
		parent::__construct();
		$this->template->info 		= '';
		$this->template->navi 		= new View('naviOut');
        $this->template->content	= new View('registerCont');
		$this->template->content->error     = '';
        $this->template->content->captcha   = new Captcha();
	}
	
	
	/**
	 * Starts a registration process, using the post data submitted by
	 * the register form.
	 */
	public function register(){

        //Captcha Bot Check #1
        if ($this->template->content->captcha->invalid_count() > 49){
	        exit('Bye! Stupid bot.');
        }

		if($this->input->post('submit')){                                   //Check if Form was submitted
			$username	= $this->input->post('name');
			$password	= $this->input->post('password');
            $passwordRep= $this->input->post('passwordRep');
			$email		= $this->input->post('email');

            if (!Captcha::valid($this->input->post('captcha_response'))){   //Check Captcha
				$this->template->content->error = 'regWrongCaptcha';								
			}elseif(!$password == $passwordRep){                            //Check if Pws match
                $this->template->content->error = 'regNoPwMatch';
            }elseif(!$this->isValid($username, $password, $email)){         //Validate RegData
			}else{                                                          //Register User
				$code	 			= uniqid($username, true);
				$newUser			= ORM::factory('user');
				$newUser->username 	= $username;
				$newUser->email 	= $email;
				$newUser->password 	= $password;
				$newUser->regKey	= $code;
				$newUser->add(ORM::factory('role', 'unverified'));
				$newUser->save();
					$this->sendActivationEmail($code, $email);
			}
		}
		else{
			$this->template->content->error = 'regElementEmpty';
		}
		$this->index();
	}
	
	
	/**
	 * Validates the registration input
	 * 
	 * @param $username
	 * @param $password
	 * @param $email
	 * @return unknown_type
	 */
	private function isValid($username, $password, $email){
		$this->template->content->error = '';
		//check if needed information is missing
		if(empty($username) OR empty($password) OR empty($email)){
			$this->template->content->error = 'regElementEmpty';
			return false;
		}
		
		//Check email validity
		if(!valid::email($email)){
			$this->template->content->error =  'regInvalidEmail';
			return false;
		}
		
		//Check if only allowed digits are used
		if(!valid::alpha($username)){
			$this->template->content->error =  'regInvalidName';
			return false;
		}
		
		//check if a user with this name already exists
		$user = ORM::factory('user')->where('username', $username)->find();
		if($user->loaded == true){
			$this->template->content->error = 'regUserExists';
			return false;
		}
		
		//check if a user with this email already exists
		$user = ORM::factory('user')->where('email', $email)->find();
		if($user->loaded == true){
			$this->template->content->error = 'regEmailExists';
			return false;
		}
		
		return true;
	}
	
	
	/**
	 * Prepares and sends the email needed for activation of the account
	 * @param $code
	 * @param $email
	 */
	private function sendActivationEmail($code, $email){
		$domain	 	= kohana::config('config.site_domain', true);		
		$verifyLink = $domain."index.php/verify/index/".$code;
		
		echo $verifyLink;
		
		$to      = $email;
		$from    = 'Cremoria';
		$subject = Kohana::lang('general.regEmailSubject');
		$message = Kohana::lang('general.regEmailMessage', $verifyLink);		 
		//TODO Test email::send($to, $from, $subject, $message, TRUE);
	}
}
?>