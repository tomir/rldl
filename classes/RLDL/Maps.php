<?php
namespace RLDL;

class Maps {
	private $markers=array();

	public static function create($options=array()) {
		return new self($options);
	}
	
	public function __construct($options=array()) {
		if (!is_array($options)) {
			$options=array();
		}
		
		if (array_key_exists('markers', $options) && is_array($options['markers'])) {
			foreach ($options['markers'] as &$marker) {
				if (is_array($marker) && count($marker)>=2 && is_numeric($marker[0]) && is_numeric($marker[1])) {
					$this->markers[]=$marker;
				}
			}
		}
	}
	
	public function staticImage($w=100, $h=100) {
		$url='https://maps.googleapis.com/maps/api/staticmap?size='.$w.'x'.$h.'&scale=2&maptype=roadmap&markers=color:red';
		foreach ($this->markers as &$point) {
			$url.='%7C'.$point[0].','.$point[1];
		}
		
		$url.="&key=".Config::getVar('google_maps_key');
		
		return $url;
	}
	
	public function data() {
		return array(
			'markers'=>$this->markers
		);
	}
}