<?php
switch ($route->getParam()) {
	case 'iframe':
		echo (RLDL\Detect\Iframe::isAllowed($route->request('url', 'url')) ? 'true' : 'false');
	break;
	case 'go':
		echo RLDL\Detect\Iframe::isAllowed($route->request('url', 'url'));
	break;
	case 'head':
		$curl=new RLDL\Curl();
		
		$status='ok';
		
		$error=function($data){
			$status='error';
			print_r($data);
		};
		
		$curl->error($error);
		$curl->head($route->request('url', 'url'));
		
		print_r($status);
	break;
	default:
		throw new \Exception(
			'Bad request.',
			400
		);
}
?>