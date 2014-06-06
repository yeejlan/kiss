<?php
require_once('Exception.php');

class Kiss_Config implements arrayaccess{

	private $_config = array();
    private $_configObj = null;
	private $nestSeparator = '.';

	/**
	* construct function
	**/
	public function __construct($configFile){
		if(!is_file($configFile)){
			throw new Kiss_Exception('File not found: '.$configFile);
		}

		if(!is_readable($configFile)){
			throw new Kiss_Exception('File not readable: '.$configFile);
		}

        $data = parse_ini_file($configFile, false);
        $this->process($data);
        $this->_configObj = $this->toObject();
	}

	/**
	* get config as array
	**/
	public function toArray(){
		return $this->_config;
	}

	/**
	* get config as object
	**/
	public function toObject(){
		return $this->arrayToObject($this->_config);
	}

	/**
	* process config, from db_sns.username = 'username'  to $config['db_sns']['username'] = 'username'
	**/
    private function process(array $data){
        foreach ($data as $key => $value) {
        	if (strpos($key, $this->nestSeparator) !== false){
        		$keys = explode($this->nestSeparator, $key);
        		$this->_config[$keys[0]][$keys[1]] = $value;
        	}else{
        		$this->_config[$key] = $value;
        	}
        }
    }

	/**
	* convert array to object
	* @param $array array
	* @return object
	**/
	private function arrayToObject($array){
		if(!is_array($array)){
			return $array;
		}

        $object = new stdClass();
   
        if (is_array($array) && count($array) > 0){
            foreach ($array as $key => $value){
                $key = strtolower(trim($key));
                if (!empty($key)){
                    $object->{$key} = $this->arrayToObject($value);
                }
            }
        }else{
            return false;
        }

        return $object; 
	}

    /**===object access method===**/

    public function __get($key) {
        return $this->_configObj->$key;
    }

    /**===end of object access method===**/    

	/**===array access method===**/
    public function offsetSet($offset, $value) {
        if (is_null($offset)) {
            $this->_config[] = $value;
        } else {
            $this->_config[$offset] = $value;
        }
    }

    public function offsetExists($offset) {
        return isset($this->_config[$offset]);
    }

    public function offsetUnset($offset) {
        unset($this->_config[$offset]);
    }

    public function offsetGet($offset) {
        return isset($this->_config[$offset]) ? $this->_config[$offset] : null;
    }	
    /**===end of array access method===**/
}