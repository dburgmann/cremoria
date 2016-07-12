<?php
class Role_Model extends Auth_Role_Model{
	protected $has_and_belongs_to_many = array('users');
}
?>