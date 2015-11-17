<?php

namespace RLDL2\User\Model;


/**
 * Description of Invite
 *
 * @author tomi_weber
 */
class User extends \RLDL2\Db\Model {
	
	public function getDbTable() {
		return '[User]Users';
	}

	public function setPrimaryColumn() {
		return 'user_id';
	}

}