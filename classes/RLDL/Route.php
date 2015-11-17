<?php
namespace RLDL;

final class Route {
	private static $oInstance=false;
	
	private $domain_routes=array(
		'images.rldl.net'=>'images',
		'static.rldl.net'=>'static',
		'api.rldl.net'=>'api',
		'api.rldl.local'=>'api',
		'passbook.rldl.net'=>'passbook',
		'geoip.rldl.net'=>'geoip',
		'login.rldl.net'=>'login',
		'documents.rldl.net'=>'documents',
		'avatars.rldl.net'=>'avatars',
		'ping.rldl.net'=>'ping',
		'privacy.rldl.net'=>'privacy',
		'cdn.rldl.net'=>'cdn',
		'go.rldl.net'=>'go',
		'tools.rldl.net'=>'tools',
		'appstore.rldl.net'=>'appstore',
		'as.rldl.net'=>'appstore',
		'pay.rldl.net'=>'pay'
	);
	private $domain_redirect=array(
		'rldl.net'=>'http://tryrealdeal.com',
		'mail.rldl.net'=>'https://mail.google.com/a/rldl.net',
		'drive.rldl.net'=>'https://docs.google.com/a/rldl.net',
		'calendar.rldl.net'=>'https://www.google.com/calendar/hosted/rldl.net'
	);
	private $request_method;
	private $request_domain;
	private $request_params;
	
	private $request_url;
	
	static private $top_domain='rldl.net';
	
	static private $cookie_prefix='RLDL-';
	
	private $route;
	
	private $request_https;
	
	private $request_files;
	
	private $request;
	
	private $config;
	
	private $locale;
	
	public static function getInstance() {
		if (self::$oInstance==false) {
			self::$oInstance=new Route();
		}
		return self::$oInstance;
	}

	private function __construct() {
		$this->config=Config::getInstance();
		
		$this->locale=$this->config->get('default_locale');
		
		if (array_key_exists($_SERVER['HTTP_HOST'], $this->domain_redirect)) {
			header('Location:' .$this->domain_redirect[$_SERVER['HTTP_HOST']]);
			exit();
		}
		else if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']!='off') {
			$this->request_https=true;
			$this->request_domain='https://'.$_SERVER['HTTP_HOST'];
		}
		else {
			$this->request_https=false;
			$this->request_domain='http://'.$_SERVER['HTTP_HOST'];
		}
		
		if (strpos($this->request_domain, self::$top_domain)===false) {
			self::$top_domain='';
		}
		
		$this->request_method=strtolower($_SERVER['REQUEST_METHOD']);
		
		$this->request=$_REQUEST;
		
		if (array_key_exists('REQUEST_METHOD', $this->request)) {
			if (in_array($this->request['REQUEST_METHOD'], array('POST','GET','DELETE','PUT'))) {
				$this->request_method=strtolower($this->request['REQUEST_METHOD']);
				unset($this->request['REQUEST_METHOD']);
			}
		}
		
		$pathEnd=strpos($_SERVER['REQUEST_URI'], '?');
		$this->request_params=explode('/', substr($_SERVER['REQUEST_URI'], 1, ($pathEnd===false ? strlen($_SERVER['REQUEST_URI']) : $pathEnd-1)));

		$this->request_url=$this->request_domain.'/'.substr($_SERVER['REQUEST_URI'], 1, ($pathEnd===false ? strlen($_SERVER['REQUEST_URI']) : $pathEnd-1));
		
		if (array_key_exists($_SERVER['HTTP_HOST'], $this->domain_routes)) {
			$this->route=$this->domain_routes[$_SERVER['HTTP_HOST']];
			if ($this->request_params[0]==$this->route) {
				array_shift($this->request_params);
			}
		}
		else {
			$this->route=array_shift($this->request_params);
		}
		
		if ($this->request_method=='put') {
			$content=file_get_contents('php://input');
			if (stripos($content, 'Content-Disposition: form-data')!==false) {
				$request=$this->parseFormData($content);
			}
			else {
				parse_str($content, $request);
			}
			$this->request = array_merge($request, $this->request);
		}
		
		if (count($_FILES)>0) {
			$this->request_files=$_FILES;
		}
		else {
			$this->request_files=array();
		}
	}
	
	private function parseFormData($raw_data) {
		$boundary = substr($raw_data, 0, strpos($raw_data, "\r\n"));
		
		$parts = array_slice(explode($boundary, $raw_data), 1);
		$data = array();
		
		foreach ($parts as $part) {
			if ($part == "--\r\n") break; 
			$part = ltrim($part, "\r\n");
			list($raw_headers, $body) = explode("\r\n\r\n", $part, 2);
		
			$raw_headers = explode("\r\n", $raw_headers);
			$headers = array();
			foreach ($raw_headers as $header) {
				list($name, $value) = explode(':', $header);
				$headers[strtolower($name)] = ltrim($value, ' '); 
			} 
			
			if (isset($headers['content-disposition'])) {
				$filename = null;
				preg_match(
					'/^(.+); *name="([^"]+)"(; *filename="([^"]+)")?/', 
					$headers['content-disposition'], 
					$matches
				);
				list(, $type, $name) = $matches;
				
				$value=substr($body, 0, strlen($body) - 2);
				
				if (preg_match('/(.+)\[(.+)\]/i', $name, $m)) {
					$data[$m[1]][$m[2]] = $value;
				}
				else {
					$data[$name] = $value;
				}
				
				
			}
		}
		return $data;
	}
	
	public function get() {
		return $this->route;
	}
	
	public function getParam() {
		return array_shift($this->request_params);
	}
	
	public function method() {
		return $this->request_method;
	}
	
	public function domain() {
		return $this->request_domain;
	}
	
	public function url() {
		return $this->request_url;
	}
	
	public function topDomain() {
		return self::$top_domain;
	}
	
	static public function setSessionVar($name, $value, $no_prefix=false) {
		if ($no_prefix==true) {
			$_SESSION[$name]=$value;
		}
		else {
			$_SESSION[self::$cookie_prefix.$name]=$value;
		}
		return true;
	}
	
	static public function getSessionVar($name) {
		if (isset($_SESSION) && is_array($_SESSION) && count($_SESSION)>0 && array_key_exists(self::$cookie_prefix.$name, $_SESSION)) {
			return $_SESSION[self::$cookie_prefix.$name]; 
		}
		return null;
	}
	
	static public function getCookie($name) {
		if (array_key_exists(self::$cookie_prefix.$name, $_COOKIE)) {
			return $_COOKIE[self::$cookie_prefix.$name]; 
		}
		return self::getSessionVar($name);
	}
	
	static public function setCookie($name, $value, $time=null,$no_prefix=false) {
		if (!$no_prefix) $name=self::$cookie_prefix.$name;
		
		if (!is_numeric($time) && is_string($time) && $time!=null) {
			$time=strtotime($time);
		}
		
		self::setSessionVar($name, $value, true);
		
		return setcookie($name, $value, $time, '/', '.'.self::$top_domain);
	}
	
	static public function getCookieDomain() {
		return '.'.self::$top_domain;
	}
	
	static public function unsetCookie($name,$no_prefix=false) {
		return self::setCookie($name, null, time()-3600, $no_prefix);
	}
	
	static public function unsetAllCookies() {
		$return=true;
		foreach ($_COOKIE as $name => $value) {
			$return=self::unsetCookie($name,true);
		}
		return $return;
	}
	
	public function files() {
		return $this->request_files;
	}
	
	public function file($name=null) {
		if ($name!=null) {
			if (array_key_exists($name, $this->request_files)) {
				return $this->request_files[$name];
			}
			return null;
		}
		return array_shift($this->request_files);
	}
	
	public function request($field=null, $type=null, $allowed=null) {
		if ($field==null) {
			return $this->request;
		}
		$types=array('array','integer', 'int','string','bool','url','numeric','email');
		if (array_key_exists($field, $this->request) && ($type==null || in_array($type, $types))) {
			$value=$this->request[$field];
			switch ($type) {
				case 'bool':
					if ($value=='true' || $value=='1' || $value===true) {
						return true;
					}
					else {
						return false;
					}
				break;
				case 'array':
					if (!is_array($value)) {
						if (is_numeric($value) || is_string($value)) {
							return array($value);
						}
						return array();
					}
				break;
				case 'int':
				case 'integer':
					if (is_numeric($value)) {
						$value=(int)$value;
					}
					if (!is_int($value)) {
						return null;
					}
				break;
				case 'numeric':
					if (!is_numeric($value)) {
						return null;
					}
				break;
				case 'url':
					if (!filter_var($value, FILTER_VALIDATE_URL)) {
						return null;
					}
				break;
				case 'email':
					if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
						return null;
					}
				break;
				default:
					if (!is_string($value)) {
						return null;
					}
					if (is_array($allowed)) {
						if (!in_array($value, $allowed)) {
							return null;
						}
					}
			}
			return $value;
		}
		else if ($type=='bool') {
			return false;
		}
		else if ($type=='array') {
			return array();
		}
		return null;
	}
	public function getIP() {
		return (strlen($_SERVER['REMOTE_ADDR'])>0 ? $_SERVER['REMOTE_ADDR'] : null);
	}
	public function setLocale($locale) {
		if (in_array($locale, $this->config->get('locales'))) {
			$this->locale=$locale;
			return true;
		}
		return false;
	} 
	public function getLocale() {
		return $this->locale;
	}
	
	public function header($field=null, $type=null) {
		$headers=self::getallheaders();
		if ($field==null) {
			return $headers;
		}
		
		$types=array('array','integer','int','string','str','bool','url','numeric','email');
		if (array_key_exists($field, $headers) && ($type==null || in_array($type, $types))) {
			$value=$headers[$field];
			switch ($type) {
				case 'bool':
					if ($value=='true' || $value=='1' || $value===true) {
						return true;
					}
					else {
						return false;
					}
				break;
				case 'array':
					if (!is_array($value)) {
						if (is_numeric($value) || is_string($value)) {
							return array($value);
						}
						return array();
					}
				break;
				case 'int':
				case 'integer':
					if (is_numeric($value)) {
						$value=(int)$value;
					}
					if (!is_int($value)) {
						return null;
					}
				break;
				case 'numeric':
					if (!is_numeric($value)) {
						return null;
					}
				break;
				case 'url':
					if (!filter_var($value, FILTER_VALIDATE_URL)) {
						return null;
					}
				break;
				case 'email':
					if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
						return null;
					}
				break;
				default:
					if (!is_string($value)) {
						return null;
					}
					if (is_array($allowed)) {
						if (!in_array($value, $allowed)) {
							return null;
						}
					}
			}
			return $value;
		}
		else if ($type=='bool') {
			return false;
		}
		else if ($type=='array') {
			return array();
		}
		return null;
	}
	
	private static function getallheaders() {
		if (!function_exists('getallheaders')) { 
			$headers = ''; 
			foreach ($_SERVER as $name => $value) { 
				if (substr($name, 0, 5) == 'HTTP_') { 
					$headers[str_replace(' ', '-', strtolower(str_replace('_', ' ', substr($name, 5))))] = $value; 
				} 
			}
		}
		else {
			foreach (getallheaders() as $name => $value) { 
				$headers[strtolower($name)] = $value;
			}
		}
		return $headers;
	}
	
	public function isGAE() {
		if (array_key_exists('APPLICATION_ID', $_SERVER)) {
			return true;
		}
		return false;
	}
}
?>