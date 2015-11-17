<?php
$method=$route->method();

switch ($method) {
	case 'get':
		$ids=array();
		
		switch ($route->getParam()) {
			case 'recently-downloaded':
				$campaigns=RLDL\Campaign::getRecentlyDownloadedItems($route->request('limit','int'));
			break;
			case 'tagged':
				$limit=$route->request('limit','numeric');
				$filters=$route->request('filters', 'array');
				
				$tag=$route->getParam();
				if ($tag==null) {
					throw new \Exception(
						'Bad request.',
						400
					);
				}
				else {
					$tag=urldecode($tag);
				}
				$response['tag']=$tag;
				$campaigns=RLDL\Campaign::getItemsByTag($tag, $route->request('start','numeric'), ($limit!=null && abs($limit)<=100 ? $limit : 100));
			break;
			case null:
				$ids=array();
				if (count($route->request('ids','array'))>0 || count($route->request('aliases','array'))>0) {
					$ids=array_merge($route->request('ids','array'), $route->request('aliases','array'));
				}
				$campaigns=array();
				
				foreach ($ids as $id) {
					$campaign_item=RLDL\Campaign::getItem($id);
					$campaigns[$campaign_item->id()]=$campaign_item;
				}
			break;
			default:
				throw new \Exception(
					'Bad request.',
					400
				);
		}
		$response['data']=array();
		
		if (array_key_exists('next', $campaigns)) {
			$response['paging']['next']=$route->url().'?'.http_build_query(array(
				'start'=>$campaigns['next'][0],
				'limit'=>$campaigns['next'][1],
			));
			unset($campaigns['next']);
		}
		
		foreach ($campaigns as &$campaign) {
			$campaign_data=$campaign->get();
			
			if ($route->request('terms','bool')) {
				$terms=$campaign->getTerms();
				if (is_array($terms)) {
					$campaign_data=array_merge($terms, $campaign_data);
				}
			}
			if ($route->request('images','bool')) {
				$campaign_data['images']=$campaign->getImages();
			}
			if ($route->request('avatars','bool')) {
				$avatars=$campaign->getAvatars();
				if (count($avatars)>0) {
					$campaign_data['avatars']=$avatars;
				}
			}
			if ($route->request('aliases','bool')) {
				$campaign_data['aliases']=$campaign->getAliases();
			}
			if ($route->request('deals','bool')) {
				foreach ($campaign->deals($route->request('all','bool')) as $item) {
					$deal=$item->get();
					if ($route->request('variants','bool')) {
						$deal['variants']=$item->getVariants();
					}
					
					if ($route->request('images','bool')) {
						$deal['images']=$item->getImages();
					}
					$campaign_data['deals'][]=$deal;
				}
			}
			
			$response['data'][]=$campaign_data;
		}
	break;
	default:
		throw new \Exception(
			'Bad request.',
			400
		);
}