<?php
/**
 * ProdHandler
 * 
 */
abstract class ProductionHandler{
	//town ORM object
	protected $town;
	//user ORM object
	protected $user;
	//Errors which occured while processing user Data
	protected $error;
	
	//items of the user arrayformat: $arr[$type][$itemId]['quantity']
	protected $has;
	//buildings the user is allowed to build in this town, arrayformat: $arr[$itemId][$attr]
	protected $allowedItems;
	
	/**
	 * Constructor, sets the initial Variables
	 * 
	 * @param User_Model Object	$user	ORM Object of the user
	 * @param Town_Model Object	$town	ORM Object of the active town
	 * @param array()			$items	items this Handler should work with
	 **/
	public function __construct($user, $town){
		$this->user 		= $user;
		$this->town 		= $town;
		
		foreach($this->user->techs as $tech){
			$this->has['tech'][$tech->id]['quantity'] = $tech->quantity;
		}
		
       	foreach($this->town->buildings as $bld){            
			$this->has['building'][$bld->id]['quantity'] = $bld->quantity;
		}
    }


    /**
     *
     */
    protected function calculatePts($gold, $wood, $stone, $iron){
        return ceil(eval(kohana::config('crmGame.ptsFormula')));
    }



   /**
    * Calculates the price of the given Building
    * @param <stdClass> $item
    * @param <int> $no
    * @return array or object
    */
    protected function calculatePrice($item, $no = 0){
        if(is_object($item)){
            return $this->calculateObject($item, $no);
        }
        if(is_array($item)){
             return $this->calculateArray($item, $no);
        }
    }


  	
	/**
	 * Returns the items the user is allowed to produce in the town
	 * 
	 * @params array()	$items	Items to check
	 * @return array();
	 **/
	protected function checkAllowed($items, $type){
		$alloweditems = array();

		foreach ($items as $id => $item){            
            $allowed = true;
            //Check if the user has all requiered techs
			if(isset($item['reqTech'])){
				foreach($item['reqTech'] as $techId)
				{			
					if(!isset($this->has['tech'][$techId])){
						$allowed = false;
						break;
					}
				}
			}
			//Check if the user has all requiered buildings
			if(isset($item['reqBld'])){
				foreach($item['reqBld'] as $buildingId)
				{
					if(!isset($this->has['building'][$buildingId])){
						$allowed = false;
						break;
					}
				}
			}
            //If all checks passed calculate, and add item to allowed items
			if($allowed == true){
                if(isSet($this->has[$type][$id]))
					 $quantity = $this->has[$type][$id]['quantity'];
				else
					$quantity = 0;
                $alloweditems[$id]              = $this->calculatePrice($items[$id], $quantity);
                $alloweditems[$id]['quantity']  = $quantity;
                $alloweditems[$id]['id']        = $id;
			}            
		}
		
		return $alloweditems;		
	}



	/**
	 * Checks if there are enough ressources in the town to produce the given item
	 * 
	 * @param object $item	item to check
	 * @return bool
	 */
	protected function checkRes($item, $no = 1){
		if($this->town->gold < ($no * $item->gold)){
			return false;
		}		
		if($this->town->wood < ($no * $item->wood)){
			return false;
		}
		if($this->town->stone < ($no * $item->stone)){
			return false;
		}		
		if($this->town->iron < ($no * $item->iron)){
			return false;
		}			
		return true;	
	}



	/**
	 * Pays the resources for the given item
	 * 
	 * @param object $item	Item to pay for
	 * @return 
	 */
	protected function pay($item, $no = 1){
		$this->town->gold 	= $this->town->gold  - ($no * $item->gold);
		$this->town->wood 	= $this->town->wood  - ($no * $item->wood);
		$this->town->iron 	= $this->town->iron  - ($no * $item->iron);
		$this->town->stone 	= $this->town->stone - ($no * $item->stone);
	}
    
	
	/**
     * returns allowed Items which contains all Items the user is allowed
     * to build
     */
	public function getAllowed(){
		return $this->allowedItems;
	}
	
	/**
	 * Returns the last Error which occured
	 */
	public function getError(){
		return $this->error;
	}


    /**### Abstract function definitions ###**/

    /**
     * These fucntions calculate the cost of an item object
     */
    protected abstract function calculateObject($item, $no);
    protected abstract function calculateArray($item, $no);
	
}
?>