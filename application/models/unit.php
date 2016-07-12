<?php
class Unit_Model extends ORM {
	protected $belongs_to = array('town');
	protected $table_name ='units';
}
?>