<?php
namespace RLDL\Auth;

class Null {
	static protected $sql;
	static protected $route;
	static protected $config;
	
	protected static $oInstance=false;
	
	static protected $user_id=null;
	
	static protected $clients=array();
	
	static protected $p_id=null;
	
	final public static function replaceInstance() {
		self::$oInstance=false;
		self::$user_id=null;
		self::$clients=array();
		return self::getInstance();
	}
	
	final public static function getInstance() {
		if (self::$oInstance==false) {
			$calledClass = get_called_class();
			$args = func_get_args();
			$class = new \ReflectionClass($calledClass);
			self::$oInstance=$class->newInstanceArgs($args);
		}
		return self::$oInstance;
	}
	
	public function __construct() {
		self::$route=\RLDL\Route::getInstance();
		self::$sql=\MySQL::getInstance();
		self::$config=\RLDL\Config::getInstance();
		
		$time=time()+30*24*60*60;
		
		$cookie=\RLDL\Route::getCookie('Q');
		
		if ($cookie!=null) {
			self::$p_id=$cookie;
		}
		else {
			self::$p_id=uniqid('rldl_',true);
		}
		
		if ($this->isUser()) {
			$session=self::$sql->SelectSingleRowArray('[API]Sessions',array(
				'user_id'=>\MySQL::SQLValue($this->userId(), 'int'),
				'session_ip'=>\MySQL::SQLValue(self::$route->getIP())
			), array(
				'session_uid'
			), array(
				'-session_id'
			));
			
			if (isset($session['session_uid'])) {
				self::$p_id=$session['session_uid'];
			}
			
			self::$sql->AutoInsertUpdate('[API]Sessions', array(
				'user_id'=>\MySQL::SQLValue($this->userId()),
				'session_uid'=>\MySQL::SQLValue(self::$p_id),
				'session_valid_to'=>\MySQL::SQLValue(date("Y-m-d H:i:s",$time),'datetime'),
				'session_ip'=>\MySQL::SQLValue(self::$route->getIP())
			), array(
				'user_id'=>\MySQL::SQLValue($this->userId()),
				'session_uid'=>\MySQL::SQLValue(self::$p_id)
			));
		
		}
		
		\RLDL\Route::setCookie('Q', self::$p_id, $time);
		
	}
	
	public function __destruct() {
	}
	
	public function isLogin() {
		return false;
	}
	
	public function isAuthorized() {
		return false;
	}
	public function isUser() {
		if ($this->isAuthorized() && $this->userId()>0) {
			return true;
		}
		return false;
	}
	
	public function userId() {
		return (string)self::$user_id;
	}
	
	public function user() {
		if ($this->isUser()) {
			return \RLDL\User::getUser($this->userId());
		}
		return new \RLDL\Null();
	}
	
	public function logout() {
		$seesion_id=\RLDL\Route::getCookie('Q');
		self::$route->unsetAllCookies();
		session_unset();
		\RLDL\Route::setCookie('Q', $seesion_id);
	}
	
	public function isSystem() {
		if ($this->isCron() || (array_key_exists('system', self::$clients) && self::$clients['system']===true)) {
			return true;
		}
		return false;
	}
	
	public function isCron() {
		if (\RLDL\Route::getInstance()->header(\RLDL\Config::getVar('cron_header'),'bool')==true) {
			return true;
		}
		return false;
	}
	
	public function guessUserId() {
		$user=array(null,0);
		
		if ($this->userId()>0) {
			$user=array($this->userId(),3);
		}
		else {
			$byIp=$this->guessUserIdByIP();
			$bySession=$this->guessUserIdBySession();
			
			if ($byIp!==null && $bySession!==null && $byIp===$bySession) {
				$user=array($byIp,2);
			}
			else if ($bySession!==null) {
				$user=array($bySession,1);
			}
			else if ($byIp!==null) {
				$user=array($byIp,1);
			}
		}
		$user[1]=round($user[1]/3*100)/100;
		return $user;
	}
	
	private function guessUserIdByIP() {
		$session=self::$sql->SelectSingleRowArray('[API]Sessions',array(
			'session_ip'=>\MySQL::SQLValue(self::$route->getIP())
		), array('user_id'), array('-session_valid_to'));
		if (is_array($session)) {
			if (array_key_exists('user_id', $session)) {
				return (int)$session['user_id'];
			}
		}
		return null;
	}
	private function guessUserIdBySession() {
		$session=self::$sql->SelectSingleRowArray('[API]Sessions',array(
			'session_uid'=>\MySQL::SQLValue(self::$p_id)
		), array('user_id'), array('-session_valid_to'));
		if (is_array($session)) {
			if (array_key_exists('user_id', $session)) {
				return (int)$session['user_id'];
			}
		}
		return null;
	}
	
	public function getPermission($client_id) {
		if ($this->isAuthorized()) {
			if (!is_numeric($client_id) || $client_id<1) {
				throw new \InvalidArgumentException(
					'Wrong client id.',
					1003
				);
			}
			if (isset(self::$clients[$client_id])) {
				return self::$clients[$client_id];
			}
			else if (array_key_exists('system', self::$clients) && self::$clients['system']===true) {
				return '3';
			}
		}
		return 0;
	}
	
	public function getClients() {
		if ($this->isUser()) {
			return self::$clients;
		}
		else {
			throw new \Exception(
				'Allowed only for users.',
				401
			);
		}
	}
	
	protected static function updateFriendsList($user) {
		$sql=\MySQL::getInstance();
		if ($sql->SelectRows('[User]Friends_update', array(
			'user_id'=>\MySQL::SQLValue($user->id(),'int')
		))) {
			if (!$sql->HasRecords()) {
				$sql->InsertRow('[User]Friends_update', array(
					'user_id'=>\MySQL::SQLValue($user->id(),'int')
				));
			}
		}
	}
	
	public function getUserPermisions() {
		if ($this->userId()>0) {
			if (in_array($this->userId(), self::$config->get('admins'))) {
				self::$clients['system']=true;
				//return true;
			}
			foreach (self::$sql->SelectArray('[Client]Users', 'WHERE `user_id`='.\MySQL::SQLValue($this->userId(),'int').' GROUP BY `client_id`, `user_id` ORDER BY `client_name` ASC', 'client_id, client_name, MAX(`user_type`) AS `user_type`') as $row) {
				self::$clients[$row['client_id']]=$row['user_type'];
			}
			return true;
		}
	}
	
	public static function getToken($user_id) {
		$sql=\MySQL::getInstance();
		$token_data=$sql->SelectSingleRowArray('[API]Tokens', array("user_id"=>\MySQL::SQLValue($user_id,'int')), array('token'), '-token_time');
		
		return (is_array($token_data) && array_key_exists('token', $token_data) ? $token_data['token'] : null);
	}
}
?>