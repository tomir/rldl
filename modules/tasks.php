<?php
header('Content-Type: text/javascript; charset=utf-8');
$response=array();

$task_route=$route->getParam();


try {
	if ($auth->isSystem()) {
		
		require(BASE_PATH.'/modules/tasks/'.$task_route.'.php');
		
		echo json_encode($response);
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
	),'json');
}
?>