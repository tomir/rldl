<?php
$data=json_decode(base64_decode($route->getParam()), true);

if (!is_array($data)) $data=array();

if ($route->getParam()==null && array_key_exists('user_id', $data) && array_key_exists('code_value', $data) && array_key_exists('variant_id', $data)) {
	try {
		$browser=new RLDL\Detect\Mobile;
		
		
		$deal=RLDL\Deal::findByVariant($data['variant_id']);
		
		if ($deal->get()['deal_url']==null) {
			new RLDL\Error(404, 'html');
		}
		
		
		
//		if ($auth->isLogin()) {
//			$data['user_id']=$auth->userId();
//		}
		$user=RLDL\User::getUser($data['user_id']);
		
		foreach ($deal->getVariants() as $variant_data) {
			if ($variant_data['variant_id']==$data['variant_id']) {
				$variant=$variant_data;
				$variant['code_value']=$data['code_value'];
				break;
			}
		}
		
		$url=$deal->parseUrlWithCode($variant);
		$cart_url=$deal->parseUrlWithCode($variant, true);
		
		if (strlen($cart_url)>0) {
			$url="/static/pages/addToCart.html?u=".urlencode($url).'&c='.urlencode($cart_url);
		}
		
		$variant['code_url']=$url;
		
		if (
			stripos($url, 'RLDLframe=0')>0 || 
			stripos($url, 'RLDLframe=false')>0 || 
			(
				$browser->isMobile() && 
				$route->request('force_desktop','bool')!=true
			) || 
			RLDL\Detect\Iframe::isAllowed($url)==false
		) {
			header('Location: '.$url);
		}
		
		$campaign=RLDL\Campaign::getItem($deal->get()['campaign_id'])->get();
		
		$route->setLocale($campaign['campaign_locale']);
		
		$route->setLocale($user->locale());
		
		RLDL\I18n::load('campaign');
		
		$m=new RLDL\Mustache();
		$m->setTemplateFile('frame','frame');
		
		$m->setView(array(
			'user'=>array(
				'first_name'=>$user->firstName(),
				'last_name'=>$user->lastName(),
				'name'=>$user->name(),
				'gender'=>$user->gender(),
				'avatar'=>$user->avatar()
			),
			'campaign'=> $campaign,
			'deal'=> array_merge($deal->get(), array('variant'=>$variant))
		));
		
		echo $m->render('frame');
	}
	catch (Exception $e) {
		new RLDL\Error(array(
			'code'=>$e->getCode(),
			'message'=>$e->getMessage()
		),'html');
	}
}
else {
	new RLDL\Error(404, 'html');
}
?>