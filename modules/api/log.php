<?php 
$method=$route->method();
switch ($route->getParam()) {
	case null:
		switch ($method) {
			case 'get':
				$limit=$route->request('limit','numeric');
				$filters=$route->request('filters', 'array');
				
				$log=(new RLDL\Log($filters, true))->itemsList($route->request('start','numeric'), ($limit!=null && abs($limit)<=100 ? $limit : 100), $route->request('group_by', 'string', array('user_id', 'campaign_id', 'deal_id')), $route->request('remove_duplicates', 'bool'));
				if (array_key_exists('next', $log)) {
					
					if ($route->request('show_paging','bool')) {
						$response['paging']['next']=$route->url().'?'.http_build_query(array(
							'start'=>$log['next'][0],
							'limit'=>$log['next'][1],
							'filters'=>$filters,
							'group_by'=>$route->request('group_by', 'string', array('user_id', 'campaign_id', 'deal_id')),
							'with_details'=>$route->request('with_details', 'bool'),
							'remove_duplicates'=>$route->request('remove_duplicates', 'bool'),
							'show_paging'=>$route->request('show_paging', 'bool')
						));
					}
					unset($log['next']);
				}
				
				foreach ($log as &$item) {
					if (array_key_exists('log_string', $item)) {
						$log_array=array(&$item);
					}
					else {
						$log_array=&$item;
					}
					foreach ($log_array as &$sub_item) {
						unset($sub_item['log_ip']);
					}
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
											//$sub_item['log_info']['user']=RLDL\User::getUser($log_info)->get();
											
											$user=RLDL\User::getUser($log_info);
											$sub_item['log_info']['user']=array(
												'first_name'=>$user->firstName(),
												'gender'=>$user->gender(),
												'avatar'=>$user->avatar()
											);
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
	default:
		throw new \Exception(
			'Bad request.',
			400
		);
}
