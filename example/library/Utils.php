<?php

class Utils{

	/**
	* get client ip address
	**/
	static function getClientIp($useUntrusted = true){
		$ip = $_SERVER['REMOTE_ADDR'];
		if(!$useUntrusted){
			return $ip;
		}
		if(!empty($_SERVER['HTTP_CLIENT_IP'])){
			$ip = $_SERVER['HTTP_CLIENT_IP'];
		}elseif(!empty($_SERVER['HTTP_X_FORWARDED_FOR'])){
			$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
		}
		return $ip;
	}
	
	/**
	* log function
	* @param $message string
	* @param $prefix string, prefix for log file name
	* @param $floodControl bool
	**/
	static function log($message, $prefix = 'kiss', $floodControl = false){
		static $lastMsg;

		if($floodControl && $lastMsg == $message){
			return;
		}

		$logPath = APP_PATH.'/../logs';
		try{
			$log = new Kiss_Log($logPath);
			$log->write($message, $prefix);
		}catch(Exception $e){
			//pass
		}
		$lastMsg = $message;
	}
	
}