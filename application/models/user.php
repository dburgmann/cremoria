<?php
class User_Model extends Auth_User_Model{
	protected $has_and_belongs_to_many = array('roles');
	protected $has_many = array('towns', 'techs', 'userTokens', 'movements', 'messages');
}
?>