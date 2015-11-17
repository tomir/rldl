<?php
namespace RLDL\Post;

class Facebook {
	private static $url=array(
		'action'=>'/me/real_deal:get',
		'post'=>'/me/feed'
	);
	
	public static function parse($data) {
		if (!is_array($data)) {
			throw new \Exception(
				'Wrong post data.'
			);
		}
		
		if (array_key_exists('post_data', $data)) {
			if (array_key_exists('url', $data['post_data'])) {
				$data['post_type']=array_search($data['post_data']['url'], self::$url);
				
				if (array_key_exists($data['post_type'], self::$url) && array_key_exists('data', $data['post_data'])) {
					if ($data['post_type']=='action') {
						if (!array_key_exists('post_link', $data) || filter_var($data['post_link'], FILTER_VALIDATE_URL)) {
							if (array_key_exists('voucher', $data['post_data']['data'])) {
						 		$data['post_link']=$data['post_data']['data']['voucher'];
						 		$data['post_action_type']='voucher';
							}
							else if (array_key_exists('offer', $data['post_data']['data'])) {
								$data['post_link']=$data['post_data']['data']['offer'];
								$data['post_action_type']='offer';
							}
						}
					}
					else {
						if ((!array_key_exists('post_image', $data) || filter_var($data['post_link'], FILTER_VALIDATE_URL)) && array_key_exists('picture', $data['post_data']['data'])) {
							$data['post_image']=$data['post_data']['data']['picture'];
						}
						if ((!array_key_exists('post_link', $data) || !filter_var($data['post_link'], FILTER_VALIDATE_URL)) && array_key_exists('link', $data['post_data']['data'])) {
							$data['post_link']=$data['post_data']['data']['link'];
						}
						if ((!array_key_exists('post_message', $data) || strlen($data['post_message'])==0) && array_key_exists('message', $data['post_data']['data'])) {
							$data['post_message']=$data['post_data']['data']['message'];
						}
					}
				}
			}
			unset($data['post_data']);
		}
		
		if (!array_key_exists('post_message', $data) && !array_key_exists('post_image', $data) && !array_key_exists('post_link', $data)) {
			throw new \Exception(
				'Wrong post data.'
			);
		}
		
		if (!array_key_exists('post_type', $data) || !array_key_exists($data['post_type'], self::$url)) {
			throw new \Exception(
				'Wrong post type.'
			);
		}
		
		$parsed=array(
			'url'=>self::$url[$data['post_type']],
			'data'=>array()
		);
		
		if ($data['post_type']=='action') {
			if (!array_key_exists('post_action_type', $data) || !in_array($data['post_action_type'], array('voucher', 'offer'))) {
				$data['post_action_type']='offer';
			}
			
			if (array_key_exists('post_link', $data) && filter_var($data['post_link'], FILTER_VALIDATE_URL)) {
				$parsed['data'][$data['post_action_type']]=$data['post_link'];
			}
			else {
				return null;
			}
		}
		else {
			if (array_key_exists('post_message', $data) && strlen($data['post_message'])>0) {
				$parsed['data']['message']=$data['post_message'];
			}
			
			if (array_key_exists('post_link', $data) && filter_var($data['post_link'], FILTER_VALIDATE_URL)) {
				$parsed['data']['link']=$data['post_link'];
				if (array_key_exists('post_image', $data) && filter_var($data['post_image'], FILTER_VALIDATE_URL)) {
					$parsed['data']['picture']=$data['post_image'];
				}
			}
		}
		
		return json_encode($parsed);
	}
	
	public static function onUpdate($data) {
		if (is_array($data) && array_key_exists('id', $data)) {
			try {
				$session=\RLDL\Auth\Facebook::getSession();
				$response=(new \Facebook\FacebookRequest(
					$session,
					'POST',
					'/'.$data['id'],
					array(
						'method'=>'delete'
					)
				))->execute();
			}
			catch (\Exception $e) {
				return false;
			}
			return true;
		}
	}
	
	public static function send($post, $token=null) {
		try {
			if ($token==null) {
				$session=\RLDL\Auth\Facebook::getSession();
			}
			else {
				\Facebook\FacebookSession::setDefaultApplication(\RLDL\Config::getInstance()->get('fb_app_id'), \RLDL\Config::getInstance()->get('fb_app_secret'));
				
				$session=new \Facebook\FacebookSession($token);
			}
			
			$response=(new \Facebook\FacebookRequest(
				$session,
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