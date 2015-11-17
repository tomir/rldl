<?php
namespace RLDL;

class Post {
	static protected $sql;
	static protected $auth;
	protected $id=null;
	protected $uid=null;
	protected $cache=null;
	
	public static function newItem($data) {
		if (!Auth::getInstance()->isUser()) {
			throw new \Exception(
				'Allowed only for users.',
				401
			);
		}
		
		if (!is_array($data)) {
			throw new \Exception(
				'Wrong post data.'
			);
		}
		
		if (!array_key_exists('post_uid', $data) || is_numeric($data['post_uid'])) {
			throw new \Exception(
				'Wrong post uid.'
			);
		}
		
		if (!array_key_exists('post_type', $data) || $data['post_type']!='action') {
			$data['post_type']='post';
		}
		
		$user=Auth::getInstance()->user();
		
		if (!file_exists(dirname(__FILE__).'/Post/'.$user->platform().'.php')) {
			throw new \Exception(
				'Wrong post platform.'
			);
		}
		
		if (array_key_exists('post_link', $data)) {
			$data['post_link']=self::postLink($data['post_link'], $user->id(), $user->platform());
		}
		
		$func='RLDL\\Post\\'.$user->platform();
		
		try {
			$id=(new Post($data['post_uid']))->id();
		}
		catch (\Exception $e) {
			$id=null;
		}
		if (is_numeric($id)) {
			throw new \Exception(
				'Post uid exists.'
			);
		}
		
		$post=$func::parse($data); 
		
		if (strlen($post)<1) {
			throw new \Exception(
				'Wrong post data.'
			);
		}
		
		if (\MySQL::getInstance()->InsertRow('[Post]Posts',array(
			'user_id'=>\MySQL::SQLValue($user->id(),'int'),
			'post_uid'=>\MySQL::SQLValue($data['post_uid']),
			'post_platform'=>\MySQL::SQLValue($user->platform()),
			'post_data'=>\MySQL::SQLValue($post),
			'post_response'=>'NULL',
			'post_info'=>array_key_exists('post_info', $data) ? \MySQL::SQLValue(json_encode($data['post_info'])) : 'NULL'
		))) {
			return new Post($data['post_uid']);
		}
		else {
			throw new \Exception(
				$sql->Error(),
				500
			);
		}
		
	}
	
	public function __construct($id=null) {
		if ((!is_numeric($id) && strlen($id)<1) || (is_numeric($id) && $id<1)) {
			throw new \InvalidArgumentException(
				'Wrong post id.'
			);
		}
		
		self::$auth=Auth::getInstance();
		
		self::$sql=\MySQL::getInstance();
		
		if (!is_numeric($id)) {
			$this->uid=$id;
		}
		else {
			$this->id=(string)$id;
		}
	}
	
	public function id() {
		if ($this->id==null) {
			$this->get();
			$this->id=$this->cache['post_id'];
		}
		return $this->id;
	}
	
	public function uid() {
		if ($this->uid==null) {
			$this->get();
			$this->uid=$this->cache['post_uid'];
		}
		return $this->uid;
	}
	
	public function get() {
		if (!self::$auth->isSystem() && !self::$auth->isUser()) {
			throw new \Exception(
				'Not authorized for this operation.',
				403
			);
		}
		if (is_null($this->cache)) {
			if (($data=self::$sql->SelectSingleRowArray(
				'[Post]Posts', array_merge(self::$auth->isUser() ? array(
					'user_id'=>\MySQL::SQLValue(self::$auth->user()->id(),'int')
				) : array(), $this->id==null ? array(
					'post_uid'=>\MySQL::SQLValue($this->uid),
					'post_update>=DATE_SUB(NOW(), INTERVAL 1 DAY)'
					
				) : array(
					'post_id'=>\MySQL::SQLValue($this->id,'int'),
					'post_update>=DATE_SUB(NOW(), INTERVAL 1 DAY)'
				)), null, '-post_id', 1
			))!==false) {
				$data['post_info']=json_decode($data['post_info'], true);
				$data['post_data']=json_decode($data['post_data'], true);
				$data['post_response']=json_decode($data['post_response'], true);
				$this->cache=$data;
			}
			else {
				throw new \Exception(
					'Post not exists.',
					404
				);
			}
		}
		return $this->cache;
	}
	
	public function update($data) {
		if (!self::$auth->isUser()) {
			throw new \Exception(
				'Allowed only for users.',
				401
			);
		}
		
		if (!is_array($data)) {
			throw new \Exception(
				'Wrong post data.'
			);
		}
		
		if ($this->get()['user_id']!=self::$auth->user()->id()) {
			throw new \Exception(
				'Not authorized for this operation.',
				403
			);
		}
		
		if (array_key_exists('post_link', $data)) {
			$data['post_link']=self::postLink($data['post_link'], $this->get()['user_id'], $this->get()['post_platform']);
		}
		
		$func='RLDL\\Post\\'.$this->get()['post_platform'];
		
		$post=$func::parse(array_merge($this->get(), $data));
		
		if (strlen($post)<1) {
			throw new \Exception(
				'Wrong post data.'
			);
		}
		
		$func::onUpdate($this->get()['post_response']);
		
		if (self::$sql->UpdateRow('[Post]Posts',array(
			'post_data'=>\MySQL::SQLValue($post),
			'post_response'=>'NULL'
		), array(
			'post_id'=>\MySQL::SQLValue($this->id(),'int')
		))!=false) {
			$this->cache=null;
			return true;
		}
		else {
			throw new \Exception(
				'DB error.'
			);
		}
	}
	
	public function send(){
		$post=$this->get();
		
		$func='RLDL\\Post\\'.$post['post_platform'];
		
		if (is_null($post['post_response']) || (!is_array($post['post_response']) && strlen($post['post_response'])==0) || (is_array($post['post_response']) && count($post['post_response'])==0)) {
			$response=(string)$func::send($post['post_data']);
			
			if (is_null($response)) {
				$response='[]';
			}
			
			if (self::$sql->UpdateRow('[Post]Posts',array(
				'post_response'=>\MySQL::SQLValue($response)
			), array(
				'post_id'=>\MySQL::SQLValue($this->id(),'int')
			))!=false) {
				$this->cache=null;
				return true;
			}
			else {
				throw new \Exception(
					'DB error.'
				);
			}
		}
		return false;
	}
	
	private static function postLink($url, $user, $platform) {
		$url.=(strpos($url, '?')===false ? '?' : '&').str_replace(array('{{platform}}', '{{user_id}}'), array($platform, $user), Config::getInstance()->get('post_utm'));
		return $url;
	}
	
	public static function sendItems($limit=100, $time_limit=9999) {
		$timer=0;
		
		$auth=\RLDL\Auth::getInstance();
		
		if (!$auth->isSystem()) {
			throw new \Exception(
				'Unautorized.',
				401
			);
		}
		
		$sql=\MySQL::getInstance();
		
		$sql->TimerStart();
		
		$items=$sql->SelectArray('[Post]Posts', array('post_response IS NULL'), null, 'post_id', $limit);
		
		$sql->TimerStop();
		$timer+=$sql->TimerDuration();
		
		if ($items===false) {
			throw new \Exception(
				'DB error.'
			);
		}
		
		foreach ($items as &$post) {
			$sql->TimerStart();
			
			$func='RLDL\\Post\\'.$post['post_platform'];
			
			$response=(string)$func::send(json_decode($post['post_data'], true), Auth::getToken($post['user_id']));
			
			if (is_null($response)) {
				$response='[]';
			}
			
			if ($sql->UpdateRow('[Post]Posts',array(
				'post_response'=>\MySQL::SQLValue($response)
			), array(
				'post_id'=>\MySQL::SQLValue($post['post_id'],'int')
			))===false) {
				throw new \Exception(
					'DB error.'
				);
			}
			
			$sql->TimerStop();
			$timer+=$sql->TimerDurationSeconds();
			
			if ($timer>=$time_limit) {
				break;
			}
		}
		return true;
	}
}
?>