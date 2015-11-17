<?php

namespace RLDL2\Db\Select;

/**
 * $package  \RLDL2\Db\Select
 * @author   Tomasz Cisowski
 */
class Builder
{
	/**
	 * @var \Zend_Db_Select
	 */
	protected $select;

	/**
	 * @var array
	 */
	protected $filters = array();

	/**
	 * @var \Enp\Db\FiltrPluginInterface[]
	 */
	protected $plugins = array();

	/**
	 * @var \RLDL2\Db\Model
	 */
	protected $model = null;

	/**
	 * @param \Zend_Db_Select $select
	 * @param array $filters
	 * @param \RLDL2\Db\FiltrPluginInterface[] $plugins
	 * @param \RLDL2\Db\Model
	 */
	public function __construct(\Zend_Db_Select $select, $filters = array(), $plugins = array(), $model = null)
	{
		$this->select	= $select;
		$this->filters  = $filters;
		$this->plugins  = $plugins;
		$this->model	= $model;
	}

	public function setFilter(array $filters)
	{
		foreach ($filters as $key => $val) {
			if (array_key_exists($key, $this->filters)) {

				// clear additional quotes, will be bind/escape in \Zend\Db
				if (!is_array($val) && !$val instanceof \Zend_Db_Expr) {

					$condition = $this->filters[$key];
					$pattern = '/(\'|\")?( )*(\%)?\?(\%)?( )*(\'|\")?/';

					$adapter = $this->select->getAdapter();
					$replacer = function($match) use ($val, $adapter) {
						if ($match[3] == '%') {
							$val = '%' . $val;
						}
						if ($match[4] == '%') {
							$val = $val . '%';
						}
						return $match[2] . $adapter->quote($val) . $match[5];
					};
					
					$this->filters[$key] = preg_replace_callback($pattern, $replacer, $condition);
				}

				$this->select->where($this->filters[$key], $val);

			// plugins
			} elseif (array_key_exists($key, $this->plugins)) {
				$plugin = $this->plugins[$key];
				/* @var $plugin FiltrPluginInterface */
				$plugin->processSelect($val, $this->select, $this->model);

			} else {
				$this->setSimpleFilter($key, $val);
			}
		}

		return $this->select;
	}

	/**
	 * One simple filter
	 *
	 * @param string $key
	 * @param string $val
	 */
	protected function setSimpleFilter($key, $val)
	{
		if (null === $val) {
			$this->select->where("$key is null");

		// like
		} elseif (preg_match('/_like$/', $key) == 1) {
			$key = preg_replace('/_like$/', '', $key);

			if ($val instanceof \Zend_Db_Expr) {
				$val = $val->__toString();
			}

			$val = '%' . $val . '%';
			$this->select->where("$key LIKE ? ", $val);

		// od
		} elseif (preg_match('/_od$/', $key) == 1) {
			$key = preg_replace('/_od$/', '', $key);
			$this->select->where("$key >= ? ", $val);

		// do
		} elseif (preg_match('/_do$/', $key) == 1) {
			$key = preg_replace('/_do$/', '', $key);
			$this->select->where("$key <= ? ", $val);

		// tab negacja
		} elseif (preg_match('/_not$/', $key) == 1) {
			if (!is_array($val)) {
				$val = (array) $val;
			}
			$key = preg_replace('/_not$/', '', $key);
			$this->select->where("$key NOT IN (?) ", $val);

		// tab
		} elseif (is_array($val)) {
			$this->select->where("$key IN (?) ", $val);

		// bind
		} elseif (preg_match('/\?/', $key)) {
			$this->select->where($key, $val);

		// normal
		} elseif (!is_array($val)) {
			$val = trim($val);
			$this->select->where("$key = ? ", $val);
		}
	}
}
