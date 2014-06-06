<?php

/**
 * Helper class is always cached and act like single instance, 
 * so please avoid using static and class level variables unless you know exactly what you want.
**/
class Helper_GetSquare{

	public function getSquare($val){
		return $val * $val;
	}

	/**Add this function if you want access view object in helper function**/
	public function setView($view){
		$this->view = $view;
	}
}