<?php
class IniORM{

    //TODO: Comment
	protected $ini	= '';
	
	protected static $items = array();
	protected $select		= array();
    protected $objectData   = array();

    protected $idAttribute = 'id';
		
	public static function factory($ini, $id = -1){
		$obj = new IniORM($ini);
		if($id != -1){
			return $obj->find($id);
		}
		else{
			return $obj;
		}
	}
		
	
	private function __construct($ini){
		$this->ini = $ini;
		if(empty(self::$items[$this->ini])){
			$path  = Kohana::config('crmGeneral.iniPath', TRUE);
	        $items = parse_ini_file($path.$this->ini.'s.ini', TRUE);
	        if($items){
	        	self::$items[$this->ini] = $items;
	        }
	        else{
	        	throw new Kohana_Exception('errors.file_not_found');
	        }
		}				
	}
	
	public function select($columns){
		if(is_array($columns)){
			$this->select = $columns;
		}
		return $this;
	}

    public function addObjectData($columns){
        if(is_array($columns)){
			$this->objectData = $columns;
		}
		return $this;
    }

	private function doSelect($item){
		foreach($item as $col => $data){
			//Select Collumns
			if(!in_array($col, $this->select)){
				unset($item[$col]);
			}						
		}
		return $item;
	}
	
	public function find($id, $asArray = false){
        $item = null;
        if(is_numeric($id)){
            $item = $this->findId($id);
        }
        elseif(is_object($id)){
            $item = $this->findObject($id);
        }
        if(isset($item)){
            if(!empty($this->select)){
                    $item = $this->doSelect($item);
            }
            if(!$asArray){
                $item = (object) $item;
            }
        }
		$this->select = array();
		return $item;
	}

    private function findObject($obj){
        $attr = $this->idAttribute;
        if(isset($obj->$attr)){
            $id = $obj->$attr;
            $item = $this->findId($id);
            foreach($this->objectData as $column){
                $item[$column] = $obj->$column;
            }
        }
        return $item;
    }

    private function findId($id){
        $item = null;
        if(isset(self::$items[$this->ini][$id])){
            $item = self::$items[$this->ini][$id];
        }
        return $item;
    }
	
	/**
	 * returns all data about the given items,
	 * on default data of all items will be returned.
	 * 
	 * @param array $items	array of itemIds about which data is needed
	 * @return array Contains the item data arrayformat: $arr[$itemId]['attr']
	 */
	public function findAll($ids = NULL, $asArray = false){
		$items = array();
		if(isset($ids) AND (is_array($ids) OR is_object($ids))){
			foreach($ids as $id){
 				$select  = $this->select;
				$items[] = $this->find($id, $asArray);
				$this->select = $select;
			}
        }else{
                if($asArray AND empty($this->select)){
                    return self::$items[$this->ini];
                }
                else{
                    return $this->findAll(array_keys(self::$items[$this->ini]), $asArray);
                }            
		}
		$this->select = array();
		return $items;
	}

    public function setIdAttribute($name){
        $this->idAttribute = $name;
        return $this;
    }
}
//TODO: Comment

?>