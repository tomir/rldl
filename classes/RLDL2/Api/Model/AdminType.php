<?php

namespace RLDL2\Api\Model;


/**
 * Description of Invite
 *
 * @author tomi_weber
 */
class AdminType extends \RLDL2\Db\Model {
	
	public function getDbTable() {
		return '[API]admin_type';
	}

	public function setPrimaryColumn() {
		return 'id_admin_type';
	}
	
	

}