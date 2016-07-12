<?php
class Town_Model extends ORM{
	protected $belongs_to = array('user');
	protected $has_many   = array('buildings', 'units', 'unitProductions', 'movements');

    /**
     * Returns the quantity of the building of given id in the town
     * @param <int> $id
     * @return <int>
     */
    public function quantityOfBuilding($id){
        foreach($this->buildings as $bld){
			if($bld->id == $id){
                return $bld->quantity;
            }
		}
        return 0;
    }


    /**
     * Returns the quantity of the unit of given id in the town
     * @param <int> $id
     * @return <int>
     */
    public function quantityOfUnit($id){
        foreach($this->units as $unit){
			if($unit->id == $id){
                return $unit->quantity;
            }
		}
        return 0;
    }
}
?>