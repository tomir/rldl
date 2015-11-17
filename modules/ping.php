<?php
function ping($domain, $port=80){
    $starttime = microtime(true);
    $file      = fsockopen ($domain, $port, $errno, $errstr, 10);
    $stoptime  = microtime(true);
    $status    = 0;

    if (!$file) $status = -1;  // Site is down
    else {
        fclose($file);
        $status = ($stoptime - $starttime) * 1000;
        $status = floor($status);
    }
    return $status;
}

if ($auth->isSystem() && $route->request('url')) {
	$response=ping($route->request('url'));
	
	if ($route->request('callback', 'string')!=null) {
		echo $route->request('callback', 'string').'('.json_encode($response).');';
	}
	else {
		echo json_encode($response);
	}
}
else {
	new RLDL\Error(404, 'html');
}
?>