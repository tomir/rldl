<?php

namespace RLDL2\Db\Adapter;


class Mysqli extends \Zend_Db_Adapter_Mysqli
{

	/**
	 * Prosta implementacji metody _connect, ktora uniemozliwia ponowne polaczenie
	 * 
	 * @return void
	 * @throws \Exception
	 */
	protected function _connect()
	{
		if ($this->_connection) {
			return;
		}

		throw new \Exception("Nigdy nie powinno do tego dojsc. Polaczenie jest nawiazywane w obiekcie Adodb i przekazywane w postaci obiektu mysqli");
	}

	/**
	 * Nadpisanie domyslnej metody sleep ktora czycic polaczenie
	 * co przy wspolpracy z tranzakcjami i adodb powoduje niemale problemy
	 */
	public function __sleep()
	{
		//return parent::__sleep();
	}
}
