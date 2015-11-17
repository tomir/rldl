<?php

namespace RLDL2\Db;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of ModelAbstract
 *
 * @author tomi_weber
 */
interface ModelInterface {
	public function __construct($id);
	public function getAll($filtr, $sort, $limit);
	public function getAllCount($filtr);
	public function load($id);
	public function insert($data);
	public function update($data, $id = null);
	public function delete($id = null);
}
