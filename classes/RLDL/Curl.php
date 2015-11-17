<?php
namespace RLDL;

class Curl extends \Curl\Curl {
	public function __construct()
    {
        if (!function_exists('curl_init')) {
            require_once(dirname(__FILE__) . '/Curl/Purl.php');
            $this->curl = purl_init();
        }
		else {
        	$this->curl = curl_init();
        }
        $this->setDefaultUserAgent();
        $this->setOpt(CURLINFO_HEADER_OUT, true);
        $this->setOpt(CURLOPT_HEADER, true);
        $this->setOpt(CURLOPT_RETURNTRANSFER, true);
    }
    
    public function setDefaultUserAgent($withIP=true) {
        $user_agent = 'RLDL-extended-PHP-Curl-Class/' . parent::VERSION;
        $user_agent .= ' PHP/' . PHP_VERSION;
        $curl_version = curl_version();
        $user_agent .= ' curl/' . $curl_version['version'];
        if ($withIP) {
        	 $user_agent .= ' IP/' . (strlen($_SERVER['REMOTE_ADDR'])>0 ? $_SERVER['REMOTE_ADDR'] : null);
        }
        $this->setUserAgent($user_agent);
    }
    
    public function contentType() {
    	 return isset($this->response_headers['Content-Type']) ? explode(';', $this->response_headers['Content-Type'])[0] : null;
    }
    
    public function contentSize($i='MB') {
    	switch ($i) {
    		case 'MB':
    			$s=1048576;
    		break;
    		case 'KB':
    			$s=1024;
    		break;
    		default:
    			$s=1;
    	}
    	return isset($this->response_headers['Content-Length']) ? round((explode(';', $this->response_headers['Content-Length'])[0] / $s), 2) : null;
    }
    
    public function info($info) {
    	return curl_getinfo($this->curl, $info);
    }
}
?>