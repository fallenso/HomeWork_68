<?php
/**
 * 本金最低限制設定
 */
class TCostlimit  extends TIProtocol {
	
	private static $s_Instance;
	
	// ==========================================================================
	//
	function __construct()
	{
		parent::__construct();
	}
	
	//
	public static function GetInstance()
	{
		if (self::$s_Instance == null)
		{
			self::$s_Instance = new TCostlimit();
		}
		//
		return self::$s_Instance;
	}
	
	// ==========================================================================
	//
	
	/**
	 * 讀取資料
	 */
	public function GetData()
	{
		$_sql = "select context from costlimit";
		$this->m_Read->exec($_sql);
		$_bet = $this->m_Read->fetch();
		$_json = json_decode($_bet['context'], true);
		return $_json;
	}
	
	/**
	 * 更新資料
	 * @param $iPost : post 過來要更新的資料
	 */
	public function UPData($iPost)
	{
		$_data = [];
		for($i = 0; $i<3; ++$i)
		{
			for($j = 1; $j<10; ++$j)
			{
				$_key = $i.'_'.$j;
				$_val = (isset($iPost[$_key]))?$iPost[$_key]:0;
				$_data[$_key] = $_val;
			}
		}
		
		//
		$_data = json_encode($_data);
		
		$_sql = "update costlimit set context=?";
		$ret = $this->m_Writer->exec($_sql,$_data);
		return $ret;
	}
	
}