<?php

namespace RLDL2\Client\Model;


/**
 * Description of Invite
 *
 * @author tomi_weber
 */
class Admin extends \RLDL2\Db\Model {
	
	public function getDbTable() {
		return '[Client]Admins';
	}

	public function setPrimaryColumn() {
		return null;
	}

}