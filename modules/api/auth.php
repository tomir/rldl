<?php
$method=$route->method();
switch ($route->getParam()) {
	case 'methods': 
		$methods=array(
			'Facebook'=>array(
				'url'=>'https://login.rldl.net/facebook',
				'available_permissions'=>array('publish')
			),
			'Google'=>array(
				'url'=>'https://login.rldl.net/google',
				'available_permissions'=>array()
			)
		);
		$guessUser=$auth->guessUserId();
		
		$platform=null;
		
		if ($guessUser[0]!=null) {
			try {
				$user=\RLDL\User::getUser($guessUser[0]);
				$platform=$user->platform();
			}
			catch (\Exception $e) {
			}
		}
		$response['data']=array();
		foreach ($methods as $name => $data) {
			if ($name==$platform) {
				array_unshift($response['data'], array(
					'name'=>$name,
					'url'=>$data['url'],
					'available_permissions'=>$data['available_permissions']
				));
			}
			else {
				array_push($response['data'], array(
					'name'=>$name,
					'url'=>$data['url'],
					'available_permissions'=>$data['available_permissions']
				));
			}
		}
	break;
	case 'user':
		switch ($route->method()) {
			case 'get':
				$guessUser=$auth->guessUserId();
				if ($guessUser[0]!=null) {
					try {
						$user=\RLDL\User::getUser($guessUser[0]);
						$response=array(
							'first_name'=>$user->firstName(),
							'last_name'=>$user->lastName(),
							'name'=>$user->name(),
							'gender'=>$user->gender(),
							'platform'=>$user->platform(),
							'probability'=>$guessUser[1],
							'locale'=>$user->locale()
						);
					}
					catch (\Exception $e) {
					}
				}
				if (!array_key_exists('locale', $response)) {
					$response=array(
						'locale'=>$route->getLocale()
					);
				}
			break;
			default:
				throw new \Exception(
					'Bad request.',
					400
				);
		}
	break;
	case 'logout':
		$auth->logout();
		$auth=RLDL\Auth\Null::replaceInstance();
		$response=array(
			'authorized'=>$auth->isAuthorized(),
			'login'=>$auth->isLogin(),
			'user'=>$auth->isUser(),
		);
	break;
	case null:
		switch ($route->method()) {
			case 'delete':
				$auth->logout();
				$auth=RLDL\Auth\Null::replaceInstance();
				$response=array(
					'authorized'=>$auth->isAuthorized(),
					'login'=>$auth->isLogin(),
					'user'=>$auth->isUser(),
				);
			break;
			case 'get':
				$response=array(
					'authorized'=>$auth->isAuthorized(),
					'login'=>$auth->isLogin(),
					'user'=>$auth->isUser(),
				);
//				if ($route->request('locale','bool')) {
//					$guessUser=$auth->guessUserId();
//					if ($guessUser[0]!=null && $guessUser[1]>=0.5) {
//						try {
//							$response['locale']=\RLDL\User::getUser($guessUser[0])->locale();
//						}
//						catch (\Exception $e) {
//						}
//					}
//				}
			break;
			default:
				throw new \Exception(
					'Bad request.',
					400
				);
		}
	break;
	default:
		throw new \Exception(
			'Bad request.',
			400
		);
}
?>