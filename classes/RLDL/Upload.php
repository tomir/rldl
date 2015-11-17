<?php
namespace RLDL;

class Upload {
	protected $sets;
	protected $default_permissions=777;
	protected $filePostData;
	protected $mimeType;
	
	public function __construct($sets=array()) {
		if (!is_array($sets)) {
			throw new \InvalidArgumentException(
				'Wrong upload settings.'
			);
		}
		$this->sets=array_merge(array(
			'destination'=>null,
			'mimes'=>array(),
			'max_file_size'=>10
		), $sets);
		
		if ($this->sets['destination']!=null && !$this->setDestination()) {
			throw new \Exception('Can\'t create upload destination.');
		}
	}
	
	protected function setDestination() {
		$this->sets['destination'] = $this->sets['destination'].DIRECTORY_SEPARATOR;
		return is_writable($this->sets['destination']) ?: $this->createDestination();
	}
	
	protected function createDestination() {
		return mkdir($this->sets['destination'], $this->default_permissions, true);
	}
	
	public function file($file) {
		if (is_array($file) && $this->checkFileArray($file)){
			$this->filePostData=$file;
		}
		else {
			throw new \InvalidArgumentException(
				'Wrong file handler.'
			);
		}
		
		$this->mimeType=$this->getFileMime();
		
		if (!empty($this->sets['mimes']) && !in_array($this->mimeType, $this->sets['mimes'])) {
			throw new \Exception(
				'Unsuported file type.',
				415
			);
		}
		
		if (is_numeric($this->sets['max_file_size']) && $this->getFileSize()>$this->sets['max_file_size']) {
			throw new \Exception(
				'File too big to process.',
				413
			);
		}
		
		return $this->filePostData['tmp_name'];
	}
	
	public function upload($filename=null) {
		if (!is_string($filename) || strlen($filename)<1) {
			throw new \InvalidArgumentException(
				'Wrong file name.'
			);
		}
		
		return move_uploaded_file($this->filePostData['tmp_name'], $this->sets['destination'].$filename);
	}
	
	public function source() {
		return file_get_contents($this->filePostData['tmp_name']);
	}
	
	public function mimeType() {
		return $this->mimeType;
	}

	protected function checkFileArray($file) {
		return isset($file['error'])
			&& !empty($file['name'])
			&& !empty($file['type'])
			&& !empty($file['tmp_name'])
			&& !empty($file['size']);
	}
	
	protected function getFileSize() {
		return round(filesize($this->filePostData['tmp_name'])/1048576,2);
	}
	
	protected function getFileMime() {
		if (strpos($this->filePostData['tmp_name'], 'gs://')===0) {
			$fp=fopen($this->filePostData['tmp_name'], 'r');
			require_once 'google/appengine/api/cloud_storage/CloudStorageTools.php';
			return \google\appengine\api\cloud_storage\CloudStorageTools::getContentType($fp);
		}
		else {
			$finfo = new \finfo();
			return $finfo->file($this->filePostData['tmp_name'], FILEINFO_MIME_TYPE);
		}
	}

}
?>