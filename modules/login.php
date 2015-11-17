<?php
try {
	switch ($route->getParam()) {
		case 'blank':
			$m=new RLDL\Mustache();
			$m->setTemplateFile('blank','blank');
			
			echo $m->render('blank');
		break;
		case 'facebook':
			switch ($route->getParam()) {
				case 'tab':
					if (!$auth->isLogin()) {
						$helper=RLDL\Auth\Facebook::setSessionFromSignedRequest(true);
						$auth=RLDL\Auth\Facebook::replaceInstance();
					}
					else {
						$helper=new Facebook\FacebookPageTabHelper();
					}
					
					$campaign=RLDL\Campaign\Facebook::getItemByPage($helper->getPageId());
					
					$url=str_replace('{{campaign_id}}', $campaign->alias(), $url=$config->get('url_campaign'));
					
					header('Location: '.$url);
				break;
				case 'canvas':
					if (!$auth->isLogin()) {
						RLDL\Auth\Facebook::setSessionFromSignedRequest();
						$auth=RLDL\Auth\Facebook::replaceInstance();
					}
					$campaign=$route->getParam();
					$deal=$route->getParam();
					$variant=$route->getParam();
					
					if ($variant!=null) {
						$url=$config->get('url_variant');
					}
					else if ($deal!=null) {
						$url=$config->get('url_deal');
					}
					else {
						$url=$config->get('url_campaign');
					}
					$url=str_replace(array('{{campaign_id}}', '{{deal_id}}', '{{variant_id}}'), array($campaign, $deal, $variant), $url);
					
					header('Location: '.$url);
				break;
				case 'publish':
					if ($auth->isLogin() && $auth->user()->platform()=='Facebook') {
						switch ($route->getParam()) {
							case 'do':
								if (array_key_exists('login_redirect_url', $_SESSION)) {
									header('Location: '.$_SESSION['login_redirect_url']);
								}
							break;
							case null:
								$redirect=$route->request('redirect','url');
								$redirect_mode=$route->request('redirect','string', array('close','post'));
								
								if ($redirect!=null || $redirect_mode!=null) {
									if ($redirect_mode=='close') {
										$_SESSION['login_redirect_url']='/static/pages/close.html';
									} 
									else if ($redirect_mode=='post') {
										$_SESSION['login_redirect_url']='/static/pages/closeWithPostMessage.html#'.urlencode($_SERVER['HTTP_REFERER']);
									} 
									else {
										$_SESSION['login_redirect_url']=$redirect;
									}
									header('Location: '.RLDL\Auth\Facebook::getRedirectUrl($route->url().'/do',array('publish_actions')));
								}
								else {
									new RLDL\Error(400,'html');
								}
							break;
							default:
								new RLDL\Error(400,'html');
						};
					}
					else {
						new RLDL\Error(400,'html');
					}
				break;
				case 'do':
					RLDL\Auth\Facebook::setSessionFromRedirect($route->url());
					$auth=RLDL\Auth\Facebook::replaceInstance();
					
					if (array_key_exists('login_redirect_publish', $_SESSION) && $_SESSION['login_redirect_publish']) {
						$permissions=$auth->user()->permissions();
						
						if (!array_key_exists('publish', $permissions) || $permissions['publish']!=true) {
							$_SESSION['login_redirect_publish']=false;
							header('Location: '.RLDL\Auth\Facebook::getRedirectUrl($route->url().'/do',array('publish_actions')));
							exit();
						}
					}
					
					
					if (array_key_exists('login_redirect_url', $_SESSION)) {
						header('Location: '.$_SESSION['login_redirect_url']);
						exit();
					}
				break;
				case null:
					$redirect=$route->request('redirect','url');
					$redirect_mode=$route->request('redirect','string', array('close','post'));
					
					if ($redirect!=null || $redirect_mode!=null) {
						$_SESSION['login_redirect_publish']=$route->request('publish','bool');
						
						if ($redirect_mode=='close') {
							$_SESSION['login_redirect_url']='/static/pages/close.html';
						} 
						else if ($redirect_mode=='post') {
							$_SESSION['login_redirect_url']='/static/pages/closeWithPostMessage.html#'.urlencode($_SERVER['HTTP_REFERER']);
						} 
						else {
							$_SESSION['login_redirect_url']=$redirect;
						}
						
						header('Location: '.RLDL\Auth\Facebook::getRedirectUrl($route->url().'/do',array('email', 'user_friends')));
						exit();
					}
					else {
						new RLDL\Error(400,'html');
					}
				break;
				default:
					new RLDL\Error(400,'html');
			}
		break;
		case 'google':
			switch ($route->getParam()) {
				case 'do':
					RLDL\Auth\Google::setSessionFromRedirect($route->url());
					$auth=RLDL\Auth\Google::replaceInstance();
					if (array_key_exists('login_redirect_url', $_SESSION)) {
						header('Location: '.$_SESSION['login_redirect_url']);
					}
				break;
				case null:
					$redirect=$route->request('redirect','url');
					$redirect_mode=$route->request('redirect','string', array('close','post'));
					
					if ($redirect!=null || $redirect_mode!=null) {
						if ($redirect_mode=='close') {
							$_SESSION['login_redirect_url']='/static/pages/close.html';
						} 
						else if ($redirect_mode=='post') {
							$_SESSION['login_redirect_url']='/static/pages/closeWithPostMessage.html#'.urlencode($_SERVER['HTTP_REFERER']);
						} 
						else {
							$_SESSION['login_redirect_url']=$redirect;
						}
						header('Location: '.RLDL\Auth\Google::getRedirectUrl($route->url().'/do'));
					}
					else {
						new RLDL\Error(400,'html');
					}
				break;
				default:
					new RLDL\Error(400,'html');
			}
		break;
		case 'logout':
			if ($auth->isLogin()) {
				$auth->logout();
				$auth=RLDL\Auth\Null::replaceInstance();
			}
			$redirect=$route->request('redirect','url');
			if ($redirect==null) {
				$redirect='/';
			}
			header('Location: '.$redirect);
		break;
		case null:
			if ($auth->isLogin()) {
				$redirect=$route->request('redirect','url');
				
				if ($redirect==null) {
					$redirect='/static/pages/close.html';
				}
				header('Location: '.$redirect);
			}
			
			if ($route->request('source','string')!=null) {
				/* guess login platform from referer */
				$sources=array(
					'fb'=>'facebook',
					'facebook'=>'facebook',
					'gp'=>'google',
					'google'=>'google'
				);
				
				$source=strtolower($route->request('source','string'));
				
				if (array_key_exists($source, $sources)) {
					$redirect=$route->request('redirect','url');
					if ($redirect==null) {
						$redirect=$route->request('redirect','string', array('close','post'));
					}
					if ($redirect!=null) {
						header('Location: /'.$sources[$source].'?redirect='.urlencode($redirect).'&publish='.$route->request('publish', 'bool'));
					}
				}
			}
			
			RLDL\I18n::load('login');
			$m=new RLDL\Mustache();
			$m->setTemplateFile('login','login/layout');
			
			$m->setView(array(
				'publish'=>$route->request('publish', 'bool') ? '1' : '0'
			));
			
			echo $m->render('login');
			
		break;
		default:
			new RLDL\Error(404,'html');
	}
}
catch (Exception $e) {
	new RLDL\Error(array(
		'code'=>$e->getCode(),
		'message'=>$e->getMessage()
	),'html');
}
?>