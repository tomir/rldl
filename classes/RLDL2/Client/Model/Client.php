<?php

namespace RLDL2\Client\Model;

/**
 * Description of Client
 *
 * @author tomi_weber
 */
class Client extends \RLDL2\Db\Model {
	
	public function getDbTable() {
		return '[Client]Clients';
	}

	public function setPrimaryColumn() {
		return 'client_id';
	}

}
