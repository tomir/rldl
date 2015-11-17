<?php 
$template_name='other/templates/'.$route->getParam();

$part_name=$route->getParam();

while ($part_name!=null) {
	if ($part_name=='..' || $part_name=='.') {
		exit();
	}
	$template_name.='/'.$part_name;
	$part_name=$route->getParam();
}

if (strpos(strrev($template_name), 'ehcatsum.')===0 &&file_exists($template_name)) {
	if ($route->request('callback', 'string')!=null) {
		header('Content-Type: text/javascript; charset=utf-8');
		echo $route->request('callback', 'string').'('.json_encode(array('template'=>file_get_contents($template_name))).');';
	}
	else {
		echo file_get_contents($template_name);
	}
}
?>