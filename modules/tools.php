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
header('P3P:CP="IDC DSP COR ADM DEVi TAIi PSA PSD IVAi IVDi CONi HIS OUR IND CNT"');
$response=array();

$api_routes=array(
	'xlstojson',
	'csvtojson',
	'txttojson',
	'stringrandomizer',
	'randomizer',
	'appstorelink',
	'tests'
);

$api_route=strtolower($route->getParam());

if (in_array($api_route, $api_routes)) {
	require(BASE_PATH.'/modules/tools/'.$api_route.'.php');
}
else {
	new RLDL\Error(404, 'html');
}
?>