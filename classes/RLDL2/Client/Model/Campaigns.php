<?php

namespace RLDL2\Client\Model;


/**
 * Description of Invite
 *
 * @author tomi_weber
 */
class Campaigns extends \RLDL2\Db\Model {
	
	public function getDbTable() {
		return '[Client]Campaigns';
	}

	public function setPrimaryColumn() {
		return 'campaign_id';
	}

}