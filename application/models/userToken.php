<?php
class UserToken_Model extends Auth_User_Token_Model {
	protected $belongs_to = array('user');
 }
?>