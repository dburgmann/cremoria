<?php
class Movement_Model extends ORM{
	protected $belongs_to = array(	'owner'=>array('foreign_key'=>'owner', 'model'=>'user'),
									'start'=>array('foreign_key'=>'start', 'model'=>'town')
								 );
	
	protected $has_many = array('troops');
}
?>