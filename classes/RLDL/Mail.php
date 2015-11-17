<?php
namespace RLDL;

class Mail {
	protected $sql=null;
	protected $auth;
	protected $config;
	protected $id;
	
	protected static $cache=array();
	
	private static $allowed_filters=array('client_id', 'campaign_id', 'deal_id', 'user_id', 'mail_type');
	
	public static function newItem($address=null, $bodyHTML=null, $bodyTXT=null) {
		return new Mail\NewItem($address, $bodyHTML, $bodyTXT);
	}
	
	public static function getItem($id) {
		return new self($id);
	}
	
	public function __construct($id) {
		if (!is_numeric($id) || $id<1) {
			throw new \InvalidArgumentException(
				'Wrong mail id.'
			);
		}
		
		$this->sql=\MySQL::getInstance();
		$this->auth=Auth::getInstance();
		$this->config=Config::getInstance();
		
		$this->id=(string)$id;
		
	}
	
	public function id() {
		return $this->id;
	}
	
	public function get() {
		return (array_key_exists($this->id(), self::$cache) ? self::$cache[$this->id] : null);
	}
	
	public static function sentItemCount($filters, $days=null) {
		$auth=Auth::getInstance();
		
		if (!is_array($filters)) {
			throw new \InvalidArgumentException(
				'Wrong parametrs.'
			);
		}
		
		if (!$auth->isAuthorized() && !$auth->isSystem()) {
			throw new \Exception(
				'Unautorized.',
				401
			);
		}
		
		if (array_key_exists('client_id', $filters) && !$auth->isSystem()) {
			foreach ($filters['client_id'] as &$client_id) {
				if ($auth->getPermission($client_id)<1) {
					throw new \Exception(
						'Not authorized for this operation.',
						403
					);
				}
			}
		}
		
		$where=self::filters($filters);
		$where[]='mail_sent_time IS NOT NULL';
		
		if (is_int($days) && $days>0) {
			$where[]="mail_sent_time>=DATE_SUB(CURRENT_DATE, INTERVAL ".$days." DAY)";
		}
		
		$sql=\MySQL::getInstance();
		
		$response=$sql->SelectArray('[Mail]Mails', $where, "COUNT(*) AS count");
		
		if (!is_array($response)) {
			throw new \Exception(
				$sql->Error(),
				500
			);
		}
		
		return (count($response)>0 ? (int)$response[0]['count'] : 0);
	}
	
	public static function itemsList($start=null, $limit=1000, $filters=array(), $only_new=false) {
		$auth=Auth::getInstance();
		
		if (!is_array($filters)) {
			throw new \InvalidArgumentException(
				'Wrong parametrs.'
			);
		}
		
		if (!$auth->isAuthorized()) {
			throw new \Exception(
				'Unautorized.',
				401
			);
		}
		
		foreach ($filters as &$filter) {
			if (is_numeric($filter)) {
				$filter=array($filter);
			}
			else if (is_array($filter)) {
				foreach ($filter as &$value) {
					if (!is_numeric($value)) {
						unset($value);
					}
				}
			}
			else {
				unset($filter);
			}
		}
		
		if (array_key_exists('client_id', $filters)) {
			foreach ($filters['client_id'] as &$client_id) {
				if ($auth->getPermission($client_id)<1) {
					throw new \Exception(
						'Not authorized for this operation.',
						403
					);
				}
			}
		}
		else if (!$auth->isSystem()) {
			if ($auth->userId()!=null) {
				$filters['user_id']=array($this->auth->userId());
			}
			else {
				throw new \Exception(
					'Not authorized for this operation.',
					403
				);
			}
		}
		
		if (!is_numeric($start) || $start<0) {
			$start=null;
		}
		if (!is_numeric($limit) || $limit==0) {
			$limit=20;
		}
		
		$order=($limit>0 ? '' : '-').'mail_create_time';
		
		$where=self::filters($filters);
		
		if ($start!=null) {
			$where[]='mail_id'.($limit>0 ? '>=' : '<=').$start;
		}
		if ($only_new) {
			$where[]='mail_sent_time IS NULL';
		}
		
		$sql=\MySQL::getInstance();
		
		$items=$sql->SelectArray('[Mail]Mails', $where, null, $order, (abs($limit)+1));
		
		if ($items===false) {
			throw new \Exception(
				'DB error.'
			);
		}
		
		foreach ($items as &$item) {
			$item['mail_info']=json_decode($item['mail_info'], true);
			$item['mail_data']=json_decode($item['mail_data'], true);
		}
		
		self::toCache($items);
		
		if (count($items)>abs($limit)) {
			$last_item=array_pop($items);
		}
		
		return array_merge($items, (isset($last_item) ? array('next'=>array($last_item['log_id'], $limit)) : array()));;
	}
	
	private static function filters($f) {
		$filters=array();
		foreach ($f as $filter => $filter_values) {
			if (in_array($filter, self::$allowed_filters)) {
				$values=array();
				if (!is_array($filter_values)) {
					$filter_values=array($filter_values);
				}
				foreach ($filter_values as $value) {
					$values[]='mail_info LIKE \'%"'.$filter.'":"'.$value.'"%\'';
				}
				$filters[]='('.implode(' OR ', $values).')';
			}
		}
		return $filters;
	}
	
	private static function toCache($items) {
		foreach ($items as &$item) {
			self::$cache[$item['mail_id']]=$item;
		}
	}
}
?>