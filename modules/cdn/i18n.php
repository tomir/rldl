<?php 
$locale_file='other/i18n/'.$route->getParam();

$part_name=$route->getParam();

while ($part_name!=null) {
	if ($part_name=='..' || $part_name=='.') {
		exit();
	}
	$locale_file.='/'.$part_name;
	$part_name=$route->getParam();
}

if (strpos(strrev($locale_file), 'nosj.')===0 && file_exists($locale_file)) {
	header('Content-Type: text/javascript; charset=utf-8');
	if ($route->request('callback', 'string')!=null) {
		echo $route->request('callback', 'string').'('.file_get_contents($locale_file).');';
	}
	else {
		echo file_get_contents($locale_file);
	}
	
}
else {
	if ($route->request('callback', 'string')!=null) {
		echo $route->request('callback', 'string').'([]);';
	}
	else {
		echo '[]';
	}
}
?>