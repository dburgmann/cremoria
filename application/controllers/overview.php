<?php

/**
 * Controller of the overview Page,
 * shows general info and information about incomign and outgoing movements
 */
class Overview_Controller extends ControllerIngame {

    public $template = 'main';

    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();
        $this->template->content = new View('overview');
        $this->template->content->error = '';
    }


    /**
     * Retrieves the data to show an overview
     */
    public function index() {
        parent::index();
        $towns      = ORM::factory('town')->where('user_id', $this->user->id)->find_all();

        //Collect Movement Data
        $i = 0;
        foreach($towns as $town){
            $recogTime           = $this->getMovRecogTime($town);
            $movsTo[$i]['mov']   = $this->getMovementsTo($town, $recogTime);
            $movsFrom[$i]['mov'] = $this->getMovementsFrom($town);
            /*
            $movsTo[$i]['mov']   = ORM::factory('movement')
                                    ->where(array('destX'=>$town->x, 'destY'=>$town->y))
                                    ->find_all();

            $movsFrom[$i]['mov'] = ORM::factory('movement')
                                    ->where(array('startX'=>$town->x, 'startY'=>$town->y))
                                    ->find_all();*/
            
            
            $movsTo[$i]['town']  = $town->name;
            $movsFrom[$i]['town']= $town->name;
            $i++;
        }
        $this->template->content->towns     = $towns;
        $this->template->content->movsTo    = $movsTo;
        $this->template->content->movsFrom  = $movsFrom;
        $this->template->content->actions = kohana::config('crmGame.movementTypes');

        $this->template->content->user      = $this->user;    
     }


     /**
      * Detects all Movements to the given town arriving in the given recognize time
      * @param <int> $town          id of the town
      * @param <int> $recogTime     time to arrival at which movements are recognized
      * @return <array>
      */
     private function getMovementsTo($town, $recogTime){
         return ORM::factory('movement')
                ->where("destX={$town->x} AND destY= {$town->y} AND (owner= {$this->user->id} OR arrival= {$recogTime})")
                ->find_all();
     }


     /**
      * Detects all movements from the given town
      * @param <int> $town     id of the town
      * @return <array>
      */
     private function getMovementsFrom($town){
        return ORM::factory('movement')
               ->where(array('startX'=>$town->x, 'startY'=>$town->y, 'owner'=>$this->user->id))
               ->find_all();
     }


     /**
      * Calculates the movement recognize time of the given town
      * @param <int> $town  id of the town
      * @return <int>
      */
     private function getMovRecogTime($town){
         $id = kohana::config('crmItems.movRecogBld');
         $no = ORM::factory('building')->where(array('id' => $id, 'town_id'=>$town->id))->find()->quantity;
         return eval(kohana::config('crmGame.movRecogTimeFormula'));
     }
}
?>
