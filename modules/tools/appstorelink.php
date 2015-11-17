<?php
header('Content-Type: text/javascript; charset=utf-8');

try {
	switch ($route->getParam()) {
		case null:
			switch ($route->method()) {
				case 'post':
					$data=array();
					
					if ($route->request('ios','string')!=null) {
						$data['ios']=$route->request('ios','string');
					}
					if ($route->request('ipad','string')!=null) {
						$data['ipad']=$route->request('ipad','string');
					}
					if ($route->request('wp','string')!=null) {
						$data['wp']=$route->request('wp','string');
					}
					if ($route->request('play','string')!=null) {
						$data['play']=$route->request('play','string');
					}
					if ($route->request('name','string')!=null) {
						$data['name']=$route->request('name','string');
					}
					
					$response['url']='https://as.rldl.net/'.str_replace('=', '', base64_encode(json_encode($data)));
					
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

	if ($route->request('callback', 'string')!=null) {
		echo $route->request('callback', 'string').'('.json_encode($response).');';
	}
	else {
		echo json_encode($response);
	}
}
catch (Exception $e) {
	new RLDL\Error(array(
		'code'=>$e->getCode(),
		'message'=>$e->getMessage()
	),'json', $route->request('callback', 'string'), ($route->request('http_response_code', 'string')===null ? true : $route->request('http_response_code', 'bool')));
}