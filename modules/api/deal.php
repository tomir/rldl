<?php
$id=$route->getParam();
$method=$route->method();

$image_types=array('ico', 'ico_alternative');
switch ($route->getParam()) {
	case 'reminders':
		switch ($method) {
			case 'get':
				$deal=RLDL\Deal::getItem($id);
				$response['data']=$deal->getReminders();
			break;
			default:
				throw new \Exception(
					'Bad request.',
					400
				);	
		}
	break;
	case 'reminder':
		$deal=RLDL\Deal::getItem($id);
		switch ($method) {
			case 'get':
				$response=$deal->getReminder($route->getParam());
			break;
			case 'post':
				$response['reminder_id']=$deal->createReminder($route->request('reminder_time','string'), $route->request('reminder_options','array'));
			break;
			case 'delete':
				$reminder_id=$route->getParam();
				if ($deal->deleteReminder($reminder_id)) {
					$response['reminder_id']=$reminder_id;
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
		$deal=RLDL\Deal::getItem($id);
		$variant_id=$route->request('variant_id','int');
		if ($variant_id==null) {
			$variant_id=$route->request('with_sharing','bool');
		}
		switch ($method) {
			case 'get':
				$limit=$route->request('limit','numeric');
				
				$codes=$deal->getCodes($variant_id, $route->request('start','numeric'), ($limit!=null && abs($limit)<=1000 ? $limit : 1000));
				
				if (is_string($codes)) {
					$response['code_value']=$codes;
				}
				else {
					if (array_key_exists('next', $codes)) {
						$response['paging']['next']=$route->url().'?'.http_build_query(array(
							'start'=>$codes['next'][0],
							'limit'=>$codes['next'][1],
							'with_sharing'=>$route->request('with_sharing','bool')
						));
						unset($codes['next']);
					}
					$response['data']=$codes;
				}
			break;
			case 'post':
				$response['count']=$deal->addCodes($variant_id, $route->request('codes','array'));
			break;
			case 'put':
				$response['count']=$deal->markCodesUsed($variant_id, $route->request('codes','array'));
			break;
			case 'delete':
				$deal->deleteCodes($variant_id, $route->request('codes','array'));
				$response['count']=count($route->request('codes','array'));
			break;
			default:
				throw new \Exception(
					'Bad request.',
					400
				);
		}
	break;
	case 'stats':
		switch ($method) {
			case 'get':
				$deal=RLDL\Deal::getItem($id);
				foreach (array('count', 'count_in_days', 'best_days', 'best_hours', 'with_sharing', 'from_mobile', 'gender', 'number_of_downloads', 'best_users', 'affiliations') as $key) {
					if (in_array($key, array('count_in_days'))) {
						if ($route->request($key, 'int')!==null && $route->request($key, 'int')>=0) {
							$response[$key]=$deal->stats($key, $route->request($key, 'int'));
						}
					}
					else if (in_array($key, array('best_users')) && $route->request($key, 'bool') && $route->request('with_details', 'bool')) {
						$response[$key]=array();
						foreach ($deal->stats($key) as $id => $value) {
							try {
								$response[$key][]=RLDL\User::getUser($id)->get();
							}
							catch (Exception $e) { }
							
						}
					}
					else if (in_array($key, array('affiliations')) && $route->request($key, 'bool') && $route->request('with_details', 'bool')) {
						$client=RLDL\Client::getItem($deal->get()['client_id']);
						$response[$key]=array();
						foreach ($deal->stats($key) as $id => $value) {
							try {
								$response[$key][]=array(
									'affiliation'=>$client->getAffiliation($id),
									'count'=>$value
								);
							}
							catch (Exception $e) { }
							
						}
					}
					else if ($route->request($key, 'bool')) {
						$response[$key]=$deal->stats($key);
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
	case 'code':
		switch ($method) {
			case 'get':
				$deal=RLDL\Deal::getItem($id);
				$response=$deal->get();
				$response['variant']=$deal->getCode($route->request('publish','bool'));
				
				if ($route->request('mail','bool') && (!array_key_exists('code_redownload', $response['variant']) || $response['variant']['code_redownload']!=true) && filter_var($auth->user()->email(), FILTER_VALIDATE_EMAIL)) {
					RLDL\I18n::load('campaign');
					
					$campaign=RLDL\Campaign::getItem($response['campaign_id'])->get();
					
					$mail=RLDL\Mail::newItem(array(
						'to'=>$auth->user(),
						'from'=>array(
							'name'=>(strlen($campaign['campaign_brand'])>0 ? $campaign['campaign_brand'].' ('.$config->get('name').')' : $config->get('name')),
							'address'=>$config->get('mail_from')
						),
						'Reply-To'=>array(
							'name'=>$config->get('name'),
							'address'=>$config->get('mail_replyto')
						)
					));
					
					$mail->setInfo(array(
						'client_id'=>$response['client_id'],
						'campaign_id'=>$response['campaign_id'],
						'deal_id'=>$id,
						'user_id'=>$auth->user()->id(),
						'mail_type'=>'deal'
					));
					
					$mail->createFromTemplates(array(
						'title'=>'mail/deal/title',
						'html'=>'mail/deal/html'
					), array(
						'user'=>$auth->user()->get(),
						'campaign'=>array_merge($campaign, array(
							'images'=>RLDL\Campaign::getItem($response['campaign_id'])->getImages(),
							'campaign_url'=>str_replace(
								'{{campaign_id}}',
								$campaign['campaign_alias'],
								$config->get('url_campaign')
							)
						)),
						'deal'=>array_merge($response, array('images'=>$deal->getImages()))
					));
					$response['mail']['mail_id']=$mail->create()->id();
				}
				if ($route->request('publish','bool') && !array_key_exists('code_redownload', $response['variant'])) {
					$response['post']=$deal->post();
					//$response['action']=$deal->postAction();
				}
			break;
			default:
				throw new \Exception(
					'Bad request.',
					400
				);
		}
	break;
	case 'image':
		$image=$route->getParam();
		if (!in_array($image, $image_types)) {
			throw new \Exception(
				'Bad request.',
				400
			);
		}
		$deal=RLDL\Deal::getItem($id);
		switch ($method) {
			case 'get':
				$response=$deal->getImage($image);
			break;
			case 'put':
			case 'post':
				$file=$route->file();
				if (!is_null($file)) {
					$deal->uploadImage($image, $file);
				}
				else {
					$deal->setImage($image, $route->request('url', 'url'));
				}
				$response=$deal->getImage($image);
			break;
			case 'delete':
				$deal->deleteImage($image);
			break;
			default:
				throw new \Exception(
					'Bad request.',
					400
				);
		}
	break;
	case 'status':
		switch ($method) {
			case 'put':
				$deal=RLDL\Deal::getItem($id);
				
				switch ($route->request('deal_status', 'int')) {
					case 1:
						$deal->activate();
					break;
					case 3:
						$deal->hide();
					break;
					default:
						throw new \Exception(
							'Bad request.',
							400
						);
						
					$response=$deal->get();
				}
			break;
			default:
				throw new \Exception(
					'Bad request.',
					400
				);
		}
	break;
	case null:
		switch ($method) {
			case 'get':
				$deal=RLDL\Deal::getItem($id);
				$response=$deal->get();
				if ($route->request('images','bool')) {
					$response['images']=$deal->getImages();
				}
				if ($route->request('variants','bool')) {
					$response['variants']=$deal->getVariants();
					if ($route->request('code','bool') && !$response['deal_limited']) {
						foreach ($response['variants'] as &$variant) {
							$variant['variant_code']=$deal->getCodes($variant['variant_id']);
						}
					}
				}
			break;
			case 'put':
			case 'post':
				if ($method=='put') {
					$deal=RLDL\Deal::getItem($id);
					$deal->update($route->request());
				}
				else {
					$deal=RLDL\Deal::create($route->request());
				}
				$images=$route->request('images', 'array');
				
				foreach ($image_types as $img_name) {
					$file=$route->file($img_name);
					if ($file!=null) {
						try {
							$deal->uploadImage($img_name, $file);
						}
						catch (Exception $e) {
							print_r($e);
						}
					}
					else if (is_array($images) && array_key_exists($img_name, $images)) {
						try {
							if (strlen($images[$img_name])==0) {
								if ($method=='put') $deal->deleteImage($img_name);
							}
							else {
								$deal->setImage($img_name, $images[$img_name]);
							}
						}
						catch (Exception $e) {
							print_r($e);
						}
					}
				}
				$response=$deal->get();
			break;
			case 'delete':
				$deal=RLDL\Deal::getItem($id);
				$deal->delete();
				$response['deal_id']=$id;
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