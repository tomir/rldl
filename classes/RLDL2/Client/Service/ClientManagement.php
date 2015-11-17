<?php

namespace RLDL2\Client\Service;
use RLDL2\Client\Model\Admin,
	RLDL2\Api\Service\AdminTypeManagement;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of ClientManagement
 *
 * @author tomi_weber
 */
class ClientManagement {
	
	public function assignClient($data) {
		
		$objAdmin = new Admin();
		
		$adminType = AdminTypeManagement::getTypOfAdmin();
		
		$objAdmin->insert(array(
			'client_id' => $data['client_id'],
			'user_id' => $data['user_id'],
			'user_type' => $adminType
		));
	}
}
