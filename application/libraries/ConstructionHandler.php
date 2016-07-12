<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

class ConstructionHandler extends ProductionHandler {

    /**
     * Constructor
     * @param <user> $user
     * @param <town> $town 
     */
    public function __construct($user, $town) {
        parent::__construct($user, $town);
        $items = IniORM::factory('building')->findAll(null, true);
        $this->allowedItems = $this->checkAllowed($items, 'building');
    }

    /**
     * Produces the given items, performing checks, paying & saving
     *
     * @param string $type	type of given item
     * @param object $item	item to produce
     * @return
     */
    public function produce($item) {
        if (!isset($item)) {
            $this->error = 'prodInvalidItem';
            return;
        }
        if (!isset($this->allowedItems[$item->id])) {
            $this->error = 'prodForbiddenItem';
            return;
        }
        if ($this->town->currentBuilding != null) {
            $this->error = 'prodAlreadyBuilding';
            return;
        }

        $item = $this->calculatePrice($item, $this->allowedItems[$item->id]['quantity']);
        if (!$this->checkRes($item)) {
            $this->error = 'prodNotEnoughRes';
            return;
        }

        $this->pay($item);
        $this->town->currentBuilding= $item->id;
        $this->town->currentBldPts  = $this->calculatePts($item->gold, $item->wood, $item->stone, $item->iron);
        $this->town->buildingTime   = $item->time;
        $this->town->save();
    }

    /*     * ### Functions requiered by abstract parent ###* */

    /**
     * Calculates the cost of given Item Object
     * Need for the calculatePrize function
     */
    protected function calculateObject($item, $no) {
        $no += 1;
        $calced = eval(kohana::config('crmGame.buildingCostFormula'));
        $div    = kohana::config('crmGame.buildingTimeDivider');
        $item->gold     = ceil($item->gold  * $calced);
        $item->wood     = ceil($item->wood  * $calced);
        $item->stone    = ceil($item->stone * $calced);
        $item->iron     = ceil($item->iron  * $calced);

        $sum = ($item->gold + $item->wood + $item->wood + $item->wood);
        $item->time = ceil($item->time + ( $sum / $div));
        return $item;
    }

    /**
     * Calculates the cost of given Item Array
     * Needed for the calculatePrize function
     */
    protected function calculateArray($item, $no) {
        $no += 1;
        $calced     = eval(kohana::config('crmGame.buildingCostFormula'));
        $div        = kohana::config('crmGame.buildingTimeDivider');
        
        $item['gold']   = ceil($item['gold'] * $calced);
        $item['wood']   = ceil($item['wood'] * $calced);
        $item['stone']  = ceil($item['stone'] * $calced);
        $item['iron']   = ceil($item['iron'] * $calced);
        
        $sum            = ($item['gold'] + $item['wood'] + $item['wood'] + $item['wood']);
        $item['time']   = ceil($item['time'] + ( $sum / $div));
        return $item;
    }

}
?>
