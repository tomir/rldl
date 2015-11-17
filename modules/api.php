<?php
if (array_key_exists('HTTP_REFERER', $_SERVER)) {
	$CORS=explode('/', $_SERVER['HTTP_REFERER']);
	if (strlen($CORS[0])>0 && strlen($CORS[2])>0) {
		$CORS=$CORS[0].'//'.$CORS[2];
	}
	else $CORS='*';
}
else $CORS='*';

header('Access-Control-Allow-Origin: '.$CORS);
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Content-Type: text/javascript; charset=utf-8');
header('P3P:CP="IDC DSP COR ADM DEVi TAIi PSA PSD IVAi IVDi CONi HIS OUR IND CNT"');
$response=array();

$api_routes=array(
	'client',
	'admin',
	'campaign',
	'campaigns',
	'deal',
	'image',
	'auth',
	'paylane',
	'me',
	'post',
	'upload',
	'user',
	'ga',
	'log'
);

$api_route=$route->getParam();

try {
	if (in_array($api_route, $api_routes)) {
		
		require(BASE_PATH.'/modules/api/'.$api_route.'.php');
		
		if ($route->request('callback', 'string')!=null) {
			echo $route->request('callback', 'string').'('.json_encode($response).');';
		}
		else {
			echo json_encode($response);
		}
	}
	else {
		throw new \Exception(
			'Route not found.',
			404
		);
	}
}
catch (Exception $e) {
	new RLDL\Error(array(
		'code'=>$e->getCode(),
		'message'=>$e->getMessage()
	),'json', $route->request('callback', 'string'), ($route->request('http_response_code', 'string')===null ? true : $route->request('http_response_code', 'bool')));
}
?>