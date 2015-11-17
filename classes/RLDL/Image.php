<?php
namespace RLDL;

require_once 'google/appengine/api/cloud_storage/CloudStorageTools.php';

class Image {

	static private $allowed=array('image/gif', 'image/jpeg', 'image/png', '');
	
	static private $image_types=array('campaign', 'deal');
	
	static public function all($type, $id=null) {
		if (
			!in_array($type, self::$image_types)
		) {
			throw new \InvalidArgumentException(
				'Wrong image type.'
			);
		}
		
		$items=array();
		$sql=\MySQL::getInstance();
		
		foreach ($sql->SelectArray('[Client]Images', 'WHERE `image_type` LIKE '.\MySQL::SQLValue($type.'%').' AND `image_type_id`='.\MySQL::SQLValue($id,'int')) as $img) {
			$img_type=substr($img['image_type'], strpos($img['image_type'], '_')+1);
			$items[$img_type]=array(
				'url'=>$img['image_uri'],
				'data'=>json_decode($img['image_data'],true)
			);
		}
		return $items;
	}
	
	static public function get($type, $name, $id=null) {
		if (
			!in_array($type, self::$image_types)
		) {
			throw new \InvalidArgumentException(
				'Wrong image type.'
			);
		}
		
		$sql=\MySQL::getInstance();
		
		$item=$sql->SelectSingleRowArray('[Client]Images', array(
			'image_type'=>\MySQL::SQLValue($type.'_'.$name),
			'image_type_id'=>\MySQL::SQLValue($id, 'int')
		));
		
		if (!$item) {
			throw new \InvalidArgumentException(
				'Image not found.',
				404
			);
		}
		
		return array(
			'url'=>$item['image_uri'],
			'data'=>json_decode($item['image_data'],true)
		);
	}
	
	static public function getVideo($type, $name, $id=null) {
		return self::get($type, $name, $id);
	}
	
	static public function delete($type, $name, $id=null) {
		if (
			!in_array($type, self::$image_types)
		) {
			throw new \InvalidArgumentException(
				'Wrong image type.'
			);
		}
		
		$sql=\MySQL::getInstance();
		
		if (!$sql->DeleteRows('[Client]Images', array(
			'image_type'=>\MySQL::SQLValue($type.'_'.$name),
			'image_type_id'=>\MySQL::SQLValue($id, 'int')
		))) {
			throw new \Exception(
				$sql->Error(),
				500
			);
		}
		
		return true;
	}
	
	static public function deleteVideo($type, $name, $id=null) {
		return self::delete($type, $name, $id);
	}
	
	static private function newItem($src, $filetype, $type, $name, $id=null, $data=array()) {
		if (
			!in_array($type, self::$image_types)
		) {
			throw new \InvalidArgumentException(
				'Wrong image type.'
			);
		}
		
		$config=Config::getInstance();
		
		$filename=array(self::randomName($name));
		$destination=$config->get('img_dir').DIRECTORY_SEPARATOR.date("Ym").DIRECTORY_SEPARATOR;
		
		$saved=false;
		$count=0;
		
		mkdir($destination, 0777, true);
		
		$ctx = stream_context_create(array('gs'=>array(
			'acl'=>'public-read',
			'enable_cache'=>true,
			'enable_optimistic_cache'=>true,
			'read_cache_expiry_seconds'=> 86400,
			'Content-Type'=> 'image/'.$filetype
		)));
		
		while (!$saved) {
			$filename[1]=$count>0 ? '-'.$count : '';
			if (file_exists($destination.implode('',$filename).'.'.$filetype)) {
				$count++;
			}
			else if (file_put_contents($destination.implode('',$filename).'.'.$filetype, $src, 0, $ctx)>0) {
				$saved=true;
			}
			else {
				throw new \Exception(
					'Error writing file.'
				);
			}
		}
		
		$imgColors=new \SNX_IMG_colors($destination.implode('',$filename).'.'.$filetype);
		
		$image_url=\google\appengine\api\cloud_storage\CloudStorageTools::getImageServingUrl($destination.implode('',$filename).'.'.$filetype, ['secure_url' => true]);
		
		$sql=\MySQL::getInstance();
		
		if (!$sql->AutoInsertUpdate('[Client]Images', array(
			'image_type'=>\MySQL::SQLValue($type.'_'.$name),
			'image_type_id'=>\MySQL::SQLValue($id, 'int'),
			'image_uri'=>\MySQL::SQLValue($image_url.'=w{{w}}-h{{h}}'),
			'image_data'=>\MySQL::SQLValue(json_encode(array_merge(array(
				'colors'=>$imgColors->colors(),
				'org'=>\google\appengine\api\cloud_storage\CloudStorageTools::getPublicUrl($destination.implode('',$filename).'.'.$filetype, true)
			), $data)))
		), array(
			'image_type'=>\MySQL::SQLValue($type.'_'.$name),
			'image_type_id'=>\MySQL::SQLValue($id, 'int')
		))) {
			throw new \Exception(
				$sql->Error(),
				500
			);
		}
		
		return true;
	}
	
	static private function randomName($name) {
		return sha1(mt_rand(1, 9999).uniqid().$name);
	}
	
	static public function set($url, $type, $name, $id=null, $data=array()) {
		if (
			!in_array($type, self::$image_types)
		) {
			throw new \Exception(
				'Wrong image type.'
			);
		}
		
		$config=Config::getInstance();
		
		$max_size=$config->get('img_max_size');
		
		$curl=new Curl();
		$file=$curl->get($url);
		
		$mime=$curl->contentType();
		
		if (!in_array($mime, self::$allowed)) {
			throw new \Exception(
				'Unsuported file type.',
				415
			);
		}
		
		$file_size=$curl->contentSize();
		
		if (!is_numeric($file_size) || $file_size>$max_size) {
			throw new \Exception(
				'File too big to process.',
				413
			);
		}
		
		$curl=new Curl();
		
		return self::newItem($file, explode('/', $mime)[1], $type, $name, $id, $data);
	}
	
	static public function upload($file, $type, $name, $id=null, $data=array()) {
		if (
			!in_array($type, self::$image_types)
		) {
			throw new \Exception(
				'Wrong image type.'
			);
		}
		
		$config=Config::getInstance();
		
		$upload=new Upload(array(
			'mimes'=>self::$allowed,
			'max_file_size'=>$config->get('img_max_size')
		));
		
		$upload->file($file);
		
		return self::newItem($upload->source(), explode('/', $upload->mimeType())[1], $type, $name, $id, $data);
	}
	
	static public function setVideo($url, $type, $name, $id=null) {
		$url=Tools::parseUrl($url);
		
		$video_url=null;
		
		if (strpos($url['host'], 'youtu.be')!==false && array_key_exists('path', $url) && strlen($url['path'][0])>0) {
			$video_url='https://www.youtube.com/v/'.$url['path'][0];
		}
		else if (strpos($url['host'], 'youtube.com')!==false) {
			if (array_key_exists('path', $url) && ($url['path'][0]=='embed' || $url['path'][0]=='v')) {
				$video_url='https://www.youtube.com/v/'.$url['path'][1];
			}
			else if (array_key_exists('query', $url) && array_key_exists('v', $url['query'])) {
				$video_url='https://www.youtube.com/v/'.$url['query']['v'];
			}
		}
		
		if (is_null($video_url)) {
			throw new \InvalidArgumentException(
				'Unsuported file type.',
				415
			);
		}
		
		$sql=\MySQL::getInstance();
		
		if (!$sql->AutoInsertUpdate('[Client]Images', array(
			'image_type'=>\MySQL::SQLValue($type.'_'.$name),
			'image_type_id'=>\MySQL::SQLValue($id, 'int'),
			'image_uri'=>\MySQL::SQLValue($video_url)
		), array(
			'image_type'=>\MySQL::SQLValue($type.'_'.$name),
			'image_type_id'=>\MySQL::SQLValue($id, 'int')
		))) {
			throw new \Exception(
				$sql->Error(),
				500
			);
		}
		
		return true;
	}
}
?>