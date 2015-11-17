<?php
namespace RLDL;

class Client {
	protected $sql;
	protected $auth;
	protected $id;
	protected $permission;
	protected $settings;
	protected $active_campaigns=0;
	protected $campaigns;
	protected $active=null;
	protected $locked=false;
	public $default_content=array();
	
	protected static $items;
	
	private $cache=null;
	
	public static function getItem($id, $invite=null) {
		if ($invite!=null) {
			$id=self::setPermissionsByInvite($invite);
		}
		
		if (self::$items==false) {
			self::$items=array();
		}
		if (!array_key_exists($id, self::$items)) {
			self::$items[$id]=new self($id);
		}
		return self::$items[$id];
	}
	
	public static function getItemByInvite($invite) {
		return self::getItem(self::setPermissionsByInvite($invite));
	}
	
	public static function checkInviteIsValid($invite_code_h) {
		$sql=\MySQL::getInstance();
		
		if (strlen($invite_code_h)<1) {
			throw new \Exception(
				'Wrong invite code.',
				1020
			);
		}
		
		$invite=explode('/', base64_decode($invite_code_h));
		
		if (count($invite)!=4) {
			throw new \Exception(
				'Wrong invite code.',
				1020
			);
		}
		
		$invite_id=$invite[0];
		$client_id=$invite[1];
		$user_id=$invite[2];
		$invite_code=$invite[3];
		
		$invite_data=$sql->SelectSingleRowArray('[Client]Invites', array(
			'invite_id'=>\MySQL::SQLValue($invite_id,'int'),
			'client_id'=>\MySQL::SQLValue($client_id,'int'),
			'user_id'=>\MySQL::SQLValue($user_id,'int'),
			'invite_code'=>\MySQL::SQLValue($invite_code),
			'invite_user_id IS NULL',
			'invite_valid_to>=NOW()'
		));
		
		if (!is_array($invite_data)) {
			throw new \Exception(
				'Wrong invite code.',
				1020
			);
		}
		
		return array(
			'invite_code'=>$invite_code,
			'invite_valid_to'=>$invite['invite_valid_to']
		);
	}
	
	public static function setPermissionsByInvite($invite_code_h) {
		$auth=Auth::getInstance();
		
		if (strlen($invite_code_h)<1) {
			throw new \Exception(
				'Wrong invite code.',
				1020
			);
		}
		
		$invite=explode('/', base64_decode($invite_code_h));
		
		if (count($invite)!=4) {
			throw new \Exception(
				'Wrong invite code.',
				1020
			);
		}
		
		if (!$auth->isAuthorized()) {
			throw new \Exception(
				'Unautorized.',
				401
			);
		}
		
		$invite_id=$invite[0];
		$client_id=$invite[1];
		$user_id=$invite[2];
		$invite_code=$invite[3];
		
		
		if ($auth->getPermission($client_id)>=2) {
			return $client_id;
		}
		
		$sql=\MySQL::getInstance();
		
		$invite_data=$sql->SelectSingleRowArray('[Client]Invites', array(
			'invite_id'=>\MySQL::SQLValue($invite_id,'int'),
			'client_id'=>\MySQL::SQLValue($client_id,'int'),
			'user_id'=>\MySQL::SQLValue($user_id,'int'),
			'invite_code'=>\MySQL::SQLValue($invite_code),
			'invite_user_id IS NULL',
			'invite_valid_to>=NOW()'
		));
		
		if (!is_array($invite_data)) {
			throw new \Exception(
				'Wrong invite code.',
				1020
			);
		}
		
		if (!in_array($user_id, Config::getVar('admins'))) {
			$invite_user=$sql->SelectSingleRowArray('[Clients]Permissions', array(
				'user_id'=>\MySQL::SQLValue($user_id,'int')
			));
			
			if (!is_array($invite_user) || $invite_user['user_type']<2) {
				throw new \Exception(
					'Wrong invite code.',
					1020
				);
			}
		}
		
		$sql->TransactionBegin();
		
		if ($sql->UpdateRow('[Client]Invites', array(
			'invite_user_id'=>\MySQL::SQLValue($auth->userId(), 'int')
		), array(
			'invite_id'=>\MySQL::SQLValue($invite_id,'int'),
			'client_id'=>\MySQL::SQLValue($client_id,'int'),
			'user_id'=>\MySQL::SQLValue($user_id,'int'),
			'invite_code'=>\MySQL::SQLValue($invite_code),
			'invite_user_id IS NULL',
			'invite_valid_to>=NOW()'
		))===false || $sql->AutoInsertUpdate('[Client]Admins', array(
			'client_id'=>\MySQL::SQLValue($client_id, 'int'),
			'user_id'=>\MySQL::SQLValue($auth->userId(), 'int'),
			'user_type'=>\MySQL::SQLValue($invite_data['invite_user_type'], 'int')
		), array(
			'client_id'=>\MySQL::SQLValue($client_id, 'int'),
			'user_id'=>\MySQL::SQLValue($auth->userId(), 'int')
		))===false) {
			$sql->TransactionRollback();
			throw new \InvalidArgumentException(
				$sql->Error(),
				500
			);
		}
		
		$sql->TransactionEnd();
		
		$auth->getUserPermisions();
		
		Log::add('Accept invitation.', array(
			'client_id'=>$client_id,
			'user_id'=>$auth->userId()
		));
		
		return $client_id;
	}
	
	public function __construct($id=null) {
		if (!is_numeric($id) || $id<1) {
			throw new \InvalidArgumentException(
				'Wrong client id.'
			);
		}
		
		$this->sql=\MySQL::getInstance();
		
		$this->id=(string)$id;
		
		$this->auth=Auth::getInstance();
	}
	
	public function id() {
		return $this->id;
	}
	
	private function isAuthorized() {
		if (!$this->auth->isAuthorized()) {
			$this->id=null;
			
			throw new \Exception(
				'Unautorized.',
				401
			);
		}
		
		$this->permission=$this->auth->getPermission($this->id);
		
		if ($this->permission<1) {
			$this->id=null;
			
			throw new \Exception(
				'Not authorized for this client account.',
				403
			);
		}
	}
	
	public function get() {
		$this->isAuthorized();
		
		if ($this->cache!=null) {
			return $this->cache;
		}
		
		if (($data=$this->sql->SelectSingleRowArray('[Client]Clients',array('client_id'=>$this->id)))!==false) {
			$data['permission']=$this->permission;
			return $data;
		}
		else {
			throw new \Exception(
				'Client account not exists.',
				404
			);
		}
	}
	
	public function name(){
		return $this->get()['client_name'];
	}
	
	public function getCampaigns() {
		$this->isAuthorized();
		
		if (is_null($this->campaigns)) {
			$this->campaigns=array();
			foreach ($this->campaigns() as $campaign) {
				$campaign_data=$campaign->get();
				array_push($this->campaigns, $campaign_data);
				if ($campaign_data['campaign_active']) {
					$this->active_campaigns++;
				}
			}
		}
		return $this->campaigns;
	}
	
	public function campaigns() {
		$this->isAuthorized();
		return Campaign::getItemsByClient($this->id());
	}
	
	public function updateName($name) {
		$this->isAuthorized();
		if ($this->permission<2) {
			throw new \Exception(
				'Not authorized for this operation.',
				403
			);
		}
		
		if (!is_string($name) || strlen($name)<1) {
			throw new \InvalidArgumentException(
				'Wrong client name.',
				1011
			);
		}
		
		if (!$this->sql->UpdateRow('[Client]Clients', array('client_name'=>\MySQL::SQLValue($name)), array('client_id'=>\MySQL::SQLValue($this->id(),'int')))) {
			throw new \Exception(
				$sql->Error(),
				500
			);
		}
		
		Log::add('Change client account name.', array(
			'client_id'=>$this->id(),
			'user_id'=>$this->auth->userId()
		));
		
		$this->cache=null;
		
		return true;
	}
	
	public function createInvite($invite_type=0, $invite_days=0) {
		$this->isAuthorized();
		if ($this->permission<2) {
			throw new \Exception(
				'Not authorized for this client account.',
				403
			);
		}
		
		$invite=array();
		
		if (is_numeric($invite_type) && $invite_type>=1 && $invite_type<=2) {
			$invite['user_type']=$invite_type;
		}
		if (is_numeric($invite_days) && $invite_days>=1 && $invite_days<=365) {
			$invite['valid_days']=$invite_days;
		}
			
		$invite=array_merge(array(
			'user_type'=>Config::getVar('invite_type'),
			'valid_days'=>Config::getVar('invite_days')
		), $invite);
		
		if ($invite['user_type']!=2) {
			$invite['user_type']=1;
		}
		
		if (!is_numeric($invite['valid_days'])) {
			$invite['valid_days']=Config::getVar('invite_days');
		}
		else if ($invite['valid_days']<1) {
			$invite['valid_days']=1;
		}
		else if ($invite['valid_days']>Config::getVar('invite_days_max')) {
			$invite['valid_days']=Config::getVar('invite_days_max');
		}
		else {
			$invite['valid_days']=round($invite['valid_days']);
		}
		
		if ($this->sql->InsertRow('[Client]Invites', array(
			'client_id'=>\MySQL::SQLValue($this->id(), 'int'),
			'user_id'=>\MySQL::SQLValue($this->auth->userId(),'int'),
			'invite_user_type'=>\MySQL::SQLValue($invite['user_type'],'int'),
			'invite_valid_to'=>'NOW() + INTERVAL '.$invite['valid_days'].' DAY',
			'invite_code'=>'MD5(NOW()+RAND())'
		))===false) {
			throw new \Exception(
				$sql->Error(),
				500
			);
		}
	
		return $this->getInvite($this->sql->GetLastInsertID());
	}
	
	public function getInvite($id) {
		$this->isAuthorized();
		if ($this->permission<2) {
			throw new \Exception(
				'Not authorized for this client account.',
				403
			);
		}
			
		$invite=$this->sql->SelectSingleRowArray('[Client]Invites', array('invite_id'=>\MySQL::SQLValue($id,'int'), 'invite_user_id IS NULL', 'invite_valid_to>=NOW()'));
		
		if (!is_array($invite)) {
			throw new \Exception(
				'Wrong invite id.',
				1021
			);
		}
		
		return array(
			'invite_code'=>str_replace('=', '', base64_encode($invite['invite_id'].'/'.$invite['client_id'].'/'.$invite['user_id'].'/'.$invite['invite_code'])),
			'invite_valid_to'=>$invite['invite_valid_to']
		);
	}
	
	public function activationIsAllowed() {
		return ($this->maxCampaigns()-$this->activeCampaigns())>=1 ? true : false;
	}
	
	public function maxCampaigns() {
		$campaigns=$this->getSettings('campaigns');
		return is_null($campaigns) || !array_key_exists('max', $campaigns) ? 0 : (int)$campaigns['max'];
	}
	
	public function activeCampaigns() {
		$this->getCampaigns();
		return $this->active_campaigns;
	}
	
	public function mailIsAllowed() {
		return ($this->maxMails()-$this->mailLastSentCount())>=1 ? true : false;
	}
	
	public function maxMails() {
		$mails=$this->getSettings('max_mails');
		if ($mails==null) {
			$mails=Config::getVar('client_mail_campaign_count')*$this->maxCampaigns();
		}
		return $mails;
	}
	
	public function mailLastSentCount() {
		return Mail::sentItemCount(array(
			'client_id'=>$this->id(),
			'mail_type'=>'msg'
		), Config::getVar('client_mail_days'));
	}
	
	public function getSettings($name=null) {
		if (is_null($this->settings)) {
			foreach ($this->sql->SelectArray('[Client]Settings', array('client_id'=>$this->id)) as $setting) {
				$this->parseSetting($setting['entity_name'], $setting['entity_value']);
			}
		}
		
		if ($name==null) {
			return $this->settings;
		}
		
		if (is_string($name) && is_array($this->settings) && array_key_exists($name, $this->settings)) {
			return $this->settings[$name];
		}
		else if (is_array($name)) {
			$to_return=array();
			
			foreach ($name as $key) {
				if (is_string($key) && array_key_exists($key, $this->settings)) {
					$to_return[]=$this->settings[$name];
				}
			}
			
			return $to_return;
		}
		
		return null;
	}
	
	protected function parseSetting($name, $value) {
		if (preg_match('/\(([a-z]+)\)(.+)/i', $name, $m)) {
			$name=$m[2];
			if ($m[1]=='json') {
				$value=json_decode($value, true);
			}
			else {
				settype($value, $m[1]);
			}
		}
		
		$key=null;
		if (preg_match('/(.+)\[(.+)\]/i', $name, $n)) {
			$name=$n[1];
			$key=$n[2];
		}
		
		if (is_null($key)) {
			$this->settings[$name]=$value;
			return true;
		}
		else {
			if (!isset($this->settings[$name])) {
				$this->settings[$name]=array();
			}
			
			if (is_array($this->settings[$name])) {
				$this->settings[$name][$key]=$value;
				return true;
			}
		}
		
		throw new \InvalidArgumentException(
			'Wrong client settings.'
		);
		
	}
	
	public function getAdmins($start=null, $limit=100) {
		if (!$this->auth->isAuthorized()) {
			throw new \Exception(
				'Unautorized.',
				401
			);
		}
		
		$obj=$this->get();
		
		if ($this->auth->getPermission($obj['client_id'])<1) {
			throw new \Exception(
				'Not authorized for this operation.',
				403
			);
		}
		
		if (!is_numeric($start) || $start<0) {
			$start=null;
		}
		if (!is_numeric($limit) || $limit==0) {
			$limit=20;
		}
		
		$order=($limit>0 ? '' : '-').'user_id';
		
		$where=array('client_id'=>$this->id());
		
		if ($start!=null) {
			$where[]='user_id'.($limit>0 ? '>=' : '<=').$start;
		}
		
		$items=$this->sql->SelectArray('[Client]Admins', $where, null, $order, (abs($limit)+1));
		
		if ($items===false) {
			throw new \Exception(
				'DB error.'
			);
		}
		
		if (count($items)>abs($limit)) {
			$last_item=array_pop($items);
		}
		
		return array_merge($items, (isset($last_item) ? array('next'=>array($last_item['user_id'], $limit)) : array()));;
	}
	
	public function deleteAdmin($user_id) {
		if (!$this->auth->isAuthorized()) {
			throw new \Exception(
				'Unautorized.',
				401
			);
		}
		
		$obj=$this->get();
		
		if ($this->auth->getPermission($obj['client_id'])<2) {
			throw new \Exception(
				'Not authorized for this operation.',
				403
			);
		}
		
		return $this->sql->DeleteRows('[Client]Admins', array(
			'client_id'=>$this->id(),
			'user_id'=>\MySQL::SQLValue($user_id,'int')
		));
	}
	
	private function deleteAllAdmins() {
		if (!$this->auth->isSystem()) {
			throw new \Exception(
				'Only for system.'
			);
		}
		
		return $this->sql->DeleteRows('[Client]Admins', array(
			'client_id'=>$this->id()
		));
	}
	
	public function getAffiliations($start=null, $limit=100) {
		if (!$this->auth->isAuthorized()) {
			throw new \Exception(
				'Unautorized.',
				401
			);
		}
		
		$obj=$this->get();
		
		if ($this->auth->getPermission($obj['client_id'])<1) {
			throw new \Exception(
				'Not authorized for this operation.',
				403
			);
		}
		
		if (!is_numeric($start) || $start<0) {
			$start=null;
		}
		if (!is_numeric($limit) || $limit==0) {
			$limit=20;
		}
		
		$order=($limit>0 ? '' : '-').'affiliation_id';
		
		$where=array('client_id'=>$this->id());
		
		if ($start!=null) {
			$where[]='affiliation_id'.($limit>0 ? '>=' : '<=').$start;
		}
		
		$items=$this->sql->SelectArray('[Client]Affiliations', $where, null, $order, (abs($limit)+1));
		
		if ($items===false) {
			throw new \Exception(
				$this->sql->Error(),
				500
			);
		}
		
		if (count($items)>abs($limit)) {
			$last_item=array_pop($items);
		}
		
		return array_merge($items, (isset($last_item) ? array('next'=>array($last_item['affiliation_id'], $limit)) : array()));;
	}
	
	public function getAffiliation($id) {
		$affiliations=$this->getAffiliations($id, 1);
		if (isset($affiliations[0]) && $affiliations[0]['affiliation_id']==$id) {
			return $affiliations[0];
		}
		else {
			throw new \Exception(
				'Affiliation not found.',
				404
			);
		}
	}
	
	public function createAffiliation($name, $url=null) {
		if (!$this->auth->isAuthorized()) {
			throw new \Exception(
				'Unautorized.',
				401
			);
		}
		
		$obj=$this->get();
		
		if ($this->auth->getPermission($obj['client_id'])<2) {
			throw new \Exception(
				'Not authorized for this operation.',
				403
			);
		}
		
		if (!is_string($name) || strlen(trim($name))<1) {
			throw new \InvalidArgumentException(
				'Wrong affiliation name.'
			);
		}
		
		if ($this->sql->InsertRow('[Client]Affiliations', array(
			'client_id'=>\MySQL::SQLValue($this->id(),'int'),
			'affiliation_name'=>\MySQL::SQLValue($name),
			'affiliation_call'=>($url==null || !filter_var($url, FILTER_VALIDATE_URL) ? 'NULL' : \MySQL::SQLValue($url))
		))===false) {
			throw new \Exception(
				$sql->Error(),
				500
			);
		}
		
		return $this->sql->GetLastInsertID();
	}
	
	public function updateAffiliation($id, $name, $url=null) {
		if (!$this->auth->isAuthorized()) {
			throw new \Exception(
				'Unautorized.',
				401
			);
		}
		
		$obj=$this->get();
		
		if ($this->auth->getPermission($obj['client_id'])<2) {
			throw new \Exception(
				'Not authorized for this operation.',
				403
			);
		}
		
		if (!is_string($name) || strlen(trim($name))<1) {
			throw new \InvalidArgumentException(
				'Wrong affiliation name.'
			);
		}
		
		$to_update=array();
		
		if ($name!=null) {
			$to_update['affiliation_name']=\MySQL::SQLValue($name);
		}
		if ($url!==null && filter_var($url, FILTER_VALIDATE_URL)) {
			$to_update['affiliation_call']=\MySQL::SQLValue($name);
		}
		else if ($url=='') {
			$to_update['affiliation_call']='NULL';
		}
		
		if ($this->sql->UpdateRow('[Client]Affiliations', $to_update, array(
			'client_id'=>\MySQL::SQLValue($this->id(),'int'),
			'affiliation_id'=>\MySQL::SQLValue($id,'int')
		))===false) {
			throw new \Exception(
				$sql->Error(),
				500
			);
		}
		
		return true;
	}
	
	public function deleteAffiliation($affiliation_id) {
		if (!$this->auth->isAuthorized()) {
			throw new \Exception(
				'Unautorized.',
				401
			);
		}
		
		$obj=$this->get();
		
		if ($this->auth->getPermission($obj['client_id'])<2) {
			throw new \Exception(
				'Not authorized for this operation.',
				403
			);
		}
		
		return $this->sql->DeleteRows('[Client]Affiliations', array(
			'client_id'=>$this->id(),
			'affiliation_id'=>\MySQL::SQLValue($affiliation_id,'int')
		));
	}
	
	public function isActive() {
		if ($this->active!=null) {
			return $this->active;
		}
		
		$this->active=false;
		$this->locked=true;
		
		if (is_string($this->id) && strlen($this->id)>=1) {
			$payment=$this->getSettings('payment');
			if (is_array($payment) && array_key_exists('value', $payment) && (float)$payment['value']==0) {
				$this->active=true;
				$this->locked=false;
			}
			else if (is_array($payment) && array_key_exists('valid_to', $payment)) {
				$valid_to=strtotime($payment['valid_to']);
				$this->active=($valid_to>=time());
				$this->locked=($valid_to<strtotime('- '.Config::getVar('client_days_to_lock').' days'));
			}
		}
		
		return $this->active;
	}
	
	public function isLocked() {
		if ($this->isActive()) {
			return true;
		}
		else {
			return $this->locked;
		}
	}
	
	public static function create($name, $template_code=null) {
		$auth=Auth::getInstance();
		$sql=\MySQL::getInstance();
		
		if (!is_string($name) || strlen($name)<1) {
			throw new \Exception(
				'Wrong client account name.',
				1015
			);
		}
		
		if (!$auth->isAuthorized()) {
			throw new \Exception(
				'Unautorized.',
				401
			);
		}
		
		if (is_null($template_code) || $template_code=='') {
			$template_code=Config::getVar('client_default_template');
		}
		
		if (!is_string($template_code) || strlen($template_code)<3) {
			throw new \Exception(
				'Wrong template code.',
				1016
			);
		}
		
		$sql->TransactionBegin();
		
		$template_data=$sql->SelectSingleRowArray('[API]Client_templates', array('template_code'=>\MySQL::SQLValue($template_code), '(template_limit>0 OR template_limit IS NULL)'));
		
		if (!is_array($template_data)) {
			$sql->TransactionRollback();
			
			throw new \Exception(
				'Template not exists.',
				1017
			);
		}
	
		$template=json_decode($template_data['template_json'], true);
		
		if (array_key_exists('allowed_users', $template)) {
			if (!in_array($auth->userId(), $template['allowed_users'])) {
				$sql->TransactionRollback();
				
				throw new \Exception(
					'Not authorized for this template.',
					403
				);
			}
		}
		
		if ($sql->InsertRow('[Client]Clients', array(
			'client_name'=>\MySQL::SQLValue($name),
			'user_id'=>\MySQL::SQLValue($auth->userId(), 'int')
		))===false) {
			$error=$sql->Error();
			$sql->TransactionRollback();
			
			
			throw new \Exception(
				$error,
				500
			);
		}
		
		$obj=self::getItem($sql->GetLastInsertID());
		
		$auth->getUserPermisions();
		
		if (array_key_exists('settings', $template)) {
			if (array_key_exists('payment', $template['settings'])) {
				if (array_key_exists('valid_to', $template['settings']['payment'])) {
					$template['settings']['payment']['valid_to']=date('Y-m-d H:i:s', strtotime($template['settings']['payment']['valid_to']));
				}
			}
			
			$obj->updateSettings($template['settings'], false);
		}
		
		if (array_key_exists('content', $template)) {
			if (!is_array($template['content'])) {
				$template['content']=array($template['content']);
			}
			
			$obj->default_content=$template['content'];
		}
		
		if ($template_data['template_limit']>0) {
			if ($sql->UpdateRow('[API]Client_templates', array(
				'template_limit'=>'GREATEST(0, template_limit-1)'
			), array(
				'template_id'=>\MySQL::SQLValue($template_data['template_id'],'int')
			))===false) {
				$error=$sql->Error();
				$sql->TransactionRollback();
				
				
				throw new \Exception(
					$error,
					500
				);
			}
		}
		
		$sql->TransactionEnd();
		
		return $obj;
	}
	
	public function updateSettings($settings, $merge=true) {
		$this->isAuthorized();
		
		if ($merge) {
			$settings=array_merge($this->getSettings(), $settings);
		}
		
		$this->sql->DeleteRows('[Client]Settings', array('client_id'=>\MySQL::SQLValue($this->id(), 'int')));
		
		foreach ($settings as $name=>$value) {
			if (is_array($value)) {
				$insert=array(
					'entity_name'=>\MySQL::SQLValue('(json)'.$name),
					'entity_value'=>\MySQL::SQLValue(json_encode($value))
				);
			}
			else if (is_string($value)) {
				$insert=array(
					'entity_name'=>\MySQL::SQLValue($name),
					'entity_value'=>\MySQL::SQLValue($value)
				);
			}
			else {
				$insert=array(
					'entity_name'=>\MySQL::SQLValue('('.gettype($value).')'.$name),
					'entity_value'=>\MySQL::SQLValue($value)
				);
			}
			$insert['client_id']=\MySQL::SQLValue($this->id(), 'int');
			
			if ($this->sql->InsertRow('[Client]Settings', $insert)===false) {
				throw new \Exception(
					$this->sql->Error(),
					500
				);
			}
		}
	}
	
	public function delete() {
		if (!$this->auth->isSystem()) {
			throw new \Exception(
				'Only for System.'
			);
		}
		
		$this->sql->TransactionBegin();
		
		
		$this->updateSettings(array(), false);
		
		$this->deleteAllAdmins();
		
		foreach ($this->campaigns() as $campaign) {
			$campaign->delete(false);
		}
		
		if (!$this->sql->UpdateRow('[Client]Clients', array('user_id'=>'NULL'), array('client_id'=>\MySQL::SQLValue($this->id(),'int')))) {
			$error=$this->sql->Error();
			
			$this->sql->TransactionRollback();
			throw new \Exception(
				$error,
				500
			);
		}
		
		$this->sql->TransactionEnd();
		return true;
	}
	
	public function createFirstPayment() {
	
	}
	
	public function doRepayment() {
	
	}
	
	/*public function updatePayment($pay_id) {
		if (!$this->auth->isSystem()) {
			throw new \Exception(
				'Not authorized for this operation.',
				403
			);
		}
		
		if (!is_string($pay_id) || strlen($pay_id)<1) {
			throw new \InvalidArgumentException(
				'Wrong payment id.'
			);
		}
		
		if (!$this->sql->UpdateRow('[Client]Settings', array('entity_value'=>\MySQL::SQLValue($name)), array('client_id'=>\MySQL::SQLValue($this->id(),'int')))) {
			self::$sql->TransactionRollback();
			throw new \Exception(
				$sql->Error(),
				500
			);
		}
	};
	
	public static function pendingPayments($max=100) {
		$auth=Auth::getInstance();
		
		if (!$auth->isSystem()) {
			throw new \Exception(
				'Not authorized for this operation.',
				403
			);
		}
		
		if (!is_numeric($max) || $max<1 || $max>1000) {
			$max=100;
		}
		
		$sql=\MySQL::getInstance();
		$config=Config::getInstance();
		
		$payment_def=array(
			'period'=>30,
			'automatic'=>0,
			'value'=>0,
			'currency'=>$config->get('default_currency'),
			'last'=>null,
			'id'=>null,
			'valid_to'=>null,
		);
		
		$list=$sql->QueryArray("SELECT * FROM `[Client]Settings` WHERE client_id IN (SELECT client_id FROM `[Client]Settings` WHERE entity_name='payment[valid_to]' AND entity_value<=NOW()) AND entity_name LIKE 'payment%'");
		
		if ($list===false) {
			throw new \Exception(
				$sql->Error(),
				500
			);
		}
		
		$clients=array();
		
		foreach ($list as $row) {
			(!is_set($clients[$row['client_id']])) {
				$clients[$row['client_id']]=array();
			}
			
			if (preg_match('/(.+)\[(.+)\]/i', $row['entity_name'], $n)) {
				$clients[$row['client_id']][$n[2]]=$row['entity_value'];
			}
		}
		
		foreach ($clients as &$client) {
			$client=array_merge($payment_def, $client);
		}
		
		return $clients;
	}*/
}
?>