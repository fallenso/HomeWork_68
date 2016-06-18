<?php
/**
 * 聯絡
 */
class TConnection extends TIProtocol  {
	
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
		if (isset($_POST['active']))
		{
			$this->ToSend();
		}else $this->ShowBasic();
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
				'page'=>"TConnection",
		];
		TViewBase::Main('TConnection/TConnection', $_param);
	}
	
	/**
	 * 送出訊息
	 */
	private function ToSend()
	{		
		$_subject = '客戶問題表單_'.date('Ymd', time());
		$_body = '<p>忘記密碼的玩家資訊 ---<br/>'.
			      '暱稱: '.$_POST['name'].
			      '電話: '.$_POST['phome'].
			      '傳真: '.$_POST['fax'].
			      '電子信箱 : '.$_POST['mail'].
				  '內容:'.$_POST['context'].
			      '</p>';
		$_ret = TSendMail::SendMail(SERVICE_MAIL, '系統', $_subject, $_body);
		if (!$_ret)
		{
			echo '<script>alert("信件發送失敗！ ");</script>';
		}else echo '<script>alert("信件發送成功！ ");</script>';
		//
		$this->ShowBasic();
	}
	
	
}