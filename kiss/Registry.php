<?php

class Kiss_Registry{

    private static $instance;	
    
    /**
    *Registry storage
    */
    private static $storage = array();

    /**
    * Get stored object
    * @param $key string 
    * @return object
    */
    public static function get($key){
        return self::instance()->_get($key);
    }

    /**
    * Set stored object
    * @param    string $key
    * @param    object $instance
    */
    public static function set($key, $val){
        return self::instance()->_set($key, $val);
    }    

    /**
    * delete object
    * @param $key string 
    * @param $val object 
    */
    public static function delete($key){
        unset(self::$storage[$key]);
    }   

    private static function instance(){        
        if(!isset(self::$instance)){
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function _get($key){
        if(isset(self::$storage[$key])){
            return self::$storage[$key];
        }
        return null;
    }

    private function _set($key, $val){
        self::$storage[$key] = $val;
    }
}