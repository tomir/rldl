<?php
foreach (RLDL\Campaign::getAllAnnouncements(500) as $d) {
	$sql->TransactionBegin();
	$count=0;
	$timer=0;
	
	$campaign=RLDL\Campaign::getItem($d['campaign_id']);
	
	foreach ($sql->SelectArray('[Campaign]Followers', array(
		'follow_time<="'.$d['announcement_time'].'"',
		'(announcement_time<=DATE_SUB(NOW(), INTERVAL '.$config->get('announcement_days').' DAY) OR announcement_time IS NULL)',
		'campaign_id'=>MySQL::SQLValue($d['campaign_id'],'int')
	)) as $r) {
		
		$sql->TimerStart();
		
		if (!$sql->UpdateRow('[Campaign]Followers',array('announcement_time'=>'NOW()'),array('campaign_id'=>MySQL::SQLValue($r['campaign_id'],'int'), 'user_id'=>MySQL::SQLValue($r['user_id'],'int')))) {
			$sql->TransactionRollback();
		}
		else {
			$user=RLDL\User::getUser($r['user_id']);
			$route->setLocale($campaign->get()['campaign_locale']);
			$route->setLocale($user->locale());
			
			RLDL\I18n::load('campaign');
			$m=new RLDL\Mustache;
			
			$view=array(
				'user'=>$user->get(),
				'campaign'=>array_merge($campaign->get(), array(
						'campaign_url'=>str_replace(
						'{{campaign_id}}',
						$campaign->get()['campaign_alias'],
						$config->get('url_campaign')
					),
					'images'=>$campaign->getImages()
				))
			);
			
			$m->setView($view);
			
			if (filter_var($user->email(), FILTER_VALIDATE_EMAIL)) {
				$mail=RLDL\Mail::newItem(array(
					'to'=>$user,
					'from'=>array(
						'name'=>(strlen($view['campaign']['campaign_brand'])>0 ? $view['campaign']['campaign_brand'].' ('.$config->get('name').')' : $config->get('name')),
						'address'=>$config->get('mail_from')
					),
					'Reply-To'=>array(
						'name'=>$config->get('name'),
						'address'=>$config->get('mail_replyto')
					)
				));
				
				$mail->setInfo(array(
					'client_id'=>$view['campaign']['client_id'],
					'campaign_id'=>$view['campaign']['campaign_id'],
					'user_id'=>$user->id(),
					'mail_type'=>'campaign_announcement'
				));
				
				$mail->createFromTemplates(array(
					'title'=>'mail/announcement/title',
					'html'=>'mail/announcement/html'
				), $view);
				
				if (is_array($d['announcement_options'])) {
					foreach ($d['announcement_options'] as $option=>$value) {
						if (in_array($option, array('html', 'txt', 'title'))) {
							$m->setTemplateString('announcement_'.$option, $value);
							$template=$m->render('announcement_'.$option);
							
							switch ($option) {
								case 'html':
									$mail->setHtml($template);
								break;
								case 'txt':
									$mail->setTxt($template);
								break;
								case 'title':
									$mail->setTitle($template);
								break;
							}
						}
					}
				}
				
				$mail->create()->id();
			}
			
			if (is_array($d['announcement_options']) && array_key_exists('notification', $d['announcement_options'])) {
				$m->setTemplateString('notification', $d['announcement_options']['notification']);
			}
			else {
				$m->setTemplateFile('notification', 'notification/announcement');
			}
			
			try {
				$notification=RLDL\Notification::newItem(array(
					'user'=>$user,
					'text'=>$m->render('notification'),
					'link'=>str_replace(
						array('{{campaign_id}}'),
						array($campaign->alias()),
						$config->get('url_campaign')
					),
					'info'=>array(
						'client_id'=>$campaign->get()['client_id'],
						'campaign_id'=>$campaign->id(),
					)
				));
			}
			catch (Exception $exception) {
				
			}
				
			$count++;
		}
		
		$sql->TimerStop();
		
		$timer+=$sql->TimerDurationSeconds();
		
		if ($timer>=$config->get('cron_timelimit')) {
			$campaign->updateAnnouncementStatus($d['announcement_id'], array(
				'announcement_count'=>$d['announcement_count']+$count
			));
			$sql->TransactionEnd();
			break;
			exit();
		}
	}
	
	$campaign->updateAnnouncementStatus($d['announcement_id'], array(
		'announcement_status'=>1,
		'announcement_count'=>$d['announcement_count']+$count
	));
	
	$sql->TransactionEnd();
}