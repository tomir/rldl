<?php

namespace RLDL2\Api\Service;

use RLDL2\Api\Model\AdminType;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of AdminTypeManagement
 *
 * @author tomi_weber
 */
class AdminTypeManagement {

	protected $_filtry = array(
		'nazwa_not_empty' => 'nazwa!=""'
	);

	public function init() {

		$this->addPlugin(new \RLDL2\Api\Plugin\HasAdmin(), 'has_admin');
	}

	public static function getTypeOfAdmin($adminId) {

		$objAdminType = new AdminType();
		return $objAdminType->getAll(array(
					'has_admin' => $adminId
		)[0]['type']);
	}

}
