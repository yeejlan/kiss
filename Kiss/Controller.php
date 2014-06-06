<?php

require_once 'View.php';
require_once 'Router.php';

class Kiss_Controller{

	/**
	* Kiss_View
	**/
	public $view;

	/**
	* construct
	**/
	public function __construct(){
		$this->view = new Kiss_View();
	}

	/**
	* before method
	**/
	public function before(){
		//pass
	}

	/**
	* after method
	**/
	public function after(){
		//pass
	}

	/**
	* render a template(without .phtml)
	**/
    public function render($template){
    	$this->view->render($template);
    }

	/**
	* render a template to string
	**/
    public function renderToString($template){
    	return $this->view->renderToString($template);
    }

	/**
	* internal redirect to another controller/action
	* @param $controller string
	* @param $action string
	**/    
	public function callAction($controller, $action){
		Kiss_Router::callAction($controller, $action);
	}
}