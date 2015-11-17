<?php
namespace RLDL\Notification;

class Facebook {
	public static function parse($data) {
		if (!is_array($data) || !array_key_exists('user', $data) || !array_key_exists('link', $data) || !array_key_exists('text', $data)) {
			throw new \Exception(
				'Wrong notification data.'
			);
		}
		
		return json_encode(array(
			'url'=>'/'.$data['user']->platformId().'/notifications',
			'data'=>array(
				'href'=>explode('?', substr($data['link'], strpos($data['link'], '/', 8)+1))[0],
				'template'=>$data['text']
			)
		));
	}
	
	public static function send($post) {
		try {
			\Facebook\FacebookSession::setDefaultApplication(\RLDL\Config::getInstance()->get('fb_app_id'), \RLDL\Config::getInstance()->get('fb_app_secret'));
			
			$response=(new \Facebook\FacebookRequest(
				\Facebook\FacebookSession::newAppSession(),
				'POST',
				$post['url'],
				$post['data']
			))->execute();
		}
		catch (\Exception $e) {
			return null;
		}
		return $response->getRawResponse();
	}
}
?>