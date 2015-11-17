<?php

\Facebook\FacebookSession::setDefaultApplication(\RLDL\Config::getInstance()->get('fb_app_id'), \RLDL\Config::getInstance()->get('fb_app_secret'));

$session=\RLDL\Auth\Facebook::getSession();
print_r($session);
$response=(new \Facebook\FacebookRequest(
	$session,
	'GET',
	'/'.$route->request('post_id')
))->execute();

print_r($response->getRawResponse());