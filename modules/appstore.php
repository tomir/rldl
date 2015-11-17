<?php
$data=json_decode(base64_decode($route->getParam()), true);
if (!is_array($data)) {
	new RLDL\Error(400, 'html');
}

$detect=new RLDL\Detect\Mobile();

/*
data:
	ios - iOS app ID / or iPhone app ID if iPad is present
	ipad - iPad app ID
	wp - windows phone app ID
	play - android app ID
	name - App name string


*/

$name=false;

if (array_key_exists('name', $data)) {
	if (is_string($data['name']) & strlen($data['name'])>=1) {
		$name=$data['name'];
	}
	unset($data['name']);
}


$app_name=($name!=false ? strtolower(preg_replace("/[^a-zA-Z0-9\/-]+/", "", str_replace(' ', '-', $name))) : 'app');

$url_patterns=array(
	'ios'=>'https://itunes.apple.com/pl/app/'.$app_name.'/id{{id}}?mt=8',
	'ipad'=>'https://itunes.apple.com/pl/app/'.$app_name.'/id{{id}}?mt=8',
	'wp'=>'https://www.windowsphone.com/pl-pl/store/app/'.$app_name.'/{{id}}',
	'play'=>'https://play.google.com/store/apps/details?id={{id}}'
);

foreach ($url_patterns as $key=>$url) {
	if (array_key_exists($key, $data)) {
		if (is_string($data[$key])) {
			if (!filter_var($data[$key], FILTER_VALIDATE_URL)) {
				$data[$key]=str_replace('{{id}}', $data[$key], $url);
			}
		}
		else {
			unset($data['key']);
		}
	}
}

$url=false;

switch ($route->method()) {
	case 'get':
		if ($detect->isMobile()) {
			if (array_key_exists('ios', $data) && $detect->isiOS()) {
				// App Store
				if (array_key_exists('ipad', $data) && $detect->isiPad()) {
					// ipad
					$url=$data['ipad'];
				}
				else {
					// other ios
					$url=$data['ios'];
				}
			}
			else if (array_key_exists('wp', $data) && $detect->isWindowsPhoneOS()) {
				// Windows Phone Store
				$url=$data['wp'];
			}
			else if (array_key_exists('play', $data) && $detect->isAndroidOS()) {
				// Google Play Store
				$url=$data['play'];
			}
		}
		
		if ($url!=false) {
			header('Location: '.$url);
			exit();
		}
		
		$user=$auth->user();
		
		$route->setLocale($user->locale());
		RLDL\I18n::load('store');
		
		$m=new RLDL\Mustache();
		$m->setTemplateFile('appstore','appstore');
		
		$view=array(
			'name'=>$name,
			'stores'=>$data,
			'locale'=>$route->getLocale()
		);
		
		if ($auth->isUser()) {
			$view['user']=array(
				'first_name'=>$user->firstName(),
				'last_name'=>$user->lastName(),
				'name'=>$user->name(),
				'gender'=>$user->gender(),
				'avatar'=>$user->avatar()
			);
		}
		
		$m->setView($view);
		
		echo $m->render('appstore');
	break;
	default:
		new RLDL\Error(400, 'html');
}