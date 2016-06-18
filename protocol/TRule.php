<?php
/**
 * 規則說明 頁面
 */
class TRule  extends TIProtocol {
	
	private $m_MenuFunction = '';	// 子頁面功能 : 預設 
	private $m_param; // 頁面所需的參數資料
	
	function __construct()
	{
		parent::__construct();
	}
	
	// =========================================
	//
	public function Main()
	{
		self::ShowBasic();
	}
	
	// ==========================================================================
	// 頁面控制
	/**
	 * 基本頁面
	 */
	private function ShowBasic()
	{
		
		// 設定要帶的參數
		$_param = [
				'token'=>TSecurity::encrypt(json_encode($this->m_data)),
				'page'=>"TRule",
		];
		TViewBase::Main('TRule/TRule', $_param);
	}
	
	
}