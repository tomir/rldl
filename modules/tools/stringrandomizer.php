<?php
header('Content-Type: text/javascript; charset=utf-8');

try {
	switch ($route->getParam()) {
		case null:
			switch ($route->method()) {
				case 'get':
					$start=$route->request('start', 'int');
					$end=$route->request('end', 'int');
					
					if (!is_numeric($start) || $start<0) $start=0;
					if (!is_numeric($end) || $end>($start+1000)) $end=$start+1000;
					
					$string=$route->request('string', 'string');
					
					$string=preg_replace("/[^a-zA-Z0-9\/_]+/", "", str_replace(' ', '_', $string));
					
					if (is_null($string) || strlen($string)<1) $string='code';
					
					$delimiter=$route->request('delimiter', 'string');
					
					if (is_null($delimiter)) $delimiter='';
					
					if (strlen($string)>50) $string=substr($string, 0, 50);
					$string=strtolower($string);
					
					$string_length=strlen($string);
					
					$numbers_length=strlen($end);
					
					$data=array();
					
					for ($i = $start; $i <= $end; $i++) {
						$randomized='';
						$number=$delimiter.str_pad($i, $numbers_length, '0', STR_PAD_LEFT).$delimiter;
						$number_pos=$string_length<4 ? rand(0, $string_length) : rand(1, $string_length-1);
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
						array_push($data, $randomized);
					}
					
					$response=array(
						'data'=>$data
					);
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