<?php
namespace RLDL;

class Stats {
	private static $allowed_filters=array('campaign_id', 'deal_id', 'user_id', 'variant_id');
	
	public static function bestAffiliations($what, $id) {
		if (!in_array($what,array('campaign_id', 'deal_id')) || !is_numeric($id) || $id<1) {
			throw new \InvalidArgumentException(
				'Wrong parametrs.'
			);
		}
		
		$tags=array();
		$affiliations=array();
		
		foreach (\MySQL::getInstance()->QueryArray("SELECT user_id, affiliation_id, affiliation_type, CONCAT(campaign_id, '-', deal_id, '-', variant_id) as tag FROM `[User]Variants` WHERE affiliation_id IS NOT NULL AND ".$what."=".$id." ORDER BY user_deal_id") as $line) {
			if (!array_key_exists($line['tag'], $tags)) {
				$tags[$line['tag']]=array();
			}
			$users=&$tags[$line['tag']];
			if ($line['affiliation_type']=='user') {
				if (!array_key_exists($line['affiliation_id'], $users)) {
					$users[$line['affiliation_id']]=new CountTree();
				}
			}
			if (!array_key_exists($line['user_id'], $users)) {
				$users[$line['user_id']]=new CountTree($users[$line['affiliation_id']]);
			}
			$users[$line['user_id']]->add();
			if ($line['affiliation_type']!='user') {
				if (!array_key_exists($line['affiliation_id'], $affiliations)) {
					$affiliations[$line['affiliation_id']]=array();
				}
				$affiliations[$line['affiliation_id']][]=$users[$line['user_id']];
			}
		}
		
		$affiliations_count=array();
		
		foreach ($affiliations as $id => $users) {
			if (!array_key_exists($id, $affiliations_count)) {
				$affiliations_count[$id]=0;
			}
			foreach ($users as $user) {
				$affiliations_count[$id]+=$user->count();
			}
		}
		
		arsort($affiliations_count, SORT_NUMERIC);
		
		return $affiliations_count;
	}
	
	public static function bestUsers($what, $id, $limit=5) {
		if (!in_array($what,array('campaign_id', 'deal_id')) || !is_numeric($id) || $id<1 || !is_numeric($limit) || $limit<1) {
			throw new \InvalidArgumentException(
				'Wrong parametrs.'
			);
		}
		
		$tags=array();
		
		foreach (\MySQL::getInstance()->QueryArray("SELECT user_id, affiliation_id, CONCAT(campaign_id, '-', deal_id, '-', variant_id) as tag FROM `[User]Variants` WHERE affiliation_id IS NOT NULL AND affiliation_type='user' AND ".$what."=".$id." ORDER BY user_deal_id") as $line) {
			if (!array_key_exists($line['tag'], $tags)) {
				$tags[$line['tag']]=array();
			}
			$users=&$tags[$line['tag']];
			if (!array_key_exists($line['affiliation_id'], $users)) {
				$users[$line['affiliation_id']]=new CountTree();
			}
			$users[$line['affiliation_id']]->add();
			if (!array_key_exists($line['user_id'], $users)) {
				$users[$line['user_id']]=new CountTree($users[$line['affiliation_id']]);
			}
			$users[$line['user_id']]->add();
		}
		
		$users_count=array();
		
		foreach ($tags as &$tag) {
			foreach ($tag as $id => $user) {
				if (!array_key_exists($id, $users_count)) {
					$users_count[$id]=0;
				}
				$users_count[$id]+=$user->count();
			}
		}
		
		arsort($users_count, SORT_NUMERIC);
		
		$i=0;
		$response=array();
		
		foreach ($users_count as $id=>$user_count) {
			if ($i>=$limit || $user_count<=1) {
				break;
			}
			$response[$id]=$user_count;
			$i++;
		}
		
		return $response;
	}
	
	public static function count($what, $ids, $days=null) {
		if (!in_array($what, self::$allowed_filters) || (!is_array($ids) && count($ids)<1)) {
			throw new \InvalidArgumentException(
				'Wrong parametrs.'
			);
		}
		
		$where=array();
		$response=array();
		
		foreach ($ids as $id) {
			$where[]="`".$what."`=".\MySQL::SQLValue($id, 'int');
			$response[$id]="0";
		}
		
		
		$lines=\MySQL::getInstance()->QueryArray("SELECT ".$what." AS id, count(*) AS count FROM `[User]Variants` WHERE (".implode(' OR ', $where).")".((is_numeric($days) && $days>=0) ? " AND DATE(get_time)>=DATE_SUB(CURRENT_DATE, INTERVAL ".$days." DAY)" : '')." GROUP BY ".$what);
		
		if (is_array($lines) && count($lines)>0) {
			foreach ($lines as $line) {
				$response[$line['id']]=$line['count'];
			}
		}
		
		return $response;
	}
	
	public static function bestDays($what, $ids) {
		if (!in_array($what, self::$allowed_filters) || (!is_array($ids) && count($ids)<1)) {
			throw new \InvalidArgumentException(
				'Wrong parametrs.'
			);
		}
		
		$where=array();
		$response=array();
		
		foreach ($ids as $id) {
			$where[]="`".$what."`=".\MySQL::SQLValue($id, 'int');
			$response[$id]=array();
		}
		
		
		$lines=\MySQL::getInstance()->QueryArray("SELECT ".$what." AS id, count(*) AS count, DATE(get_time) as `day` FROM `[User]Variants` WHERE ".implode(' OR ', $where)." GROUP BY ".$what.', `day` ORDER BY count DESC LIMIT '.count($ids)*30);
		
		foreach ($lines as $line) {
			$response[$line['id']][]=array(
				'day'=>(string)$line['day'],
				'count'=>$line['count']
			);
		}
		
		return $response;
	}
	
	public static function countInDays($what, $id, $day_start, $day_end) {
		if (!in_array($what, self::$allowed_filters) || $id<1) {
			throw new \InvalidArgumentException(
				'Wrong parametrs.'
			);
		}
		
		$where=array();
		$response=array();
		
		$day_start=strtotime($day_start);
		$day_end=strtotime($day_end);
		
		$i=$day_start;
		$days=array();
		while ($i<=$day_end) {
			$days[date('Y-m-d', $i)]='0';
			$i=$i+86400;
		}
		
		$lines=\MySQL::getInstance()->QueryArray("SELECT count(*) AS count, DATE(get_time) as `day` FROM `[User]Variants` WHERE DATE(get_time)>=".\MySQL::SQLValue($day_start,'date')." AND DATE(get_time)<=".\MySQL::SQLValue($day_end, 'date')." AND `".$what."`=".\MySQL::SQLValue($id, 'int')." GROUP BY ".$what.", `day` ORDER BY day ASC");
		
		foreach ($lines as $line) {
			$days[$line['day']]=$line['count'];
		}
		
		return $days;
	}
	
	public static function bestHours($what, $ids) {
		if (!in_array($what, self::$allowed_filters) || (!is_array($ids) && count($ids)<1)) {
			throw new \InvalidArgumentException(
				'Wrong parametrs.'
			);
		}
		
		$where=array();
		$response=array();
		
		foreach ($ids as $id) {
			$where[]="`".$what."`=".\MySQL::SQLValue($id, 'int');
			$response[$id]=array();
		}
		
		
		$lines=\MySQL::getInstance()->QueryArray("SELECT ".$what." AS id, count(*) AS count, HOUR(get_time) as `hour` FROM `[User]Variants` WHERE ".implode(' OR ', $where)." GROUP BY ".$what.', `hour` ORDER BY count DESC');
		
		
		foreach ($lines as $line) {
			$response[$line['id']][]=array(
				'hour'=>(string)$line['hour'],
				'count'=>(int)$line['count'],
				'compared_to_best'=>round($line['count']/($lines[0]['count']>0 ? $lines[0]['count'] : 1),2)
			);
		}
		
		return $response;
	}
	
	public static function fromMobile($what, $ids) {
		if (!in_array($what, self::$allowed_filters) || (!is_array($ids) && count($ids)<1)) {
			throw new \InvalidArgumentException(
				'Wrong parametrs.'
			);
		}
		
		$response=array();
		
		foreach ($ids as $id) {
			$line=\MySQL::getInstance()->QuerySingleRowArray("SELECT count(*) as `count_mobile`, (SELECT count(*) FROM `[API]Log` WHERE (log_string='Get offer.' OR log_string='Get and share offer.') AND log_info LIKE '%\"".$what."\":\"".$id."\"%') as count_all FROM `[API]Log` WHERE (log_string='Get offer.' OR log_string='Get and share offer.') AND log_info LIKE '%\"".$what."\":\"".$id."\"%' AND log_info LIKE '%\"mobile\":true%'");
			if ($line===false || !array_key_exists('count_all', $line) || $line['count_all']==0) {
				$response[$id]=0;
			}
			else {
				$response[$id]=round($line['count_mobile']/$line['count_all'],2);
			}
		}
		
		return $response;
	}
	
	public static function gender($what, $ids) {
		if (!in_array($what, self::$allowed_filters) || (!is_array($ids) && count($ids)<1)) {
			throw new \InvalidArgumentException(
				'Wrong parametrs.'
			);
		}
		
		$response=array();
		
		foreach ($ids as $id) {
			$count=array(
				'men'=>0,
				'women'=>0,
				'other'=>0,
				'all'=>0
			);
			
			foreach (\MySQL::getInstance()->QueryArray("SELECT user_gender AS gender, count(*) AS `count` FROM `[User]Users` WHERE user_id IN (SELECT DISTINCT(user_id) FROM `[User]Variants` WHERE ".$what."='".$id."') GROUP BY user_gender") as $line) {
				switch ($line['gender']) {
					case 'm':
						$count['men']+=$line['count'];
					break;
					case 'f':
						$count['women']+=$line['count'];
					break;
					default:
						$count['other']+=$line['count'];
				}
				$count['all']+=$line['count'];
			}
			
			if ($count['all']>0) {
				$response[$id]=array(
					'men'=>round($count['men']/$count['all'],2),
					'women'=>round($count['women']/$count['all'],2)
				);
				$response[$id]['other']=round(1-$response[$id]['men']-$response[$id]['women'],2);
				
				if ($response[$id]['other']<0) {
					$response[$id]['other']=0;
					$response[$id]['women']=round(1-$response[$id]['men'],2);
				}
			}
			else {
				$response[$id]=array(
					'men'=>0,
					'women'=>0,
					'other'=>0
				);
			}
		}
		
		return $response;
	}
	
	public static function numberOfDownloads($what, $ids) {
		if (!in_array($what, self::$allowed_filters) || (!is_array($ids) && count($ids)<1)) {
			throw new \InvalidArgumentException(
				'Wrong parametrs.'
			);
		}
		
		$response=array();
		
		foreach ($ids as $id) {
			$response[$id]=array();
			foreach (\MySQL::getInstance()->QueryArray("SELECT number, count(*) AS `count` FROM (SELECT count(*) as `number` FROM `[User]Variants` WHERE ".$what."='".$id."' GROUP BY user_id) AS t1 GROUP BY number") as $line) {
				$response[$id][$line['number']]=(int)$line['count'];
			}
		}
		
		return $response;
	}
}

class CountTree {
	private $parent=null;
	private $count;
	public function __construct(&$parent=null) {
		$this->count=0;
		$this->parent=&$parent;
	}
	public function count() {
		return $this->count;
	}
	public function add() {
		$this->count++;
		if (is_object($this->parent)) {
			$this->parent->add();
		}
	}
}
?>