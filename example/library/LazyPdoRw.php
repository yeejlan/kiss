<?php

/**
* Use this class to force PDO lazy connect to database
**/

class LazyPdoRw{

	/**
	* safely salve read interval after data write to master
	**/
	const SAFE_READ_INTERVAL = 20;

	/**
	* PDO instance
	**/
	private $dbh;
	private $slave_dbh;

	/**
	* connection config
	**/
	private $dsn;
	private $dsn_hash;
	private $slave_dsn;
	private $username;
	private $password;
	private $driver_options;

	/**
	* initial query
	*/
	private $initialQuery = null;

	/**
	* readonly related
	*/
	private $readonly = false;
	private $lastWrite = 0;

	/**
	* construct function
	* @param $dsn string
	* @param $username string
	* @param $password string
	* @param $driver_options array
	**/
	public function __construct($dsn, $username=null, $password=null, $driver_options=null){
		$this->dbh = null;
		$this->slave_dbh = null;
		$this->dsn = $dsn;
		$this->dsn_hash = md5($dsn);
		$this->slave_dsn = null;
		$this->username = $username;
		$this->password = $password;
		$this->driver_options = $driver_options;
	}

	/**
	* set slave DSN
	* @param $dsn string
	**/
	public function setSlaveDsn($dsn){
		$this->slave_dsn = $dsn;
	}

	/**
	* set initial query
	* @param $dsn string
	**/
	public function setInitialQuery($query){
		$this->initialQuery = $query;
	}	

	/**
	* use master dsn
	**/
	public function useMaster(){
		$this->readonly = false;
	}

	/**
	* use slave dsn
	**/
	public function useSlave(){
		$this->readonly = true;
	}

	/**
	* query on slave db or not
	**/
	private function querySlave(){
		if($this->readonly && $this->slave_dsn){
			$lw = $this->getLastWrite();
			if((time() - $lw) > LazyPdo::SAFE_READ_INTERVAL){
				return true;
			}
		}else{
			//there is a delay between this and real update operation, but we can ignore it safely
			if(!$this->readonly){
				$this->updateLastWrite();  
			}
			return false;
		}
	}

	/**
	* get last master write timestamp
	**/
	private function getLastWrite(){
		if(isset($_SESSION)){
			$lw = intval($_SESSION[$this->dsn_hash]);
		}else{
			$lw = $this->lastWrite;
		}
		return $lw;
	}

	/**
	* update last master write timestamp
	**/
	private function updateLastWrite(){
		$lw = time();
		if(isset($_SESSION)){
			$_SESSION[$this->dsn_hash] = $lw;
		}else{
			$this->lastWrite = $lw;
		}
	}

	/**
	* magic function
	**/
    public function __call($method, $args){
    	$dbh = $this->initPdo($this->querySlave());
    	return call_user_func_array(array($dbh, $method), $args);
    }

	/**
	* create PDO instance
	**/
	private function initPdo($querySlave = false){
		if($querySlave){
			$dsn = $this->slave_dsn;
			$dbh = $this->slave_dbh;
		}else{
			$dsn = $this->dsn;
			$dbh = $this->dbh;
		}

		if(!$dbh){
			try{
				$dbh = new PDO($dsn, $this->username, $this->password, $this->driver_options);
				$dbh->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
				$dbh->exec('SET NAMES utf8');
				if($this->initialQuery){
					$dbh->query($this->initialQuery);
				}
			}catch(PDOException $e){
				new LogDbError(__METHOD__.' '.$dsn, $e);
				throw $e;
			}

			if($querySlave){
				$this->slave_dbh = $dbh;
			}else{
				$this->dbh = $dbh;
			}
		}

		return $dbh;
	}
}
