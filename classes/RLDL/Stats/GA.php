<?php
namespace RLDL\Stats;

class GA {
	public static function getPageviews($day_start, $day_end, $aliases=array()) {
		$day_start=strtotime($day_start);
		$day_end=strtotime($day_end);
		
		if (!$day_start || !$day_end || !is_array($aliases)) {
			throw new \InvalidArgumentException(
				'Wrong params.'
			);
		}
		
		$sql=\MySQL::getInstance();
		
		$rows=$sql->QueryArray('SELECT pageview_date AS day, SUM(pageview_value) AS count FROM `[GA]Pageviews` WHERE pageview_date>='.\MySQL::SQLValue($day_start,'date').' AND pageview_date<='.\MySQL::SQLValue($day_end, 'date').' AND campaign_alias IN ("'.implode('", "', $aliases).'") GROUP BY pageview_date ORDER BY pageview_date ASC');
		
		if (!is_array($rows)) {
			throw new \InvalidArgumentException(
				$sql->Error(),
				500
			);
		}
		
		$i=$day_start;
		$days=array();
		while ($i<=$day_end) {
			$days[date('Y-m-d', $i)]='0';
			$i=$i+86400;
		}
		
		foreach ($rows as $line) {
			$days[$line['day']]=$line['count'];
		}
		
		return $days;
		
	}
}
