<?php
/**
 * TLoginLog
 */
class TLoginLog extends TIProtocol {
	
	private static $s_instance = null;
	
	function __construct()
	{
		parent::__construct();
	}
	
	public static function GetInstance()
	{
		if (self::$s_instance == NULL)
		{
			self::$s_instance = new TLoginLog();
		}
		return self::$s_instance;
	}
	
	// =============================================
	
	/**
	 * 儲存登入資料
	 * @param $iUid
	 * @param $iAcc
	 * 
	 */
	public function ToSave($iUid, $iAcc)
	{
		$_sql = 'insert into login_log (uid, account, ip, time) values (?, ?, ?, ?)';
		$_ret = $this->m_Writer->exec($_sql, $iUid, $iAcc, $_SERVER["REMOTE_ADDR"], time());
		if (!$_ret)
		{
			$_msg = "error : ".$this->m_Writer->errorCode()."<br>\n";
			TLogs::log("TLoginLog-ToSave", $_msg);
		}
	}
	
	/**
	 * 讀取上次登入時間
	 * @param $iUid
	 */
	public function ToReadLast($iUid)
	{
		$_sql = "select time from login_log where uid=? order by time DESC";
		$this->m_Read->exec($_sql, $iUid);
		$_ret = $this->m_Read->fetch();
		
		if (!$_ret)
		{
			return '歡迎您初次登入。';
		}
		else 
		{
			return '上次登入時間:'.date('Y/m/d H:i:s', $_ret['time']);
		}
	}
	
	/**
	 * 讀取登入資料
	 * @param $iUid
	 * @param $iSTime : 時間戳
	 * @param $iETime : 時間戳
	 */
	public function ToRead($iUid = '', $iSTime = '', $iETime = '')
	{
		$_sql = "select * from login_log";
		
		if ($iUid != '' && $iSTime != '')
		{
			$_sql .= ' where uid=? and time>=? and time<?';
			$this->m_Read->exec($_sql, $iUid, $iSTime, $iETime);
		}
		else if ($iUid != '' && $iSTime == '')
		{
			$_sql .= ' where uid=?';
			$this->m_Read->exec($_sql, $iUid);
		}
		else if ($iUid == '' && $iSTime != '')
		{
			$_sql .= ' where time>=? and time<?';
			$this->m_Read->exec($_sql, $iSTime, $iETime);
		}
		else 
		{
			$this->m_Read->exec($_sql);
		}
		
		//
		$_ret = $this->m_Read->fetchAll();
		if (!$_ret)
		{
			TLogs::log("TLogin-27", '100001');
			TViewBase::Main('TLogin/TLogin');
			return;
		}
		//
		return $_ret;
	}
	
	
}