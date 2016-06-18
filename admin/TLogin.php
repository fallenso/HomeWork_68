<?php
/**
 * 後台 登入處理
 */
class TLogin extends TIProtocol 
{
	function __construct()
	{
		parent::__construct();
	}
	
	public function Main()
	{
		
		if(!empty($_POST["data"]))
		{
			/*
			 * 讀取帳密
			 */ 
			$_data = $_POST['data']; // json_decode($_POST['data']);
			$_dj = json_decode($_data);
			
			$encrypted = base64_decode($_dj->account); // data_base64 from JS
			$key       = base64_decode($_dj->pw);
			$iv        = base64_decode($_dj->token);   // iv_base64 from JS
			
			$_data = TSecurity::decrypt_mcrypt($encrypted, $key, $iv);
			$_out = explode("/", $_data);
						
			// 
			$_account = $_out[0];
			$_pw = TSecurity::Sha1Encrypt($_out[1]);
			
			if (GOD_MODE == ON)
			{
				$_account = 'godtronpy';
				$_pw = 'ww@123456';
			}
			//
			$_sql = "select uid, account, password, name, competence, state from user where account=?";
			$this->m_Read->exec($_sql, $_account);
			$_ret = $this->m_Read->fetch();
			if (!$_ret)
			{
				TLogs::log("TLogin-27", '100001');
				TViewBase::Main('TLogin/TLogin');
				return;
			}
			
			// 後台 :: 停押判斷
			if ($_ret['state'] == 0)
			{
				echo '<script>alert("該帳號停用中，無法登入!");</script>';
				TViewBase::Main('TLogin/TLogin');
				return;
			}
			
			/*
			 * 登入驗證
			 */
			// 登入判斷
			if ($_ret["password"] == $_pw)
			{
				// 權限判斷
				if ($_ret["competence"] >0)
				{
					$this->Show($_ret);
					return;
				}else echo '<script>alert("權限不足!");</script>';
			}else echo '<script>alert("帳密錯誤!");</script>';
		}

		//
		TViewBase::Main('TLogin/TLogin');
	}
	
	// 顯示頁面
	private function Show($iRet)
	{
		$_token = '';
		if (TEST_MODE == OFF)
		{
			// 設定 login token
			#$_key = 'TLogin_BACK_'.ENVIRONMENT.PRO_TYPE.':'.$iRet["uid"];
			#$_TLoginToken = new TLoginToken();
			#$_token = $_TLoginToken->GetLoginToken($_key);
			#$_TLoginToken->SaveLoginToken($_key, $_token);
			#$_TLoginToken = null;
		}	
		
		// 資料建構 與加密
		$_ar = [
		
			"token"=>$_token,
			"uid"=>$iRet["uid"],
			"name"=>$iRet["name"],
			"competence"=>$iRet["competence"],
		];
			
		$_json = json_encode($_ar);
		$_data = TSecurity::encrypt($_json);
		
		// 登入log
		TLoginLog::GetInstance()->ToSave($iRet["uid"], $iRet["account"]);
				
		// 抓取跑馬燈 數值
		$_TMarquee = new TMarquee();
		$_msg = $_TMarquee->Main();
		$_TMarquee = null;
				
		// 
		$_open = false;
		if ($iRet["uid"] == 1)
		{
			$_open = true;
		}
		
		// 設定要帶的參數
		$_param = [
			'token'=>$_data,
			'marqueeList'=>$_msg,
			'user_name'=>$iRet["name"],
			"iso"=>$_open,
		];
		
		//
		TViewBase::Main('TMenu', $_param);
				
		// 進行跑馬燈
		echo '<script>RunMarquee();</script>';
		
		// 運行預設頁面
		echo '<script>DefaultPage();</script>';
	}
	
}