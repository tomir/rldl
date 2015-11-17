<?php
namespace RLDL;

class Notification {
	static protected $sql;
	static protected $auth;
	protected $id=null;
	protected $uid=null;
	protected $cache=null;
	
	public static function newItem($data) {
		if (!Auth::getInstance()->isSystem()) {
			throw new \Exception(
				'Only for System'
			);
		}
		
		if (!is_array($data)) {
			throw new \Exception(
				'Wrong notification data.'
			);
		}
		
		$user=$data['user'];
		
		if (!file_exists(dirname(__FILE__).'/Notification/'.$user->platform().'.php')) {
			throw new \Exception(
				'Wrong notification platform.'
			);
		}
		
		if (!array_key_exists('link', $data) || !filter_var($data['link'], FILTER_VALIDATE_URL)) {
			throw new \Exception(
				'Wrong notification link.'
			);
		}
		
		if (!array_key_exists('text', $data) || strlen($data['text'])==0) {
			throw new \Exception(
				'Wrong notification text.'
			);
		}
		
		$func='RLDL\\Notification\\'.$user->platform();
		
		$notification=$func::parse($data); 
		
		if (strlen($notification)<1) {
			throw new \Exception(
				'Wrong notification data.'
			);
		}
		
		if (\MySQL::getInstance()->InsertRow('[Notification]Notifications',array(
			'user_id'=>\MySQL::SQLValue($user->id(),'int'),
			'notification_platform'=>\MySQL::SQLValue($user->platform()),
			'notification_data'=>\MySQL::SQLValue($notification),
			'notification_response'=>'NULL',
			'notification_info'=>array_key_exists('info', $data) ? \MySQL::SQLValue(json_encode($data['info'])) : 'NULL'
		))) {
			
			return new Notification(\MySQL::getInstance()->GetLastInsertID());
		}
		else {
			throw new \Exception(
				'DB error.'
			);
		}
		
	}
	
	public function __construct($id=null) {
		if (!is_numeric($id) || $id<1) {
			throw new \InvalidArgumentException(
				'Wrong notification id.'
			);
		}
		
		self::$auth=Auth::getInstance();
		
		self::$sql=\MySQL::getInstance();
		
		$this->id=(string)$id;
	}
	
	public function id() {
		if ($this->id==null) {
			$this->get();
			$this->id=$this->cache['notification_id'];
		}
		return $this->id;
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
				'[Notification]Notifications', array_merge(self::$auth->isUser() ? array(
					'user_id'=>\MySQL::SQLValue(self::$auth->user()->id(),'int')
				) : array(), array(
					'notification_id'=>\MySQL::SQLValue($this->id,'int')
				))
			))!==false) {
				$data['notification_info']=json_decode($data['notification_info'], true);
				$data['notification_data']=json_decode($data['notification_data'], true);
				$data['notification_response']=json_decode($data['notification_response'], true);
				$this->cache=$data;
			}
			else {
				throw new \Exception(
					'notification not exists.',
					404
				);
			}
		}
		return $this->cache;
	}
	
	public function send(){
		$notification=$this->get();
		
		$func='RLDL\\Notification\\'.$notification['notification_platform'];
		
		if (strlen($notification['notification_response'])==0) {
			$response=(string)$func::send($notification['notification_data']);
			
			if (is_null($response)) {
				$response='[]';
			}
			
			if (self::$sql->UpdateRow('[Notification]Notifications',array(
				'notification_response'=>\MySQL::SQLValue($response)
			), array(
				'notification_id'=>\MySQL::SQLValue($this->id(),'int')
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
		
		$items=$sql->SelectArray('[Notification]Notifications', array('notification_response IS NULL'), null, 'notification_id', $limit);
		
		$sql->TimerStop();
		$timer+=$sql->TimerDuration();
		
		if ($items===false) {
			throw new \Exception(
				'DB error.'
			);
		}
		
		foreach ($items as &$post) {
			$sql->TimerStart();
			
			$func='RLDL\\Notification\\'.$post['notification_platform'];
			
			$response=(string)$func::send(json_decode($post['notification_data'], true));
			
			if (is_null($response)) {
				$response='[]';
			}
			
			if ($sql->UpdateRow('[Notification]Notifications',array(
				'notification_response'=>\MySQL::SQLValue($response)
			), array(
				'notification_id'=>\MySQL::SQLValue($post['notification_id'],'int')
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