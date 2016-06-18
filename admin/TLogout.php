<?php
/**
 * 登出處理
 */
class TLogout extends TIProtocol {
	
	// ==========================================================================
	// 首頁
	function __construct()
	{
		parent::__construct();
	}
	
	//
	public function Main($iData)
	{
		// login token 消滅
		#$_key = 'TLogin_BACK:'.$iData->uid;
		#$this->m_Redis->delete($_key);
	}
}