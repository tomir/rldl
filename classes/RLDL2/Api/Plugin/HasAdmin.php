<?php

namespace RLDL2\Api\Plugin;

class HasAdmin implements \RLDL2\Db\FiltrPluginInterface {

	public function processSelect($value = null, \Zend_Db_Select $select = null, \RLDL2\Db\Model $model = null) {
		if ($value == true) {
			
			
			$select->where('x.id IN (?)', array());
		}
	}

}
