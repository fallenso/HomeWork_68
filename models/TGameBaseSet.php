<?php
/**
 * 資料表 :game_set　相關處理
 */
class TGameBaseSet extends TIProtocol  {
	
	// ==========================================================================
	// 首頁
	function __construct()
	{
		parent::__construct();
	}
	
	// ==========================================================================
	
	/**
	 * 設定可刪單時間
	 * @param $iTime
	 * @return bool
	 */
	public function UPDateDelTime($iTime)
	{
		$_sql = "update game_set set del_time=?";
		$_ret = $this->m_Writer->exec($_sql, $iTime);
		//
		return $_ret;
	}
	
	/**
	 * 讀取 可刪單時間
	 * @return int (分)
	 */
	public function ReadDelTime()
	{
		$_sql = "select del_time from game_set";
		$this->m_Read->exec($_sql);
		$_ret = $this->m_Read->fetch();
		
		return $_ret['del_time'];
	}
	
	/**
	 * 設定全車相關參數值
	 * @param $iCar_limit_down
	 * @param $iCar_limit_top
	 * @param $iCar_price_down
	 * @param $iCar_price_top
	 * 
	 * @return bool
	 */
	public function UPDateAboutCar($iCar_limit_down, $iCar_limit_top, $iCar_price_down, $iCar_price_top)
	{
		$_sql = "update game_set set car_limit_down=?, car_limit_top=?, car_price_down=?,car_price_top=?";
		$_ret = $this->m_Writer->exec($_sql, $iCar_limit_down, $iCar_limit_top, $iCar_price_down, $iCar_price_top);
		
		return $_ret;
	}
	
	/**
	 * 讀取全車相關參數值
	 * @return data[car_limit_down, car_limit_top, car_price_down, car_price_top]
	 */
	public function ReadAboutCar()
	{
		$_sql = "select car_limit_down, car_limit_top, car_price_down, car_price_top from game_set";
		$this->m_Read->exec($_sql);
		$_bet = $this->m_Read->fetch();
		
		return $_bet;
	}
	
	// ==========================================================================
	
}