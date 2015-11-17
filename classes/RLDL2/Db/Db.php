<?php

namespace RLDL2\Db;

class Db extends \Zend_Db
{
	/**
	 * @var \Zend_Db_Adapter_Abstract 
	 */
	protected static $instance = null;

	/**
	 * 
	 * @var AdodbConn
	 */
	protected static $adodb = null;

	/**
	 * 
	 * @param string $dbhost
	 * @param string $dbname
	 * @param string $dblogin
	 * @param string $dbpass
	 */
	public static function setDbAccessData($dbhost, $dbname, $dblogin, $dbpass)
	{
		$data = array(
			'dbhost' => $dbhost,
			'dbname' => $dbname,
			'dblogin' => $dblogin,
			'dbpass' => $dbpass
		);

		self::$dbAccessData = $data;
	}

	/**
	 * @return array
	 */
	public static function getDbAccessData()
	{
		return self::$dbAccessData;
	}


	/**
	 * @param Enp\Db\Adodb\ConnectionInterface $adodb
	 */
	public static function setAdodb(AdodbConn $adodb)
	{
		self::$adodb = $adodb;
	}

	/**
	 * 
	 * @return \Zend_Db_Adapter_Abstract
	 */
	public static function getInstance()
	{
		if (self::$instance == null) {

			$db = self::getConnectionAdapter();
			\Zend_Db_Table::setDefaultAdapter($db);

			self::$instance = $db;
		}

		return self::$instance;
	}
	
	/**
	 * @return \Zend_Db_Adapter_Mysqli
	 */
	protected static function getConnectionAdapter()
	{
		$config = array(
			'fetchMode' => \Zend_Db::FETCH_ASSOC,
			'host' => SQL_SERV,
			'username' => SQL_USER,
			'password' => SQL_PASS,
			'dbname' => SQL_DB
		);

		$db = new Adapter\Mysqli($config);
		return $db;
	}
	
	protected static function beginTrans()
	{
		$db = self::getInstance();
		$db->beginTransaction();
	}

	protected static function commitTrans()
	{
		$db = self::getInstance();
		$db->commit();
	}

	protected static function rollbackTrans()
	{
		$db = self::getInstance();
		$db->rollBack();
	}
}
