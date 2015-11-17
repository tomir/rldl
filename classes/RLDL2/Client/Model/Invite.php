<?php

namespace RLDL2\Client\Model;


/**
 * Description of Invite
 *
 * @author tomi_weber
 */
class Invite extends \RLDL2\Db\Model {
	
	public function getDbTable() {
		return '[Client]Invite';
	}

	public function setPrimaryColumn() {
		return 'invite_id';
	}
	
	public function processGetOneRecord($record) {
		
		$record['invite_code'] = str_replace('=', '', base64_encode($record['invite_id'].'/'.$record['client_id'].'/'.$record['user_id'].'/'.$record['invite_code']));
		$record['invite_valid_to']  = $record['invite_valid_to'];
		return $record;
	}

}