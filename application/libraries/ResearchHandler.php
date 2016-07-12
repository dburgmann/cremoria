<?php
/**
 * Research Handler
 * coordinates the research process
 */
class ResearchHandler extends ProductionHandler{
	protected $labs;

    /**
     * Constructor
     * determines the number of labs and the allowed technologies
     * @param <user> $user
     * @param <town> $town
     */
    public function __construct($user, $town){
        parent::__construct($user, $town);
        $items = IniORM::factory('tech')->findAll(null, true);
        $labId = kohana::config('crmItems.researchBld');
        $this->labs = (isset ($this->has['building'][$labId])) ? $this->has['building'][$labId]['quantity'] : 0;
               
        $this->allowedItems = ($this->labs > 0) ? $this->checkAllowed($items, 'tech') : array();
    }

    /**
     * Returns the number of Laboratories
     * @return <int>
     */
    public function getLabs(){
    	return $this->labs;
    }


    /**
	 * Produces the given items, performing checks, paying & saving
	 *
	 * @param string $type	type of given item
	 * @param object $item	item to produce
	 * @return
	 */
	public function produce($item){
        if($this->labs == 0){
        	$this->error = 'prodNoLab';
        	return;
        }
		if(!isset($item)){
			$this->error = 'prodInvalidItem';
            return;
        }
		if($this->user->currentResearch != null){
			$this->error = 'prodAlreadyResearching';
            return;
        }
		if(!isset($this->allowedItems[$item->id])){
			$this->error = 'prodForbiddenItem';
			return;
		}
		if($this->allowedItems[$item->id]['quantity'] > 0 && $item->type != 'repeatable'){
			$this->error = 'prodForbiddenItem';
			return;
		}
		
		$item = $this->calculatePrice($item, $this->allowedItems[$item->id]['quantity']);
        if(!$this->checkRes($item)){
        	$this->error = 'prodNotEnoughRes';
			return;
		}

		$this->pay($item);
    	$this->user->currentResearch 	= $item->id;
        $this->user->currentRscPts      = $this->calculatePts($item->gold, $item->wood, $item->stone, $item->iron);
		$this->user->researchTime		= $item->time;
		$this->town->save();
		$this->user->save();
	}




    /**### Functions requiered by abstract parent ###**/
    /**
     * Calculates the cost of given Item Object
     * Need for the calculatePrize function
     */
    protected function calculateObject($item, $no){
		$labs 	= $this->labs;
        $no += 1;
        if($item->type == 'repeatable'){
            $calced = eval(kohana::config('crmGame.researchCostFormula'));
            $item->gold     = ceil($item->gold  * $calced);
            $item->wood     = ceil($item->wood  * $calced);
            $item->stone    = ceil($item->stone * $calced);
            $item->iron     = ceil($item->iron  * $calced);
        }
        $calced = eval(kohana::config('crmGame.researchTimeFormula'));
        $item->time  = ceil($item->time * $calced);
        return $item;
    }

    /**
     * Calculates the cost of given Item Array
     * Need for the calculatePrize function
     */
    protected function calculateArray($item, $no){
		$labs 	= $this->labs;
        $no += 1;
        if($item['type'] == 'repeatable'){
            $calced     = eval(kohana::config('crmGame.researchCostFormula'));
            $item['gold']   = ceil($item['gold'] * $calced);
            $item['wood']   = ceil($item['wood'] * $calced);
            $item['stone']  = ceil($item['stone'] * $calced);
        }
        $calced = eval(kohana::config('crmGame.researchTimeFormula'));
        $item['time']  = ceil($item['time'] * $calced);
        return $item;
    }
}

?>
