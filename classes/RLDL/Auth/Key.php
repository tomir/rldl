<?php
namespace RLDL\Auth;

class Key extends Null {
	public $key;
	
	public function __construct($key) {
		self::$route=\RLDL\Route::getInstance();
		self::$sql=\MySQL::getInstance();
		self::$config=\RLDL\Config::getInstance();
		
		if (func_num_args() != 1) {
			throw new \InvalidArgumentException(
				'The constructor takes exactly one argument.'
			);
		}
		
		$key=explode('|', base64_decode($key));
		
		if (!count($key)==2) {
			throw new \InvalidArgumentException(
				'Argument is not key.'
			);
		}
		
		if (!is_numeric($key[0]) || strlen($key[1])==0) {
			throw new \InvalidArgumentException(
				'Argument is not valid key.'
			);
		}
		
		$this->getFromDB($key);
	
		parent::__construct();
	}
	
	function __destruct() {
		parent::__destruct();
	}
	
	public function isLogin() {
		return false;
	}
	
	public function isAuthorized() {
		if (is_array($this->key)) {
			return $this->isKeyValid();
		}
		return false;
	}
	
	private function getFromDB($key) {
		$dbkey=self::$sql->SelectSingleRowArray('[API]Keys', array(
			'key_id'=>\MySQL::SQLValue($key[0],'int'),
			'key_secret'=>\MySQL::SQLValue($key[1])
		));
		
		if ($dbkey!==false) {
			$this->key=array(
				'type'=>$dbkey['key_type'],
				'valid_to'=>strtotime($dbkey['key_valid_to'])
			);
			if ($this->key['type']=='user') {
				parent::$user_id=$dbkey['key_type_id'];
				$this->getUserPermisions();
			}
			else if ($this->key['type']=='client') {
				$this->clients[$dbkey['key_type_id']]=2;
			}
			else if ($this->key['type']=='system') {
				$this->clients=array('system'=>true);
			}
		}
	}
	
	private function isKeyValid() {
		if (is_array($this->key)) {
			if ($this->key['valid_to']>=time()) {
				return true;
			}
			else {
				return false;
			}
		}
		return false;
	}
}
?>