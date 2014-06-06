<?php

require_once 'Exception.php';

class Kiss_View{

    /**
    * helper instance cache
    **/
    private $_helperPool = array();

	/**
	* render a template
	* @param $template string, template name without '.phtml'
	* @param $data stdClass object
	**/
    public function render($template){
    	include $template.'.phtml';
    }

    /**
    * render a template to string
    **/
    public function renderToString($template){
        $str = '';
        $l = ob_get_level();
        ob_start();
        try{
            $this->render($template);
            $str = ob_get_clean();
        }catch(Exception $e){
            if($l != ob_get_level()){
                ob_end_clean();
            }
            throw $e;
        }
        return $str;
    }
    
	/**
	* magic method for helper function calling, for example in template file: $this->getUserName($userid)
	* @param $method string, helper function name
	* @param $args array
	**/
    public function __call($method, $args){
    	$helperObj = $this->getHelper($method);
    	//set view
    	$funcs = get_class_methods($helperObj);
    	if(in_array('setView', $funcs)){
    		$helperObj->setView($this);
    	}

    	if(!in_array($method, $funcs)){
    		throw new Kiss_Exception('Function not found: '.$method);
    	}
    	return call_user_func_array(array($helperObj, $method), $args);
    }

	/**
	* load a helper class
	* @param $method string
	**/
    private function getHelper($method){
		$method = ucfirst($method);
        if(isset($this->_helperPool[$method]) && $this->_helperPool[$method] != null){
            return $this->_helperPool[$method];
        }
        $classname = 'Helper_'.$method;
		$obj = new $classname();
        //set helper cache
        $this->_helperPool[$method] = $obj;
		return $obj;    
    }    
}
