<?php

namespace RLDL2\Client\Model;


/**
 * Description of Invite
 *
 * @author tomi_weber
 */
class Payments extends \RLDL2\Db\Model {
	
	public function getDbTable() {
		return '[Client]Payments';
	}

	public function setPrimaryColumn() {
		return 'payment_id';
	}

}