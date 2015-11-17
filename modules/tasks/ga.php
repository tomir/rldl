<?php
$ga=RLDL\GA::getInstance();
$mode=$route->getParam();
switch ($mode) {
	case 'day':
		$call=$sql->SelectSingleRowArray('[GA]Pageviews_calls', array(
			'(call_date IS NULL OR call_date<=CURDATE())',
			'call_time IS NULL'
		), null, 'call_id');
		
		if (is_array($call)) {
			$sql->UpdateRow('[GA]Pageviews_calls', array('call_time'=>'NOW()'), array('call_id'=>$call['call_id']));
		
			$day=date('Y-m-d', strtotime($call['pageview_date']));
			$limit=10000;
			$start=$call['call_start'];
			$alias='([A-Za-z0-9-]+)';
			
			$rows=$ga->get('ga:pageviews', $day, $day, array(
				'dimensions'=>'ga:pagePath',
				'sort'=>'ga:pagePath',
				'filters'=>'ga:pagePath=~^\/'.$alias.'(|\/|\.swf|\/[0-9]*)$',
				'start-index'=>$start,
				'max-results'=>$limit+1
			));
			
			if (count($rows)>=$limit) {
				array_shift($rows);
				
				//add next call to DB
				
				$sql->InsertRow('[GA]Pageviews_calls', array(
					'call_start'=>$start+$limit,
					'pageview_date'=>$day
				));
				
			}
			
			if (is_array($rows) && count($rows)>0) {
				foreach ($rows as $row) {
					$row[0]=explode('.', explode('/', $row[0])[1]);
					$campaign_alias=$row[0][0];
					$pageview_value=$row[1];
					
					$sql->Query('INSERT INTO `[GA]Pageviews` (campaign_alias,pageview_date,pageview_value) VALUES ('.MySQL::SQLValue($campaign_alias).','.MySQL::SQLValue($day).','.MySQL::SQLValue($pageview_value, 'int').')
					  ON DUPLICATE KEY UPDATE pageview_value=pageview_value+'.$pageview_value);
					
					
				}
			}
		}
	break;
	default:
		throw new \Exception(
			'Bad request.',
			400
		);
}