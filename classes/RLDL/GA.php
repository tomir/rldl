<?php
namespace RLDL;

class GA {
	protected static $oInstance=false;
	private static $prefix='rldl-google-analitics-';
	
	private $client=null;
	private $service=null;
	
	final public static function getInstance() {
		if (self::$oInstance==false) {
			self::$oInstance=new self();
		}
		return self::$oInstance;
	}
	
	public function __construct() {
		$this->client=new \Google_Client();
		$this->client->setApplicationName(Config::getVar('ga_app_name'));
		
		$this->service=new \Google_Service_Analytics($this->client);
		
		if (isset($_SESSION[self::$prefix.'service_token'])) {
			$this->client->setAccessToken($_SESSION[self::$prefix.'service_token']);
		}
		
		$credits=new \Google_Auth_AssertionCredentials(
		    Config::getVar('ga_mail'),
		    array('https://www.googleapis.com/auth/analytics.readonly'),
		    file_get_contents(Config::getVar('ga_key'))
		);
		
		$this->client->setAssertionCredentials($credits);
		
		if ($this->client->getAuth()->isAccessTokenExpired()) {
			$this->client->getAuth()->refreshTokenWithAssertion($credits);
		}
		
		$_SESSION[self::$prefix.'service_token']=$this->client->getAccessToken();
	}
	
	public function get($metrics, $start_date, $end_date, $params=array()){
		$data=$this->service->data_ga->get(
			Config::getVar('ga_ids'),
			$start_date,
			$end_date,
			$metrics,
			$params
		);
		if (isset($data->rows)) {
			return $data->rows;
		}
		return array();
	}
}
