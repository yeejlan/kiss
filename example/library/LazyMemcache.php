<?php

/**
* Use this class to force cache client lazy connect to cache server
**/

class LazyMemcache {

	/**
	 * memcache server list
	 * @var array
	 */
    private $serverList = null;
    private $serverCount = 0;
	/**
	 *memcache connection pool
	 * @var array
	 */
	private $connPool = null;
	/**
	 *local cache storage
	 * @var array
	 */
	public static $localCache = array();

	/**
	* construct function
	* @param $hostlist string such as '192.168.1.1:11211:3,192.168.1.2:11211'
	**/
	public function __construct($hostlist){
		$this->initServerList($hostlist);
	}

	/**
	 *init server list from configuration
	 * @return null
	 */
	public function initServerList($hostlist){
	
		$server_arr = explode(',', $hostlist);
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
				for($i=0; $i<$single_server['weight']; $i++){
					$server_list_arr[] = $single_server;
				}				
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
	 * get a random server index
	 * @param string $key
	 * @return int
	 */
	public function hashIndex($key){
		if($this->serverCount == 1) {
			return 0;
		}

		if(is_array($key)){
			$key = implode(',', $key);
		}

		$md5_key_str = md5($key);
		$server_index = hexdec(substr($md5_key_str,0,4)) % count($this->serverList);
		return $server_index;
	}
	/**
	 * connect to a server
	 * @param string $key
	 * @return mixed
	 */
	private function connect($key){
		$server_index = $this->hashIndex($key);
		if(isset($this->connPool[$server_index]['conn'])){
			return $this->connPool[$server_index]['conn'];
		}
		$conn = new Memcached();
		$conn->addServer($this->serverList[$server_index]["host"],$this->serverList[$server_index]["port"]);
		
		$this->connPool[$server_index]['conn'] = $conn;
		return $conn;
	}
	/**
	 * get key from memcache server
	 * @param mixed $key
	 * @param string $custom_hash_key if $custom_hash_key existes, use it for hash index creation
	 * @return mixed
	 */
	public function get($key, $custom_hash_key = null){
		//get from localCache
		if(isset(self::$localCache[$key]) && self::$localCache[$key]){
			return self::$localCache[$key];
		}
		if($custom_hash_key != null){
			$conn = $this->connect($custom_hash_key);
		}else{
			$conn = $this->connect($key);
		}
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
	public function set($key, $data, $expire = 0, $custom_hash_key = null){
		if($custom_hash_key != null){
			$conn = $this->connect($custom_hash_key);
		}else{
			$conn = $this->connect($key);
		}
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
	public function delete($key, $custom_hash_key = null){
		if($custom_hash_key != null){
			$conn = $this->connect($custom_hash_key);
		}else{
			$conn = $this->connect($key);
		}
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