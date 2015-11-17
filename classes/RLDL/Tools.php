<?php
namespace RLDL;

class Tools {
	public static function parseUrl($url) {
		$url=parse_url($url);
		if (array_key_exists('query', $url) && !is_null($url['query'])) {
			$query=$url['query'];
			$url['query']=array();
			parse_str($query, $url['query']);
		}
		if (array_key_exists('path', $url) && !is_null($url['path'])) {
			$url['path']=array_slice(explode('/', $url['path']),1);
		}
		return $url;
	}
}
?>