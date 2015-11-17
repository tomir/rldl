<?php
namespace RLDL\Auth;

\Facebook\FacebookSession::setDefaultApplication(\RLDL\Config::getInstance()->get('fb_app_id'), \RLDL\Config::getInstance()->get('fb_app_secret'));

class Facebook extends Null {
	private static $prefix='rldl-facebook-';
	
	private static $session=null;
	
	public function __construct() {
		self::$sql=\MySQL::getInstance();
		
		self::load();
		
		parent::__construct();
		
		$this->getUserPermisions();
	}
	
	static private function load(){
		if (self::$session==null) {
			self::$session=self::getSession();
		}
		
		if (self::$session!=null) {
			try {
				self::$session->validate();
			} catch (\Exception $ex) {
				self::$session=null;
				self::unsetSessionCookie();
			}
		}
		
		if (self::$session!=null) {
			if (($user=\RLDL\User\Facebook::getUserByID(self::FB_getUser()->getID()))!=false) {
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
		if (self::$session!=null && self::$user_id>0) {
			return true;
		}
		return false;
	}
	
	public function isAuthorized() {
		return $this->isLogin();
	}
	
	
	static private function FB_getUser() {
		if (self::$session!=null) {
			$response=(new \Facebook\FacebookRequest(
				self::$session, 'GET', '/me'
			))->execute();
			
			if (is_object($response)) {
				return $response->getGraphObject(\Facebook\GraphUser::className());
			}
		}
		return new \RLDL\Null();
	}
	
	static public function FB_getPermissions() {
		$permissions=array();
		if (self::$session!=null) {
			$response=(new \Facebook\FacebookRequest(
				self::$session, 'GET', '/me/permissions'
			))->execute();
			if (is_object($response)) {
				foreach ($response->getGraphObject()->asArray() as $item){
					if ($item->status==='granted') {
						$permissions[$item->permission]=true;
					}
				}
			}
		}
		return $permissions;
	}
	
	static function getSession() {
		if (array_key_exists(self::$prefix.'token', $_SESSION)) {
			return new \Facebook\FacebookSession($_SESSION[self::$prefix.'token']);
		}
		else if (($cookie=\RLDL\Route::getCookie('FBTH'))!=null) {
			$token=self::$sql->QuerySingleRowArray('SELECT * FROM `[API]Tokens` WHERE MD5(CONCAT(user_id,"|",token))='.\MySQL::SQLValue($cookie));
			
			if ($token!=false) {
				return new \Facebook\FacebookSession($token['token']);
			}
		}
		return null;
	}
	
	private static function FB_updateUser() {
		$FBuser=self::FB_getUser();
		$id=$FBuser->getID();
		if ($id!=null) {
			$user_data=array(
				'fb_id'=>$id,
				'fb_link'=>$FBuser->getLink(),
				'fb_location'=>($FBuser->getLocation()!=null ? $FBuser->getLocation()->getCity() : null),
				'name'=>$FBuser->getName(),
				'email'=>$FBuser->getProperty('email'),
				'first_name'=>$FBuser->getFirstName(),
				'last_name'=>$FBuser->getLastName(),
				'gender'=>$FBuser->getProperty('gender'),
				'locale'=>$FBuser->getProperty('locale'),
				'avatar'=>'https://graph.facebook.com/'.$id.'/picture?width=50&height=50',
				'raw'=>$FBuser->asArray()
			);
			
			if (($user=\RLDL\User\Facebook::getUserByID($id))==false) {
				$user=\RLDL\User\Facebook::newUser($user_data);
			}
			else {
				$user->update($user_data);
				
			}
			
			$token=self::$session->getAccessToken();
			$longLivedAccessToken = $token->extend();
			
			$sql=\MySQL::getInstance();

			$last=$sql->QueryArray("SELECT token_id FROM `[API]Tokens` WHERE user_id=".\MySQL::SQLValue($user->id(),'int')." ORDER BY token_id DESC LIMIT 1 OFFSET 5");

			if (count($last)==1) {
				$sql->DeleteRows('[API]Tokens', array(
					'user_id='.\MySQL::SQLValue($user->id(),'int').' AND token_id<='.$last[0]['token_id']
				));
			}
						
			$sql->InsertRow('[API]Tokens', array(
				'user_id'=>\MySQL::SQLValue($user->id(),'int'),
				'token'=>\MySQL::SQLValue((string)$longLivedAccessToken)
			));
			
			self::$user_id=$user->id();
			
			self::setSessionCookie((string)$longLivedAccessToken);
			
			parent::updateFriendsList($user);
			
			return true;
		}
		return false;
	}
	
	public static function setSessionFromRedirect($url) {
		$helper = new \Facebook\FacebookRedirectLoginHelper($url);
		try {
		    self::$session = $helper->getSessionFromRedirect();
		} catch(\Exception $e) {
		    self::$session=null;
		    return false;
		}
		self::FB_updateUser();
		return $helper;
	}
	
	public static function setSessionFromSignedRequest($tab=false) {
		$helper = ($tab ? new \Facebook\FacebookPageTabHelper() : new \Facebook\FacebookCanvasLoginHelper());
		try {
		  self::$session = $helper->getSession();
		} catch (\Exception $ex) {
		  self::$session=null;
		  return false;
		}
		self::FB_updateUser();
		return $helper;
	}
	
	public static function getRedirectUrl($url,$scope=array()) {
		$helper = new \Facebook\FacebookRedirectLoginHelper($url);
		return $helper->getLoginUrl($scope);
	}
	
	static private function setSessionCookie($token) {
		$_SESSION[self::$prefix.'token']=$token;
		\RLDL\Route::setCookie('FBTH', md5(self::$user_id.'|'.$token), '+1 month');
		\RLDL\Route::setCookie('P', 'Facebook', '+1 month');
	}
	
	static private function unsetSessionCookie() {
		if (array_key_exists(self::$prefix.'token', $_SESSION)) {
			unset($_SESSION[self::$prefix.'token']);
		}
		\RLDL\Route::unsetCookie('FBTH');
		\RLDL\Route::unsetCookie('P');
	}
}
?>