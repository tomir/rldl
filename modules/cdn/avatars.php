<?php
require_once 'google/appengine/api/cloud_storage/CloudStorageTools.php';
use google\appengine\api\cloud_storage\CloudStorageTools;

$id=str_replace('.png','',$route->getParam());

if (is_numeric($id)) {
	$object_image_file='gs://'.C::GSBUCKET_AVATARS.'/'.$id.'.png';
	
	if (!file_exists($object_image_file)) {
		$sql=MySQL::getInstance(true, C::SQL_DB, C::SQL_SERV, C::SQL_USER, C::SQL_PASS);
		
		$q=$sql->SelectSingleRowArray('[User]Users', array('user_id'=>\MySQL::SQLValue($id,'int')), array('user_avatar'));
		
		if (isset($q['user_avatar'])) {
			$avatar=file_get_contents($q['user_avatar']);
			file_put_contents($object_image_file, $avatar);
		}
		else {
			$object_image_file='gs://'.C::GSBUCKET_IMAGES.'/default.png';
		}
	}
}
else {
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
header('Location:' .$object_image_url.'=w50-h50');
?>