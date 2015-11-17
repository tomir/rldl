<?php
$id=$route->getParam();
$method=$route->method();
switch ($route->getParam()) {
	case 'affiliations':
		$client=RLDL\Client::getItem($id);
		switch ($method) {
			case 'get':
				$limit=$route->request('limit','numeric');
				
				$affiliations=$client->getAffiliations($route->request('start','numeric'), ($limit!=null && abs($limit)<=1000 ? $limit : 1000));
				
				if (array_key_exists('next', $affiliations)) {
					$response['paging']['next']=$route->url().'?'.http_build_query(array(
						'start'=>$affiliations['next'][0],
						'limit'=>$affiliations['next'][1]
					));
					unset($affiliations['next']);
				}
				
				$response['data']=$affiliations;
			break;
			default:
				throw new \Exception(
					'Bad request.',
					400
				);
		}
	break;
	case 'affiliation':
		$client=RLDL\Client::getItem($id);
		switch ($method) {
			case 'get':
				$response=$client->getAffiliation($route->getParam());
			break;
			case 'post':
				$response=$client->getAffiliation($client->createAffiliation($route->request('affiliation_name','string'), $route->request('affiliation_call','url')));
			break;
			case 'put':
				$affiliation_id=$route->getParam();
				$client->updateAffiliation($affiliation_id, $route->request('affiliation_name','string'), $route->request('affiliation_call','url'));
				$response=$client->getAffiliation($affiliation_id);
			break;
			case 'delete':
				$affiliation_id=$route->getParam();
				$client->deleteAffiliation($affiliation_id);
				$response['affiliation_id']=$affiliation_id;
			break;
			default:
				throw new \Exception(
					'Bad request.',
					400
				);
		}
	break;
	case 'admins':
		$client=RLDL\Client::getItem($id);
		switch ($method) {
			case 'get':
				$limit=$route->request('limit','numeric');
				
				$admins=$client->getAdmins($route->request('start','numeric'), ($limit!=null && abs($limit)<=1000 ? $limit : 1000));
				
				if (array_key_exists('next', $admins)) {
					$response['paging']['next']=$route->url().'?'.http_build_query(array(
						'start'=>$admins['next'][0],
						'limit'=>$admins['next'][1],
						'with_details'=>$route->request('with_details','bool')
					));
					unset($admins['next']);
				}
				
				if ($route->request('with_details','bool')) {
					foreach ($admins as &$admin) {
						try {
							$admin['user']=RLDL\User::getUser($admin['user_id'])->get();
							unset($admin['user_id']);
						}
						catch (Exception $e) {
							unset($admin);
						}
					}
					
				}
				$response['data']=$admins;
			break;
			default:
				throw new \Exception(
					'Bad request.',
					400
				);
		}
	break;
	case 'admin':
		switch ($method) {
			case 'delete':
				$user_id=$route->getParam();
				RLDL\Client::getItem($id)->deleteAdmin($user_id);
				$response['user_id']=$user_id;
			break;
			default:
				throw new \Exception(
					'Bad request.',
					400
				);
		}
	break;
	case 'invites':
		switch ($method) {
			case 'post':
				$quantity=$route->request('quantity', 'int');
				if ($quantity!=null) {
					$client=RLDL\Client::getItem($id);
					$response['data']=array();
					for ($i=0; ($i<$quantity && $i<100); $i++) {
						$response['data'][]=$client->createInvite($route->request('user_type', 'int'), $route->request('valid_days', 'int'));
					}
				}
				else {
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
	break;
	case 'invite':
		$client=RLDL\Client::getItem($id);
		switch ($method) {
			case 'post':
				$response=$client->createInvite($route->request('user_type', 'int'), $route->request('valid_days', 'int'));
			break;
			case 'put':
				$response=RLDL\Client::checkInviteIsValid($route->request('invite', 'string'));
			break;
			default:
				throw new \Exception(
					'Bad request.',
					400
				);
		}
		
		if (($method=='post' || $method=='put') && $route->request('mail','email')!=null) {
			RLDL\I18n::load(array('campaign', 'invite'));
			
			$mail=RLDL\Mail::newItem(array(
				'to'=>$route->request('mail','email'),
				'from'=>array(
					'name'=>$auth->user()->name().' ('.$config->get('name').')',
					'address'=>$config->get('mail_from')
				),
				'Reply-To'=>array(
					'name'=>$config->get('name'),
					'address'=>$config->get('mail_replyto')
				)
			));
			
			$mail->setInfo(array(
				'client_id'=>$client->id(),
				'user_id'=>$auth->user()->id(),
				'mail_type'=>'invite'
			));
			
			$m=new Mustache_Engine;
			
			$mail->createFromTemplates(array(
				'title'=>'mail/invite/title',
				'html'=>'mail/invite/html'
			), array(
				'client'=>array(
					'client_id'=>$client->id(),
					'client_name'=>$client->name(),
					'invite_url'=>$m->render($config->get('url_app_invite'), array(
						'invite'=>$response['invite_code'],
						'client_id'=>$client->id()
					))
				),
				'user'=>$auth->user()->get()
			));
			$response['mail']['mail_id']=$mail->create()->id();
		}
	break;
	case 'log':
		switch ($method) {
			case 'get':
				$limit=$route->request('limit','numeric');
				$filters=$route->request('filters', 'array');
				
				$log=(new RLDL\Log(array_merge($filters, array('client_id'=>$id))))->itemsList($route->request('start','numeric'), ($limit!=null && abs($limit)<=100 ? $limit : 100), $route->request('group_by', 'string', array('user_id', 'campaign_id', 'deal_id')), $route->request('remove_duplicates', 'bool'));
				if (array_key_exists('next', $log)) {
					$response['paging']['next']=$route->url().'?'.http_build_query(array(
						'start'=>$log['next'][0],
						'limit'=>$log['next'][1],
						'filters'=>$filters,
						'group_by'=>$route->request('group_by', 'string', array('user_id', 'campaign_id', 'deal_id')),
						'with_details'=>$route->request('with_details', 'bool'),
						'remove_duplicates'=>$route->request('remove_duplicates', 'bool')
					));
					unset($log['next']);
				}
				
				if ($route->request('with_details', 'bool') && count($log)>0) {
					foreach ($log as &$item) {
						if (array_key_exists('log_string', $item)) {
							$log_array=array(&$item);
						}
						else {
							$log_array=&$item;
						}
						foreach ($log_array as &$sub_item) {
							foreach ($sub_item['log_info'] as $log_key=>$log_info) {
								switch ($log_key) {
									case 'user_id':
										try {
											$sub_item['log_info']['user']=RLDL\User::getUser($log_info)->get();
											unset($sub_item['log_info']['user_id']);
										}
										catch (Exception $e) {}
									break;
									case 'campaign_id':
										try {
											$campaign=RLDL\Campaign::getItem($log_info);
											$sub_item['log_info']['campaign']=$campaign->get();
											$sub_item['log_info']['campaign']['images']=$campaign->getImages();
											unset($sub_item['log_info']['campaign_id']);
										}
										catch (Exception $e) {}
									break;
									case 'deal_id':
										try {
											$deal=RLDL\Deal::getItem($log_info);
											$sub_item['log_info']['deal']=$deal->get();
											$sub_item['log_info']['deal']['images']=$deal->getImages();
											unset($sub_item['log_info']['deal_id']);
										}
										catch (Exception $e) {}
									break;
								}
								
							}
						}
					}
				}
				
				$response['data']=$log;
			break;
			default:
				throw new \Exception(
					'Bad request.',
					400
				);
		}
	break;
	case 'campaigns':
		switch ($method) {
			case 'get':
				$client=RLDL\Client::getItem($id);
				$response['data']=array();
				foreach ($client->campaigns() as $item) {
					$campaign=$item->get();
					if ($route->request('images','bool')) {
						$campaign['images']=$item->getImages();
					}
					$response['data'][]=$campaign;
				}
			break;
			default:
				throw new \Exception(
					'Bad request.',
					400
				);
		}
	break;
	case 'settings': 
		$client=RLDL\Client::getItem($id);
		switch ($method) {
			case 'get':
				$response=$client->getSettings();
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
				$client=RLDL\Client::getItem($id, $route->request('invite', 'string'));
				$response=$client->get();
				if ($route->request('campaigns','bool')) {
					foreach ($client->campaigns() as $item) {
						$campaign=$item->get();
						if ($route->request('images','bool')) {
							$campaign['images']=$item->getImages();
						}
						$response['campaigns'][]=$campaign;
					}
				}
				if ($route->request('settings','bool')) {
					$response['settings']=$client->getSettings();
				}
				if ($route->request('valid','bool')) {
					$response['active']=$client->isActive();
					$response['locked']=$client->isLocked();
				}
			break;
			case 'put':
				$client=RLDL\Client::getItem($id);
				$client->updateName($route->request('client_name','string'));
				$response=$client->get();
			break;
			case 'post':
				$client=RLDL\Client::create($route->request('client_name','string'), $route->request('template_code','string'));
				$response=$client->get();
				$response['default_content']=$client->default_content;
			break;
			case 'delete':
				$client=RLDL\Client::getItem($id);
				$client->delete();
				$response['client_id']=$id;
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

