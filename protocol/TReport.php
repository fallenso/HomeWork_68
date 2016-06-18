<?php
/**
 * 下注明細
 */
class TReport extends TIProtocol {
	
	private $m_Billing = null;
	private $m_param; // 頁面所需的參數資料
	
	function __construct()
	{
		parent::__construct();
		$this->m_Billing = new TBilling();
	}
	
	function __destruct()
	{
		$this->m_Billing = null;
	}
	
	// =========================================
	//
	public function Main($iData)
	{
		$this->m_data = $iData;
		
		if (isset($_POST['delbet']) && $_POST['delbet'] != '')
		{
			self::DelBet();
		}
		
		// 是否選擇列印
		if (isset($_POST['PrintSelect']))
		{
			self::SelectPrint();
		}
		
		//
		self::GetBilling();
		
		//
		$this->ShowBasic();
	}
	
	// =========================================
	//
	
	// 刪單
	private function DelBet()
	{
		$_id = $_POST['delbet'];
		
		//
		$_ret = $this->m_Billing->DelBet($_id);
		TLogs::log("TLogin-27", $_ret);
	}
	
	// 撈取玩家今日下注單
	private function GetBilling()
	{
		$game = self::SelectGame();
		
		$_now = time();
		$_st = strtotime(date ("Y-m-d 0:0:0", $_now));
		$_et = strtotime(date ("Y-m-d 23:59:59", $_now));
		
		if ($game == 99) // 選擇 顯示全部
		{			
			$_list = $this->m_Billing->GetBetByUserAndTime($this->m_data->uid, $_st, $_et);
		}   
		else 
		{				
			$_list = $this->m_Billing->GetBetByUserAndTime($this->m_data->uid, $_st, $_et, $game);
		}
				
		//
		$this->m_param['list'] = $_list;		
	}
	
	// 取出 選擇要列印的資料 處理
	private function SelectPrint()
	{
		/*
		 * 取出選的
		 */
		$_list = [];
		$_k = 'selp_';
		foreach ($_POST as $key=>$val)
		{
			$_ret = strpos($key, $_k);
			if ($_ret !== false)
			{
				array_push($_list, $val);
			}
		}
		
		// 取得這些的資料
		$_data = [];
		foreach ($_list as $val)
		{
			$_tmp = $this->m_Billing->GetBetByID($val);
			array_push($_data, $_tmp);
		}
		$_list = null;
				
		//
		$this->m_param['ShowPrintSelect'] = true;
		$this->m_param['PrintTime'] = date('Y/m/d H:i:s', time());
		$this->m_param['PrintUser'] = $this->m_data->name;
		$this->m_param['Printlist'] = $_data;
	}
	
	
	// 測試用下注清單
	private function TBet()
	{
		/*
		 * 撈取 所有期數 列表
		 */
		$_list = $this->m_Billing->GetTitleIdList();
	
		$id = (isset($_POST['select_title_id']))?$_POST['select_title_id']:$_list[0];
	
		/*
		 * 撈取 指定期數資料
		 */
		$_data = $this->m_Billing->GetBetDataByTitleId($id);
	
		//
		$this->m_param['list'] = $_list;
		$this->m_param['data'] = $_data;
		$this->m_param['selectid'] = $id;
	}
	
	// ==========================================================================
	// 基本頁面
	/**
	 * 基本頁面
	 */
	private function ShowBasic()
	{
		// 設定要帶的參數
		$this->m_param['token'] = TSecurity::encrypt(json_encode($this->m_data));
		$this->m_param['page'] = "TReport";
	
		//
		TViewBase::Main('TReport/TReport', $this->m_param);
	}
	
	// ==========================================================================
	// 頁面控制項
	
	// 遊戲選擇
	private function SelectGame()
	{
		$_game = (isset($_POST['select_game']))?$_POST['select_game']:99;
		$this->m_param['select_game'] = $_game;
		return $_game;
	}
	
}