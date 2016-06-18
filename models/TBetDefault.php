<?php
/**
 * 各遊戲 每期預設設定值 規範
 */
class TBetDefault extends TIProtocol  {
	
	function __construct()
	{
		parent::__construct();
	}
	
	// ===============================================
	/**
	 * 資料更新
	 */
	public function ToUPDate($idata)
	{
		$_lottery = $this->DeData($idata, Lottery);
		$_lotto = $this->DeData($idata, Lotto);
		$_five = $this->DeData($idata, Five);
		
		//
		// 開始交易
		$ret = $this->m_Writer->beginTransaction();
		if (false == $ret) {
		
			$_msg = "beginTransaction fail! error code: ".$this->m_Writer->errorCode()."<br>\n";
			TLogs::log("TBetDefault-ToUPDate", $_msg);
				
			$this->m_Writer->rollBack();
			return false;
		}
		
		$_sql = "update bet_default set context=? where gametype=?";
		$_ret = $this->m_Writer->exec($_sql, $_lottery, Lottery);
		if (false == $_ret) {
		
			$_msg = "write fail! :".$this->m_Writer->errorCode()."<br>\n";
			TLogs::log("TBetDefault-ToUPDate", $_msg);
			$this->m_Writer->rollBack();
			return false;
		}
		
		$_sql = "update bet_default set context=? where gametype=?";
		$_ret = $this->m_Writer->exec($_sql, $_lotto, Lotto);
		if (false == $_ret) {
		
			$_msg = "write fail! :".$this->m_Writer->errorCode()."<br>\n";
			TLogs::log("TBetDefault-ToUPDate", $_msg);
			$this->m_Writer->rollBack();
			return false;
		}
		
		$_sql = "update bet_default set context=? where gametype=?";
		$_ret = $this->m_Writer->exec($_sql, $_five, Five);
		if (false == $_ret) {
		
			$_msg = "write fail! :".$this->m_Writer->errorCode()."<br>\n";
			TLogs::log("TBetDefault-ToUPDate", $_msg);
			$this->m_Writer->rollBack();
			return false;
		}
		
		// 交易完成
		$this->m_Writer->commit();
		return true;
	}
	
	/**
	 * 讀取全部
	 */
	public function ToReadAll()
	{
		$_sql = "select * from bet_default";
		$this->m_Read->exec($_sql);
		$_data = $this->m_Read->fetchAll();
		
		// 資料拆解
		$_ar = array();
		foreach($_data as $val)
		{
			if ($val['context'] != null)
			{
				$_tmp = json_decode($val['context'], true);
				$_ar += $_tmp;
			}
		}
		//
		return $_ar;
	}
	
	/**
	 * 讀取指定遊戲的資料
	 * @param $iGame [int]: gametype
	 */
	public function ToRead($iGame = 0)
	{
		$_sql = "select * from bet_default where gametype=?";
		$this->m_Read->exec($_sql, $iGame);
		$_data = $this->m_Read->fetch();
		
		// 資料拆解
		$_de = array();
		if ($_data['context'] != null)
		{
			$_de = json_decode($_data['context'], true);
		}
		return $_de;
	}
	
	// ===============================================
	// 
	
	// 拆解資料
	private function DeData($iData, $iGame = 0)
	{
		$_str = '0_';
		if ($iGame == 0)
		{
			$_str = '0_';
		}
		else if ($iGame == 1)
		{
			$_str = '1_';
		}
		else if ($iGame == 2)
		{
			$_str = '2_';
		}
		
		//
		$_ar = array();
		foreach ($iData as $key=>$val)
		{
			$_ret = strpos($key, $_str);
			if ($_ret !== false)
			{
				$_ar[$key] = $val;
			}
		}
		
		$_js = json_encode($_ar);
		return $_js;
	}
	
	
}