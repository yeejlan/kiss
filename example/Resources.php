<?php
class Resources {
	/**
	 * application config
	 * @var Config Object
	 */
	protected static $config = null;

	/**
	 * memcache connection collection
	 */
	protected static $cachePool = array();
	/**
	 * PDO collection
	 * @var Array
	 */
	protected static $dbPool = array();
	/**
	 * redis collection
	 * @var Array
	 */
	protected static $redisPool = array();

	/**
	 *  no instance
	 */
	private function __construct(){}

	/**
	 * get config object
	 * @param Config Object $config
	 */
	public static function getConfig() {
		return self::$config;
	}

	/**
	 * set config object
	 * @param Config Object $config
	 */
	public static function setConfig($config) {
		self::$config = $config;
	}	

	/**
	 * get a db connection
	 * @param string $dbConfigName
	 * @param Config Object $config 
	 * @return PDO
	 */
	public function getDb($dbConfigName, $config = null){
		if(!$config) {
			$config = self::$config;
		}
		$dbCfg = $config[$dbConfigName];
		
		if(isset(self::$dbPool[$dbCfg['dsn']]) && self::$dbPool[$dbCfg['dsn']]!=null){
			return self::$dbPool[$dbCfg['dsn']];
		}

		$db = new LazyPdoRw($dbCfg['dsn'],$dbCfg['username'],$dbCfg['password']);

		if(isset($dbCfg['slave_dsn'])){
			$db->setSlaveDsn($dbCfg['slave_dsn']);
		}
	
		self::$dbPool[$dbCfg['dsn']] = $db;

		return $db;
	}

	/**
	 * get a memcache connection
	 * @param string $cacheConfigName
	 * @param Config Object $config 
	 * @return Memecache
	 */
	public function getCache($cacheConfigName, $config = null){
		if(!$config) {
			$config = self::$config;
		}		
		$cacheCfg = $config[$cacheConfigName];
		
		if(!$cacheCfg['serverlist']) {
			throw new Exception('No serverlist property found via configName: '.$cacheConfigName);
		}

		if(isset(self::$cachePool[$cacheConfigName]) && self::$cachePool[$cacheConfigName]!=null){
			return self::$cachePool[$cacheConfigName];
		}

		$client = new LazyMemcached($cacheCfg['serverlist']);
		
		self::$cachePool[$cacheConfigName] = $client;

		return $client;
	}

	/**
	 * get a memcache connection
	 * @param string $redisConfigName
	 * @param Config Object $config 
	 * @return redis
	 */
	public function getRedis($redisConfigName, $config = null){
		if(!$config) {
			$config = self::$config;
		}			
		$redisCfg = $config[$redisConfigName];
		
		if(!$redisCfg['serverlist']) {
			throw new Exception('No serverlist property found via configName: '.$cacheConfigName);
		}

		if(isset(self::$redisPool[$redisConfigName]) && self::$redisPool[$redisConfigName]!=null){
			return self::$redisPool[$redisConfigName];
		}

		$client = new LazyRedis($redisCfg['serverlist'], $redisCfg['name']);
		
		self::$redisPool[$redisConfigName] = $client;

		return $client;
	}	

}