<?php defined('SYSPATH') OR die('No direct access allowed.');
/*
 * General Values
 */
$config['initGold']     = 300;
$config['initWood']     = 300;
$config['initStone']    = 300;
$config['initIron']     = 300;

//Iron is made of Stone & Wood.
//Define how many of each is needed for 1 Iron
$config['ironToStone']  = 2;
$config['ironToWood']   = 1;

//Size of the map (quadratic)
$config['mapSize']      = 100;

//Number of Rounds in each Battle
$config['battleRounds']         = 3;

//All movement types
$config['movementTypes']        = array('0' => "Transfer", '1' => "Transport", '2' => "Attack", '3' => "Espionage", '4' => "Return");

//movement types the user can choose from
$config['movementTypesView']    = array('0' => "Transfer", '1' => "Transport", '2' => "Attack", '3' => "Espionage");


/*
 * Formulas
 */

//Formulas to calculate the income, based on the number ($no) of buildings
$config['goldIncomeFormula']    = 'return 50 * $no * pow(1.05, $no);';
$config['woodIncomeFormula']    = 'return 50 * $no * pow(1.05, $no);';
$config['stoneIncomeFormula']   = 'return 50 * $no * pow(1.05, $no);';
$config['ironIncomeFormula']    = 'return 50 * $no * pow(1.05, $no);';


//Formula to calculate the building costs, based on the level ($no) of the building
//the result of the calculation is multiplied with the base cost.
$config['buildingCostFormula']  = 'return pow(1.25, ($no - 1));';

//Divider which is used to calculate the time a building will need
$config['buildingTimeDivider']  = 1000;


//Formula to calculate the time a research takes, based on the number of labs ($labs) and in case of repeatable
//techs on the level
//The result is multiplied with the base cost
$config['researchTimeFormula']  = 'return pow(0.95, $labs) * $no;';

//Formula to calculate the costs of repeatable researches
$config['researchCostFormula']  = 'return pow(1.35, ($no - 1));';


//Formula to calculate the unitCap based on the number of unit Buildings ($no)
$config['unitCapFormula']       = 'return 5 * $no;';


//Formula to calculate the duration (in Ticks) of a movement based on the distance ($dist) and armyspeed ($sp)
$config['movementTimeFormula']  = 'return (1 + $dist)*100 * 1 / $sp;';

//Formula to calculate the number of ticks a foreign army is seen before arrival based on number of outposts ($no)
$config['movRecogTimeFormula']        = 'return 3 + $no;';


//Formula to calculate the success chance of espionage based on the difference of Spiopower
$config['spioSuccessFormula']   = 'return 50 + ($dif * 5);';


//Formula to calculate the Damage of a Unit based on its ap
$config['damageFormula']        = 'return $ap * 10;';


//Formula to calculate the pts based on cost
$config['ptsFormula']           = 'return ($gold * 2 + $iron * 3 + $wood + $stone) / 100;';


?>