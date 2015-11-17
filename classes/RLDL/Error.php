<?php
namespace RLDL;

class Error {
	private $key;
	
	private $default_error_msg=array(
		400=> 'Bad request.',
		401=> 'Unauthorized.',
		402=> 'Payment required.',
		403=> 'Forbidden.',
		404=> 'Not found.',
		405=> 'Method not allowed.',
		406=> 'Not acceptable.',
		408=> 'Request timeout.',
		415=> 'Unsupported media type.',
		500=> 'Internal server error.',
		501=> 'Not implemented.',
		502=> 'Bad gateway.',
		503=> 'Service unavailable.',
	);
	
	private $accepted_error_codes=array(400,401,402,403,404,405,406,408,409,415,500,501,502,503);
	
	public function __construct($error,$format='txt',$param=null,$http_response_code=true) {
		if (!is_array($error)) {
			$error=array('code'=>$error);
		}
		if (!array_key_exists('code', $error)) {
			$error['code']=500;
		}
		
		if (!array_key_exists('code', $error) || !in_array($error['code'], $this->accepted_error_codes)) {
			$error['response_code']=400;
			if (!array_key_exists('code', $error) || $error['code']==0) {
				$error['code']=$error['response_code'];
			}
		}
		else {
			$error['response_code']=$error['code'];
		}
		
		if ((!array_key_exists('message', $error) || $error['message']=='' || $error['message']==null) && array_key_exists($error['code'], $this->default_error_msg)) {
			$error['message']=$this->default_error_msg[$error['code']];
		}
		
		if ($http_response_code) http_response_code($error['response_code']);
		switch ($format) {
			case 'json':
				header('Content-Type: text/javascript; charset=utf-8');
				$response=array(
					'error'=>$error
				);
				if (array_key_exists('message', $response['error'])) {
					$response['error']['message_i18n']=I18n::getInstance('error')->_($response['error']['message']);
				}
				if ($param!=null) {
					echo $param.'('.json_encode($response).');';
				}
				else {
					echo json_encode($response);
				}
			break;
			case 'html':
				I18n::load('error');
				$m=new Mustache();
				$m->setView(array(
					'error'=>$error
				));
				$m->setTemplateFile('error');
				echo $m->render('error');
			break;
			default:
				echo($error['code'].(array_key_exists('message', $error) ? ' - '.$error['message'] : ''));
		}
		exit();
	}
}
?>