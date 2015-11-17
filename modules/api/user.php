<?php
$id=$route->getParam();
$campaign_id=$route->request('campaign_id','int');
if ($campaign_id==null) {
	$campaign_id=$route->request('campaign_alias','string');
}

if (!$auth->isSystem() && (!$auth->isUser() || $campaign_id==null)) {
	throw new \Exception(
		'Bad request.',
		400
	);
}

$user=RLDL\User::getUser($id);

if ($campaign_id!=null) {
	$campaign_obj=RLDL\Campaign::getItem($campaign_id);
	if (!$user->isFollowingCampaign($campaign_obj->id())) {
		throw new \Exception(
			'Not follower.',
			1040
		);
	}
	
	$campaign=$campaign_obj->get();
	
	
	if (!$auth->isSystem() && $auth->getPermission($campaign['client_id'])<2) {
		throw new \Exception(
			'Not authorized for this operation.',
			403
		);
	}
}

switch ($route->getParam()) {
	case 'mail':
		switch ($route->method()) {
			case 'post':
				if (!isset($campaign_obj)) {
					throw new \Exception(
						'Bad request.',
						400
					);
				}
				
				if (!$auth->isSystem() && !RLDL\Client::getItem($campaign['client_id'])->mailIsAllowed()) {
					throw new \Exception(
						'To many mails sent.',
						1030
					);
				}
				
				$mail_msg=trim($route->request('mail_msg','string'));
				$mail_title=trim($route->request('mail_title','string'));
				
				if (strlen($mail_msg)<1) {
					throw new \Exception(
						'Wrong mail msg.',
						1031
					);
				}
				if (strlen($mail_title)<1) {
					throw new \Exception(
						'Wrong mail title.',
						1032
					);
				}
				
				$route->setLocale($campaign['campaign_locale']);
				$route->setLocale($user->locale());
				
				RLDL\I18n::load('campaign');
				
				$mail=RLDL\Mail::newItem(array(
					'to'=>$user,
					'from'=>array(
						'name'=>(strlen($campaign['campaign_brand'])>0 ? $campaign['campaign_brand'].' ('.$config->get('name').')' : $config->get('name')),
						'address'=>$config->get('mail_from')
					)
				));
				
				$mail->setTitle($mail_title);
				
				$mail_replyto=$route->request('mail_replyto','email');
				if ($mail_replyto!=null) {
					$mail->setAddress(array(
						'Reply-To'=>$mail_replyto
					));
				}
				else if ($auth->isSystem() && !$auth->isUser()) {
					$mail->setAddress(array(
						'Reply-To'=>array(
							'name'=>$config->get('name'),
							'address'=>$config->get('mail_replyto')
						)
					));
				}
				else if ($auth->user()->email()!=null) {
					$mail->setAddress(array(
						'Reply-To'=>$auth->user()
					));
				}
				else {
					$mail->setAddress(array(
						'Reply-To'=>array(
							'name'=>$config->get('name'),
							'address'=>$config->get('mail_replyto')
						)
					));
				}
				
				$mail->setInfo(array(
					'client_id'=>$campaign['client_id'],
					'campaign_id'=>$campaign['campaign_id'],
					'user_id'=>$auth->user()->id(),
					'mail_type'=>'msg'
				));
				
				$mail->createFromTemplates(array(
					'html'=>'mail/msg/html'
				), array(
					'mail'=>array(
						'title'=>$mail_title,
						'msg'=>$mail_msg
					),
					'user'=>$user->get(),
					'campaign'=>array_merge($campaign, array('campaign_url'=>str_replace(
						'{{campaign_id}}',
						$campaign['campaign_alias'],
						$config->get('url_campaign')
					)))
				));
				
				$response['mail']['mail_id']=$mail->create()->id();
				
			break;
			default:
				throw new \Exception(
					'Bad request.',
					400
				);
		}
	break;
	case null:
		switch ($route->method()) {
			case 'delete':
				if ($user->delete()) {
					$response=array(
						'id'=>$id
					);
				}
				else {
					throw new \Exception(
						'',
						500
					);
				}
			break;
			case 'get':
				$response=array(
					'id'=>$user->id(),
					'first_name'=>$user->firstName(),
					'last_name'=>$user->lastName(),
					'name'=>$user->name(),
					'gender'=>$user->gender(),
					'platform'=>$user->platform(),
					'locale'=>$user->locale(),
					'email'=>($auth->isSystem() && $route->request('show_email','bool')) ? $user->email(): $user->emailPreview(),
					'avatar'=>$user->avatar()
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
?>