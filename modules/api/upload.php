<?php
$url=$route->request('url','url');
if ($route->getParam()==null && $route->method()=='get' && $url!=null) {
	if ($auth->isAuthorized()) {
		require_once 'google/appengine/api/cloud_storage/CloudStorageTools.php';
		$response['upload_url']=google\appengine\api\cloud_storage\CloudStorageTools::createUploadUrl($url, [ 'gs_bucket_name' => $config->get('gsbucket_temp') ]);
	}
	else {
		throw new \Exception(
			'Unautorized.',
			401
		);
	}
}
else {
	throw new \Exception(
		'Bad request.',
		400
	);
}
?>