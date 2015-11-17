<?php 
$user=RLDL\User::getUser(1);

$route->setLocale('en_US');

echo($route->getLocale().'/'.$user->locale());


RLDL\I18n::load('privacy');
$m=new RLDL\Mustache();
$m->setTemplateFile('top','privacy/top');
$m->setTemplateFile('privacy','privacy/layout');

$browser=new RLDL\Detect\Mobile;

$m->setView(array(
	'browser'=>array(
		'mobile'=>$browser->isMobile()
	)
));

if ($auth->isLogin()) {
	$m->setView(array(
		'user'=>array(
			'first_name'=>$auth->user()->firstName(),
			'last_name'=>$auth->user()->lastName(),
			'name'=>$auth->user()->name(),
			'gender'=>$auth->user()->gender(),
			'email'=>$auth->user()->email(),
			'avatar'=>$auth->user()->avatar(),
			'login'=>true
		)
	));
}
else {
	$guessUser=$auth->guessUserId();
	if ($guessUser[1]>0.5) {
		$user=\RLDL\User::getUser($guessUser[0]);
		$m->setView(array(
			'user'=>array(
				'first_name'=>$user->firstName(),
				'last_name'=>$user->lastName(),
				'name'=>$user->name(),
				'gender'=>$user->gender(),
				'avatar'=>$auth->user()->avatar()
			)
		));
	}
}

echo $m->render('privacy');

$route->setLocale($user->locale());
echo($route->getLocale().'/'.$user->locale());

RLDL\I18n::load('privacy');
$m=new RLDL\Mustache();
$m->setTemplateFile('top','privacy/top');
$m->setTemplateFile('privacy','privacy/layout');

$browser=new RLDL\Detect\Mobile;

$m->setView(array(
	'browser'=>array(
		'mobile'=>$browser->isMobile()
	)
));

if ($auth->isLogin()) {
	$m->setView(array(
		'user'=>array(
			'first_name'=>$auth->user()->firstName(),
			'last_name'=>$auth->user()->lastName(),
			'name'=>$auth->user()->name(),
			'gender'=>$auth->user()->gender(),
			'email'=>$auth->user()->email(),
			'avatar'=>$auth->user()->avatar(),
			'login'=>true
		)
	));
}
else {
	$guessUser=$auth->guessUserId();
	if ($guessUser[1]>0.5) {
		$user=\RLDL\User::getUser($guessUser[0]);
		$m->setView(array(
			'user'=>array(
				'first_name'=>$user->firstName(),
				'last_name'=>$user->lastName(),
				'name'=>$user->name(),
				'gender'=>$user->gender(),
				'avatar'=>$auth->user()->avatar()
			)
		));
	}
}

echo $m->render('privacy');

if ($route->setLocale('pl_PL')) echo('ok');
else echo('!');
print_r($config->get('locales'));
echo($route->getLocale().'/'.$user->locale());

RLDL\I18n::load('privacy');
$m=new RLDL\Mustache();
$m->setTemplateFile('top','privacy/top');
$m->setTemplateFile('privacy','privacy/layout');

$browser=new RLDL\Detect\Mobile;

$m->setView(array(
	'browser'=>array(
		'mobile'=>$browser->isMobile()
	)
));

if ($auth->isLogin()) {
	$m->setView(array(
		'user'=>array(
			'first_name'=>$auth->user()->firstName(),
			'last_name'=>$auth->user()->lastName(),
			'name'=>$auth->user()->name(),
			'gender'=>$auth->user()->gender(),
			'email'=>$auth->user()->email(),
			'avatar'=>$auth->user()->avatar(),
			'login'=>true
		)
	));
}
else {
	$guessUser=$auth->guessUserId();
	if ($guessUser[1]>0.5) {
		$user=\RLDL\User::getUser($guessUser[0]);
		$m->setView(array(
			'user'=>array(
				'first_name'=>$user->firstName(),
				'last_name'=>$user->lastName(),
				'name'=>$user->name(),
				'gender'=>$user->gender(),
				'avatar'=>$auth->user()->avatar()
			)
		));
	}
}

echo $m->render('privacy');