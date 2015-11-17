<?php
header('Content-Type: text/javascript; charset=utf-8');

try {
	
	switch ($route->getParam()) {
		case null:
			switch ($route->method()) {
				case 'post':
					$file=$route->file();
					if (!is_null($file)) {
						$upload=new RLDL\Upload(array(
							'mimes'=>array('application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'text/plain', 'text/csv', 'application/x-gnumeric', 'application/vnd.oasis.opendocument.spreadsheet'),
							'max_file_size'=>$config->get('img_max_size')
						));
						
						$file_uri=$upload->file($file);
						
						$inputFileType = PHPExcel_IOFactory::identify($file_uri);
						$objReader = PHPExcel_IOFactory::createReader($inputFileType);
						$objReader->setReadDataOnly(true);
						
						if ($route->request('only_first_column', 'bool')==true) {
							class FirstColumnFilter implements PHPExcel_Reader_IReadFilter { 
								public function readCell($column, $row, $worksheetName = '') { 
									if ($column=='A') { 
										return true; 
									} 
									return false; 
								} 
							} 
							
							$objReader->setReadFilter(new FirstColumnFilter()); 
						}
						
						$objPHPExcel = $objReader->load($file_uri);
						
						$data=$objPHPExcel->getActiveSheet()->toArray(null,true,true,true);
						
						if ($route->request('return_column_name', 'bool')==false) {
							foreach ($data as &$row) {
								$row = array_pop($row);
								if ($row=='') unset($row);
							}
							
							$data=array_values($data);
						}
						
						$response=$data;
					}
					else {
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