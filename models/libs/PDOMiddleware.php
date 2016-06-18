<?php

/**
 * PDO Middleware 功能模組
 */
class PDOMiddleware
{
	private $_dbh = null;
	private $_sth = null;

	private $_driver_options = array(
		\PDO::ATTR_ERRMODE => \PDO::ERRMODE_SILENT,			// Error Mode (ERRMODE_SILENT, ERRMODE_WARNING, ERRMODE_EXCEPTION)
		\PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC, // Fetch Style (ETCH_ASSOC, FETCH_BOTH, ...)
		\PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8',	// Set
	);

	/**
	 * 建構式
	 *
	 * @param string   $db      DB Type (ex: mysql, pgsql)
	 * @param string   $host    Host (ex: 127.0.0.1)
	 * @param int      $port    Port (ex: 3306)
	 * @param string   $dbname  DB name
	 * @param string   $user    DB username
	 * @param string   $pass    DB password
	 */
	protected function __construct($db, $host, $port, $dbname, $user, $pass)
	{
		// 連線
		$this->connect($db, $host, $port, $dbname, $user, $pass);
	}

	/**
	 * connect method
	 *
	 * @param string   $db      DB Type (ex: mysql, pgsql)
	 * @param string   $host    Host (ex: 127.0.0.1)
	 * @param int      $port    Port (ex: 3306)
	 * @param string   $dbname  DB name
	 * @param string   $user    DB username
	 * @param string   $pass    DB password
	 *
	 * @return int    0    成功.
	 *         int   -1    失敗.
	 */
	protected function connect($db, $host, $port, $dbname, $user, $pass)
	{
		// 有連線就先斷
		if ($this->_dbh) $this->_dbh = null;

		// 初始化
		$dsn = "$db:host=$host;port=$port;dbname=$dbname";

		try {
			$this->_dbh = new \PDO($dsn, $user, $pass, $this->_driver_options);
			return 0;

		} catch (\PDOException $e) {
			$this->_dbh = null;
			//echo 'Connection failed: ' . $e->getMessage();
			return -1;
		}
	}

	/**
	 * close connection method
	 *
	 * @return int    0    成功.
	 *         int   -1    失敗. (非連線中)
	 */
	protected function close()
	{
		if (null == $this->_dbh) return -1;

		$this->_dbh = null;

		return 0;
	}

	/**
	 * is connect method
	 *
	 * @return int  0    非連線中.
	 *         int  1    連線中.
	 */
	public function isConnect()
	{
		if (null == $this->_dbh) return 0;

		return 1;
	}

	/**
	 * prepare method
	 *
	 * @param string   $sql    SQL statment
	 *
	 * @return boolean  true     成功.
	 *         boolean  false    失敗.
	 */
	public function prepare($sql)
	{

		if (null == $this->_dbh) return false;
		$this->_sth = null;
		
		$ret = $this->_dbh->prepare($sql);
		
		if (false == $ret) return false;
		
		$this->_sth = $ret;
		
		return true;
	}

	/**
	 * execute method
	 *
	 * @param fix   $para_list   parameter list
	 *
	 * @return boolean  true     成功.
	 *         boolean  false    失敗. (PDO statment 錯誤)
	 */
	public function execute()
	{
		if (null == $this->_sth) return false;

		// 取得不定參數
		$arg_list = array();
		$num = func_num_args();
		if ($num > 0) $arg_list = func_get_args();

		// 處理 array in array
		if ($num == 1 && is_array($arg_list[0])) $arg_list = $arg_list[0];

		$ret = $this->_sth->closeCursor();

		$ret = $this->_sth->execute($arg_list);
		if (false == $ret) return false;

		return true;
	}

	/**
	 * execute method (combine prepare & execute)
	 *
	 * @param fix   $para_list   parameter list
	 *
	 * @return boolean  true     成功.
	 *         boolean  false    失敗.
	 */
	public function exec($sql, ...$param)
	{
		$ret = $this->prepare($sql);		
		if (false == $ret) return false;
		
		// 不定參數數量
		$num = count($param);
		
		// 處理 array in array
		if (1 == $num && is_array($param[0])) $param = $param[0];
		
		// 沒有參數就不用帶進去
		if ($num > 0) {
			$ret = $this->execute($param);
		} else {
			$ret = $this->execute();
		}

		if (false == $ret) return false;
		return true;
	}

	/**
	 * fetch method
	 *
	 * @return array    $result   成功. (資料 array)
	 *         boolean  false     失敗.
	 */
	public function fetch()
	{
		if (null == $this->_sth) return false;

		$result = $this->_sth->fetch();

		return $result;
	}

	/**
	 * fetch all method
	 *
	 * @return array    $result   成功. (資料 array)
	 *         boolean  false     失敗.
	 */
	public function fetchAll()
	{
		if (null == $this->_sth) return false;

		$result = $this->_sth->fetchAll();

		return $result;
	}

	/**
	 * rowCount method (returns the number of rows affected by the last DELETE, INSERT, or UPDATE statement executed
	 *                  If the last SQL statement executed by the associated PDOStatement was a SELECT statement,
	 *                  some databases may return the number of rows returned by that statement.
	 *                  However, this behaviour is not guaranteed for all databases and should not be relied on for portable applications.)
	 *
	 * @return int    $count   成功. (數量)
	 */
	public function rowCount()
	{
		if (null == $this->_sth) return false;

		$count = $this->_sth->rowCount();

		return $count;
	}

	/**
	 * lastInsertId method (Returns the ID of the last inserted row, or the last value from a sequence object,
	 *                  depending on the underlying driver. For example,
	 *                  PDO_PGSQL requires you to specify the name of a sequence object for the name parameter.)
	 *
	 *                  This method may not return a meaningful or consistent result across different PDO drivers,
	 *                  because the underlying database may not even support the notion of auto-increment fields or sequences.
	 *
	 *                  Remember, if you use a transaction you should use lastInsertId BEFORE you commit
	 *                  otherwise it will return 0
	 *
	 * @return string  $id   成功. (id)
	 */
	public function lastInsertId()
	{
		if (null == $this->_dbh) return false;

		$id = $this->_dbh->lastInsertId();

		return $id;
	}

	/**
	 * beginTransaction method
	 *
	 * @return boolean  true     成功.
	 *         boolean  false    失敗. (PDO statment 錯誤)
	 */
	public function beginTransaction()
	{
		if (null == $this->_dbh) return false;

		try {
			$ret = $this->_dbh->beginTransaction();
			if (false == $ret) return false;

		} catch (\PDOException $e) {

			//echo 'beginTransaction failed: ' . $e->getMessage();

			return false;
		}

		return true;
	}

	/**
	 * commit method
	 *
	 * @return boolean  true     成功.
	 *         boolean  false    失敗. (PDO statment 錯誤)
	 */
	public function commit()
	{
		if (null == $this->_dbh) return false;

		try {
			$ret = $this->_dbh->commit();
			if (false == $ret) return false;

		} catch (\PDOException $e) {

			//echo 'commit failed: ' . $e->getMessage();

			return false;
		}

		return true;
	}

	/**
	 * rollBack method
	 *
	 * @return boolean  true     成功.
	 *         boolean  false    失敗. (PDO statment 錯誤)
	 */
	public function rollBack()
	{
		if (null == $this->_dbh) return false;

		try {
			$ret = $this->_dbh->rollBack();
			if (false == $ret) return false;

		} catch (\PDOException $e) {

			//echo 'rollBack failed: ' . $e->getMessage();

			return false;
		}

		return true;
	}

	/**
	 * errorCode method
	 *
	 * @return string    $err   成功. (error code)
	 *         boolean   false  失敗. (PDO statment 錯誤)
	 */
	public function errorCode()
	{
		if (null == $this->_sth) return false;

		return $this->_sth->errorCode();
	}

	/**
	 * errorInfo method
	 *
	 * @return array     $arr   成功. (error info)
	 *         boolean   false  失敗. (PDO statment 錯誤)
	 */
	public function errorInfo()
	{
		if (null == $this->_sth) return false;

		return $this->_sth->errorInfo();
	}
}
