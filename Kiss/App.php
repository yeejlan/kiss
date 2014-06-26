<?php

require_once('Exception.php');
require_once('Config.php');

class Kiss_App{

    private static $instance;
    
    /**
    * application setting
    */
    private static $setting = array();

    private static $isInit = false;

    const PRODUCTION = 10;
    const STAGING = 20;
    const TESTING = 30;
    const DEVELOPMENT = 40;

    /**
    * initialize application
    */
    public static function init($strEnv, $configFile){
        if(!isset(self::$instance)){
            self::$instance = new self();
        }

        self::$setting['envString'] = $strEnv;
        self::$setting['env'] = constant(__CLASS__.'::'.strtoupper($strEnv));

        $config = new Kiss_Config($configFile);
        self::$setting['config'] = $config;

        self::$isInit = true;
        return self::$instance;
    }

    /**
    * get environment integer
    * @return int
    */
    public static function getEnv(){
        self::checkInit();  
        return self::$setting['env'];
    }

    /**
    * get environment string
    * @return string
    */
    public static function getEnvString(){
        self::checkInit();  
        return self::$setting['envString'];
    }

    /**
    * get config object
    * @return object
    */
    public static function getConfig(){
        self::checkInit();     
        return self::$setting['config'];
    }

    /**
    * get application setting
    * @return array
    */
    public static function getSetting(){
        self::checkInit();      
        return self::$setting;
    }

    /**
    * application bootstrap
    */
    public static function bootstrap(){
        self::checkInit();

        //set display_errors
        if(self::getEnv() > self::PRODUCTION){
            error_reporting(E_ALL & ~E_NOTICE);
            ini_set('display_errors', 'On');
        }else{
            ini_set('display_errors', 'Off');        
        }

        //set cookie as httponly
        ini_set("session.cookie_httponly", 1);

        //set autoload
        spl_autoload_register(array(__CLASS__, 'loadClass'));

        //set timezone
        $config = self::getConfig();
        if(!$config['timezone']){
            throw new Kiss_Exception('Please set "timezone" in config file');
        }

        if(!date_default_timezone_set($config['timezone'])){
            throw new Kiss_Exception('Set timezone failed: '.$config['timezone']);
        }
    }

    /**
    * autoload function
    */
    public static function loadClass($classname){
        $filename = str_replace('_', '/', $classname).'.php';
        $resolvedName = stream_resolve_include_path($filename);
        if ($resolvedName !== false) {
            return require $resolvedName;
        }
        return false;       
    }    

    private static function checkInit(){
        if(!self::$isInit){
            throw new Kiss_Exception('Please call '.__CLASS__.'::init first');
        }    
    }
}
