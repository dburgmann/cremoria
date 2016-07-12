<?php
/**
 * Provides special functions for attacking armies
 */
class AttackerArmy extends Army {
     /**
     * Save changes to Db
     */
    public function save($movId){
        foreach($this->casualties as $id => $cas){
            $troop = ORM::factory('troop')->where(array("unit_id" => $id, "movement_id" => $movId))->find();
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
