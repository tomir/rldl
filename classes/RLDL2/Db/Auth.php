<?php

namespace RLDL2\Db;

/**
 * Description of Auth
 *
 * @author tomi_weber
 */
class Auth {

	protected $auth;
	protected $permission;

	public function __construct() {
		$this->auth = \RLDL\Auth::getInstance();
	}
	
	public function getPermission() {
		return $this->permission;
	}

	private function isAuthorized() {
		if (!$this->auth->isAuthorized()) {
			$this->id = null;

			throw new \Exception(
				'Unautorized.', 401
			);
		}

		$this->permission = $this->auth->getPermission($this->id);

		if ($this->permission < 1) {
			$this->id = null;

			throw new \Exception(
				'Not authorized for this client account.', 403
			);
		}
	}

}
