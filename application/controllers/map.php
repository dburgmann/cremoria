<?php
/**
 * Map Controller
 * Executes map controls, calculates borders
 */
class Map_Controller extends ControllerIngame {
	public $template = 'main';

    /**
     * Constructor
     * sets View
     */
	public function __construct(){
		parent::__construct();
		$this->template->content		= new View('map');
		$this->template->content->error = '';
		parent::index();	
	}
	

    /**
     * Calculates the standard view / coordinates
     */
	public function index(){		
		$max = kohana::config('crmGame.mapSize');
		$x = $this->activeTown->x;
		$y = $this->activeTown->y;
		$x = ($x < 5)? 5 : $x;
		$x = ($x > ($max-5))? max-5 : $x;
		
		$y = ($y < 5)? 5 : $y;
		$y = ($y > ($max-5))? $max-5 : $y;

		$this->show($x, $y);
	}


    /**
     * Reads post data to jump to given position
     */
    public function jump(){
        $this->show($this->input->post('x'), $this->input->post('y'));
    }


    /**
     * Shows the area around the given position
     * @param <int> $x  
     * @param <int> $y
     */
	public function show($x, $y){
        if(!$this->validateCoords($x, $y)){
            $x = 1;
            $y = 1;
        }

		$buttonCords = array();
		$max = kohana::config('crmGame.mapSize');
		$towns = ORM::factory('town')->where(array('x <=' => $x+5, 'x >=' => $x-5, 'y <=' => $y+5, 'y >=' => $y-5))
									 ->find_all();
        $x = ($x < 5)? 5 : $x;
        $y = ($y < 5)? 5 : $y;
		
		$buttonCords['top']['x']	= $x;
		$buttonCords['bottom']['x']	= $x;
		$buttonCords['left']['x']	= ($x >= 10)? $x - 5 : 5;
		$buttonCords['right']['x']	= ($x <= ($max-10))? $x + 5 : ($max - 5);
		
		$buttonCords['top']['y']	= ($y >= 10)? $y - 5 : 5;
		$buttonCords['bottom']['y']	= ($y <= ($max-10))? $y + 5 : ($max - 5);
		$buttonCords['left']['y']	= $y;
		$buttonCords['right']['y']	= $y;
		
		$this->template->content->startX  = (($x - 5) < 1) ? 1 : $x-5;
		$this->template->content->startY  = (($y - 5) < 1) ? 1 : $y-5;
		$this->template->content->endX 	  = (($x + 5) > $max) ? $max : $x + 5;		
		$this->template->content->endY 	  = (($y + 5) > $max) ? $max : $y + 5;
		$this->template->content->map 	  = $this->createMap($towns);
		$this->template->content->buttons = $buttonCords;		
	}


    /**
     * Creates a town map of given towns
     * @param <array> $towns
     * @return <array>          2 dimensional map of towns
     */
	private function createMap($towns){
		$map = array();
		foreach($towns as $town){
			$map[$town->x][$town->y] = $town;	
		}
		return $map;
	}


    /**
     * Checks if given coordinates are valid
     * @param <int> $x
     * @param <int> $y
     * @return <boolean>
     */
	private function validateCoords($x, $y){
        $max = kohana::config('crmGame.mapSize') - 1;
        if(!is_numeric($x) OR !is_numeric($y) OR $x < 0 OR $y < 0 OR $y > $max OR $x > $max) return false;
        return true;
    }
}
?>