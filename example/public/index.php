<?php
define('APP_PATH', realpath(dirname(__FILE__) . '/../'));

//set include path
set_include_path(implode(PATH_SEPARATOR, array(
	realpath(APP_PATH),
	realpath(APP_PATH . '/../'), 
	realpath(APP_PATH . '/view'), 
	get_include_path(),
)));


$appEnv = getenv('APP_ENV') ? getenv('APP_ENV') : 'production';

//define config path
define('CONFIG_PATH', APP_PATH . '/config/' .$appEnv);


//initial application
require "Kiss/App.php";
Kiss_App::init($appEnv, CONFIG_PATH.'/app.ini');
Kiss_App::bootstrap();

//store config to Kiss_Registry
$config = Kiss_App::getConfig();
Kiss_Registry::set('config', $config);
//Resources::setConfig($config);

//create router
$router = new Kiss_Router();

//map "/hello/*" to Controller/Home.php::hiAction()
$router->addRoute('/hello/(.*)', 'home/hi', array(1 => 'username'));

//run
$router->dispatch();
