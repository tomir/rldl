<?php


/**
 * Simple PHP upload class
 * 
 * @author Aivis Silins
 */
 
require_once 'google/appengine/api/cloud_storage/CloudStorageTools.php';
use google\appengine\api\cloud_storage\CloudStorageTools;
 
 
class Upload {
	
	/**
	 * File post array
	 * 
	 * @var array
	 */
	protected $files_post = array();
	
	
	/**
	 * Destination directory
	 * 
	 * @var string
	 */
	protected $destination;
	
	
	/**
	 * Fileinfo 
	 * 
	 * @var object
	 */
	protected $finfo;
	
	
	/**
	 * Data about file
	 * 
	 * @var array
	 */
	public $file = array();
	
	
	/**
	 * Max. file size
	 * 
	 * @var int 
	 */
	protected $max_file_size;
	
	
	/**
	 * Allowed mime types
	 * 
	 * @var array
	 */
	protected $mimes = array();
	
	
	/**
	 * Temp path
	 * 
	 * @var string
	 */
	protected $tmp_name;
	
	
	/**
	 * Validation errors
	 * 
	 * @var array
	 */
	protected $validation_errors = array();
	
	
	/**
	 * Filename (new)
	 * 
	 * @var string
	 */
	protected $filename;
	
	
	/**
	 * Internal callbacks (filesize check, mime, etc)
	 * 
	 * @var array
	 */
	private $callbacks = array();
	
	private $file_type = '';
	
	private $return = false;
	private $return_temp = false;
	
	
	/**
	 * Return upload object
	 * 
	 * $destination		= 'path/to/your/file/destination/folder';
	 * 
	 * @param string $destination
	 * @return Upload 
	 */
	public static function factory($destination, $return=false) {
		
		return new Upload($destination, $return);
		
	}
	

	/**
	 *  Define ROOT constant and set & create destination path
	 * 
	 * @param string $destination 
	 */
	public function __construct($destination, $return=false) {
		
		// set & create destination path
		if (!$return && $destination && !$this->set_destination($destination)) {
			
			throw new Exception('Upload: Can\'t create destination.');
			
		}
		else if (!$destination) {
			$this->return_temp=true;
		}
		
		$this->return = $return;
		
		
		//create finfo object
		//$this->finfo = new finfo();
		
	}
	
	
	/**
	 * Check & Save file
	 * 
	 * Return data about current upload
	 *  
	 * @return array
	 */
	public function upload() {
		
		if ($this->check()) {
			if ($this->return) {
				$this->content();
			}
			else if ($this->return_temp) {
				$this->file['status'] = true;
			}
			else {
				$this->save();
			}
			
		}
		
		// return state data
		return $this->get_state();
		
	}
	
	private function content() {
		$this->file['content'] = @file_get_contents($this->tmp_name);
		
		$this->file['status'] = true;
	}
	
	
	/**
	 * Save file on server
	 * 
	 * Return state data
	 * 
	 * @return array 
	 */
	public function save() {
		
		$this->save_file();
		
		return $this->get_state();

	}
	
	public function unlink() {
		unlink($this->tmp_name);
	}
	
	
	/**
	 * Validate file (execute callbacks)
	 * 
	 * Returns TRUE if validation successful
	 * 
	 * @return bool
	 */
	public function check() {
		
		//execute callbacks (check filesize, mime, also external callbacks
		$this->validate();
		
		//add error messages
		$this->file['errors'] = $this->get_errors();
		
		//change file validation status
		$this->file['status'] = empty($this->validation_errors);
		
		return $this->file['status'];
		
	}
	
	
	/**
	 * Get current state data
	 * 
	 * @return array
	 */
	public function get_state() {
		
		return $this->file;
		
	}
	
	
	/**
	 * Save file on server
	 */
	protected function save_file() {
		
		//create & set new filename
		$this->create_new_filename();
		
		//set filename
		$this->file['filename']	= $this->filename;
		
		//set full path
		$this->file['full_path'] = $this->destination . $this->filename;
		
		$status = move_uploaded_file($this->tmp_name, $this->file['full_path']);
		
		//checks whether upload successful
		if (!$status) {
			throw new Exception('Upload: Can\'t upload file.');
		}
		
		//done
		$this->file['status']	= true;
		
	}
	
	
	/**
	 * Set data about file
	 */
	protected function set_file_data() {
		
		$file_size = $this->get_file_size();
		
		$this->file = array(
			'status'				=> false,
			'destination'			=> $this->destination,
			'size_in_bytes'			=> $file_size,
			'size_in_mb'			=> $this->bytes_to_mb($file_size),
			'mime'					=> $this->get_file_mime(),
			'original_filename'		=> $this->file_post['name'],
			'tmp_name'				=> $this->file_post['tmp_name'],
			'post_data'				=> $this->file_post,
		);
		
	}
	
	/**
	 * Set validation error
	 * 
	 * @param string $message 
	 */
	public function set_error($message) {
		
		$this->validation_errors[] = $message;
		
	}
	
	
	/**
	 * Return validation errors
	 * 
	 * @return array
	 */
	public function get_errors() {
		
		return $this->validation_errors;
		
	}
	
	/**
	 * Execute callbacks
	 */
	protected function validate() {
		
		//get curent errors
		$errors = $this->get_errors();
		
		if (empty($errors)) {
			
			//set data about current file
			$this->set_file_data();
			
			//execute internal callbacks
			$this->execute_callbacks($this->callbacks, $this);
		
			
		}
	
	}
	
	
	/**
	 * Execute callbacks
	 */
	protected function execute_callbacks($callbacks, $object) {
		
		foreach($callbacks as $method) {
			
			$object->$method($this);
			
		}
		
	}
	
	
	/**
	 * File mime type validation callback
	 * 
	 * @param obejct $object 
	 */
	protected function check_mime_type($object) {
		
		if (!empty($object->mimes)) {
			
			if (!in_array($object->file['mime'], $object->mimes)) {
				
				$object->set_error('mime');
				
			}
			
		}

	}
	
	
	/**
	 * Set allowed mime types
	 * 
	 * @param array $mimes 
	 */
	public function set_allowed_mime_types($mimes) {
		
		$this->mimes		= $mimes;
		
		//if mime types is set -> set callback
		$this->callbacks[]	= 'check_mime_type';
		
	}
	
	
	/**
	 * File size validation callback
	 * 
	 * @param object $object 
	 */
	protected function check_file_size($object) {
		
		if (!empty($object->max_file_size)) {
			
			$file_size_in_mb = $this->bytes_to_mb($object->file['size_in_bytes']);
			
			if ($object->max_file_size <= $file_size_in_mb) {
				
				$object->set_error('too_big_file');
				
			}
			
		}
		
	}
	
	
	/**
	 * Set max. file size
	 * 
	 * @param int $size 
	 */
	public function set_max_file_size($size) {
		
		$this->max_file_size	= $size;
		
		//if max file size is set -> set callback
		$this->callbacks[]	= 'check_file_size';
		
	}
	
	
	/**
	 * Set File array to object
	 *  
	 * @param array $file 
	 */
	public function file($file) {
		
		$this->set_file_array($file);
		
	}
	
	
	/**
	 * Set file array 
	 * 
	 * @param array $file 
	 */
	protected function set_file_array($file) {
		
		//checks whether file array is valid
		if (!$this->check_file_array($file)) {
			
			//file not selected or some bigger problems (broken files array)
			$this->set_error('Please select file.');
			
		}
		
		//set file data
		$this->file_post = $file;

		//set tmp path
		$this->tmp_name  = $file['tmp_name'];
		
	}
	
	
	/**
	 * Checks whether Files post array is valid
	 * 
	 * @return bool
	 */
	protected function check_file_array($file) {
		
		return isset($file['error']) 
			&& !empty($file['name']) 
			&& !empty($file['type']) 
			&& !empty($file['tmp_name']) 
			&& !empty($file['size']);
		
	}


	/**
	 * Get file mime type
	 * 
	 * @return string
	 */
	protected function get_file_mime() {
		$fp = fopen($this->tmp_name, 'r');
		return CloudStorageTools::getContentType($fp);//$this->finfo->file($this->tmp_name, FILEINFO_MIME_TYPE);
		
	}
	
	
	/**
	 * Get file size
	 * 
	 * @return int
	 */
	protected function get_file_size() {

		return filesize($this->tmp_name);
		
	}

	
	/**
	 * Set destination path (return TRUE on success)
	 * 
	 * @param string $destination
	 * @return bool 
	 */
	protected function set_destination($destination) {

		$this->destination = $destination . DIRECTORY_SEPARATOR;

		return $this->destination_exist() ?: $this->create_destination();

	}
	
	
	/**
	 * Checks whether destination folder exists
	 * 
	 * @return bool
	 */
	protected function destination_exist() {
	
		return is_writable($this->destination);
		
	}
	
	
	/**
	 * Create path to destination
	 * 
	 * @param string $dir
	 * @return bool
	 */
	protected function create_destination() {
		
		return @mkdir($this->destination, 0777, true);
		
	}
	
	
	/**
	 * Set unique filename
	 * 
	 * @return string
	 */
	protected function create_new_filename() {
		$ok=false;
		$count=0;
		$filename=array(sha1(mt_rand(1, 9999).uniqid()),'');
		if (preg_match('/^image\//', $this->file['mime'])) {
			$filename[2]='.'.preg_replace('/^image\//', '', $this->file['mime']);
		}
		else {
			$filename[2]=strrchr($this->file['original_filename'], ".");
		}
		while ($ok) {
			$filename[1]=sha1($count);
			if (file_exists($this->destination.implode('',$filename))) {
				$count++;
			}
			else {
				$ok=true;
			}
		}
		
		$this->filename = implode('',$filename);
	}
	
	
	/**
	 * Convert bytes to mb.
	 *  
	 * @param int $bytes
	 * @return int
	 */
	protected function bytes_to_mb($bytes) {
		
		return round(($bytes / 1048576), 2);
		
	}
	
	function __destruct() {
		$this->unlink();
	}
	
	
} // end of Upload