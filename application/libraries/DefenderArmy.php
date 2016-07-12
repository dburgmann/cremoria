<?php
/**
 * provides special functions for defending armies
 */
class DefenderArmy extends Army {

    /**
    * Save changes to Db
    * @param <int> townId
    */
    public function save($townId){
        foreach($this->casualties as $id => $cas){
            $troop = ORM::factory('unit')->where(array("unit_id" => $id, "town_id" => $townId))->find();
            if($cas->quantity == $troop->quantity)
                $troop->delete();
            else{
                $troop->quantity -= $cas->quantity;
                $troop->save();
            }
        }
    }
}
?>
