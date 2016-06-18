<?php
/**
 * 設定權限頁面
 */
class TGP  extends TIProtocol {
	
	function __construct()
	{
		parent::__construct();
	}
	
	// ==========================================
	public function Main()
	{
		if (isset($_POST['updategp']))
		{
			TGod::GetInstance()->UPDateGod($_POST);
			echo '<script>alert("ok");</script>';
		}
		
		// 顯示頁面
		$this->ShowBasic();
	}
	
	// ==========================================================================
	// 基本功能
	/**
	 * 基本頁面
	 */
	private function ShowBasic()
	{
		// 設定要帶的參數
		$this->m_PageParam['token'] = TSecurity::encrypt(json_encode($this->m_data));
		$this->m_PageParam['page'] = "TGP";
		$this->m_PageParam['menu'] = $this->m_MenuFunction;
		//
		TViewBase::Main('TGod/TGod', $this->m_PageParam);
	}
	
}