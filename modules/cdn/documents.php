<?php
$sql=MySQL::getInstance(true, C::SQL_DB, C::SQL_SERV, C::SQL_USER, C::SQL_PASS);
switch ($route->getParam()) {
	case 'terms':
		try {
			$campaign=new RLDL\Campaign($route->getParam());
			
			$campaign->get();
			
			try {
				$data=$campaign->getTerms();
				if ($data['terms_type']==1) {
					header('Location: '.$data['terms_data']);
				}
				else {
					$m=new RLDL\Mustache();
					$m->setTemplateFile('layout','documents/terms');
					
					$m->setView(array(
						'terms'=>$data['terms_data'],
						'html'=>function($text, \Mustache_LambdaHelper $helper) {
							$text=$helper->render($text);
							
							if (trim($text) !== '') {
								$text = preg_replace('|<br[^>]*>\s*<br[^>]*>|i', "\n\n", $text . "\n");
								$text = preg_replace("/\n\n+/", "\n\n", str_replace(array("\r\n", "\r"), "\n", $text));
								$texts = preg_split('/\n\s*\n/', $text, -1, PREG_SPLIT_NO_EMPTY);
								$text = '';
								foreach ($texts as $txt) {
									$text .= '<p>' . nl2br(trim($txt, "\n")) . "</p>\n";
								}
								$text = preg_replace('|<p>\s*</p>|', '', $text);
							}
							return $text;
						}
					));
					echo $m->render('layout');
				}
			}
			catch (Exception $e) {
				new RLDL\Error(404, 'html');
			}
		}
		catch (Exception $e) {
			new RLDL\Error(array(
				'code'=>$e->getCode(),
				'message'=>$e->getMessage()
			),'html');
		}
	break;
	default:
		new RLDL\Error(404, 'html');
}
?>