<?php
$formats=array(
	'txt',
	'json'
);
$format=(array_key_exists('format', $_REQUEST) ? strtolower($_REQUEST['format']) : 'txt');

if (!in_array($format,$formats)) $format='txt';

$start=(array_key_exists('start', $_REQUEST) ? ($_REQUEST['start']<0 ? 0 : $_REQUEST['start']) : 0);
$end=(array_key_exists('end', $_REQUEST) ? ($_REQUEST['end']<$start ? $start : ($_REQUEST['end']>$start+1000 ? $start+1000 : $_REQUEST['end'])) : $start+10);
$string=(array_key_exists('string', $_REQUEST) ? (strlen($_REQUEST['string'])>0 ? $_REQUEST['string'] : 'code') : 'code');

$delimiter=(array_key_exists('delimiter', $_REQUEST) ? $_REQUEST['delimiter'] : "");

if (strlen($string)<4) {
	$string=str_pad($string, 4, $string);
}
else if (strlen($string)>20) {
	$string=substr($string, 0, 20);
}
$string=strtolower($string);

$string_length=strlen($string);


$numbers_length=strlen($end);

$response=array();

for ($i = $start; $i <= $end; $i++) {
	$randomized='';
	$number=$delimiter.str_pad($i, $numbers_length, '0', STR_PAD_LEFT).$delimiter;
	$number_pos=rand(0, $string_length);
	for ($j = 0; $j < $string_length; $j++) {
		if ($j==$number_pos) {
			$randomized.=$number;
		}
		$upper=rand(0, 1);
		$randomized.=($upper==1 ? strtoupper($string[$j]) : $string[$j]);
	}
	if ($number_pos==$string_length) {
		$randomized.=$number;
	}
	array_push($response, $randomized);
}

switch ($format) {
	case 'json':
		header('Content-Type: text/javascript');
		if (array_key_exists('callback', $_REQUEST)) {
			echo $_REQUEST['callback'].'('.json_encode($response).');';
		}
		else {
			echo json_encode($response);
		}
	break;
	default:
		header('Content-Type: plain/text');
		$is_first=true;
		foreach ($response as $line) {
			if ($is_first) $is_first=false;
			else echo "\n";
			echo $line;
		}
}
?>