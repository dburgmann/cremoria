<?php defined('SYSPATH') OR die('No direct access allowed.');
/*
 * Definition of items with special effects on the game
 */


/*
 * Buildings
 */
$config['tickRelevantBlds'] 	= array(1, 2, 3, 4, 5, 10);
$config['goldBld']              = '1'; //gold production building
$config['woodBld']              = '2'; //wood production building
$config['stoneBld']             = '3'; //stone production building
$config['ironBld']              = '4'; //iron production building
$config['unitBld']              = '5'; //unit production building
$config['researchBld']          = '6'; //research building
$config['movRecogBld']          = '8'; //building that increases the recognizing of foreign movements
$config['movCapBld']            = '10';//building that increases the number of allowed army movements
$config['spioDefBld']           = '9'; //building that increases the defense against espionage

/*
 * Units
 */
$config['spioUnit']             = '9'; //unit for espionage

/*
 * Techs
 */
$config['spioOffTech']          = '25';//tech that increases espionage power
?>