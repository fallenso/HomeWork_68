<?php
/**
 * 下注報表
 */
class TReportAward extends TIProtocol {
	
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
	
	// 撈取玩家今日下注單
	private function GetBilling()
	{
		$_title = self::SelectTitle();
		
		//
		$_list = $this->m_Billing->GetTReportAward($_title, $this->m_data->uid);
		$this->m_param['list'] = $_list;
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
		$this->m_param['page'] = "TReportAward";
	
		//
		TViewBase::Main('TReportAward/TReportAward', $this->m_param);
	}
	
	// ==========================================================================
	// 頁面控制項
	
	// 期數選擇
	private function SelectTitle()
	{
		/*
		 * 取得已開獎的期數
		 */
		$_TAwardControl = new TAwardControl();
		$_data = $_TAwardControl->GetAwardedList();
		$_TAwardControl = null;
		
		$_betlist = [];
		$_gamename = '';
		foreach ($_data as $val)
		{
			$_k = $val['id'];
				
			if ($val['type'] == 0)
			{
				$_gamename = '六合彩';
			}
			else if ($val['type'] == 1)
			{
				$_gamename = '大樂透';
			}
			else
			{
				$_gamename = '539';
			}
				
			$_v = $val['id'].'['.$_gamename.']'.' '.date('Y-m-d', $val['time']);
			$_betlist[$_k] = $_v;
		}
		$this->m_param['betlist'] = $_betlist;
		
		//
		$_title = (isset($_POST['select_title']))?$_POST['select_title']:$_data[0]['id'];
		$this->m_param['select_title'] = $_title;
		return $_title;
	}
	
}