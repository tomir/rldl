<?php
switch ($route->getParam()) {
	case 'paypal':
		switch ($route->method()) {
			case 'get':
			
			break;
			default:
				throw new \Exception(
					'Bad request.',
					400
				);
		}
	break;
	case 'card':
		switch ($route->method()) {
			case 'get':
			
			break;
			default:
				throw new \Exception(
					'Bad request.',
					400
				);
		}
	break;
	case null:
		$response['methods']=$config->get('paylane_methods');
	break;
	default:
		throw new \Exception(
			'Bad request.',
			400
		);
}
?>