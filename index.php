<?php
ob_start();
require('config.php');

define('BASE_PATH', realpath(dirname(__FILE__)));
function rldl_autoloader($class)
{	
    $filename=BASE_PATH.'/classes/'.str_replace('\\', '/', $class).'.php';
    if (!file_exists($filename)) {
    	$filename=BASE_PATH.'/classes/'.str_replace('_', '/', $class).'.php';
    }
    include($filename);
}
spl_autoload_register('rldl_autoloader');

Facebook\FacebookRequest::setHttpClientHandler(new Facebook\HttpClients\FacebookStreamHttpClient());

$route=RLDL\Route::getInstance();

$route_name=($route->get()!=null ? $route->get() : 'null');

if ($route_name=='cdn') {
	$module_name=$route->getParam();
	if (file_exists(BASE_PATH.'/modules/cdn/'.$module_name.'.php')) {
		require(BASE_PATH.'/modules/cdn/'.$module_name.'.php');
	}
	else {
		new RLDL\Error(404, 'html');
	}
	exit();
}

try {
	$sql=MySQL::getInstance(true, C::SQL_DB, C::SQL_SERV, C::SQL_USER, C::SQL_PASS);
}
catch (Exception $e) {
	new RLDL\Error(array(
		'code'=>$e->getCode(),
		'string'=>$e->getMessage()
	), 'html');
}


session_set_cookie_params(0, '/', RLDL\Route::getCookieDomain());
if (!session_id()) {
	session_start();
}

try {
	if ($sql->Query("SET @@session.time_zone='+00:00'")==false) {
		$sql=null;
		throw new \Exception(
			'DB connection error.'
		);
	}
	
	if ($sql!=null) {
		$config=\RLDL\Config::getInstance();
		
		date_default_timezone_set('GMT');
		
		$auth_mode=$route::getCookie('P');
				
		if ($route->request('key','string')!=null) {
			$auth=RLDL\Auth\Key::getInstance($route->request('key','string'));
		} else if (in_array($auth_mode, array('Facebook','Google'))) {
			$class = new \ReflectionClass("RLDL\\Auth\\".$auth_mode);
			$auth=$class->newInstanceWithoutConstructor()->getInstance();
		}
		else {
			$auth=RLDL\Auth\Null::getInstance();
		}
		
		if ($auth->isUser() && $route->setLocale(\RLDL\User::getUser($auth->userId())->locale())) {
			// locale from user info
			$route::setCookie('L', \RLDL\User::getUser($auth->userId())->locale(), '+1 month');
		}
		else if (($route_locale=$route->request('locale','string'))!==null) {
			if ($route->setLocale($route_locale)) {
				$route::setCookie('L', $route_locale, '+1 month');
			}
		}
		else if (($cookie_locale=$route::getCookie('L'))!=null) {
			if ($route->setLocale($cookie_locale)===false) {
				$route::unsetCookie('L');
			}
		}
		else if (array_key_exists('HTTP_ACCEPT_LANGUAGE', $_SERVER)) {
			$langs=explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']);
			if (count($langs)>0) {
				$lang=explode('-',$langs[0]);
				switch (count($lang)) {
					case 1:
						$lang[1]=$lang[0];
					case 2:
						$route->setLocale(strtolower($lang[0]).'_'.strtoupper($lang[1]));
					break;
				}
			}
		}
	}
}
catch (Exception $e) {
	new RLDL\Error(array(
		'code'=>$e->getCode(),
		'string'=>$e->getMessage()
	), 'html');
}

if (file_exists(BASE_PATH.'/modules/'.$route_name.'.php')) {
	require(BASE_PATH.'/modules/'.$route_name.'.php');
}
else {
	new RLDL\Error(404, 'html');
}
ob_flush();
?>
