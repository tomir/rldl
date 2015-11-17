<?php
namespace RLDL\User;

class Google extends \RLDL\User {
	/** get user byc fb id */
	public static function getUserByID($gp_id=null) {
		self::$sql=\MySQL::getInstance();
		if (($user_from_db=self::$sql->SelectSingleRowArray('[User]GP_users',array('gp_user_id'=>\MySQL::SQLValue($gp_id)),array('user_id')))!==false) {
			return self::getUser($user_from_db['user_id']);
		}
		return false;
	}
	
	public static function newUser($user_data=array()) {
		self::$sql=\MySQL::getInstance();
		
		self::$sql->TransactionBegin();
		
		$user_data['platform']='Google';
		
		$user_data['gp_gender']=$user_data['gender'];
		if (!in_array($user_data['gender'], array('male','female'))) {
			$user_data['gender']='o';
		}
		else {
			$user_data['gender']=substr($user_data['gender'], 0, 1);
		}
		
		$user=parent::newUser($user_data);
		
		if ($user->updateGPuser($user_data)) {
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
	
	private function updateGPuser($user_data=array()) {
		if (self::$sql->AutoInsertUpdate('[User]DataJSON', array(
			'user_id'=>\MySQL::SQLValue($this->id(),'int'),
			'user_data_json'=>\MySQL::SQLValue(json_encode($user_data['raw']))
		), array(
			'user_id'=>\MySQL::SQLValue($this->id(),'int')
		))!==false && self::$sql->AutoInsertUpdate('[User]GP_users', array(
			'user_id'=>\MySQL::SQLValue($this->id(),'int'),
			'gp_user_id'=>\MySQL::SQLValue($user_data['gp_id']),
			'gp_user_link'=>\MySQL::SQLValue($user_data['gp_link']),
			'gp_user_gender'=>\MySQL::SQLValue($user_data['gp_gender']),
			'gp_user_location'=>\MySQL::SQLValue($user_data['gp_location'])
		), array(
			'user_id'=>\MySQL::SQLValue($this->id(),'int')
		))!==false) {
			return true;
		}
		return false;
	}
	
	public function update($user_data=array()){
		$user_data['gp_gender']=$user_data['gender'];
		if (!in_array($user_data['gender'], array('male','female'))) {
			$user_data['gender']='o';
		}
		else {
			$user_data['gender']=substr($user_data['gender'], 0, 1);
		}
		return ($this->updateGPuser($user_data) && parent::update($user_data));
	}
	
	public function delete() {
		if (parent::delete()) {
			if (self::$sql->DeleteRows('[User]DataJSON', array(
				'user_id'=>\MySQL::SQLValue($this->id(),'int')
			)) && self::$sql->DeleteRows('[User]GP_users', array(
				'user_id'=>\MySQL::SQLValue($this->id(),'int')
			))) {
				return true;
			}
		}
		return false;
	}
	
	public static function getPlatformId($user_id){
		self::$sql=\MySQL::getInstance();
		if (($user_from_db=self::$sql->SelectSingleRowArray('[User]GP_users',array('user_id'=>\MySQL::SQLValue($user_id)),array('gp_user_id')))!==false) {
			return $user_from_db['gp_user_id'];
		}
	}
}
?>