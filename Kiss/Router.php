<?php

require_once('Exception.php');

class Kiss_Router{
	private $routers = array();
	private static $controller = null;
	private static $action = null;

	/**
	* get current controller
	**/
	public static function currController(){
		return self::$controller;
	}

	/**
	* get current action
	**/
	public static function currAction(){
		return self::$action;
	}	

	/**
	* add a regex router
	* @param $urlRewrite string, for example: 'shop/product/(\d+)'
	* @param $action string, for example: 'shop/showprod'
	* @param $matchMap array, for example: array(1 => 'prod_id')
	* @return bool
	* addRoute('shop/product/(\d+)', 'shop/showprod', array(1 => 'prod_id'))
	* will match uri '/shop/product/1001' to 'shop' controller and 'showprod' action, with $_GET['prod_id'] = 1001
	**/
	public function addRoute($urlRewrite, $action, $matchMap = null){
		$this->routers[] = array($urlRewrite, $action, $matchMap);
	}
	
	/**
	* router dispatch to controller/action
	*/		
	public function dispatch(){
		$uri = $_SERVER['REQUEST_URI'];
		$uri = str_replace("\\", '/', $uri);
		if(strpos($uri, '?') !== false){
			$uri = preg_replace('#(\?.*)#', '', $uri);
		}

		$routes = $this->routers;
		$controller = $action = null;
		$routeMatched = false;

		//match regex routes
		if(is_array($routes) && count($routes)>0){
			foreach($routes as $route){
				list($rewrite, $action, $matchMap) = $route;
				if(preg_match('#^'.$rewrite.'$#', $uri, $matches)){ //route matched
					if(is_array($matchMap) && count($matchMap)>0){
						if((count($matchMap) + 1) != count($matches)){
							throw new Kiss_Exception('Match map doesn\'t match rewrite rule:'.$rewrite);
						}
						foreach($matchMap as $key => $value){ //set $_GET with matches
							if(isset($matches[$key])){
								$_GET[$value] = urldecode($matches[$key]);
							}
						}						
					}

					list($controller, $action) = array_pad(explode('/', $action), 2, '');
					$routeMatched = true;
					break;
				}
			}
		}
		//no route match here, normal controller/action parse
		if(!$routeMatched){
			$uriArr = explode('/', $uri);
			$uriArr = array_slice($uriArr, 1);
			$actionUri = implode('/', $uriArr);  //format: 'controller/action'			
			list($controller, $action) = array_pad(explode('/', $actionUri), 2, '');
			
			if(count($uriArr) == 3) {  //format: 'module/controller/action'
				list($module, $controller, $action) = explode('/', $actionUri);
				$controller = "{$module}_{$controller}";
			}
		}

		self::callAction($controller, $action);
	}

	/**
	* find controller and call action
	**/
	public static function callAction($controller, $action){
		if(!$controller){
			$controller = 'home';
		}

		if(!$action){
			$action = 'index';
		}

		self::$controller = $controller;
		self::$action = $action;

		//set content type
		if(!headers_sent()){
			header('Content-type: text/html; charset=utf-8');
		}

        if(strpos($controller, '_') === false){ //such as 'user/action'
            $controller = ucfirst($controller);
        }else{ //such as 'user_extra/action'
            $controllerArr = explode('_', $controller);
            $controllerArr = array_map('ucfirst', $controllerArr);
            $controller = implode('_', $controllerArr);
        }

        if(!self::controllerExists($controller)){
        	self::_pageNotFound();
        }else{
        	$classname = 'Controller_'.$controller;
        	$klass = new $classname();
        	$methods = get_class_methods($klass);
        	if(!in_array($action.'Action', $methods)){ //action not found
        		self::_pageNotFound();
        	}else{ //action find
        		try{
	        		//call 'before' function
	        		$klass->before();
	        		//call action
	        		$do = $action.'Action';
	        		$klass->$do();
	        		//call 'after' function
	        		$klass->after();   
	        	}catch(Exception $e){
	        		self::_internalServerError($e);
	        	}   		
        	}
        }
	}

	/**
	* controller exists or not
	* @param  $controller string
	* @return bool
	**/
	private static function controllerExists($controller){
		$found = true;
        $filename = str_replace('_', '/', $controller).'.php';
        $resolvedName = stream_resolve_include_path('Controller/'.$filename);
        if ($resolvedName === false) {
            $found = false;
        }
        return $found;
	}

	/**
	* page not found handler
	**/
	private static function _pageNotFound(){
        if(!headers_sent()){
        	$sapi = php_sapi_name();
        	if(substr($sapi, 0, 3) == 'cgi'){
        		header('Status: 404 Not Found');
        	}else{
        		header('HTTP/1.0 404 Not Found');
        	}
        }

		$controller = 'Error';
        if(!self::controllerExists($controller)){
        	die('Page Not Found!');
        }else{
        	$classname = 'Controller_'.$controller;
        	$klass = new $classname();
        	$methods = get_class_methods($klass);
        	if(!in_array('page404Action', $methods)){ //action not found
        		die('Page Not Found!');
        	}else{
        		$klass->page404Action();
        	}
        }
	}

	/**
	* internal server error handler
	**/
	private static function _internalServerError($e){
		if(!headers_sent()){
	    	$sapi = php_sapi_name();
	    	if(substr($sapi, 0, 3) == 'cgi'){
	    		header('Status: 500 Internal Server Error');
	    	}else{
	    		header('HTTP/1.0 500 Internal Server Error');
	    	}
	    }

		$controller = 'Error';
        if(!self::controllerExists($controller)){
        	self::_terminate($e);
        }else{
        	$classname = 'Controller_'.$controller;
        	$klass = new $classname();
        	$methods = get_class_methods($klass);
        	if(!in_array('page500Action', $methods)){ //action not found
        		self::_terminate($e);
        	}else{
        		$_GET['$error'] = $e;
        		$klass->page500Action();
        	}
        }
	}

	private static function _terminate($e = null){
		echo 'Internal Server Error!';
		if($e && Kiss_App::getEnv() > Kiss_App::PRODUCTION){
			echo "<br /><pre>\r\n";
			echo $e->getMessage(), PHP_EOL;
			echo $e->getTraceAsString(), PHP_EOL;
			echo "</pre><br />\r\n";
		}
		exit;
	}

}
