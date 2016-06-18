<?php
require_once 'PDOMiddleware.php';

/**
 * PDO PDOSlave Singleton Class
 */
class PDOSlave extends PDOMiddleware
{
	private static $_instance = null;

	/**
	 * 設定檔
	 *
	 * @var   array  $_config
	 *               string   $db      DB Type (ex: mysql, pgsql)
	 *               $host    Host (ex: 127.0.0.1)
	 *               int      $port    Port (ex: 3306)
	 *               string   $dbname  DB name
	 *               string   $user    DB username
	 *               string   $pass    DB password
	 */
	private static $_config = array (
		'db' => 'mysql',
		'host' => '127.0.0.1',
		'port' => 3306,
		'dbname' => 'test',
		'user' => 'root',
		'pass' => '',
	);

	/**
	 * 建構式(防止直接使用 new)
	 */
	protected function __construct($config)
	{
		// 初始化父 class
		parent::__construct($config['db'], $config['host'], $config['port'], $config['dbname'], $config['user'], $config['pass']);
	}

	/**
     * Returns the *Singleton* instance of this class.
	 * @return Singleton The *Singleton* instance.
	 */
	public static function getInstance(...$param)
	{
		$config = (1 == count($param)) ? $param[0] : self::$_config;

		if (null === self::$_instance) {
			$c = __CLASS__;
			self::$_instance = new $c($config);
		}

		return static::$_instance;
	}
}
