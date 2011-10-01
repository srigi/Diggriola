<?php

/**
 * Copyright (C) 2011 by Igor Hlina (srigi@srigi.sk)
 */

namespace Diggriola;

use	Nette\DI\IContainer,
	Nette\Diagnostics\Debugger,
	PDO,
	NotORM,
	NotORM_Cache_Session,
	NotORM_Structure_Convention;

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
class ModelLoader extends \Nette\Object
{

	/**
	 * @var NotORM
	 */
	private $connection;

	/**
	 * @var array Models pool
	 */
	private $models = array();


	/**
	 * Connection factory. Connects to DB.
	 *
	 * @param Nette\DI\IContainer $container
	 * @return NotORM
	 */
	public static function dbConnect(IContainer $container)
	{
		$db = $container->params['database'];
		$dsn = (isset($db['port']))
			? "$db[driver]:host=$db[host];dbname=$db[database];port=$db[port]"
			: "$db[driver]:host=$db[host];dbname=$db[database]";

		$pdo = new PDO($dsn, $db['username'], $db['password']);
		$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$pdo->query('SET NAMES utf8');

		$conn = new NotORM($pdo, new NotORM_Structure_Convention('id', '%s_id', '%ss'), new NotORM_Cache_Session);

		if (isset($db['profiler']) && $db['profiler']) {
			$panel = Panel::getInstance();
			$panel->setPlatform($db['driver']);
			Debugger::addPanel($panel);

			$conn->debug = function($query, $parameters) {
				Panel::getInstance()->logQuery($query, $parameters);
			};
		}

		return $conn;
	}


	/**
	 * @param NotORM $connection
	 */
	public function __construct($connection)
	{
		$this->connection = $connection;
	}


	/**
	 * Return required Model. Instantiate if not in pool.
	 *
	 * @param string $model
	 * @return mixed
	 */
	public function getModel($model)
	{
		if (!isset($this->models[$model])) {
			$class = 'Model\\' . ucfirst($model);
			$this->models[$model] = new $class($this->connection);
		}

		return $this->models[$model];
	}

}
