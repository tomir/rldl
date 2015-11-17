<?php
namespace RLDL;

class Null {
	public function __call($a,$b){
		return false;
	}
	static public function __callStatic($a,$b){
		return false;
	}
}
?>