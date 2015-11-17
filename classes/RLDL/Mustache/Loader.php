<?php
namespace RLDL\Mustache;

class Loader implements \Mustache_Loader {
	private $templates;
	
	public function __construct() {
		$this->templates=array();
	}
	public function set($name='', $value='') {
		$name=(string)$name;
		$value=(string)$value;
		if (strlen($name)>1) {
			$this->templates[$name]=$value;
		}
	}
	
	public function setFile($name='', $url='') {
		$url=(string)$url;
		if (file_exists($url)) {
			return $this->set($name, file_get_contents($url));
		}
	}
	
	public function load($name) {
		if (!isset($this->templates[$name])) {
			throw new \InvalidArgumentException('Template '.$name.' not found.');
		}
		return $this->templates[$name];
	}
}
?>