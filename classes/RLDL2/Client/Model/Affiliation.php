<?php

namespace RLDL2\Client\Model;

/**
 * Description of Invite
 *
 * @author tomi_weber
 */
class Affiliations extends \RLDL2\Db\Model {
	
	public function getDbTable() {
		return '[Client]Affiliations';
	}

	public function setPrimaryColumn() {
		return 'affiliation_id';
	}

}