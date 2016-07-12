<?php
/**
 * Class used to create a new player existence
 */
class ExistenceCreator{
	public function createNewExistence($user){
		$this->setupTown($user);		
	}
	
	/**
	 * Sets up a new Towen for the given user
	 * @param $user
	 */
	public function setupTown($user){
		$town = ORM::Factory('town');
		$town->name = $user->username."´s Home";
		$town->user_id = $user->id;
		
		$this->setCoordinates($town);
		$this->addBuildings($town);
		$this->addRessources($town);
		$town->save();
	}
	
	/**
	 * Sets the coordinates of the town
     * @town <town> the town
	 */
	private function setCoordinates(&$town){
		$coords 	= array('x' => 0, 'y' => 0);
		$usedCoords = ORM::factory('town')->select('x, y')->find_all();
		$max 		= kohana::config('crmGame.mapSize');
		$found		= false;
		
		while(!$found){		
			$x = rand(0, $max);
			$y = rand(0, $max);
			$found = true;
			
			foreach($usedCoords as $coord){
				if($coord->x == $x && $coord->y == y){
					$found = false;
				}
			}
		}
		
		$town->x = $x;
		$town->y = $y;
	}
	
	/**
	 * Adds the inital buildings to the town
	 * @param $town
	 */
	private function addBuildings(&$town){
	}
	
	
	/**
	 * Adds the initial ressources to the town
	 * @param $town
	 */
	private function addRessources(&$town){
		$town->gold = kohana::config('crmGame.initGold');
		$town->wood = kohana::config('crmGame.initWood');
		$town->stone = kohana::config('crmGame.initStone');
		$town->iron = kohana::config('crmGame.initIron');
	}
	
	/**
	 * Sets up a new Hero for the given user
	 * @return unknown_type
	 */
	public function setupHero(){
		
	}
}
?>