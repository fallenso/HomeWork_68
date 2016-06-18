<?php
/**
 * 前台 登入處理
 */
class TLogin extends TIProtocol 
{
	function __construct()
	{
		parent::__construct();
	}
	
	public function Main()
	{
		// 是否收到帳密資料
		if(!empty($_POST["data"]))
		{
			$_haserror = false;
			
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
			$_sql = "select * from user where account=?";
			$this->m_Read->exec($_sql, $_account);
			$_ret = $this->m_Read->fetch();
			if (!$_ret)
			{
				$_haserror = "帳號不存在";
			}
			
			// 前台 :: 停押判斷
			if ($_ret['state'] == 2)
			{
				$_haserror = "該帳號停押中，無法登入!";
			}
			
			if ($_ret['state'] == 0)
			{
				$_haserror = "該帳號停用中，無法登入!";
			}
			
			/*
			 * 判斷是否為會員層級
			 */
			if ($_ret["competence"] != 0)
			{
				$_haserror = "只有會員才可登入!";
			}
			
			/*
			 * 登入驗證
			 */
			if ($_ret["password"] != $_pw)
			{
				$_haserror = "帳密錯誤!";
			}
			
			/*
			 * 登入驗證
			 */
			// 登入判斷
			if ($_haserror == false)
			{
				// 權限判斷
				$this->Show($_ret);
				return;
			}
			else
			{
				echo '<script>alert("'.$_haserror.'");</script>';
			}
		}
		//
		TViewBase::Main('TLogin/TLogin', array('IsLogin'=>true));
	}
	
	// 顯示頁面
	private function Show($iRet)
	{
		$_token = '';
		if (TEST_MODE == OFF)
		{
			// 設定 login token
			#$_key = 'TLogin_Front_'.ENVIRONMENT.PRO_TYPE.':'.$iRet["uid"];
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
				"account"=>$iRet["account"],
				"credit_limit"=>$iRet["credit_limit"],
				"pay_type"=>$iRet["pay_type"],
		];
			
		$_json = json_encode($_ar);
		$_data = TSecurity::encrypt($_json);
		
		// 取得上次登入時間
		$_loginmsg = TLoginLog::GetInstance()->ToReadLast($iRet["uid"]);
		
		// 登入log
		TLoginLog::GetInstance()->ToSave($iRet["uid"], $iRet["account"]);
	
		// 前台 抓取跑馬燈 數值
		$_TMarquee = new TMarquee();
		$_msg = $_TMarquee->Main();
		$_TMarquee = null;
		
		// 權限設定
		$_competence = ($iRet["competence"] >= 6)?true:false;
		
		// 撈取 今天有哪些遊戲有開
		$_gameState = self::GetGameState();
		
		// 設定要帶的參數
		$_param = [
				'token'=>$_data,
				'marqueeList'=>$_msg,
				'user_name'=>$iRet["name"],
				'lotteryData'=>$_gameState['lottery'],
				'lottoData'=>$_gameState['lotto'],
				'fiveData'=>$_gameState['five'],
				"credit_limit"=>$iRet["credit_limit"],
				"pay_type"=>$iRet["pay_type"],
				"spend_credit"=>$iRet["credit_limit"] - $iRet["spend_credit"],
				'loginmsg'=>$_loginmsg,
		];
		TViewBase::Main('TMenu', $_param);
			
		// 運行預設頁面
		echo '<script>DefaultPage();</script>';
		
		// 跑馬燈 更新開始 StartMarqueeUPData
		echo '<script>StartMarqueeUPData();</script>';
	}
	
	/**
	 * 取得遊戲  今天有沒有開 / 開獎時間... 設定
	 * 
	 * @return array['lottery'=>{isOpen=>bool , isUse=>bool, start=>time, end=>time, award=>time}]
	 */ 
	private function GetGameState()
	{
		$_now = time();
		$_st = strtotime(date ("Y-m-d 0:0:0", $_now));
		$_et = strtotime(date ("Y-m-d 23:59:59", $_now));
		
		$_TBet = new TBet();
		$_lottery = $_TBet->GetBetListByTimeInterval(Lottery, $_st, $_et);
		$_lotto = $_TBet->GetBetListByTimeInterval(Lotto, $_st, $_et);
		$_five = $_TBet->GetBetListByTimeInterval(Five, $_st, $_et);
		$_TAwardControl = null;
		
		$_gameState = array();
		
		// lottery 處理
		$_tmp =array();
		if (count($_lottery) > 0)
		{
			$_tmp['isOpen'] = true;
			$_tmp['start'] = $_lottery[0]['start_time'];
			$_tmp['end'] = $_lottery[0]['end_time'];
			$_tmp['award'] = $_lottery[0]['award_time'];
			
			$_tmp['isUse'] = ($_lottery[0]['start_time'] <= $_now && $_now < $_lottery[0]['end_time'] )?true:false;
			
		}else $_tmp['isOpen'] = false;
		
		$_gameState['lottery'] = $_tmp;
		
		// lotto 處理
		$_tmp =array();
		if (count($_lotto) > 0)
		{
			$_tmp['isOpen'] = true;
			$_tmp['start'] = $_lotto[0]['start_time'];
			$_tmp['end'] = $_lotto[0]['end_time'];
			$_tmp['award'] = $_lotto[0]['award_time'];
			$_tmp['isUse'] = ($_lotto[0]['start_time'] <= $_now && $_now < $_lotto[0]['end_time'] )?true:false;
				
		}else $_tmp['isOpen'] = false;
		
		$_gameState['lotto'] = $_tmp;
		
		// $_five 處理
		$_tmp =array();
		if (count($_five) > 0)
		{
			$_tmp['isOpen'] = true;
			$_tmp['start'] = $_five[0]['start_time'];
			$_tmp['end'] = $_five[0]['end_time'];
			$_tmp['award'] = $_five[0]['award_time'];
			$_tmp['isUse'] = ($_five[0]['start_time'] <= $_now && $_now < $_five[0]['end_time'] )?true:false;
				
		}else $_tmp['isOpen'] = false;
		
		$_gameState['five'] = $_tmp;
		
		//
		return $_gameState;
	}
	
}
