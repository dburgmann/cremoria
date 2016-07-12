<?php
class Building_Model extends ORM {
	protected $belongs_to = array('town');
	protected $table_name ='buildings';
}
?>