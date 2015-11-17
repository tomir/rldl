<?php
namespace RLDL;

class User {
	static protected $sql;
	
	static protected $config;
	
	protected static $oUsers=false;
	
	protected static $platforms=array(
		'FB'=>'Facebook',
		'GP'=>'Google'
	);
	
	private $data;
	
	final public static function getUser($user_id) {
		if (self::$oUsers==false) {
			self::$oUsers=array();
		}
		if (!array_key_exists($user_id, self::$oUsers)) {
			$calledClass = get_called_class();
			$args = func_get_args();
			$class = new \ReflectionClass($calledClass);
			self::$oUsers[$user_id]=$class->newInstanceArgs($args);
		}
		return self::$oUsers[$user_id];
	}
	
	public static function newUser($user_data=array()) {
		self::$sql=\MySQL::getInstance();
		
		if (!is_array($user_data)) {
			throw new \InvalidArgumentException(
				'Wrong user data.'
			);
		}
		
		if (!in_array($user_data['platform'], self::$platforms, true)) {
			throw new \InvalidArgumentException(
				'Wrong user platform.'
			);
		}
		
		if (!in_array($user_data['gender'], array('m','f','o',null))) {
			throw new \InvalidArgumentException(
				'Wrong user gender.'
			);
		}
		
		if (!preg_match('/[a-z]{2}_[A-Z]{2}/', $user_data['locale'])) {
			throw new \InvalidArgumentException(
				'Wrong user locale.'
			);
		}
		
		if (!filter_var($user_data['email'], FILTER_VALIDATE_EMAIL)) {
			$user_data['email']=null;
		}
		
		if (self::$sql->InsertRow('[User]Users', array(
			'user_name'=>\MySQL::SQLValue($user_data['name']),
			'user_mail'=>\MySQL::SQLValue($user_data['email']),
			'user_first_name'=>\MySQL::SQLValue($user_data['first_name']),
			'user_last_name'=>\MySQL::SQLValue($user_data['last_name']),
			'user_gender'=>\MySQL::SQLValue($user_data['gender']),
			'user_locale'=>\MySQL::SQLValue($user_data['locale']),
			'user_from'=>\MySQL::SQLValue(array_flip(self::$platforms)[$user_data['platform']]),
			'user_avatar'=>\MySQL::SQLValue($user_data['avatar'])
		))!=false) {
			return self::getUser(self::$sql->GetLastInsertID());
		}
		else {
			throw new \Exception(
				'DB error.'
			);
		}
	}
	
	final public function updateUser($user_id,$user_data=array()) {
		$user=self::getUser($user_id);
		$user->update($user_data);
		return $user;
	}
	
	public function __construct($user_id) {
		self::$sql=\MySQL::getInstance();
		self::$config=Config::getInstance();
		
		if (!is_numeric($user_id)) {
			throw new \InvalidArgumentException(
				'Wrong user id.'
			);
		}
		
		$data=self::$sql->SelectSingleRowArray('[User]Users',array('user_id'=>\MySQL::SQLValue($user_id,'int'),'`user_from` IS NOT NULL'));
		
		if (!is_array($data)) {
			throw new \InvalidArgumentException(
				'Wrong user id.'
			);
		}
		
		if (!array_key_exists('user_id', $data)) {
			throw new \InvalidArgumentException(
				'Wrong user id.'
			);
		}
		
		$this->data=$data;
	}
	
	private function reload(){
		$this->data=self::$sql->SelectSingleRowArray('[User]Users',array('user_id'=>\MySQL::SQLValue($this->id(),'int')));
	}
	
	public function update($user_data=array()){
		if (!is_array($user_data)) {
			$user_data=array();
		}
		
		if (!in_array($user_data['gender'], array('m','f','o',null))) {
			$user_data['gender']=$this->gender();
		}
		
		if (!preg_match('/[a-z]{2}_[A-Z]{2}/', $user_data['locale'])) {
			$user_data['locale']=$this->locale();
		}
		
		if (!filter_var($user_data['email'], FILTER_VALIDATE_EMAIL)) {
			$user_data['email']=$this->email();
		}
		
		if (self::$sql->UpdateRow('[User]Users', array(
			'user_name'=>\MySQL::SQLValue($user_data['name']),
			'user_mail'=>\MySQL::SQLValue($user_data['email']),
			'user_first_name'=>\MySQL::SQLValue($user_data['first_name']),
			'user_last_name'=>\MySQL::SQLValue($user_data['last_name']),
			'user_gender'=>\MySQL::SQLValue($user_data['gender']),
			'user_locale'=>\MySQL::SQLValue($user_data['locale']),
			'user_avatar'=>\MySQL::SQLValue($user_data['avatar'])
		), array(
			'user_id'=>\MySQL::SQLValue($this->id(), 'int')
		))!=false) {
			$this->reload();
			return true;
		}
		return false;
	}
	
	public function id(){
		return (string)$this->data['user_id'];
	}
	
	public function name(){
		return $this->data['user_name'];
	}
	public function lastName(){
		return $this->data['user_last_name'];
	}
	public function firstName(){
		return $this->data['user_first_name'];
	}
	public function gender(){
		return $this->data['user_gender'];
	}
	public function locale(){
		return $this->data['user_locale'];
	}
	public function email(){
		return (filter_var($this->data['user_mail'], FILTER_VALIDATE_EMAIL) ? $this->data['user_mail'] : null);
	}
	
	public function emailPreview(){
		if (filter_var($this->email(), FILTER_VALIDATE_EMAIL)) {
			$email=explode('@', $this->email());
			switch (strlen($email[0])) {
				case 1:
					$email[0]='*';
				break;
				case 2:
					$email[0]=substr($email[0], 0 ,1).'*';
				break;
				default:
					$email[0]=str_pad(substr($email[0], 0 ,1), strlen($email[0])-1, '*').substr($email[0], -1);
				
			}
			return implode('@', $email);
		}
		return null;
	}
	public function platform(){
		return self::$platforms[$this->data['user_from']];
	}
	public function avatar(){
		return str_replace('{{user_id}}', $this->data['user_id'], self::$config->get('url_avatars'));
	}
	public function platformId(){
		if (file_exists(dirname(__FILE__).'/User/'.$this->platform().'.php')) {
			$func='RLDL\\User\\'.$this->platform();
			return $func::getPlatformId($this->id()); 
		}
		return $this->id();
	}
	
	public function get(){
		return array(
			'id'=>$this->id(),
			'first_name'=>$this->firstName(),
			'last_name'=>$this->lastName(),
			'name'=>$this->name(),
			'gender'=>$this->gender(),
			//'platform'=>$this->platform(),
			//'locale'=>$this->locale(),
			//'email'=>$this->email(),
			'avatar'=>$this->avatar()
		);
	}
	
	public function permissions() {
		return array(
			'publish'=>false,
			'fb_page_publish'=>false
		);
	}
	
	public function delete() {
		$auth=Auth::getInstance();
		if ($auth->isSystem() || ($auth->isUser() && $auth->userId()==$this->id())) {
			if (self::$sql->UpdateRow('[User]Users', array(
				'user_name'=>\MySQL::SQLValue(null),
				'user_mail'=>\MySQL::SQLValue(null),
				'user_first_name'=>\MySQL::SQLValue(null),
				'user_last_name'=>\MySQL::SQLValue(null),
				'user_gender'=>\MySQL::SQLValue(null),
				'user_locale'=>\MySQL::SQLValue(null),
				'user_avatar'=>\MySQL::SQLValue(null),
				'user_from'=>\MySQL::SQLValue(null)
			), array(
				'user_id'=>\MySQL::SQLValue($this->id(), 'int')
			))) {
				return true;
			}
		}
		return false;
	}
	
	public function getCodes($filter=array(),$start=0,$limit=1000) {
		if (!is_array($filter)) {
			$filter=array();
		}
		$filters=array('campaign_id', 'deal_id', 'variant_id');
		foreach ($filter as $key => $value) {
			if (in_array($key, $filters) && is_numeric($value)) {
				$filter[$key]=\MySQL::SQLValue($value,'int');
			}
			else {
				unset($filter[$key]);
			}
		}
		
		if (!is_numeric($start) || $start<0) {
			$start=0;
		}
		if (!is_numeric($limit) || $limit<1) {
			$limit=1;
		}
		
		$filter['user_id']=\MySQL::SQLValue($this->id(),'int');
		$codes=self::$sql->SelectArray('[User]Variants', $filter, array(
			'user_id', 'campaign_id', 'deal_id', 'variant_id', 'get_time', 'code_value'
		), 'user_deal_id', $start.', '.($limit+1));
		
		if (count($codes)>$limit) {
			array_pop($codes);
			$codes['next']=array($start+$limit, $limit);
		}
		
		return $codes;
	}
	
	public function getFollowedCampaigns($start=0,$limit=1000) {
		$items=array();
		foreach ($this->followedCampaigns($start,$limit) as $campaign) {
			if (is_array($campaign)) {
				$next=$campaign;
			}
			else {
				try {
					array_push($items, $campaign->get());
				}
				catch (\Exception $e) {
					$campaign->unfollow();
				}
			}
		}
		if (isset($next)) {
			$items['next']=$next;
		}
		return $items;
	}
	
	public function followedCampaigns($start=0,$limit=1000) {
		$items=array();
		
		if (!is_numeric($start) || $start<0) {
			$start=0;
		}
		if (!is_numeric($limit) || $limit<1) {
			$limit=1;
		}
		
		$campaigns=self::$sql->SelectArray('[Campaign]Followers', array('user_id'=>\MySQL::SQLValue($this->id(),'int')), array('campaign_id'), 'follow_time', $start.', '.($limit+1));
		
		foreach ($campaigns as $campaign) {
			array_push($items, Campaign::getItem($campaign['campaign_id']));
		}
		
		if (count($campaigns)>$limit) {
			array_pop($items);
			$items['next']=array($start+$limit, $limit);
		}
		return $items;
	}
	
	public function isFollowingCampaign($campaign_id){
		if (!is_numeric($campaign_id)) {
			throw new \InvalidArgumentException(
				'Wrong campaign id.'
			);
		}
		
		$campaign=self::$sql->SelectSingleRowArray('[Campaign]Followers', array(
			'user_id'=>\MySQL::SQLValue($this->id(),'int'),
			'campaign_id'=>\MySQL::SQLValue($campaign_id,'int')
		), array('campaign_id'));
		
		if (is_array($campaign) && $campaign['campaign_id']==$campaign_id) {
			return true;
		}
		
		return false;
	}
}
?>