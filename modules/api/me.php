<?php
if ($auth->isUser()) {
	switch ($route->getParam()) {
		case 'clients':
			switch ($route->method()) {
				case 'get':
					$response['data']=array();
					foreach ($auth->getClients() as $id => $permission) {
						try {
							$response['data'][]=RLDL\Client::getItem($id)->get();
						}
						catch (Exception $e) {
						}
					}
				break;
				default:
					throw new \Exception(
						'Bad request.',
						400
					);
			}
		break;
		case 'follows': 
			switch ($route->method()) {
				case 'get':
					$limit=$route->request('limit','int');
					$campaigns=$auth->user()->followedCampaigns($route->request('start','int'), ($limit!=null && $limit<100 ? $limit : 100));
					if (array_key_exists('next', $campaigns)) {
						$response['paging']['next']=$route->url().'?start='.$campaigns['next'][0].'&limit='.$campaigns['next'][1];
						unset($campaigns['next']);
					}
					foreach ($campaigns as &$item) {
						try {
							$campaign=$item->get();
							if ($route->request('images','bool')) {
								$campaign['images']=$item->getImages();
							}
							$response['data'][]=$campaign;
						}
						catch (Exception $e) {
							$item->unfollow();
						}
					}
				break;
				default:
					throw new \Exception(
						'Bad request.',
						400
					);
			}
		break;
		case 'codes': 
			switch ($route->method()) {
				case 'get':
					$limit=$route->request('limit','int');
					$codes=$auth->user()->getCodes(array(
						'campaign_id'=>$route->request('campaign_id','int'),
						'deal_id'=>$route->request('deal_id','int'),
						'variant_id'=>$route->request('variant_id','int'),
					), $route->request('start','int'), ($limit!=null && $limit<100 ? $limit : 100));
					if (array_key_exists('next', $codes)) {
						$response['paging']['next']=$route->url().'?start='.$codes['next'][0].'&limit='.$codes['next'][1];
						unset($codes['next']);
					}
					$response['data']=$codes;
				break;
				default:
					throw new \Exception(
						'Bad request.',
						400
					);
			}
		break;
		case null:
			switch ($route->method()) {
				case 'get':
					$response=array(
						'id'=>$auth->user()->id(),
						'first_name'=>$auth->user()->firstName(),
						'last_name'=>$auth->user()->lastName(),
						'name'=>$auth->user()->name(),
						'gender'=>$auth->user()->gender(),
						'platform'=>$auth->user()->platform(),
						'locale'=>$auth->user()->locale(),
						'email'=>$auth->user()->emailPreview(),
						'avatar'=>$auth->user()->avatar()
					);
					if ($route->request('permissions','bool')) {
						$response['permissions']=$auth->user()->permissions();
					}
					if ($route->request('geoip','bool') && $route->isGAE()) {
						$location=explode(',', $_SERVER['HTTP_X_APPENGINE_CITYLATLONG']);
						
						$response['geoip']=array(
							'country'=>strtoupper($_SERVER['HTTP_X_APPENGINE_COUNTRY']),
							'city'=>ucwords(strtolower($_SERVER['HTTP_X_APPENGINE_CITY'])),
							'location'=>array(
								'latitude'=>$location[0],
								'longitude'=>$location[1]
							)
						);
					} 
					if ($route->request('clients','bool')) {
						$response['clients']=array();
						foreach ($auth->getClients() as $id => $permission) {
							try {
								$response['clients'][]=RLDL\Client::getItem($id)->get();
							}
							catch (Exception $e) {
							}
						}
					}
					
				break;
				case 'delete':
					if ($auth->user()->delete()) {
						$response=array(
							'id'=>$auth->user()->id()
						);
						$auth->logout();
						$auth=RLDL\Auth\Null::replaceInstance();
					}
					else {
						throw new \Exception(
							'',
							500
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
		default:
			throw new \Exception(
				'Bad request.',
				400
			);
	}
}
else if ($auth->isAuthorized()){
	throw new \Exception(
		'Allowed only for users.',
		401
	);
}
else {
	throw new \Exception(
		'Bed request.',
		400
	);
}
?>