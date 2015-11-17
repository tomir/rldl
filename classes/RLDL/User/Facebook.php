<?php
namespace RLDL\User;

class Facebook extends \RLDL\User {
	/** get user byc fb id */
	public static function getUserByID($fb_id=null) {
		self::$sql=\MySQL::getInstance();
		if (($user_from_db=self::$sql->SelectSingleRowArray('[User]FB_users',array('fb_user_id'=>\MySQL::SQLValue($fb_id)),array('user_id')))!==false) {
			return self::getUser($user_from_db['user_id']);
		}
		return false;
	}
	
	public static function newUser($user_data=array()) {
		self::$sql=\MySQL::getInstance();
		
		self::$sql->TransactionBegin();
		
		$user_data['platform']='Facebook';
		
		$user_data['fb_gender']=$user_data['gender'];
		if (!in_array($user_data['gender'], array('male','female'))) {
			$user_data['gender']='o';
		}
		else {
			$user_data['gender']=substr($user_data['gender'], 0, 1);
		}
		
		$user=parent::newUser($user_data);
		
		if ($user->updateFBuser($user_data)) {
			self::$sql->TransactionEnd();
			return $user;
		}
		else {
			self::$sql->TransactionRollback();
			throw new \Exception(
				'DB error.'
			);
		}
	}
	
	private function updateFBuser($user_data=array()) {
		if (self::$sql->AutoInsertUpdate('[User]DataJSON', array(
			'user_id'=>\MySQL::SQLValue($this->id(),'int'),
			'user_data_json'=>\MySQL::SQLValue(json_encode($user_data['raw']))
		), array(
			'user_id'=>\MySQL::SQLValue($this->id(),'int')
		))!==false && self::$sql->AutoInsertUpdate('[User]FB_users', array(
			'user_id'=>\MySQL::SQLValue($this->id(),'int'),
			'fb_user_id'=>\MySQL::SQLValue($user_data['fb_id']),
			'fb_user_link'=>\MySQL::SQLValue($user_data['fb_link']),
			'fb_user_gender'=>\MySQL::SQLValue($user_data['fb_gender']),
			'fb_user_location'=>\MySQL::SQLValue($user_data['fb_location'])
		), array(
			'user_id'=>\MySQL::SQLValue($this->id(),'int')
		))!==false) {
			return true;
		}
		return false;
	}
	
	public function update($user_data=array()){
		$user_data['fb_gender']=$user_data['gender'];
		if (!in_array($user_data['gender'], array('male','female'))) {
			$user_data['gender']='o';
		}
		else {
			$user_data['gender']=substr($user_data['gender'], 0, 1);
		}
		return ($this->updateFBuser($user_data) && parent::update($user_data));
	}
	
	public function delete() {
		if (parent::delete()) {
			if (self::$sql->DeleteRows('[User]DataJSON', array(
				'user_id'=>\MySQL::SQLValue($this->id(),'int')
			)) && self::$sql->DeleteRows('[User]FB_users', array(
				'user_id'=>\MySQL::SQLValue($this->id(),'int')
			))) {
				return true;
			}
		}
		return false;
	}
	
	public function permissions() {
		if (\RLDL\Auth::getInstance()->isUser() && \RLDL\Auth::getInstance()->userId()==$this->id()) {
			$p=\RLDL\Auth::getInstance()->FB_getPermissions();
			
			return array_merge(parent::permissions(), array(
				'publish'=>array_key_exists('publish_actions', $p),
				'fb_page_publish'=>array_key_exists('manage_pages', $p)
			));
		}
		return parent::permissions();
	}
	
	public static function getPlatformId($user_id){
		self::$sql=\MySQL::getInstance();
		if (($user_from_db=self::$sql->SelectSingleRowArray('[User]FB_users',array('user_id'=>\MySQL::SQLValue($user_id)),array('fb_user_id')))!==false) {
			return $user_from_db['fb_user_id'];
		}
	}
}
?>