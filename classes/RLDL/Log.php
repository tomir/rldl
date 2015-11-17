<?php
namespace RLDL;

class Log {
	private $filters;
	private $sql;
	private $auth;
	
	private $allowed_filters=array('client_id', 'campaign_id', 'deal_id', 'user_id');
	private $sub_groups=array(
		'client_id'=>array('client_id'),
		'campaign_id'=>array('campaign_id', 'client_id'),
		'deal_id'=>array('deal_id', 'campaign_id', 'client_id'),
		'user_id'=>array('user_id')
	);
	
	public static function add($string, $data=array()) {
		if (strlen($string)<1 || !is_array($data)) {
			throw new \InvalidArgumentException(
				'Wrong parametrs.'
			);
		}
		
		$data['mobile']=(new Detect\Mobile)->isMobile();
		
		if (\MySQL::getInstance()->InsertRow('[API]Log',array(
			'log_string'=>\MySQL::SQLValue($string),
			'log_info'=>\MySQL::SQLValue(json_encode($data)),
			'log_ip'=>\MySQL::SQLValue(Route::getInstance()->getIP())
		))) {
			return true;
		}
		else {
			throw new \Exception(
				'DB error.'
			);
		}
	}
	
	public function __construct($filters, $get_all=false) {
		$this->auth=Auth::getInstance();
		$this->sql=\MySQL::getInstance();
		
		if (!is_array($filters)) {
			throw new \InvalidArgumentException(
				'Wrong parametrs.'
			);
		}
		
		if (!$get_all && !$this->auth->isAuthorized()) {
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
				if ($this->auth->getPermission($client_id)<1) {
					throw new \Exception(
						'Not authorized for this operation.',
						403
					);
				}
			}
		}
		else if (!$get_all && !$this->auth->isSystem()) {
			if ($this->auth->userId()!=null) {
				$filters['user_id']=array($this->auth->userId());
			}
			else {
				throw new \Exception(
					'Not authorized for this operation.',
					403
				);
			}
		}
		
		$this->filters=$filters;
	}

	public function itemsList($start=null, $limit=100, $group_by=null, $remove_duplicates=false) {
		if (!is_numeric($start) || $start<0) {
			$start=null;
		}
		if (!is_numeric($limit) || $limit==0) {
			$limit=20;
		}
		
		$order=($limit>0 ? '' : '-').'log_id';
		
		$where=$this->filters();
		
		if ($start!=null) {
			$where[]='log_id'.($limit>0 ? '>=' : '<=').$start;
		}
		
		$items=$this->sql->SelectArray('[API]Log', $where, null, $order, (abs($limit)+1));
		
		if ($items===false) {
			throw new \Exception(
				'DB error.'
			);
		}
		
		if (count($items)>abs($limit)) {
			$last_item=array_pop($items);
		}
		
		$prev_item=null;
		
		foreach ($items as $id=>$item) {
			$items[$id]['log_info']=json_decode($items[$id]['log_info'], true);
			if (count($items)>1) {
				if ($remove_duplicates && $prev_item!=null && $prev_item['log_string']==$items[$id]['log_string'] && http_build_query($prev_item['log_info'])==http_build_query($items[$id]['log_info'])) {
					if ($limit>0) {
						$prev_item=$items[$id];
					}
					unset($items[$id]);
				}
				else {
					$prev_item=$items[$id];
				}
			}
		}
		
		if ($group_by!=null && array_key_exists($group_by, $this->sub_groups)) {
			$groups_id=array();
			$groups_items=array();
			
			foreach ($items as $id=>$it) {
				$group_id=null;
				
				foreach ($this->sub_groups[$group_by] as $sub_group) {
					if (array_key_exists($sub_group, $it['log_info'])) {
						$group_id=$sub_group.$it['log_info'][$sub_group];
						break;
					}
					else if (array_key_exists(str_replace('_id', '', $sub_group), $it['log_info'])) {
						$group_id=$sub_group.$it['log_info'][str_replace('_id', '', $sub_group)][$sub_group];
						break;
					}
				}
				if (is_null($group_id)) {
					$group_id=$it['log_id'];
				}
				
				$group_id.='#'.md5($it['log_string']).'#'.(array_key_exists('mobile', $it['log_info']) ? $it['log_info']['mobile'] : 'false');
				
				if (!in_array($group_id, $groups_id)) {
					$groups_id[]=$group_id;
				}
				
				$pos=array_search($group_id, $groups_id);
				
				if (!array_key_exists($pos, $groups_items)) {
					$groups_items[$pos]=array();
				}
				
				if ($limit>0) {
					array_unshift($groups_items[$pos], $it);
				}
				else {
					$groups_items[$pos][]=$it;
				}
			}
			
			if ($remove_duplicates) {
				foreach ($groups_items as &$group_items) {
					if (count($group_items)>1) {
						$prev_item=null;
						
						foreach ($group_items as $id=>$item) {
							if ($prev_item!=null && $prev_item['log_string']==$group_items[$id]['log_string'] && http_build_query($prev_item['log_info'])==http_build_query($group_items[$id]['log_info'])) {
								if ($limit>0) {
									$prev_item=$group_items[$id];
								}
								unset($group_items[$id]);
							}
							else {
								$prev_item=$group_items[$id];
							}
						}
					}
					$group_items=array_values($group_items);
				}
			}
			
			$items=$groups_items;
		}
		
		return array_merge($items, (isset($last_item) ? array('next'=>array($last_item['log_id'], $limit)) : array()));
	}
	
	private function filters() {
		$filters=array();
		foreach ($this->filters as $filter => $filter_values) {
			if (in_array($filter, $this->allowed_filters)) {
				$values=array();
				foreach ($filter_values as $value) {
					$values[]='log_info LIKE \'%"'.$filter.'":"'.$value.'"%\'';
				}
				$filters[]='('.implode(' OR ', $values).')';
			}
		}
		return $filters;
	}
}
?>