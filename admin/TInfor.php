<?php
/**
 * 個人資訊
 */
class TInfor extends TIProtocol  {
	
	private $m_MenuFunction = 'BaseInfor';	// 子頁面功能 : 預設 基本資料頁面
	private $m_param; // 頁面所需的參數資料
	
	// ==========================================================================
	// 首頁
	function __construct()
	{
		parent::__construct();
	}
	
	//
	public function Main($iData) 
	{
		$this->m_data = $iData;
		
		//
		$_fun = $this->m_MenuFunction = (isset($_POST['MenuFunction']))?$_POST['MenuFunction']:$this->m_MenuFunction;
		$this->$_fun();
		$this->ShowBasic();
	}
	
	// ==========================================================================
	// 頁面控制
	/**
	 * 玩家基本資料
	 */
	private function BaseInfor()
	{
		if (isset($_POST['set']))
		{
			// user
			$_TUser = new TUser();
			$_ret = $_TUser->UPDateUserInfor($this->m_data->uid, $_POST['password'], $_POST['name']);
			$_TUser = null;
			
			$_msg = (false == $_ret)?"資料更新失敗！":"資料更新成功！";
			echo '<script>alert("'.$_msg.'");</script>';
		}

		//
		$_sql = 'select	account, name, password, competence, pay_type, credit_limit from user where uid=?';
		$this->m_Read->exec($_sql, $this->m_data->uid);
		$_ret = $this->m_Read->fetch();
		$this->m_param['data'] = $_ret;
	}
	
	/**
	 * 玩家六合彩資料
	 */
	private function LotteryInfor()
	{
		$_TUser = new TUser();
		$_data = $_TUser->GetAcct($this->m_data->uid, 0);
		$_TUser = null;
		
		//
		$this->m_param['data'] = $_data;
	}
	
	/**
	 * 玩家大樂透資料
	 */
	private function LottoInfor()
	{
		$_TUser = new TUser();
		$_data = $_TUser->GetAcct($this->m_data->uid, 1);
		$_TUser = null;
		
		//
		$this->m_param['data'] = $_data;
	}
	
	/**
	 * 玩家539資料
	 */
	private function FiveInfor()
	{
		$_TUser = new TUser();
		$_data = $_TUser->GetAcct($this->m_data->uid, 2);
		$_TUser = null;
		
		//
		$this->m_param['data'] = $_data;
	}
	
	/**
	 * 基本頁面
	 */
	private function ShowBasic()
	{
		// 設定要帶的參數
		$this->m_param['token'] = TSecurity::encrypt(json_encode($this->m_data));
		$this->m_param['page'] = "TInfor";
		$this->m_param['Menu'] = $this->m_MenuFunction;
		$this->m_param['SubPagePath'] = TViewBase::GetPagePath('TInfor/'.$this->m_MenuFunction);
	
		//
		TViewBase::Main('TInfor/TInfor', $this->m_param);
	}
}