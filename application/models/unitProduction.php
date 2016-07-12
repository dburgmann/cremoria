<?php
class UnitProduction_Model extends ORM {
	protected $belongs_to = array('town');
	protected $table_name ='unit_productions';
}
?>