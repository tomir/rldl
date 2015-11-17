<?php

namespace RLDL2\Client\Model;

/**
 * Description of Invite
 *
 * @author tomi_weber
 */
class Settings extends \RLDL2\Db\Model {
	
	public function getDbTable() {
		return '[Client]Settings';
	}

	public function setPrimaryColumn() {
		return 'settings_id';
	}

}