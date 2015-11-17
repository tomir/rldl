<?php
namespace RLDL;

final class I18n {
	private static $dir='other/i18n';
	
	private static $oInstance=false;
	
	private static $cache;
	private static $config;
	private static $route;
	private static $auth;
	private static $engine;
	
	public static function getInstance($file_names=null) {
		if (self::$oInstance==false) {
			self::$config=Config::getInstance();
			self::$route=Route::getInstance();
			self::$auth=Auth::getInstance();
			self::$oInstance=new I18n();
			self::$cache=array();
			self::$engine=new \Mustache_Engine;
		}
		if ($file_names!=null) {
			if (!is_array($file_names)) {
				$file_names=array($file_names);
			}
			
			foreach ($file_names as $file) {
				$files=array(self::$dir.'/'.self::$config->get('default_locale').'/'.$file.'.json');
				
				if (self::$route->getLocale()!=self::$config->get('default_locale')) {
					array_push($files, self::$dir.'/'.self::$route->getLocale().'/'.$file.'.json');
				}
				
				foreach ($files as $file) {
					if (file_exists($file)) {
						$a=json_decode(file_get_contents($file),true);
						self::$cache=array_merge(self::$cache, (is_array($a) ? $a : []));
					}
				}
			}
		}
		return self::$oInstance;
	}

	private function __construct() {
		// do nothing ;)
	}
	
	public static function load($file) {
		self::getInstance($file);
	}
	
	public function _($r) {
	
		$or=$r;
	
		$j=json_decode($r,true);
		
		if ($j!==null) {
			$or=$r=$j['template']['string'];
			if (isset($j['template']['variant'])) {
				if (strlen($j['template']['variant'])==0) {
					$j['template']['variant']='default';
				}
				$r.='/'.$j['template']['variant'];
			}
		}
		
		$s=preg_replace("/[^a-zA-Z0-9\/_]+/", "", str_replace(' ', '_', $r));
		
		$a=explode('/', $s);
		
		$o=self::$cache;
		
		for ($i=0; $i<count($a); $i++) {
			if (isset($o[$a[$i]])) {
				if ($i<(count($a)-1)) {
					if (is_array($o[$a[$i]])) {
						$o=$o[$a[$i]];
					}
					else if (is_string($o[$a[$i]])){
						return self::$engine->render($o[$a[$i]], ($j!==null) ? $j['view'] : []);
					}
					else if (is_string($o['default'])){
						return self::$engine->render($o['default'], ($j!==null) ? $j['view'] : []);
					}
					else {
						return $or;
					}
				}
				else {
					return self::$engine->render($o[$a[$i]], ($j!==null) ? $j['view'] : []);
				}
			}
			else {
				return $or;
			}
		}
	}
	
	public static function cache(){
		return self::$cache;
	}
}
?>