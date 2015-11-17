<?php
foreach (RLDL\Deal::getAllReminders(500) as $d) {
	$sql->TransactionBegin();
	$count=0;
	$timer=0;
	
	$deal=RLDL\Deal::getItem($d['deal_id']);
	
	foreach ($sql->SelectArray('[User]Variants', array('reminder_sent'=>0, 'deal_id'=>MySQL::SQLValue($d['deal_id'],'int'))) as $r) {
		$sql->TimerStart();
		
		if (!$sql->UpdateRow('[User]Deals',array('reminder_time'=>'NOW()'),array('user_deal_id'=>MySQL::SQLValue($r['user_deal_id'],'int')))) {
			$sql->TransactionRollback();
		}
		else {
			$user=RLDL\User::getUser($r['user_id']);
			$campaign=RLDL\Campaign::getItem($r['campaign_id']);
			$variant=$deal->getCode($r['variant_id'], $user);
			
			$route->setLocale($campaign->get()['campaign_locale']);
			$route->setLocale($user->locale());
			
			RLDL\I18n::load('campaign');
			$m=new RLDL\Mustache;
			
			$view=array(
				'user'=>$user->get(),
				'campaign'=>array_merge($campaign->get(), array('images'=>$campaign->getImages(), 'campaign_url'=>str_replace(
					'{{campaign_id}}',
					$campaign->get()['campaign_alias'],
					$config->get('url_campaign')
				))),
				'deal'=>array_merge($deal->get(), array(
					'images'=>$deal->getImages(),
					'variant'=>$variant
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
					'deal_id'=>$view['deal']['deal_id'],
					'user_id'=>$user->id(),
					'mail_type'=>'deal_reminder'
				));
				
				$mail->createFromTemplates(array(
					'title'=>'mail/reminder/title',
					'html'=>'mail/reminder/html'
				), $view);
				
				if (is_array($d['reminder_options'])) {
					foreach ($d['reminder_options'] as $option=>$value) {
						if (in_array($option, array('html', 'txt', 'title'))) {
							$m->setTemplateString('reminder_'.$option, $value);
							$template=$m->render('reminder_'.$option);
							
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
			
			if (is_array($d['reminder_options']) && array_key_exists('notification', $d['reminder_options'])) {
				$m->setTemplateString('notification', $d['reminder_options']['notification']);
			}
			else {
				$m->setTemplateFile('notification', 'notification/reminder');
			}
			
			try {
				$notification=RLDL\Notification::newItem(array(
					'user'=>$user,
					'text'=>$m->render('notification'),
					'link'=>str_replace(
						array('{{campaign_id}}', '{{deal_id}}', '{{variant_id}}'),
						array($campaign->alias(), $deal->id(), $variant['variant_id']),
						$config->get('url_variant')
					),
					'info'=>array(
						'client_id'=>$campaign->get()['client_id'],
						'campaign_id'=>$campaign->id(),
						'deal_id'=>$deal->id(),
						'variant_id'=>$variant['variant_id']
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
			$deal->updateReminderStatus($d['reminder_id'], array(
				'reminder_count'=>$d['reminder_count']+$count
			));
			$sql->TransactionEnd();
			break;
			exit();
		}
	}
	
	$deal->updateReminderStatus($d['reminder_id'], array(
		'reminder_status'=>1,
		'reminder_count'=>$d['reminder_count']+$count
	));
	
	$sql->TransactionEnd();
}