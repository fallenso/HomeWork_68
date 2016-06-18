<?php
/**
 * Redis Middleware Class
 */
class RedisMiddleware
{
	private $_redis = null; // redis instance
	private $_conn = false; // 是否連線
	private $_multi = null; // multi handle

	const CONN_TIMEOUT = 2; // 連線 timeout 時間 (sec)
	const CONTT_RETRY_INTERVAL = 500; // 連線失敗等多久 retry (millisecond)

	const REDIS_STRING = \Redis::REDIS_STRING;
	const REDIS_SET = \Redis::REDIS_SET;
	const REDIS_LIST = \Redis::REDIS_LIST;
	const REDIS_ZSET = \Redis::REDIS_ZSET;
	const REDIS_HASH = \Redis::REDIS_HASH;
	const REDIS_NOT_FOUND = \Redis::REDIS_NOT_FOUND;

	/**
	 * 建構式
	 *
	 * @param string   $host    Host (ex: 127.0.0.1)
	 * @param int      $port    Port (ex: 3306)
	 * @param string   $pass    auth password
	 */
	protected function __construct($host, $port = 6379, $pass = '')
	{
		$this->_redis = new \Redis();

		// 連線
		$this->connect($host, $port, $pass);
	}

	/**
	 * connect method
	 *
	 * @param string   $host    Host (ex: 127.0.0.1)
	 * @param int      $port    Port (ex: 3306)
	 * @param string   $pass    auth password
	 *
	 * @return int    0    成功.
	 *         int   -1    失敗. (初始化失敗)
	 *         int   -2    失敗. (連線失敗)
	 *         int   -3    失敗. (認證失敗)
	 */
	protected function connect($host, $port = 6379, $pass = '')
	{
		if ($this->_redis == null) return -1;

		// 有連線就先斷
		if (true == $this->_conn) {
			$this->_redis->close();
			$this->_conn = false;

			$this->_multi = null; // multi handle
		}

		// 連線
		try {
			$ret = $this->_redis->connect($host, $port, self::CONN_TIMEOUT, NULL, self::CONTT_RETRY_INTERVAL);
			if (false == $ret) return -2;
			if (true == $ret) $this->_conn = true;

		} catch (\RedisException $e) {

			//echo 'Connection failed: ' . $e->getMessage();

			return -2;
		}

		// 密碼認證
		if (! $pass) return 0; // 不需密碼

		$ret = $this->_redis->auth($pass);
		if (false == $ret) {
			$this->close();
			return -3;
		}

		return 0;
	}

	/**
	 * close connection method
	 *
	 * @return int    0    成功.
	 *         int   -1    失敗. (初始化失敗)
	 *         int   -2    失敗. (非連線中)
	 */
	protected function close()
	{
		if (null == $this->_redis) return -1;
		if (false == $this->_conn) return -2;

		$this->_redis->close();
		$this->_conn = false;


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
		$ret = (true == $this->_conn) ? 1 : 0;

		return $ret;
	}

	/**
	 * select database method
	 *
	 * @return int    0    成功.
	 *         int   -1    失敗. (初始化失敗)
	 *         int   -2    失敗. (非連線中)
	 *         int   -3    失敗. (select 失敗)
	 */
	public function select($dbindex)
	{
		if (null == $this->_redis) return -1;
		if (false == $this->_conn) return -2;

		$ret = $this->_redis->select($dbindex);
		if (false === $ret) return -3;

		return 0;
	}

	/**
	 * begin transactional mode (automic 指令: 不會被中斷)
	 *
	 * @return int    0    成功.
	 *         int   -1    失敗. (初始化失敗)
	 *         int   -2    失敗. (非連線中)
	 */
	public function beginMulti()
	{
		if (null == $this->_redis) return -1;
		if (false == $this->_conn) return -2;

		if ($this->_multi != null) {
			$this->_redis->discard();
			$this->_multi = null;
		}

		$this->_multi = $this->_redis->multi();

		return 0;
	}

	/**
	 * begin pipe mode (一次傳送多個指令: 可能會被中斷)
	 *
	 * @return int    0    成功.
	 *         int   -1    失敗. (初始化失敗)
	 *         int   -2    失敗. (非連線中)
	 */
	public function beginPipe()
	{
		if (null == $this->_redis) return -1;
		if (false == $this->_conn) return -2;

		if ($this->_multi != null) {
			$this->_redis->discard();
			$this->_multi = null;
		}

		$this->_multi = $this->_redis->multi(\Redis::PIPELINE);

		return 0;
	}

	/**
	 * discard transactional or pipe mode
	 *
	 * @return int    0    成功.
	 *         int   -1    失敗. (初始化失敗)
	 *         int   -2    失敗. (非連線中)
	 *         int   -3    失敗. (非 transactional or pipe mode)
	 */
	public function discard()
	{
		if (null == $this->_redis) return -1;
		if (false == $this->_conn) return -2;
		if (null == $this->_multi) return -3;

		$this->_redis->discard();
		$this->_multi = null;

		return 0;
	}

	/**
	 * key watch method
	 *
	 * @param  mix  $key or $array_key
	 *         if the key is modified between WATCH and EXEC, the MULTI/EXEC transaction will fail (return FALSE).
	 *         unwatch cancels all the watching of all keys by this client.
	 *
	 * @return true    成功.
	 *         false   失敗.
	 */
	public function watch($key)
	{
		if (null == $this->_redis || false == $this->_conn) return false;

		$this->_redis->watch($key);

		return true;
	}

	/**
	 * key unwatch method (unwatch cancels all the watching of all keys by this client.)
	 *
	 * @return true    成功.
	 *         false   失敗.
	 */
	public function unwatch()
	{
		if (null == $this->_redis || false == $this->_conn) return false;

		$this->_redis->unwatch();

		return true;
	}

	/**
	 * exec transactional or pipe
	 *
	 * @return int    0    成功.
	 *         int   -1    失敗. (初始化失敗)
	 *         int   -2    失敗. (非連線中)
	 *         int   -3    失敗. (非 transactional or pipe mode)
	 */
	public function exec()
	{
		if (null == $this->_redis) return -1;
		if (false == $this->_conn) return -2;
		if (null == $this->_multi) return -3;

		$ret = $this->_redis->exec();
		$this->_multi = null;

		return $ret;
	}

	/**
	 * delete key method
	 *
	 * @param  mix  $key
	 *
	 * @return int  >=0    成功. (刪除數量)
	 *         int   -1    失敗. (初始化失敗)
	 *         int   -2    失敗. (非連線中)
	 *         int   -3    失敗. (select 失敗)
	 */
	public function delete($key)
	{
		if (null == $this->_redis) return -1;
		if (false == $this->_conn) return -2;

		if ($this->_multi !== null) {
			$this->_multi = $this->_multi->delete($key);
			return 0;
		}

		return $this->_redis->delete($key);
	}

	/**
	 * key exists method
	 *
	 * @param  string  $key
	 *
	 * @return int    1    成功. (存在)
	 *         int    0    成功. (不存在)
	 *         int   -1    失敗. (初始化失敗)
	 *         int   -2    失敗. (非連線中)
	 */
	public function exists($key)
	{
		if (null == $this->_redis) return -1;
		if (false == $this->_conn) return -2;

		if ($this->_multi !== null) {
			$this->_multi = $this->_multi->exists($key);
			return 0;
		}

		$ret = $this->_redis->exists($key);
		$ret = (true === $ret) ? 1 : 0;

		return $ret;
	}

	/**
	 * key set expire with TTL(second) method
	 *
	 * @param string  $key
	 * @param int     $ttl_sec
	 *
	 * @return true   成功.
	 *         false  失敗.
	 */
	public function expire($key, $ttl_sec)
	{
		if (null == $this->_redis || false == $this->_conn) return false;

		if ($this->_multi !== null) {
			$this->_multi = $this->_multi->expire($key, $ttl_sec);
			return true;
		}

		return $this->_redis->expire($key, $ttl_sec);
	}

	/**
	 * key set expire with TTL(millisecond) method
	 *
	 * @param string  $key
	 * @param int     $ttl_millisec
	 *
	 * @return true   成功.
	 *         false  失敗.
	 */
	public function pexpire($key, $ttl_millisec)
	{
		if (null == $this->_redis || false == $this->_conn) return false;

		if ($this->_multi !== null) {
			$this->_multi = $this->_multi->pexpire($key, $ttl_millisec);
			return true;
		}

		return $this->_redis->pexpire($key, $ttl_millisec);
	}

	/**
	 * key set expire with UNIX time(second) method
	 *
	 * @param string  $key
	 * @param int     $unixtime_sec
	 *
	 * @return true   成功.
	 *         false  失敗.
	 */
	public function expireAt($key, $unixtime_sec)
	{
		if (null == $this->_redis || false == $this->_conn) return false;

		if ($this->_multi !== null) {
			$this->_multi = $this->_multi->expireAt($key, $unixtime_sec);
			return true;
		}

		return $this->_redis->expireAt($key, $unixtime_sec);
	}

	/**
	 * key set expire with UNIX time(millisecond) method
	 *
	 * @param string  $key
	 * @param int     $unixtime_millisec
	 *
	 * @return true   成功.
	 *         false  失敗.
	 */
	public function pexpireAt($key, $unixtime_millisec)
	{
		if (null == $this->_redis || false == $this->_conn) return false;

		if ($this->_multi !== null) {
			$this->_multi = $this->_multi->pexpireAt($key, $unixtime_millisec);
			return true;
		}

		return $this->_redis->pexpireAt($key, $unixtime_millisec);
	}

	/**
	 * Remove the expiration timer from a key method
	 *
	 * @param string  $key
	 *
	 * @return true   成功.
	 *         false  失敗.
	 */
	public function persist($key)
	{
		if (null == $this->_redis || false == $this->_conn) return false;

		if ($this->_multi !== null) {
			$this->_multi = $this->_multi->persist($key);
			return true;
		}

		return $this->_redis->persist($key);
	}

	/**
	 * get ttl(secound) key method
	 *
	 * @param string  $key
	 *
	 * @return int  >=0    成功. (TTL 值)
	 *         int   -1    失敗. (初始化失敗)
	 *         int   -2    失敗. (非連線中)
	 *         int   -3    失敗. (No TTL - 不會過期)
	 *         int   -4    失敗. (key 不存在)
	 */
	public function ttl($key)
	{
		if (null == $this->_redis) return -1;
		if (false == $this->_conn) return -2;

		if ($this->_multi !== null) {
			$this->_multi = $this->_multi->ttl($key);
			return 0;
		}

		// LONG: The time to live in seconds. If the key has no ttl, -1 will be returned, and -2 if the key doesn't exist.
		$ret = $this->_redis->ttl($key);

		if (-1 == $ret) $ret = -3;
		if (-2 == $ret) $ret = -4;

		return $ret;
	}

	/**
	 * get ttl(millisecound) key method
	 *
	 * @param string  $key
	 *
	 * @return int  >=0    成功. (TTL 值)
	 *         int   -1    失敗. (初始化失敗)
	 *         int   -2    失敗. (非連線中)
	 *         int   -3    失敗. (No TTL - 不會過期)
	 *         int   -4    失敗. (key 不存在)
	 */
	public function pttl($key)
	{
		if (null == $this->_redis) return -1;
		if (false == $this->_conn) return -2;

		if ($this->_multi !== null) {
			$this->_multi = $this->_multi->pttl($key);
			return 0;
		}

		// LONG: The time to live in seconds. If the key has no ttl, -1 will be returned, and -2 if the key doesn't exist.
		$ret = $this->_redis->pttl($key);

		if (-1 == $ret) $ret = -3;
		if (-2 == $ret) $ret = -4;

		return $ret;
	}

	/**
	 * string get method
	 *
	 * @param string  $key
	 *
	 * @return string $value    成功.
	 *         false            失敗.
	 */
	public function get($key)
	{
		if (null == $this->_redis || false == $this->_conn) return false;

		if ($this->_multi !== null) {
			$this->_multi = $this->_multi->get($key);
			return true;
		}

		return $this->_redis->get($key);
	}

	/**
	 * string set method
	 *
	 * @param string  $key
	 * @param string  $value
	 *
	 * @return true   成功.
	 *         false  失敗.
	 */
	public function set($key, $value)
	{
		if (null == $this->_redis || false == $this->_conn) return false;

		if ($this->_multi !== null) {
			$this->_multi = $this->_multi->set($key, $value);
			return true;
		}

		return $this->_redis->set($key, $value);
	}

	/**
	 * string set with TTL(second) method
	 *
	 * @param string  $key
	 * @param int     $ttl_sec
	 * @param string  $value
	 *
	 * @return true   成功.
	 *         false  失敗.
	 */
	public function setex($key, $ttl_sec, $value)
	{
		if (null == $this->_redis || false == $this->_conn) return false;

		if ($this->_multi !== null) {
			$this->_multi = $this->_multi->setex($key, $ttl_sec, $value);
			return true;
		}

		return $this->_redis->setex($key, $ttl_sec, $value);
	}

	/**
	 * string set with TTL(millisecond) method
	 *
	 * @param string  $key
	 * @param int     $ttl_millisec
	 * @param string  $value
	 *
	 * @return true   成功.
	 *         false  失敗.
	 */
	public function psetex($key, $ttl_millisec, $value)
	{
		if (null == $this->_redis || false == $this->_conn) return false;

		if ($this->_multi !== null) {
			$this->_multi = $this->_multi->psetex($key, $ttl_millisec, $value);
			return true;
		}

		return $this->_redis->psetex($key, $ttl_millisec, $value);
	}

	/**
	 * string set if key not exist method
	 *
	 * @param string  $key
	 * @param string  $value
	 *
	 * @return true   成功.
	 *         false  失敗.
	 */
	public function setnx($key, $value)
	{
		if (null == $this->_redis || false == $this->_conn) return false;

		if ($this->_multi !== null) {
			$this->_multi = $this->_multi->setnx($key, $value);
			return true;
		}

		return $this->_redis->setnx($key, $value);
	}

	/**
	 * string multiple get method
	 *
	 * @param  array  $key_array
	 *
	 * @return array  $value   成功.
	 *         false           失敗.
	 */
	public function mget($key_array)
	{
		if (null == $this->_redis || false == $this->_conn) return false;
		if (! is_array($key_array)) return false;

		if ($this->_multi !== null) {
			$this->_multi = $this->_multi->mget($key_array);
			return true;
		}

		return $this->_redis->mget($key_array);
	}

	/**
	 * string multiple set method in one atomic command
	 *
	 * @param  array  $array
	 *
	 * @return true   成功. (if all the keys were set)
	 *         false  失敗.
	 */
	public function mset($array)
	{
		if (null == $this->_redis || false == $this->_conn) return false;
		if (! is_array($array)) return false;

		if ($this->_multi !== null) {
			$this->_multi = $this->_multi->mget($array);
			return true;
		}

		return $this->_redis->mget($array);
	}

	/**
	 * string multiple set if key not exist method in one atomic command
	 *
	 * @param  array  $array
	 *
	 * @return true   成功. (if all the keys were set)
	 *         false  失敗.
	 */
	public function msetnx($array)
	{
		if (null == $this->_redis || false == $this->_conn) return false;
		if (! is_array($array)) return false;

		if ($this->_multi !== null) {
			$this->_multi = $this->_multi->msetnx($array);
			return true;
		}

		return $this->_redis->msetnx($array);
	}

	/**
	 * strlen key method
	 *
	 * @param string  $key
	 *
	 * @return int  >=0    成功. (key 長度)
	 *         int   -1    失敗. (初始化失敗)
	 *         int   -2    失敗. (非連線中)
	 */
	public function strlen($key)
	{
		if (null == $this->_redis) return -1;
		if (false == $this->_conn) return -2;

		if ($this->_multi !== null) {
			$this->_multi = $this->_multi->strlen($key);
			return 0;
		}

		return $this->_redis->strlen($key);
	}

	/**
	 * string incrBy method (value add int $value)
	 *
	 * @param string  $key
	 * @param int     $value
	 *
	 * @return int  $value  成功. (new int value)
	 *         bool false   失敗.
	 */
	public function incrBy($key, $value)
	{
		if (null == $this->_redis || false == $this->_conn) return false;

		if ($this->_multi !== null) {
			$this->_multi = $this->_multi->incrBy($key, $value);
			return 0;
		}

		return $this->_redis->incrBy($key, $value);
	}

	/**
	 * string incrByFloat method (value add float $value)
	 *
	 * @param string  $key
	 * @param float   $value
	 *
	 * @return int  $value  成功. (new float value)
	 *         bool false   失敗.
	 */
	public function incrByFloat($key, $value)
	{
		if (null == $this->_redis || false == $this->_conn) return false;

		if ($this->_multi !== null) {
			$this->_multi = $this->_multi->incrByFloat($key, $value);
			return 0;
		}

		return $this->_redis->incrByFloat($key, $value);
	}

	/**
	 * hash get method
	 *
	 * @param string  $key
	 * @param string  $field
	 *
	 * @return string $value    成功.
	 *         false            失敗.
	 */
	public function hGet($key, $field)
	{
		if (null == $this->_redis || false == $this->_conn) return false;

		if ($this->_multi !== null) {
			$this->_multi = $this->_multi->hGet($key, $field);
			return 0;
		}

		return $this->_redis->hGet($key, $field);
	}

	/**
	 * hash set method
	 *
	 * @param string  $key
	 * @param string  $field
	 * @param string  $value
	 *
	 * @return true   成功.
	 *         false  失敗.
	 */
	public function hSet($key, $field, $value)
	{
		if (null == $this->_redis || false == $this->_conn) return false;

		if ($this->_multi !== null) {
			$this->_multi = $this->_multi->hSet($key, $field, $value);
			return true;
		}

		$ret = $this->_redis->hSet($key, $field, $value);
		if (false === $ret) return false;

		return true;
	}

	/**
	 * hash set if field not exist method
	 *
	 * @param string  $key
	 * @param string  $field
	 * @param string  $value
	 *
	 * @return true   成功.
	 *         false  失敗.
	 */
	public function hSetNx($key, $field, $value)
	{
		if (null == $this->_redis || false == $this->_conn) return false;

		if ($this->_multi !== null) {
			$this->_multi = $this->_multi->hSetNx($key, $field, $value);
			return true;
		}

		return $this->_redis->hSetNx($key, $field, $value);
	}

	/**
	 * hash multiple field value get method
	 *
	 * @param  array  $key
	 * @param  array  $array_field
	 *
	 * @return array  $field_value   成功.
	 *         false                 失敗.
	 */
	public function hMGet($key, $array_field)
	{
		if (null == $this->_redis || false == $this->_conn) return false;
		if (! is_array($array_field)) return false;

		if ($this->_multi !== null) {
			$this->_multi = $this->_multi->hMGet($key, $array_field);
			return true;
		}

		return $this->_redis->hMGet($key, $array_field);
	}

	/**
	 * hash multiple field value set method
	 *
	 * @param  array  $key
	 * @param  array  $array_field_value
	 *
	 * @return array  $field_value   成功.
	 *         false                 失敗.
	 */
	public function hMSet($key, $array_field_value)
	{
		if (null == $this->_redis || false == $this->_conn) return false;
		if (! is_array($array_field_value)) return false;

		if ($this->_multi !== null) {
			$this->_multi = $this->_multi->hMSet($key, $array_field_value);
			return true;
		}

		return $this->_redis->hMSet($key, $array_field_value);
	}

	/**
	 * hash delete field method
	 *
	 * @param string  $key
	 * @param string  $field
	 *
	 * @return true   成功.
	 *         false  失敗.
	 */
	public function hDel($key, $field)
	{
		if (null == $this->_redis || false == $this->_conn) return false;

		if ($this->_multi !== null) {
			$this->_multi = $this->_multi->hDel($key, $field);
			return true;
		}

		return $this->_redis->hDel($key, $field);
	}

	/**
	 * hash number of fields of a hash method
	 *
	 * @param string  $key
	 *
	 * @return int    >=0  成功. (hash field 數量)
	 *         false       失敗.
	 */
	public function hLen($key)
	{
		if (null == $this->_redis || false == $this->_conn) return false;

		if ($this->_multi !== null) {
			$this->_multi = $this->_multi->hLen($key);
			return 0;
		}

		return $this->_redis->hLen($key);
	}

	/**
	 * hash incrBy method (value add $value)
	 *
	 * @param string  $key
	 * @param string  $field
	 * @param string  $value
	 *
	 * @return int  $value  成功. (new value)
	 *         bool false   失敗.
	 */
	public function hIncrBy($key, $field, $value)
	{
		if (null == $this->_redis || false == $this->_conn) return false;

		if ($this->_multi !== null) {
			$this->_multi = $this->_multi->hIncrBy($key, $field, $value);
			return true;
		}

		return $this->_redis->hIncrBy($key, $field, $value);
	}

	/**
	 * hash hIncrByFloat method (value add float $value)
	 *
	 * @param string  $key
	 * @param string  $field
	 * @param float   $value
	 *
	 * @return int  $value  成功. (new float value)
	 *         bool false   失敗.
	 */
	public function hIncrByFloat($key, $field, $value)
	{
		if (null == $this->_redis || false == $this->_conn) return false;

		if ($this->_multi !== null) {
			$this->_multi = $this->_multi->hIncrByFloat($key, $field, $value);
			return true;
		}

		return $this->_redis->hIncrByFloat($key, $field, $value);
	}

	/**
	 * hash get fields of a hash method
	 *
	 * @param string  $key
	 *
	 * @return array  $fields  成功.
	 *         false           失敗.
	 */
	public function hKeys($key)
	{
		if (null == $this->_redis || false == $this->_conn) return false;

		if ($this->_multi !== null) {
			$this->_multi = $this->_multi->hKeys($key);
			return true;
		}

		return $this->_redis->hKeys($key);
	}

	/**
	 * hash get values of a hash method
	 *
	 * @param string  $key
	 *
	 * @return array  $values  成功.
	 *         false           失敗.
	 */
	public function hVals($key)
	{
		if (null == $this->_redis || false == $this->_conn) return false;

		if ($this->_multi !== null) {
			$this->_multi = $this->_multi->hVals($key);
			return true;
		}

		return $this->_redis->hVals($key);
	}

	/**
	 * hash get whole hash method
	 *
	 * @param string  $key
	 *
	 * @return array  $hash  成功.
	 *         false         失敗.
	 */
	public function hGetAll($key)
	{
		if (null == $this->_redis || false == $this->_conn) return false;

		if ($this->_multi !== null) {
			$this->_multi = $this->_multi->hGetAll($key);
			return true;
		}

		return $this->_redis->hGetAll($key);
	}

	/**
	 * hash field exists method
	 *
	 * @param  string  $key
	 * @param  string  $field
	 *
	 * @return int    1    成功. (存在)
	 *         int    0    成功. (不存在)
	 *         int   -1    失敗. (初始化失敗)
	 *         int   -2    失敗. (非連線中)
	 */
	public function hExists($key, $field)
	{
		if (null == $this->_redis) return -1;
		if (false == $this->_conn) return -2;

		if ($this->_multi !== null) {
			$this->_multi = $this->_multi->hExists($key, $field);
			return 0;
		}

		$ret = $this->_redis->hExists($key, $field);
		$ret = (true === $ret) ? 1 : 0;

		return $ret;
	}	

}
