<?php

namespace RLDL2\Db;

/**
 * @package \RLDL2\Db
 * @author Tomasz Cisowski
 */
interface FiltrPluginInterface
{
	/**
	 * @param mixed|null			$value
	 * @param \Zend_Db_Select|null	$select
	 * @param \RLDL2\Db\Model|null	$model
	 */
	public function processSelect($value = null, \Zend_Db_Select $select = null, \RLDL2\Db\Model $model = null);
}
