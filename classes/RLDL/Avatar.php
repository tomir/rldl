<?php
namespace RLDL;

class Avatar {
	public static function random($type, $id=null, $limit=20) {
		if (
			!in_array($type, array('campaign', 'deal')) ||
			!is_numeric($id) ||
			!is_numeric($limit) ||
			$limit<1
		) {
			throw new \InvalidArgumentException(
				'Invalid argument.'
			);
		}
		
		$items=array();
		$sql=\MySQL::getInstance();
		
		$config=Config::getInstance();
		
		$url=explode('{{user_id}}', $config->get('url_avatars'));
		
		foreach ($sql->QueryArray('SELECT CONCAT("'.$url[0].'", `user_id`, "'.$url[1].'") AS `user_avatar` FROM `[User]Variants` WHERE `'.$type.'_id`='.\MySQL::SQLValue($id,'int').' GROUP BY `user_id` ORDER BY RAND() LIMIT '.$limit) as $user) {
			array_push($items, $user['user_avatar']);
		}
		return $items;
	}
}
?>