<?php

namespace RLDL2\Client\Model;


/**
 * Description of Invite
 *
 * @author tomi_weber
 */
class Images extends \RLDL2\Db\Model {
	
	public function getDbTable() {
		return '[Client]Images';
	}

	public function setPrimaryColumn() {
		return 'image_id';
	}

}