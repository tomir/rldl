<?php

namespace RLDL2\Client\Service;

/**
 * Description of Invite
 *
 * @author tomi_weber
 */
class InviteManagement {

	public function createInvite($invite_type = 0, $invite_days = 0) {

		$objInvite = new \RLDL2\Client\Model\Invite();
		if ($objInvite->getPermission() < 2) {
			throw new \Exception(
			'Not authorized for this client account.', 403
			);
		}

		$invite = array();

		if (is_numeric($invite_type) && $invite_type >= 1 && $invite_type <= 2) {
			$invite['user_type'] = $invite_type;
		}
		if (is_numeric($invite_days) && $invite_days >= 1 && $invite_days <= 365) {
			$invite['valid_days'] = $invite_days;
		}

		$invite = array_merge(array(
			'user_type' => \Config::getVar('invite_type'),
			'valid_days' => \Config::getVar('invite_days')
				), $invite);

		if ($invite['user_type'] != 2) {
			$invite['user_type'] = 1;
		}

		if (!is_numeric($invite['valid_days'])) {
			$invite['valid_days'] = \Config::getVar('invite_days');
		} else if ($invite['valid_days'] < 1) {
			$invite['valid_days'] = 1;
		} else if ($invite['valid_days'] > \Config::getVar('invite_days_max')) {
			$invite['valid_days'] = \Config::getVar('invite_days_max');
		} else {
			$invite['valid_days'] = round($invite['valid_days']);
		}

		$inviteInsert = array();
		$inviteInsert = array(
			'client_id' => \MySQL::SQLValue($this->id(), 'int'),
			'user_id' => \MySQL::SQLValue($this->auth->userId(), 'int'),
			'invite_user_type' => \MySQL::SQLValue($invite['user_type'], 'int'),
			'invite_valid_to' => 'NOW() + INTERVAL ' . $invite['valid_days'] . ' DAY',
			'invite_code' => 'MD5(NOW()+RAND())'
		);

		$res = $objInvite->insert($inviteInsert);

		return $objInvite->load($res);
	}

}
