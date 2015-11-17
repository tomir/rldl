<?php
header('Content-Type: text/javascript');
$response=array();

require_once 'google/appengine/api/cloud_storage/CloudStorageTools.php';
use google\appengine\api\cloud_storage\CloudStorageTools;

if ($route->method()=='post' && count($route->files())>0 && $auth->isAuthorized()) {
	switch ($route->getParam()) {
		case 'images':
			$response['images']=array();
			$dir=date("Ym");
			$dest='gs://'.C::GSBUCKET_IMAGES.'/'.$dir;
			foreach ($route->files() as $name => $file) {
				$upload = Upload::factory($dest);
				$upload->file($file);
				$upload->set_max_file_size(C::IMG_MAX_SIZE);
				$upload->set_allowed_mime_types(array('image/gif', 'image/jpeg', 'image/png'));
				$result = $upload->upload();
				
				if ($result['status']) {
					$imgColors=new SNX_IMG_colors($dest.'/'.$result['filename']);
					
					$response['images'][]=array(
						'name'=>$result['original_filename'],
						'uri'=>'https://cdn.rldl.net/images/{{w}}/{{h}}/'.$dir.'_'.$result['filename'],
						'url'=>'https://cdn.rldl.net/images/'.$dir.'_'.$result['filename'],
						'info'=>array(
							'colors'=>$imgColors->colors()
						)
					);
				}
				else {
					if ($result['errors'][0]=='mime') {
						$response['error']=array(
							'code'=>415,
							'message'=>'Unsupported_media_type'
						);
					}
					else if ($result['errors'][0]=='too_big_file') {
						$response['error']=array(
							'code'=>400,
							'message'=>'File_is_too_large'
						);
					}
					break;
				}
			}
		break;
	}
}
else if ($route->method()=='get') {
	switch ($route->getParam()) {
		case 'images':
			$response['upload_url']=CloudStorageTools::createUploadUrl($_SERVER['REQUEST_URI'], [ 'gs_bucket_name' => C::GSBUCKET_IMAGES ]);
		break;
	}
	
}

if (isset($response['error']['code'])) {
	http_response_code($response['error']['code']);
}

if (array_key_exists('callback', $_REQUEST)) {
	echo $_REQUEST['callback'].'('.json_encode($response).');';
}
else {
	echo json_encode($response);
}
?>