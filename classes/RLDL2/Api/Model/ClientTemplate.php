<?php

namespace RLDL2\Api\Model;


/**
 * Description of Invite
 *
 * @author tomi_weber
 */
class ClientTemplate extends \RLDL2\Db\Model {
	
	public function getDbTable() {
		return '[API]Client_templates';
	}

	public function setPrimaryColumn() {
		return null;
	}

}