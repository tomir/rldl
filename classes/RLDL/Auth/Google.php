<?php
namespace RLDL\Auth;

class Google extends Null {
	private static $prefix='rldl-google-';
	
	private static $token=null;
	
	private static $client=null;
	
	public function __construct() {
		self::$sql=\MySQL::getInstance();

		self::GP_client();
		
		self::load();
		
		parent::__construct();
		
		$this->getUserPermisions();
	}
	
	static private function GP_client() {
		if (self::$client==null) {
			$config=\RLDL\Config::getInstance();
			
			self::$client=new \Google_Client();
			self::$client->setClientId($config->get('gp_client_id'));
			self::$client->setClientSecret($config->get('gp_client_secret'));
			//self::$client->setDeveloperKey($config->get('gp_dev_key'));
			self::$client->setApplicationName($config->get('gp_app_name'));
			self::$client->setScopes(array('https://www.googleapis.com/auth/plus.login','email'));
			
		}
	}
	
	private static function load(){
		self::GP_client();
		
		if (self::$token==null) {
			self::getSession();
		}
		
		if (self::$token!=null) {
			try {
				self::$client->setAccessToken(self::$token);
				$token_data=self::$client->verifyIdToken()->getAttributes();
			} catch (\Exception $ex) {
				self::$token=null;
				self::unsetSessionCookie();
			}
		}
		
		
		
		if (self::$token!=null) {
			if (($user=\RLDL\User\Google::getUserByID($token_data["payload"]["sub"]))!=false) {
				self::$user_id=$user->id();
			}
			else {
				self::unsetSessionCookie();
			}
		}
	}
	
	static public function reload() {
		return self::load();
	}
	
	public function isLogin() {
		if (self::$token!=null && self::$user_id>0) {
			return true;
		}
		return false;
	}
	
	public function isAuthorized() {
		return $this->isLogin();
	}
	
	private static function getSession() {
		self::GP_client();
		
		if (array_key_exists(self::$prefix.'token', $_SESSION)) {
			self::$token=$_SESSION[self::$prefix.'token'];
			return true;
		}
		else if (($cookie=\RLDL\Route::getCookie('GPTH'))!=null) {
			$token=self::$sql->QuerySingleRowArray('SELECT * FROM `[API]Tokens` WHERE MD5(CONCAT(user_id,"|",token))='.\MySQL::SQLValue($cookie));
			
			if ($token!=false) {
				self::$token=$token['token'];
				return true;
			}
		}
		return false;
	}
	
	private static function GP_updateUser() {
		$plus = new \Google_Service_Plus(self::$client);
		$oauth2=new \Google_Service_Oauth2(self::$client);
		
		$me = $plus->people->get('me');
		
		$userinfo = $oauth2->userinfo->get();

		if (array_key_exists('id', $me)) {
			$locale=explode('_',str_replace('-', '_', $me['language']));
			if (count($locale)<2 || strlen($locale[1])!=2) {
				$locale[1]=$locale[0];
			}
			$locale=strtolower($locale[0]).'_'.strtoupper($locale[1]);
			
			$user_data=array(
				'gp_id'=>$me['id'],
				'gp_link'=>$me['url'],
				'gp_location'=>(array_key_exists('placesLived', $me) ? $me['placesLived'][0]['value'] : null),
				'name'=>$me['displayName'],
				'email'=>(array_key_exists('email', $userinfo) ? $userinfo['email'] : null),
				'first_name'=>$me['name']['givenName'],
				'last_name'=>$me['name']['familyName'],
				'gender'=>$me['gender'],
				'locale'=>$locale,
				'avatar'=>$me['image']['url'],
				'raw'=>$me
			);
			
			if (($user=\RLDL\User\Google::getUserByID($me['id']))==false) {
				$user=\RLDL\User\Google::newUser($user_data);
			}
			else {
				$user->update($user_data);
			}
			
			$sql=\MySQL::getInstance();
			
			$last=$sql->QueryArray("SELECT token_id FROM `[API]Tokens` WHERE user_id=".\MySQL::SQLValue($user->id(),'int')." ORDER BY token_id DESC LIMIT 1 OFFSET 5");
			
			if (count($last)==1) {
				$sql->DeleteRows('[API]Tokens', array(
					'user_id='.\MySQL::SQLValue($user->id(),'int').' AND token_id<='.$last[0]['token_id']
				));
			}
			
			$token=self::$client->getAccessToken();
						
			$sql->InsertRow('[API]Tokens', array(
				'user_id'=>\MySQL::SQLValue($user->id(),'int'),
				'token'=>\MySQL::SQLValue($token)
			));
			
			self::$user_id=$user->id();
			
			self::setSessionCookie($token);
			
			parent::updateFriendsList($user);
			
			return true;
		}
		return false;
	}
	
	public static function setSessionFromRedirect($url) {
		self::GP_client();
		
		self::$client->setRedirectUri($url);
		
		$code=\RLDL\Route::getInstance()->request('code','string');
		
		if ($code!=null) {
			try {
				self::$client->authenticate($code);
			}
			catch(\Exception $e) {
			    return false;
			}
			
			$token=self::$client->getAccessToken();
			if ($token!=array() && $token!=null && $token!=false) {
				self::$token=$token;
				self::GP_updateUser();
				return true;
			}
		}
		return false;
	}
	
	public static function getRedirectUrl($url) {
		self::GP_client();
		
		self::$client->setRedirectUri($url);
		return self::$client->createAuthUrl();
	}
	
	static private function setSessionCookie($token) {
		$_SESSION[self::$prefix.'token']=$token;
		\RLDL\Route::setCookie('GPTH', md5(self::$user_id.'|'.$token), '+1 month');
		\RLDL\Route::setCookie('P', 'Google', '+1 month');
	}
	
	static private function unsetSessionCookie() {
		self::GP_client();
		
		if (array_key_exists(self::$prefix.'token', $_SESSION)) {
			unset($_SESSION[self::$prefix.'token']);
		}
		\RLDL\Route::unsetCookie('GPTH');
		\RLDL\Route::unsetCookie('P');
	}
}
?>