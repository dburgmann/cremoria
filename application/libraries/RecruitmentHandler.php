<?php
/**
 * Recruitment Handler
 * coordinates the recruiting process
 */
class RecruitmentHandler extends ProductionHandler{
    protected $production;

    /**
     * Constructor
     * checks the quantity of existing units and
     * the units the user is allowed to recruit
     * @param <user> $user
     * @param <town> $town
     */
    public function  __construct($user, $town) {
        parent::__construct($user, $town);

        foreach($this->town->units as $unit){
            $this->has['unit'][$unit->unit_id]['quantity'] = $unit->quantity;
        }

        $items = IniORM::factory('unit')->findAll(null, true);
        $this->allowedItems = $this->checkAllowed($items, 'unit');
    }

    /**
	 * Produces the given items, performing checks, paying & saving
	 *
	 * @param string $type	type of given item
	 * @param object $item	item to produce
	 * @return
	 */
	public function produce($items, $cap){
        $no         = 0;
        $item       = null;
        $prod 		= null;
        $freeCap    = $this->town->unitCap - $this->town->unitCapUsed;

        if($freeCap < $cap){
            $this->error = 'prodNotEnoughCap';
            return;
        }
        
        foreach($items as &$item){
            $no     = $item['quantity'];
            $item   = $item['item'];
            if(!isset($item)){
                $this->error = 'prodInvalidItem';
                return;
            }
            if(!isset($this->allowedItems[$item->id])){
                $this->error = 'prodForbiddenItem';
                return;
            }
            
            $item   = $this->calculatePrice($item);
            if($this->checkRes($item, $no)){
                $this->pay($item, $no);
                $prod = ORM::factory('unitProduction')
                        ->where(array('unit_id' => $item->id, 'town_id' => $this->town->id, 'time' => $item->time))
                        ->find();
                if($prod->count_last_query() > 0){
                    $prod->quantity += $no;
                }else{
                    $prod             = ORM::factory('unitProduction');
                    $prod->unit_id    = $item->id;
                    $prod->town_id    = $this->town->id;
                    $prod->quantity   = $no;
                    $prod->time       = $item->time;                    
                }
                $this->town->unitCapUsed += $no;
                $prod->save();
            }
            else{
                $this->error = 'prodNotEnoughRes';
            }
        }        
        $this->town->save();
    }


    /**
     * Returns a Array of all units which arre currently in production
     */
    public function getProduction(){
        $ids        = array();
        $data       = array();
        $production = $this->town->unitProductions;
        //TODO: In iniORM integrieren sowas wie add Data
        
        foreach($production as $unit){
        	$id   = $unit->unit_id;
        	$item = IniORM::factory('unit')->select(array('id', 'name'))->find($id, true);
        	$item['quantity'] = $unit->quantity;
            $item['time'] 	  = $unit->time;
            $data[] = $item;
        }
        return $data;

    }

   
    /**### Functions requiered by abstract parent ###**/

    /**
     * Calculates the cost of given Item Object
     * Need for the calculatePrize function
     */
    protected function calculateObject($item, $no){
        $item->gold  = ceil($item->gold);
        $item->wood  = ceil($item->wood);
        $item->stone = ceil($item->stone);
        $item->iron  = ceil($item->iron);
        return $item;
    }

    /**
     * Calculates the cost of given Item Array
     * Need for the calculatePrize function
     */
    protected function calculateArray($item, $no){
        $item['gold']  = ceil($item['gold']);
        $item['wood']  = ceil($item['wood']);
        $item['stone'] = ceil($item['stone']);
        $item['iron']  = ceil($item['iron']);
        return $item;
    }
}
?>
