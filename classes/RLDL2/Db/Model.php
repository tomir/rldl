<?php

namespace RLDL2\Db;

abstract class Model extends Auth implements ModelInterface {

	protected $sql;
	protected $auth;
	protected $id;
	protected $data;
	protected $dbTable;
	protected $dbColumn;
	
	protected $_doProcessGetOneRecord = true;
	
	/**
	 * Example of use
	 * " field = ? " , when char ? will be replace to value
	 *
	 * @var array
	 */
	protected $_filter = array();

	/**
	 * @var  \RLDL2\Db\FiltrPluginInterface[]
	 */
	protected $_plugin = array();

	public function __construct($id = null) {

		$this->sql = \MySQL::getInstance();
		if (!is_null($id)) {
			$this->id = $id;
		}

		$this->dbTable = $this->getDbTable();
		$this->dbColumn = $this->setPrimaryColumn();

		parent::__construct();
	}

	abstract public function getDbTable();

	abstract public function setPrimaryColumn();

	public function load($id) {
		
		if ($id !== null) {
			$data = $this->getOne($id);

			if ((int) $data[$this->dbColumn] == $id) {
				$this->data = $data;
				$this->id = $id;
			} else {
				$this->data = array();
				$this->id = null;
			}
		}
	}
	
	/**
	 *
	 * @param array $filtr
	 * @return \Zend_Db_Select
	 */
	protected function getSelect($filtr = array())
	{
		$adapter = \RLDL2\Db\Db::getInstance();

		// select
		$select = new \Zend_Db_Select($adapter);
		$select->from(array('x' => $this->dbTable));
		$select = $this->setFiltr($select, $filtr);

		$this->_lastSQL[] = $select->__toString();
		return $select;
	}
	
	/**
	 *
	 * Additional filters set in var
	 * $this->__filter
	 *
	 * @param Zend_Db_Select $select
	 * @param array $filtr
	 * @return Zend_Db_Select
	 */
	protected function setFiltr(\Zend_Db_Select $select, $filter = array())
	{
		$selectBuilder = new \RLDL2\Db\Select\Builder($select, $this->_filter, $this->_plugin, $this);
		$select = $selectBuilder->setFilter($filter);

		return $select;
	}

	public function getOne() {

		if(is_null($this->dbColumn)) {
			throw new \Exception(
				'No primary column in table ' . $this->dbTable . '. Do not use this method.', 404
			);
		}
		if (($row = $this->sql->SelectSingleRowArray($this->dbTable, array($this->dbColumn => $this->id))) !== false) {

			if ($this->_doProcessGetOneRecord) {
				$row = $this->processGetOneRecord($row);
			}
			return $row;
		} else {
			throw new \Exception(
			'Record in table ' . $this->dbTable . ' not exists.', 404
			);
		}
	}

	public function getAll($whereArray = null, $sort = null, $limit = null) {
		
		if(is_array($whereArray) && count($whereArray) > 0) {
			foreach($whereArray as $key => $row) {
				$whereArray[$key] = \MySQL::SQLValue($row, 'string');
			}
		}
		$result = $this->sql->SelectArray($this->dbTable, $whereArray, null, $sort, $limit);
		
		if ($this->_doProcessGetOneRecord) {
			foreach ($result as $key => $row) {
				$result[$key] = $this->processGetOneRecord($row);
			}
		}

		return $result;
	}

	public function getAllCount($whereArray = array()) {

		return count($this->sql->SelectArray($this->dbTable, $whereArray, null, $sort, $limit));
	}

	public function insert($data) {
		
		$this->isAuthorized();
		if ($this->sql->InsertRow($this->dbTable, $data) === false) {
			throw new \Exception(
			$this->sql->Error(), 500
			);
		}

		$this->id = $this->sql->GetLastInsertID();
		return $this->id;
	}

	public function update($data, $id = null) {

		$this->isAuthorized();
		if (!is_null($id)) {
			$this->id = $id;
		}
		if (!$this->sql->UpdateRow($this->dbTable, $data, array($this->dbColumn => \MySQL::SQLValue($this->id, 'int')))) {
			throw new \Exception(
			$this->sql->Error(), 500
			);
		}
	}

	public function delete($id = null) {

		$this->isAuthorized();
		if (!is_null($id)) {
			$this->id = $id;
		}

		return $this->sql->DeleteRows($this->dbTable, array(
			$this->dbColumn => $this->id,
		));
	}

	public function deleteWhere($whereArray) {
		
		$this->isAuthorized();
		return $this->sql->DeleteRows($this->dbTable, $whereArray);
	}

	/**
	 * @param boolean $boolean
	 */
	public function doProcessGetOneRecord($boolean) {
		$this->_doProcessGetOneRecord = (bool) $boolean;
	}

	/**
	 * @param type $record
	 * @return type
	 */
	public function processGetOneRecord($record) {
		return $record;
	}
	
	/**
	 * @param string $name
	 * @return boolean
	 */
	public function hasFilterNameReserved($name)
	{
		if (isset($this->_plugin[$name])) {
			return true;
		}

		if (isset($this->_filter[$name])) {
			return true;
		}

		return false;
	}
	
	/**
	 * @param \RLDL2\Db\FiltrPluginInterface $plugin
	 * @param string $name
	 * @throws \Exception
	 */
	public function addPlugin(FiltrPluginInterface $plugin, $name)
	{
		if ($this->hasFilterNameReserved($name)) {
			throw new \Exception("Filter or plugin already exist at this name: $name");
		}

		$this->_plugin[$name] = $plugin;
	}
	
	/**
	 * @param \Zend_Db_Select $select
	 * @param mixed $sort
	 * @return \Zend_Db_Select
	 */
	protected function setSort($select, $sort = null)
	{
		if ($sort !== null) {
			// order
			if (isset($sort['sort'])) {
				$sortX[] = $sort;
			} else {
				$sortX = $sort;
			}

			foreach ($sortX as $key => $sortOne) {
				if ($sortOne['sort'] != '' && $sortOne['order'] != '') {
					$select->order($sortOne['sort'] . ' ' . $sortOne['order']);
				} elseif ($sortOne['sort'] == 'rand') {
					$select->order(new \Zend_Db_Expr('RAND()'));
				}
			}
		}

		return $select;
	}
	
	/**
	 * @param \Zend_Db_Select $select
	 * @param array|null $limit
	 * @return \Zend_Db_Select
	 */
	protected function setLimit($select, $limit = null)
	{
		if ($limit !== null) {

			// limit
			if ($limit['limit'] !== '' && $limit['start'] !== '') {
				$select->limit((int) $limit['limit'], (int) $limit['start']);
			}
		}

		return $select;
	}

}
