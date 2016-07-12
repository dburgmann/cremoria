<?php
class Message_Model extends ORM{
	protected $belongs_to = array(	'sender'=>array('foreign_key'=>'sender', 'model'=>'user'),
									'receiver'=>array('foreign_key'=>'receiver', 'model'=>'user')
								 );
}
?>