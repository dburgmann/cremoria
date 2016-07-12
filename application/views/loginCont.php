<?php
	if(!empty($error)){
		echo error::display($error);	
	}
	
	echo form::open('login/login', array('id'=>'loginForm')).' <br> ';
	echo form::label('name', 'Name:')						.' <br> ';
	echo form::input('name')								.' <br> ';
	echo form::label('password', 'Passwort:')				.' <br> ';
	echo form::password('password')							.' <br> ';
	echo form::label('remember', 'Eingeloggt bleiben:');
	echo form::checkbox('remember', 1, FALSE)				.' <br> ';
	echo form::submit('submit', 'Login')					.' <br> ';
	echo form::close();
?>