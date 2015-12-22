<?php

class LogDbError{
	function __construct($location, $errorInfo = '[no info]') {
		//format info
		$errDesc = '';
		if($errorInfo instanceOf PDOException){
			$errDesc = $errorInfo->getMessage();
		}elseif($errorInfo instanceOf PDOStatement){
			$errArr = $errorInfo->errorInfo();
			$errDesc = $errArr[2];
		}else{
			$errDesc = $errorInfo;
		}
		$errDesc = str_replace(array("\r","\n"),array('',' '),$errDesc);
		$errDesc = $location.' '.$errDesc;

		//log it
		Utils::log($errDesc, 'dberror', true);
	}
}