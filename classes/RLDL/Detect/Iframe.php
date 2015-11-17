<?php
namespace RLDL\Detect;

class Iframe {
	public function __construct() {}
	
	static public $error=false;
	
	static final function isAllowed($url=null) {
		self::$error==false;
		
		$curl=new \RLDL\Curl();
		
		$url=explode('#', $url)[0];
		
		$curl->error(function($data){
			\RLDL\Detect\Iframe::$error=true;
		});
		$curl->head($url);
		
		if (self::$error) return false;
		
		return (in_array(strtolower($curl->response_headers['X-Frame-Options']), array('deny', 'sameorigin')) ? false : true);
		
	}
}
?>