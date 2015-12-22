<?php
class ApiClient {

    private $host;

    // URL to use here will vary based on your environment; ask your friendly neighborhood developer!
    public function __construct($host) {
        $this->setHost($host);
    }

    public function setHost($host) {
        $this->host = $host;
    }

    /**
     * call a remote method
     * @param $request Array such as       
     *   $request = array(
     *       'method' => 'User.InFo', 
     *       'idlist' => 'coretest01,123',
     *   );
     * @param $timeout
     *
     * @return array(error, result)
     */
    public function call($request, $timeout = 5) {
        $postdataStr = json_encode($request);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->host);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POST, 1 );
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postdataStr);
        curl_setopt($ch, CURLOPT_ENCODING, 'deflate');
        if($timeout) curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);

        $result = curl_exec($ch);

        $curlErrMsg = '';
        if($result === false) {
            $curlErrMsg = 'Curl error: ' . curl_error($ch);
        } else {
            $curlInfo = curl_getinfo($ch);
            if($curlInfo['http_code'] != 200) {
                $curlErrMsg = 'Http error: '. json_encode($curlInfo);
            }
        }
        curl_close($ch);

        if($curlErrMsg !== '') {
            return array(new ApiClientError(-1, 'request_failed', $curlErrMsg), null);
        }

        return $this->processResult($result);
    }

    private function processResult($result) {
        $resultArr = json_decode($result, true);
        if($resultArr && $resultArr['status'] == 'success') {
            return array(false, $resultArr['result']);
        }

        $err = $resultArr['error'];
        if($err) {
            return array(new ApiClientError($err['code'],
                $err['message'], $err['detail']), null);
        } 
        
        return array(new ApiClientError(-2, 'invalid_response', $result), null);
    }


    /**
     * call multiple methods at the same time
     *
     * @param $requestArr
     *   $request1 = array(
     *       'method' => 'User.InFo', 
     *       'idlist' => 'coretest01,123',
     *       '_host'   => 'http://api.utan.com', //optional, will use $this->host if it's empty
     *       '_timeout' => 5, //optional, will use $timeout if it's empty
     *   );
     *   $request2 = array(
     *       'method' => 'User.getAvatar', 
     *       'idlist' => 'coretest01,123',
     *   );     
     *   $requestArr = array(
     *       'request1' => $request1, 
     *       'request2' => $request2,
     *   );         
     * @param $timeout
     *
     * @return array(error, result)
     */
    public function callMulti($requestArr, $timeout = 5) {
        $mh = curl_multi_init();
        $ch = array();
        foreach($requestArr as $idx => $request) {
            $ch[$idx] = curl_init();

            $host = $this->host;
            if(isset($request['_host'])){
                $host = $request['_host'];
                unset($request['_host']);
            }
            curl_setopt($ch[$idx], CURLOPT_URL, $host);
            curl_setopt($ch[$idx], CURLOPT_RETURNTRANSFER, 1 );
            curl_setopt($ch[$idx], CURLOPT_HTTPAUTH, CURLAUTH_ANY);
            curl_setopt($ch[$idx], CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch[$idx], CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch[$idx], CURLOPT_POST, 1 );
            $postdataStr = json_encode($request);
            curl_setopt($ch[$idx], CURLOPT_POSTFIELDS, $postdataStr);
            curl_setopt($ch[$idx], CURLOPT_ENCODING, 'deflate');
            if(isset($request['_timeout'])){
                $timeout = $request['_timeout'];
                unset($request['_timeout']);
            }
            if($timeout) curl_setopt($ch[$idx], CURLOPT_TIMEOUT, $timeout);
            curl_multi_add_handle ($mh, $ch[$idx]);   
        }

        $active = null;
        do {
            $mrc = curl_multi_exec($mh, $active);
        } while ($mrc == CURLM_CALL_MULTI_PERFORM);

        while ($active && $mrc == CURLM_OK) {
            if (curl_multi_select($mh) != -1) {
                do {
                    $mrc = curl_multi_exec($mh, $active);
                } while ($mrc == CURLM_CALL_MULTI_PERFORM);
            }
        }

        $resultArr = array();
        foreach($requestArr as $idx => $request){
            $resultArr[$idx] = curl_multi_getcontent($ch[$idx]);
            curl_multi_remove_handle($mh, $ch[$idx]);
            curl_close($ch[$idx]);
        }

        $returnArr = array();
        foreach($resultArr as $idx => $result) {
            $returnArr[$idx] = $this->processResult($result);
        }

        return $returnArr;
    }    
}

class ApiClientError {
    public $code;
    public $message;
    public $detail;

    public function __construct($code, $message, $detail = null) {
        $this->code = $code;
        $this->message = $message;
        $this->detail = $detail;
    }
}