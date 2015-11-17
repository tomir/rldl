<?php
require_once 'google/appengine/api/cloud_storage/CloudStorageTools.php';
use google\appengine\api\cloud_storage\CloudStorageTools;

$params=array();

$param=$route->getParam();

while ($param!=null) {
	array_push($params, $param);
	$param=$route->getParam();
}

if (count($params)==0){
	$params=array(0,0,'default.png');
}
else if (count($params)==1){
	$params=array(0,0,$params[0]);
}
else {
	if (!is_numeric($params[0])) {
		$params[0]=0;
	}
	else if ($params[0]>1920) {
		$params[0]=0;
	}
	if (!is_numeric($params[1])) {
		$params[1]=0;
	}
	else if ($params[1]>1920) {
		$params[1]=0;
	}
}
if ($params[2]=='') {
	$params[2]='default.png';
}

$params[2]=str_replace('_', '/', $params[2]);


$object_image_file='gs://'.C::GSBUCKET_IMAGES.'/'.$params[2];

if (!file_exists($object_image_file)) {
	$object_image_file='gs://'.C::GSBUCKET_IMAGES.'/default.png';
	
}
try {
	$object_image_url=CloudStorageTools::getImageServingUrl($object_image_file, ['secure_url' => true]);
}
catch (Exception $e) {
	new RLDL\Error(array(
		'code'=>$e->getCode(),
		'message'=>$e->getMessage()
	),'txt');	
}
header('Access-Control-Allow-Origin: *');
header('Location:' .$object_image_url.'=w'.$params[0].'-h'.$params[1]);
?>