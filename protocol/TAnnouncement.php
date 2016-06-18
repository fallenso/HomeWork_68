<?php
/**
 * 公告
 */
class TAnnouncement extends TIProtocol  {
	
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
		$this->ShowBasic();
	}
	
	// ==========================================================================
	// 頁面控制
	/**
	 * 基本頁面
	 */
	private function ShowBasic()
	{
		// 撈取資料
		$_sql = 'select id, context, time from marquee where state=1';
		$this->m_Read->exec($_sql);
		$_ret = $this->m_Read->fetchAll();
					
		$_list = array();
		foreach ($_ret as $val)
		{
			$_tmp = array();
			$_tmp['id'] = $val['id'];
			$_tmp['context'] = $val['context'];
			$_tmp['time'] = date('Y-m-d h:i:s', $val['time']);
			//
			array_push($_list, $_tmp);
		}
		
		// 設定要帶的參數
		$_param = [
				'token'=>TSecurity::encrypt(json_encode($this->m_data)),
				'page'=>"TAnnouncement",
				'competence'=>$this->m_data->competence,
				'list'=>$_list
		];
		TViewBase::Main('TAnnouncement/TAnnouncement', $_param);
	}
	
	// ==================================================================
	
	
}