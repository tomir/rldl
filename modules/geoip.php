<?php
switch ($route->getParam()) {
	case 'map':
		$m=new RLDL\Mustache();
		$m->setTemplateFile('map','map');
		
		$browser=new RLDL\Detect\Mobile;
		
		$m->setView(array(
			'browser'=>array(
				'mobile'=>$browser->isMobile()
			),
			'geo'=>array(
				'ip'=>$_SERVER['REMOTE_ADDR'],
				'country'=>array(
					'iso'=>strtoupper($_SERVER['HTTP_X_APPENGINE_COUNTRY'])
				),
				'region'=>array(
					'iso'=>strtoupper($_SERVER['HTTP_X_APPENGINE_REGION'])
				),
				'city'=>array(
					'name'=>ucwords($_SERVER['HTTP_X_APPENGINE_CITY'])
				),
				'location'=>array(
					'latitude'=>(float)explode(',', $_SERVER['HTTP_X_APPENGINE_CITYLATLONG'])[0],
					'longitude'=>(float)explode(',', $_SERVER['HTTP_X_APPENGINE_CITYLATLONG'])[1]
				)
			)
		));
		
		if ($auth->isLogin()) {
			$m->setView(array(
				'user'=>array(
					'first_name'=>$auth->user()->firstName(),
					'last_name'=>$auth->user()->lastName(),
					'name'=>$auth->user()->name(),
					'gender'=>$auth->user()->gender(),
					'avatar'=>$auth->user()->avatar()
				)
			));
		}
		else {
			$guessUser=$auth->guessUserId();
			if ($guessUser[1]>0.5) {
				$user=\RLDL\User::getUser($guessUser[0]);
				$m->setView(array(
					'user'=>array(
						'first_name'=>$user->firstName(),
						'last_name'=>$user->lastName(),
						'name'=>$user->name(),
						'gender'=>$user->gender(),
						'avatar'=>$auth->user()->avatar()
					)
				));
			}
		}
		
		echo $m->render('map');
	break;
	case null:
		switch ($route->request('db')) {
			case 'gae':
				$response=array(
					'ip'=>$_SERVER['REMOTE_ADDR'],
					'country'=>array(
						'iso'=>strtoupper($_SERVER['HTTP_X_APPENGINE_COUNTRY'])
					),
					'region'=>array(
						'iso'=>strtoupper($_SERVER['HTTP_X_APPENGINE_REGION'])
					),
					'city'=>array(
						'name'=>ucwords($_SERVER['HTTP_X_APPENGINE_CITY'])
					),
					'location'=>array(
						'latitude'=>(float)explode(',', $_SERVER['HTTP_X_APPENGINE_CITYLATLONG'])[0],
						'longitude'=>(float)explode(',', $_SERVER['HTTP_X_APPENGINE_CITYLATLONG'])[1]
					)
				);
			break;
			default:
				$ip=$auth->isAuthorized() && array_key_exists('ip', $_REQUEST) ? (filter_var($_REQUEST['ip'], FILTER_VALIDATE_IP) ? $_REQUEST['ip'] : $_SERVER['REMOTE_ADDR']) : $_SERVER['REMOTE_ADDR'];
				
				$reader = new GeoIp2\Database\Reader('other/geoip_db/GeoLite2-City.mmdb');
				
				try {
					$record = $reader->city($ip);
					
					$response=array(
						'ip'=>$ip,
						'country'=>array(
							'iso'=>$record->country->isoCode,
							'name'=>$record->country->name
						),
						'region'=>array(
							'iso'=>$record->mostSpecificSubdivision->isoCode,
							'name'=>$record->mostSpecificSubdivision->name
						),
						'city'=>array(
							'name'=>$record->city->name,
							'postal_code'=>$record->postal->code
						),
						'location'=>array(
							'latitude'=>$record->location->latitude,
							'longitude'=>$record->location->longitude
						)
					);
				}
				catch (Exception $exception) {
					new RLDL\Error(404,'json',(array_key_exists('callback', $_REQUEST) ? $_REQUEST['callback'] : null));
				}
		}
		
		header('Content-Type: text/javascript');
		if (array_key_exists('callback', $_REQUEST)) {
			echo $_REQUEST['callback'].'('.json_encode($response).');';
		}
		else {
			echo json_encode($response);
		}
	break;
	default:
		new RLDL\Error(404, 'html');
}
?>