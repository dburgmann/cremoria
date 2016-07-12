<?php
	if(!empty($error)){
		echo error::display($error);	
	}
/*
    echo form::open('register/register', array("id" => "regForm"))   .' <br> ';
	echo form::label('name', 'Name:')			                     .' <br> ';
	echo form::input('name')					                     .' <br> ';
	echo form::label('password', 'Passwort:')	                     .' <br> ';
	echo form::password('password')				                     .' <br> ';
	echo form::label('passwordRep', 'Passwort wiederholen:')         .' <br> ';
	echo form::password('passwordRep')			                     .' <br> ';
	echo form::label('email', 'E-Mail:')		                     .' <br> ';
	echo form::input('email')					                     .' <br> ';
    echo form::label('captcha_response', 'Sicherheitsüberprüfung:')	 .' <br> ';
	echo $captcha->render()                                          .' <br> ';
	echo form::input('captcha_response')                             .' <br> ';
	echo form::submit('submit', 'Registrieren')	                     .' <br> ';
	echo form::close();*/
?>
<p> Die Registrierung ist momentan deaktiviert!</p>