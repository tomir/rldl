<?php
namespace RLDL;

final class Config {
	private static $oInstance=false;
	
	private static $cache=array();
	private static $sql=null;
	
	private $loaded=false;
	
	public static function getInstance() {
		if (self::$oInstance==false) {
			self::$oInstance=new Config();
		}
		return self::$oInstance;
	}
	
	public static function getVar($name) {
		return self::getInstance()->get($name);
	}

	private function __construct() {
		
	}
	
	private function sql(){
		if (self::$sql==null) {
			self::$sql=\MySQL::getInstance();
		}
		return self::$sql;
	}
	
	public function get($name) {
		if (!isset(self::$cache[$name])) {
			$const=strtoupper($name);
			if (@constant('\C::'.$const)) {
				self::$cache[$name]=$this->parse(constant('\C::'.$const));
			}
			else {
				$this->getAll();
				if (!isset(self::$cache[$name])) {
					if (($data=$this->sql()->SelectSingleRowArray('[API]Config', array(
						'autoload'=>0,
						'entity_name'=>\MySQL::SQLValue($name)
					)))!==false) {
						self::$cache[$name]=$this->parse($data['entity_value']);
					}
					else {
						self::$cache[$name]=null;
					}
				}
			}
		}
		return self::$cache[$name];
	}
	
	public function all() {
		$this->getAll();
		return self::$cache;
	}
	
	private function getAll() {
		if ($this->loaded==false) {
			$this->loaded=true;
			$data=$this->sql()->SelectArray('[API]Config', array('autoload'=>1));
			if (is_array($data)) {
				foreach ($data as $v) {
					self::$cache[$v['entity_name']]=$this->parse($v['entity_value']);
				}
			}
		}
	}
	
	private function parse($data) {
		if (preg_match('/\(([a-z]+)\)(.+)/i', $data, $m)) {
			$data=$m[2];
			switch ($m[1]) {
				case 'json':
					$data=json_decode($data, true);
				break;
				case 'file':
					$path=realpath(dirname(__FILE__).'/../../config/'.$data);
					
					if (file_exists($path)) {
						$data=$path;
					}
					else {
						$data=null;
					}
				break;
				default: 
					settype($data, $m[1]);
			}
		}
		else if (is_numeric($data)) {
			$data=(float)$data;
		}
		return $data;
	}
}
?>