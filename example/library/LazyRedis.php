<?php

/**
* this is a Rediska wapper
**/

class LazyRedis {

	private $serverList = null;
    private $options = null;
 
	/**
	 *connection object
	 */
	private $conn = null;

	/**
	* construct function
	* @param $hostlist string such as '192.168.1.1:11211:3,192.168.1.2:11211'
	* config example:
	*	redis_app.serverlist='192.168.1.1:11211:3,192.168.1.2:11211'
	*   redis_app.namespace = 'App_'
	*	
	*
	*
	**/
	public function __construct($serverlist, $namespace = 'App_'){
		$this->initServerList($serverlist);
		$options = array(
			'servers' => $this->serverList,
			'namespace' => $namespace,
			'addToManager' => false,
		);
		$this->options = $options; 
	}

	/**
	 *init server list from configuration
	 * @return null
	 */
	public function initServerList($serverlist){
	
		$server_arr = explode(',', $serverlist);
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
			throw new Exception("no server found.");
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
	 *get options
	 */
	public function getOptions(){
		return $this->options;
	}		

	/**
	 * connect to a server
	 * @param string $key
	 * @return mixed
	 */
	private function connect($key){
		if($this->conn) {
			return $this->conn;
		}

		$conn = new Rediska($this->options);
		$this->conn = $conn;
		return $conn;
	}
	
	/**
	 * create String instance
	 * @see http://rediska.geometria-lab.net/documentation/
	 */
	public function createString($keyName){
		$key = new Rediska_Key($keyName, array('rediska' => $this->conn));
		return $key;
	}	

	/**
	 * create List instance
	 * @see http://rediska.geometria-lab.net/documentation/
	 */
	public function createList($listName){
		$list = new Rediska_Key_List($listName, array('rediska' => $this->conn));
		return $list;
	}	

	/**
	 * create Set instance
	 * @see http://rediska.geometria-lab.net/documentation/
	 */
	public function createSet($setName){
		$set = new Rediska_Key_List($setName, array('rediska' => $this->conn));
		return $set;
	}		

	/**
	 * create Sorted Set instance
	 * @see http://rediska.geometria-lab.net/documentation/
	 */
	public function createSortedSet($setName){
		$set = new Rediska_Key_SortedSet($setName, array('rediska' => $this->conn));
		return $set;
	}		

	/**
	 * create Hash instance
	 * @see http://rediska.geometria-lab.net/documentation/
	 */
	public function createHash($hashName){
		$hash = new Rediska_Key_Hash($hashName, array('rediska' => $this->conn));
		return $hash;
	}
	
}