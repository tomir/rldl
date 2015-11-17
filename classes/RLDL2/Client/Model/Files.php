<?php

namespace RLDL2\Client\Model;


/**
 * Description of Invite
 *
 * @author tomi_weber
 */
class Files extends \RLDL2\Db\Model {
	
	public function getDbTable() {
		return '[Client]Files';
	}

	public function setPrimaryColumn() {
		return 'file_id';
	}

}