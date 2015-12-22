<?php

/**
* Use this class to force cache client lazy connect to cache server
**/

class LazyMemcached {

	/**
	 * memcache server list
	 * @var array
	 */
    private $serverList = null;
    private $serverCount = 0;
	/**
	 *local cache storage
	 * @var array
	 */
	public static $localCache = array();

	/**
	 * memcache instance
	 * @var object
	 */
	private $_memcache = null;

	/**
	* construct function
	* @param $serverList string such as '192.168.1.1:11211:3,192.168.1.2:11211'
	**/
	public function __construct($serverList){
		$this->initServerList($serverList);
	}

	/**
	 *init server list from configuration
	 * @return null
	 */
	public function initServerList($serverList){
	
		$server_arr = explode(',', $serverList);
		foreach($server_arr as $server_str){
			$single_server = array();
			$t_arr = explode(':', $server_str);
			if(trim($t_arr[0])!='' && intval($t_arr[1])>0) {
				$single_server['host'] = trim($t_arr[0]);
				$single_server['port'] = intval($t_arr[1]);
				if(isset($t_arr[2]) && $t_arr[2]>0){
					$single_server['weight'] = intval($t_arr[2]);
				}else{
					$single_server['weight'] = 1;
				}
				$server_list_arr[] = $single_server;		
			}
		}
		if(count($server_list_arr)<1){
			throw new Exception("no cache server found.");
		}
		$this->serverList = $server_list_arr;
		$this->serverCount = count($this->serverList);
	}

	/**
	 *get server list
	 */
	public function getServerList(){
		return $this->serverList;
	}	

	/**
	 *get server count
	 */
	public function getServerCount(){
		return $this->serverCount;
	}	

	/**
	 * get memcache instance
	 * @param string $key
	 * @return memcache instance
	 */
	private function connect(){
		
		if($this->_memcache){
			return $this->_memcache;
		}

		$this->_memcache = new Memcached();

		$options = array(
            Memcached::OPT_DISTRIBUTION         => Memcached::DISTRIBUTION_CONSISTENT,
            Memcached::OPT_HASH                 => Memcached::HASH_MD5,
            Memcached::OPT_LIBKETAMA_COMPATIBLE => true,
		);

		foreach($options as $key => $val) {
			$this->_memcache->setOption($key, $val);
		}

		$this->_memcache->addServers($this->serverList);
		
		return $this->_memcache;
	}
	/**
	 * get key from memcache server
	 * @param mixed $key
	 * @param string $custom_hash_key if $custom_hash_key existes, use it for hash index creation
	 * @return mixed
	 */
	public function get($key){
		//get from localCache
		if(isset(self::$localCache[$key]) && self::$localCache[$key]){
			return self::$localCache[$key];
		}

		$conn = $this->connect($key);
		if(!$conn){
			return false;
		}
		$data = $conn->get($key);
		//set for localCache
		if($data){
			self::$localCache[$key] = $data;
		}
		return $data;
	}
	/**
	 * set date to memcache server
	 * @param string $key
	 * @param mixed $data
	 * @param int $expire
	 * @param string $custom_hash_key
	 * @return boolean
	 */
	public function set($key, $data, $expire = 0){

		$conn = $this->connect($key);
		if(!$conn){
			return false;
		}

		$ret_val = $conn->set($key, $data, $expire);
		//set for localCache
		if($ret_val){
			self::$localCache[$key] = $data;
		}
		return $ret_val;
	}
	/**
	 * delete a key from memcache server
	 * @param string $key
	 * @param string $custom_hash_key
	 * @return boolean
	 */
	public function delete($key){

		$conn = $this->connect($key);
		if(!$conn){
			return false;
		}
		$ret_val = $conn->delete($key);
		//delete local cache
		unset(self::$localCache[$key]);
		return $ret_val;
	}

	/**
	 * Please don't call this function, it is for unit test
	**/

	public function clearLocalCache() {
		self::$localCache = array();
	}

}