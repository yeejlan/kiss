<?php
require_once('Exception.php');

class Kiss_Log {
	
	private $directory;

	public function __construct($directory){
		if(!is_dir($directory) || !is_writable($directory)){
			throw new Kiss_Exception('Directory must be writable: '.$directory);
		}

		$this->directory = realpath($directory).DIRECTORY_SEPARATOR;
	}	

    /**
    * write log
    * @param $message string, message to log
    * @param $string $prefix, logfile prefix
    */
	function write($message, $prefix = 'kiss'){
		//yearly directory name
		$directory = $this->directory.date('Y');
		if(!is_dir($directory)){
			//create yearly directory
			mkdir($directory, 02777);
			chmod($directory, 02777);
		}

		//monthly directory
		$directory .= DIRECTORY_SEPARATOR.date('m');
		if(!is_dir($directory)){
			//create monthly directory
			mkdir($directory, 02777);
			chmod($directory, 02777);
		}

		//name of the log file
		$filename = $directory.DIRECTORY_SEPARATOR.$prefix.'_'.date('d').'.log';
		//write
		file_put_contents($filename, $this->formatMessage($message), FILE_APPEND);
	}

	private function formatMessage($message){
		$dt = new DateTime();
		$dtStr = $dt->format(DateTime::ISO8601);
		return $dtStr.' '.$message.PHP_EOL;
	}
}