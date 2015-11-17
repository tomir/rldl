<?php
if ($auth->isSystem() && $route->method()=='get') {
	$ga=RLDL\GA::getInstance();
	$mode=$route->getParam();
	
	switch ($mode) {
		case 'day':
		case 'days':
			$day=strtotime($route->getParam());
			if ($day==null || $day>time()) {
				$day=strtotime('-1 day');
			}
			if ($mode=='days') {
				$day2=strtotime($route->getParam());
				if ($day2==null || $day2>time() || $day2<$day) {
					$day2=$day;
				}
			}
			else {
				$day2=$day;
			}
			$day=date('Y-m-d', $day);
			$day2=date('Y-m-d', $day2);
			$limit=$route->request('limit','numeric');
			$start=$route->request('start','numeric');
			$alias=$route->request('campaign_alias','string');
			
			if (is_null($limit) || $limit<1 || $limit>=10000) {
				$limit=1000;
			}
			if (is_null($start) || $start<1) {
				$start=1;
			}
			
			$query_params=array(
				'start'=>$start+$limit,
				'limit'=>$limit
			);
			
			if (is_null($alias)) {
				$alias='([A-Za-z0-9-]+)';
			}
			else {
				$query_params['campaign_alias']=$alias;	
			}
			
			
			
			$rows=$ga->get('ga:pageviews', $day, $day2, array(
				'dimensions'=>'ga:pagePath',
				'sort'=>'ga:pagePath',
				'filters'=>'ga:pagePath=~^\/'.$alias.'(|\/|\.swf|\/[0-9]*)$',
				'start-index'=>$start,
				'max-results'=>$limit+1
			));
			
			if (count($rows)>=$limit) {
				array_shift($rows);
				$response['paging']=array(
					'next'=>$route->url().'?'.http_build_query($query_params)
				);
			}
			
			$response['data']=$rows;
		break;
		default:
			throw new \Exception(
				'Bad request.',
				400
			);
	}
}
else {
	// route hidden
	throw new \Exception(
		'Route not found.',
		404
	);
}