<?php
namespace RLDL\Campaign;

class Facebook extends \RLDL\Campaign {
	public static function getItemByPage($page=null) {
		if (strlen($page)<1) {
			throw new \InvalidArgumentException(
				'Wrong facebook page.',
				1050
			);
		}
		
		$sql=\MySQL::getInstance();
		
		$data=$sql->SelectSingleRowArray(
			'[Campaign]FB',
			array('fb_page_id'=>\MySQL::SQLValue($page)),
			array('campaign_id')
		);
		
		if (!is_array($data)) {
			throw new \InvalidArgumentException(
				'Facebook page is not assigned to any campaign.',
				1051
			);
		}
		
		return parent::getItem($data['campaign_id']);
	}
}
?>