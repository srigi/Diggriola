<?php

/**
 * Copyright (C) 2011 by Igor Hlina (srigi@srigi.sk)
 */

namespace Diggriola;

/**
 * Service for Model loading & database connection
 *
 * @package	Diggriola
 * @author	Igor Hlina <srigi@srigi.sk>
 * @license	license.txt MIT
 * @version	1.0
 * @link	https://github.com/srigi/Diggriola
 * @link	http://wiki.nette.org/cs/cookbook/jednoduchy-model-s-notorm
 *
 */
class BaseModel extends \Nette\Object
{

	/**
	 * @var NotORM
	 */
	protected $connection = null;

	/**
	 * @var string
	 */
	protected $tableName = '';


	/**
	 * @param NotORM $connection
	 */
	public function __construct($connection)
	{
		$this->connection = $connection;
		$classNameParts = explode('\\', get_class($this));
		$this->tableName = strtolower(array_pop($classNameParts)) . 's';
	}


	/**
	 * Finds all records in table. Use fluent to limit result set.
	 *
	 * @return NotORM_Result
	 */
	public function findAll()
	{
		return $this->connection->{$this->tableName}();
	}


	/**
	 * Magic findBy* method
	 *
	 * @param string $name
	 * @param array $args
	 * @return NotORM_Result
	 */
	public function __call($name, $args)
	{
		if (false !== strpos($name, 'findBy')) {
			$cammelCaseSplit = preg_split('~(?<=\\w)(?=[A-Z])~', str_replace('findBy', '', $name));
			$loweredCammels = array_map(function($in) {
					return strtolower($in);
				}, $cammelCaseSplit);
			$findCondition = implode('.', $loweredCammels);

			if (isset($args[1]) && true === $args[1]) {
				// M:N relation
				$relationTableName = $loweredCammels[0] . 's_' . $this->tableName;
				$mn = $this->connection->{$relationTableName}($findCondition, $args[0])
					->select(substr($this->tableName, 0, -1) . '_id');

				try {
					$result = $this->connection->{$this->tableName}('id', $mn);
				} catch (\PDOException $e) {
					if (false !== strpos($e->getMessage(), 'Table') && false !== strpos($e->getMessage(), 'doesn\'t exist')) {
						// switch table name elements
						$relationTableName = $this->tableName . '_' . $loweredCammels[0] . 's';
						$mn = $this->connection->{$relationTableName}($findCondition, $args[0])
							->select(substr($this->tableName, 0, -1) . '_id');

						$result = $this->connection->{$this->tableName}('id', $mn);
					} else {
						throw $e;
					}
				}

				return $result;
			} else {
				// no or 1:N relation
				return $this->connection->{$this->tableName}()
					->where($findCondition, $args[0]);
			}
		}
	}


	/**
	 * Insert new record to table
	 *
	 * @param array $data
	 * @return NotORM_Row
	 */
	public function insert(array $data)
	{
		return $this->connection->{$this->tableName}()->insert($data);
	}


	/**
	 * Update rowset
	 *
	 * @param NotORM_Result $rowset
	 * @param array $data
	 * @return integer Number of affected rows
	 */
	public function update($rowset, $data)
	{
		return $rowset->update($data);
	}


	/**
	 * Delete rowset
	 *
	 * @param NotORM_Result $rowset
	 * @return integer Number of affected rows
	 */
	public function delete($rowset)
	{
		return $rowset->delete();
	}

}
