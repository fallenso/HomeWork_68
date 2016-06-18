<?php
/**
 * 設定各功能 可用的 層級
 */
class TGod extends TIProtocol {
	
	private static $s_Instance;
	
	// ==========================================
	function __construct()
	{
		parent::__construct();
	}
	
	public static function GetInstance()
	{
		if (self::$s_Instance == null)
		{
			self::$s_Instance = new TGod();
		}
		return self::$s_Instance;
	}
	
	// ==========================================
	/**
	 * 取得各功能 可用權限資料
	 */
	public function GetGod()
	{
		$_sql = "select context from god";
		$this->m_Read->exec($_sql);
		$_bet = $this->m_Read->fetch();
		$_json = json_decode($_bet['context']);
		return $_json;
	}
	
	
	/**
	 * 更新資料
	 * @param $iPost : post 過來要更新的資料
	 */
	public function UPDateGod($iPost)
	{
		$_ar = [
			"default"=>$iPost['default'],
			"create"=>$iPost['create'],
			"del"=>$iPost['del'],
			"SetPage"=>$iPost['SetPage'],
			"PricePage"=>$iPost['PricePage'],
			"PrsPage"=>$iPost['PrsPage'],
		];
		$_data = json_encode($_ar);
		
		$_sql = "update god set context=?";	
		$this->m_Writer->exec($_sql,$_data);
	}
	
}