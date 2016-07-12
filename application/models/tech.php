<?php
class Tech_Model extends ORM {
	protected $belongs_to = array('user');
	protected $table_name ='techs';
}
?>