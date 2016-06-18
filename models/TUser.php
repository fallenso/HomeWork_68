<?php
/**
 * 玩家相關資訊處理
 *
 */

class TUser extends TIProtocol {
	
	/**
	 * 玩家狀態 啟用
	 */
	const STATE_ON = 1;
	/**
	 * 玩家狀態 停押 : 針對最下級會員
	 */
	const STATE_LIMIT = 2;
	/**
	 * 玩家狀態 停用 : 針對 管理階層
	 */
	const STATE_STOP = 0;
	
	private $_error;
	
	//
	function __construct()
	{
		parent::__construct();
	}
	
	// ==========================================
	
	/**
	 * 判斷是否可進入前/後台 (帳號是否被停用)
	 * @param $iUid
	 * 
	 * @return bool
	 */
	public function IsLockAccount($iUid)
	{
		// 撈取帳號資料
		$_list = self::GetFatherList($iUid);
		$output = explode(",", $_list);
		array_push($output, $iUid);	// 加入自己
		
		// 去掉 0
		$_tmp = [];
		foreach ($output as $val)
		{
			if ($val != '' && $val != 0)
			{
				array_push($_tmp, $val);
			}
		}
		
		// 資料確認 是否有被停用的
		$_IsIn = true;
		foreach ($_tmp as $val)
		{
			if ($val != '')
			{
				$_sql = 'select state from user where uid=?';
				$this->m_Read->exec($_sql, $val);
				$_data = $this->m_Read->fetch();
					
				if ($_data['state'] == 0)
				{
					$_IsIn = false;
				}
			}
		}
		
		//
		return $_IsIn;
	}
	
	
	// ==========================================
	/**
	 * user_acct play type turn
	 * @param $iType : 玩法定義代碼
	 *
	 * @return string : user_acct 內的玩法欄位名稱
	 */
	public static function TurnAcctPlayType($iType)
	{
		switch($iType)
		{
			case PLAY_SP: // 1
				return 'sp';
			case PLAY_CAR: // 2
				return 'all_car';
			case PLAY_ST2: // 3
				return 'star_2';
			case PLAY_ST3: // 4
				return 'star_3';
			case PLAY_ST4: // 5
				return 'star_4';
			case PLAY_TW: // 6
				return 'tw';
			case PLAY_PN2: // 7
				return 'pong_2';
			case PLAY_PN3: // 8
				return 'pong_3';
			case PLAY_TWS: // 9
				return 'tawisan';
			default : 
				return null;
		}
	}
	
	// ==========================================
	
	/**
	 * 追查 指權限層級 的帳號
	 * @param $iComp
	 */
	public function GetCompetenceList($iComp)
	{
		// 撈取帳號資料
		$_sql = 'select uid from user where competence=?';
		$this->m_Read->exec($_sql, $iComp);
		$_data = $this->m_Read->fetchAll();
		
		//
		$_tmp=[];
		foreach ($_data as $val)
		{
			array_push($_tmp, $val['uid']);
		}
		
		return $_tmp;
	}
	
	/**
	 * 取得父帳號列表 直到公司 competence = 6 (不包含自己)
	 * @param $iUid
	 * 
	 * @return array
	 */
	public function GetFatherList($iUid)
	{
		
		// 撈取帳號資料
		$_sql = 'select father, competence from user where uid=?';
		$this->m_Read->exec($_sql, $iUid);
		$_data = $this->m_Read->fetch();
	
		//
		$_acc = $_data['father'];
		if ($_data['father'] == 0 || $_data['competence'] >= 5)
		{
			
		}
		else 
		{
			$_father = self::GetFatherList($_data['father']);
			if ($_father != '' && $_data['father'] != $_father)
			{
				$_acc .= ','.$_father;
			}
		}
		//
		return $_acc;
	}
	
	/**
	 * 取得玩家 總額度
	 * @param $iUid
	 */
	public function GetCreditLimit($iUid)
	{
		// 撈取帳號資料
		$_sql = 'select credit_limit, spend_credit from user where uid=?';
		$this->m_Read->exec($_sql, $iUid);
		$_data = $this->m_Read->fetch();
		return $_data;
	}
	
	/**
	 * 取得父帳號
	 * @param $iUid
	 */
	public function GetFatherUid($iUid)
	{
		// 撈取帳號資料
		$_sql = 'select father from user where uid=?';
		$this->m_Read->exec($_sql, $iUid);
		$_data = $this->m_Read->fetch();
		return $_data['father'];
	}
	
	/**
	 * 更換指定帳號 的父帳號
	 * @param $iUid
	 * @param $iFuid
	 */
	public function ChangeFather($iUid, $iFuid)
	{
		
		//
		$_sql = "update user set father=? where uid=?";
		$_ret = $this->m_Writer->exec($_sql, $iFuid, $iUid);
		if (false == $_ret) {
				
			$_msg = "TUser ChangeFather! :".$this->m_Writer->errorCode()."<br>\n";
			TLogs::log("TUser-ChangeFather", $_msg);
			return false;
		}
		return true;
	}
	
	/**
	 * 從帳號找人 取得uid
	 * @param $iAccount
	 * 
	 * @return 
	 */
	public function GetUidByAccount($iAccount)
	{
		// 撈取帳號資料
		$_sql = 'select uid, account, name from user where account=?';
		$this->m_Read->exec($_sql, $iAccount);
		$_data = $this->m_Read->fetch();
		return $_data;
	}
	
	/**
	 * 取得玩家 帳號 與名稱
	 * @param $iUid
	 */
	public function GetUserName($iUid)
	{
		// 撈取帳號資料
		$_sql = 'select account, name from user where uid=?';
		$this->m_Read->exec($_sql, $iUid);
		$_data = $this->m_Read->fetch();
		return $_data;
	}
	
	/**
	 * 修改玩家權限
	 * @param $iUid
	 * @param $iCompetence
	 */
	public function FixCompetence($iUid, $iCompetence)
	{
		$_sql = "update user set competence=? where uid=?";
		$_ret = $this->m_Writer->exec($_sql, $iCompetence, $iUid);
		return $_ret;
	}
	
	/** 
	 * 取得玩家帳號權限
	 * @param $iUid
	 */
	public function GetUserCompetence($iUid)
	{
		// 撈取帳號資料
		$_sql = 'select competence from user where uid=?';
		$this->m_Read->exec($_sql, $iUid);
		$_data = $this->m_Read->fetch();
		return $_data['competence'];
	}
	
	/*
	 * 更新玩家 密碼 與暱稱
	 * @param $iUid
	 * @param $iPw
	 * @param $iName
	 * @param $IsEncode : 是否加密
	 * 
	 * @return bool
	 */
	public function UPDateUserInfor($iUid, $iPw, $iName, $IsEncode = true)
	{
		
		$_pw = TSecurity::Sha1Encrypt($iPw);
		if ($iPw == '')	
		{
			$_sql = "update user set name=? where uid=?";
			$_ret = $this->m_Writer->exec($_sql, $iName, $iUid);
			return true;
		}

		//
		$_sql = "update user set password=?, name=? where uid=?";
		$_ret = $this->m_Writer->exec($_sql, $_pw, $iName, $iUid);
		if (false == $_ret) {
			
			$_msg = "TUser UPDateUserInfor! :".$this->m_Writer->errorCode()."<br>\n";
			TLogs::log("TUser-UPDateUserInfor", $_msg);
			return false;
		}
		return true;
	}
	
	// ===============================================================
	/**
	 * 修改玩家參數
	 * @param array $iParam: 參數陣列
	 * array 
	 * [
	 * 		password : 密碼
	 * 		name : 名稱
	 * 		money : 總額度
	 * 		pay_type : 付款方式
	 * 		
	 * 		lottery(六合彩) : [
	 * 			0 佔成 : [
	 * 				all_car:全車
	 * 				sp：特碼
	 * 				tw：台號
	 * 				tawisan：特尾三
	 * 				star_2：二星
	 * 				star_3：三星
	 * 				star_4：四星
	 * 				pong_2：天碰二
	 * 				pong_3：天碰三
	 * 			],
	 * 			1 退水..., 2單筆上限..., 3單號上限..., 4丟公司...
	 * 		],
	 * 		lotto (大樂透)... ,five(539)...
	 * ]
	 * 
	 * return	1 : 成功 
	 * 			2 : 帳號重複
	 */
	public function UPDateParam($iParam)
	{
		// 開始交易
		$ret = $this->m_Writer->beginTransaction();
		if (false == $ret) {
		
			$_msg = "beginTransaction fail! error code: ".$this->m_Writer->errorCode()."<br>\n";
			TLogs::log("TUser-UPDateParam", $_msg);
		
			$this->m_Writer->rollBack();
			return false;
		}
		
		// user
		$_ret = $this->UPDateUserInfor($iParam['uid'], $iParam['password'], $iParam['name'], false);
		if (false == $ret) {
				
			$_msg = "update user password / name! :".$this->m_Writer->errorCode()."<br>\n";
			TLogs::log("TUser-UPDateParam", $_msg);
				
			$this->m_Writer->rollBack();
			return false;
		}
		
		$_sql = "update user set credit_limit=?, spend_credit=?, pay_type=? where uid=?";
		$_ret = $this->m_Writer->exec($_sql, $iParam['money'], $iParam['spend_credit'], $iParam['pay_type'], $iParam['uid']);
		if (false == $ret) {
			
			$_msg = "update user ! :".$this->m_Writer->errorCode()."<br>\n";
			TLogs::log("TUser-UPDateParam", $_msg);
			
			$this->m_Writer->rollBack();
			return false;
		}
		
		$_ret = $this->FixCompetence($iParam['uid'], $iParam['competence']);
		if (false == $ret) {
				
			$_msg = "update user competence! :".$this->m_Writer->errorCode()."<br>\n";
			TLogs::log("TUser-UPDateParam", $_msg);
				
			$this->m_Writer->rollBack();
			return false;
		}
		
		// user_acct		
		$this->_error = true;
		
		// lottery
		$this->UPDateUserAcctVal($iParam['uid'], 0, 0, $iParam['lottery'][0]);	// 0: 佔成
		$this->UPDateUserAcctVal($iParam['uid'], 0, 1, $iParam['lottery'][1]);	// 1: 退水
		$this->UPDateUserAcctVal($iParam['uid'], 0, 2, $iParam['lottery'][2]);	// 2: 單筆上限 (二三四星以及天碰為「單碰上限」)
		$this->UPDateUserAcctVal($iParam['uid'], 0, 3, $iParam['lottery'][3]);	// 3: 單號上限 ( 正特碼雙面為「單場上限」；二三四星為「單組額度」。 )
		$this->UPDateUserAcctVal($iParam['uid'], 0, 4, $iParam['lottery'][4]);	// 4 : 丟公司
		$this->UPDateUserAcctVal($iParam['uid'], 0, 5, $iParam['lottery'][5]);	// 5 : 單邊

		// lotto
		$this->UPDateUserAcctVal($iParam['uid'], 1, 0, $iParam['lotto'][0]);
		$this->UPDateUserAcctVal($iParam['uid'], 1, 1, $iParam['lotto'][1]);
		$this->UPDateUserAcctVal($iParam['uid'], 1, 2, $iParam['lotto'][2]);
		$this->UPDateUserAcctVal($iParam['uid'], 1, 3, $iParam['lotto'][3]);
		$this->UPDateUserAcctVal($iParam['uid'], 1, 4, $iParam['lotto'][4]);
		$this->UPDateUserAcctVal($iParam['uid'], 1, 5, $iParam['lotto'][5]);
		
		// five
		$this->UPDateUserAcctVal($iParam['uid'], 2, 0, $iParam['five'][0]);
		$this->UPDateUserAcctVal($iParam['uid'], 2, 1, $iParam['five'][1]);
		$this->UPDateUserAcctVal($iParam['uid'], 2, 2, $iParam['five'][2]);
		$this->UPDateUserAcctVal($iParam['uid'], 2, 3, $iParam['five'][3]);
		$this->UPDateUserAcctVal($iParam['uid'], 2, 4, $iParam['five'][4]);
		$this->UPDateUserAcctVal($iParam['uid'], 2, 5, $iParam['five'][5]);
		
		if ($this->_error == false)
		{
			$_msg = "update fail! error code: ".$this->m_Writer->errorCode()."<br>\n";
			TLogs::log("TUser-UPDateParam", $_msg);
			$this->m_Writer->rollBack();
			return false;
		}
		
		// 交易完成
		$this->m_Writer->commit();
		return 1;
	}
	
	/**
	 * 取得玩家帳號資料 
	 * @param $iUid
	 * 
	 * @return array
	 * array 
	 * [
	 * 		uid
	 * 		father : 父帳號
	 * 		account : 帳號
	 * 		password : 密碼
	 * 		name : 名稱
	 * 		competence : 權限
	 * 		money : 總額度
	 * 		pay_type : 付款方式
	 * 		
	 * 		lottery(六合彩) : [
	 * 			0 佔成 : [
	 * 				all_car:全車
	 * 				sp：特碼
	 * 				tw：台號
	 * 				tawisan：特尾三
	 * 				star_2：二星
	 * 				star_3：三星
	 * 				star_4：四星
	 * 				pong_2：天碰二
	 * 				pong_3：天碰三
	 * 			],
	 * 			1 退水..., 2單筆上限..., 3單號上限..., 4單邊上限..., 5丟公司
	 * 		],
	 * 		lotto (大樂透)... ,five(539)...
	 * ]
	 */
	public function GetUserInfor($iUid)
	{
		$_result = array();
		
		// ------------------------------------------------
		// 撈取帳號資料
		$_sql = 'select * from user where uid=?';
		$this->m_Read->exec($_sql, $iUid);
		$_account = $this->m_Read->fetch();
				
		// 撈取參數資料 	0: 六合彩 (lottery); 1: 大樂透 (lotto); 2: 539 (five) 
		$_lottery = $this->GetAcct($iUid, 0);
		$_lotto = $this->GetAcct($iUid, 1);
		$_five = $this->GetAcct($iUid, 2);
		
		// ------------------------------------------------
		$_result['uid'] = $iUid;
		$_result['father'] = $_account['father'];
		$_result['account'] = $_account['account']; 
		$_result['password'] =  $_account['password'];
		$_result['name'] =  $_account['name'];
		$_result['competence'] =  $_account['competence'];
		$_result['money'] =  $_account['credit_limit'];
		$_result['spend_credit'] =  $_account['spend_credit'];
		$_result['pay_type'] =  $_account['pay_type'];
		
		$_result['lottery'] =  $_lottery;
		$_result['lotto'] =  $_lotto;
		$_result['five'] =  $_five;
		
		//
		return $_result;
	}
	
	/**
	 * 刪除使用者
	 * @param $iUid
	 * 
	 * @return bool
	 */
	public function DelUser($iUid)
	{
		// 清除 user
		$_sql  = "delete from user where uid=?";
		$_ret = $this->m_Writer->exec($_sql, $iUid);
		if (!$_ret)
		{
			TLogs::log("TUser-DelUser", "del user is fail.");
			return false;
		}
		
		// 清除 user_acct
		$_sql  = "delete from user_acct where uid=?";
		$_ret = $this->m_Writer->exec($_sql, $iUid);
		if (!$_ret)
		{
			TLogs::log("TUser-DelUser", "del user is fail.");
			return false;
		}
		
		//
		return true;
	}
	
	/**
	 * 尋找該帳號的 所屬直系 子帳號 (包含自己)
	 * @param $iUid : 搜尋起點的主帳號
	 * 
	 * @return string uid 1,uid 2,... 
	 */
	public function FindSubAccAll($iUid)
	{
		$_sql = 'select uid, competence from user where father=?';
		$this->m_Read->exec($_sql, $iUid);
		$_find = $this->m_Read->fetchAll();
		
		//
		$_acc = $iUid;
		foreach ($_find as $val)
		{
			if ($val['competence'] != 0 )
			{
				$_tmp = $this->FindSubAccAll($val['uid']);
				$_acc .= ','.$_tmp;
			}
			else
			{
				$_acc.= ','.$val['uid'];
			}
		}
		//
		return $_acc;
	}
		
	/**
	 * 尋找 下層一級 子帳號列表
	 * @param $iUid : 母帳號帳號
	 * 
	 * @return string uid 1,uid 2,... 
	 */
	public function FindSub($iUid)
	{
		$_sql = 'select uid, account, name, competence from user where father=?';
		$this->m_Read->exec($_sql, $iUid);
		$_list = $this->m_Read->fetchAll();
		
		//
		return $_list;
	}
	
	/**
	 * 更新玩家 狀態  啟用/停押/停用
	 * @param $iUid : user id
	 * @param $iState [const] : state code
	 * 
	 * @return bool
	 */
	public function UpdateState($iUid, $iState)
	{
		$_sql = "update user set state=? where uid=?";
		$_ret = $this->m_Writer->exec($_sql, $iState, $iUid);
		return (!$_ret)?false:true;
	}
	
	/**
	 * 創建帳號
	 * @param array : 參數陣列
	 * array 
	 * [
	 * 		father : 父帳號
	 * 		account : 帳號
	 * 		password : 密碼
	 * 		name : 名稱
	 * 		competence : 權限
	 * 		money : 總額度
	 * 		pay_type : 付款方式
	 * 		
	 * 		lottery(六合彩) : [
	 * 			0 佔成 : [
	 * 				all_car:全車
	 * 				sp：特碼
	 * 				tw：台號
	 * 				tawisan：特尾三
	 * 				star_2：二星
	 * 				star_3：三星
	 * 				star_4：四星
	 * 				pong_2：天碰二
	 * 				pong_3：天碰三
	 * 			],
	 * 			1 退水..., 2單筆上限..., 3單號上限..., 4丟公司..., 5單邊
	 * 		],
	 * 		lotto (大樂透)... ,five(539)...
	 * ]
	 * 
	 * return	1 : 成功 
	 * 			2 : 帳號重複
	 */
	public function CreateUser($iParam)
	{
		// ---------------------------------------------------------------------------------------
		// 檢查
		$_sql = "select account from user where account=? or name=?";
		$this->m_Read->exec($_sql, $iParam['account'], $iParam['name']);
		$_ret = $this->m_Read->rowCount();
		if ($_ret!= 0)
		{
			TLogs::log("TUser-CreateUser", "repeat account.");
			return 2;
		}
		
		// ---------------------------------------------------------------------------------------
		// 創建基本資料表
		$ret = $this->m_Writer->beginTransaction();
		if (false == $ret) {
		
			$_msg = "beginTransaction fail! error code: ".$this->m_Writer->errorCode()."<br>\n";
			TLogs::log("TUser-CreateUser", $_msg);
		
			$this->m_Writer->rollBack();
			return false;
		}
		
		// create user
		$_sql = 'insert into user (account, password, name, competence, credit_limit, father, pay_type, time)
				 values (?, ?, ?, ?, ?, ?, ?, ?)';
		$_ret = $this->m_Writer->exec($_sql, $iParam['account'], $iParam['password'], $iParam['name']
				, $iParam['competence'], $iParam['money'], $iParam['father'], $iParam['pay_type'], time());
		if (!$_ret)
		{
			TLogs::log("TUser-CreateUser", "insert into user is fail.");
			return false;
		}
		
		// 取得玩家uid
		$_uid = $this->m_Writer->lastInsertId();
		
		// create user_acct :: 0: 六合彩 (lottery); 1: 大樂透 (lotto); 2: 539 (five)
		// lottery 0: 佔成; 1: 退水; 2: 單筆上限; 3: 單號上限; 4: 丟公司
		$_ret = $this->CreateUserAcctIndex($_uid);
		if (!$_ret)
		{
			$_msg = "CreateUserAcctIndex fail! uid=".$_uid." <br>\n";
			TLogs::log("TUser-CreateUser", $_msg);
			$this->m_Writer->rollBack();
			return false;
		}
		
		$this->_error = true;
		// lottery
		$this->UPDateUserAcctVal($_uid, 0, 0, $iParam['lottery'][0]);
		$this->UPDateUserAcctVal($_uid, 0, 1, $iParam['lottery'][1]);
		$this->UPDateUserAcctVal($_uid, 0, 2, $iParam['lottery'][2]);
		$this->UPDateUserAcctVal($_uid, 0, 3, $iParam['lottery'][3]);
		$this->UPDateUserAcctVal($_uid, 0, 4, $iParam['lottery'][4]);
		$this->UPDateUserAcctVal($_uid, 0, 5, $iParam['lottery'][5]);
		
		// lotto
		$this->UPDateUserAcctVal($_uid, 1, 0, $iParam['lotto'][0]);
		$this->UPDateUserAcctVal($_uid, 1, 1, $iParam['lotto'][1]);
		$this->UPDateUserAcctVal($_uid, 1, 2, $iParam['lotto'][2]);
		$this->UPDateUserAcctVal($_uid, 1, 3, $iParam['lotto'][3]);
		$this->UPDateUserAcctVal($_uid, 1, 4, $iParam['lotto'][4]);
		$this->UPDateUserAcctVal($_uid, 1, 5, $iParam['lotto'][5]);
		
		// five
		$this->UPDateUserAcctVal($_uid, 2, 0, $iParam['five'][0]);
		$this->UPDateUserAcctVal($_uid, 2, 1, $iParam['five'][1]);
		$this->UPDateUserAcctVal($_uid, 2, 2, $iParam['five'][2]);
		$this->UPDateUserAcctVal($_uid, 2, 3, $iParam['five'][3]);
		$this->UPDateUserAcctVal($_uid, 2, 4, $iParam['five'][4]);
		$this->UPDateUserAcctVal($_uid, 2, 5, $iParam['five'][5]);
		
		if ($this->_error == false)
		{
			$_msg = "update fail! error code: ".$this->m_Writer->errorCode()."<br>\n";
			TLogs::log("TUser-UPDateUserAcctVal", $_msg);
			$this->m_Writer->rollBack();
			return false;
		}
		
		// 交易完成
		$this->m_Writer->commit();
		return 1;
	}
	
	// ===========================================================
	/**
	 * 通過檢查 : 是否超過單邊上限
	 * @param $iUid		:
	 * @param $iTitle : 遊戲期數
	 * @param $iGame	:遊戲名稱
	 * @param $iType	:玩法
	 * @param $iBet : 下注金額
	 * 
	 * @return bool true : 沒超過
	 * 				false :超過
	 */
	public function CheckUnilateralLimit($iUid, $iTitle, $iGame, $iType, $iBet)
	{
		// 統計 目前該玩法的下注總金額
		
		$_TBilling = new TBilling();
		$_sums = $_TBilling->GetAllBetByPlay($iTitle, $iType);
		$_TBilling = null;
		
		$_bet = $_sums + $iBet;
		
		// 撈取 該玩家的 各層父帳號
		$_list = self::GetFatherList($iUid);
		$_list = explode(",", $_list);
		
		// 取得各帳號的 單邊上限值
		$_limit = [];
		foreach ($_list as $val)
		{
			$_tmp = $this->GetAcctDesignation($iGame, $val, 5, $iType);
			if ($_tmp != 0 && $_bet >= $_tmp)
			{
				return false;
			}
		}
		return true;
	}

	/**
	 * 撈取 指定玩家 指定遊戲 指定玩法 的 佔成值
	 * @param $iUid		:
	 * @param $iGame	:遊戲名稱
	 * @param $iType	:玩法
	 * 
	 * @return floot
	 */
	public function GetCost($iUid, $iGame, $iType)
	{
		/*
		// 判斷查的人 是否為總監層級 如是 則所佔有的成數 應為 100
		$_competence = self::GetUserCompetence($iUid);
		if ($_competence >= 5)
		{
			return 100;
		}
		*/
		// 撈取資料
		$_name = self::TurnAcctPlayType($iType);
		$_sql = 'select '.$_name.' from user_acct where uid=? and type=? and attributes=0';
		$this->m_Read->exec($_sql, $iUid, $iGame);
		$_data = $this->m_Read->fetch();
		//
		$_cost = $_data[$_name]; #TTools::LenTurnFloot(strlen($_data[$_name]));
		return $_cost;
	}
	
	/**
	 * 撈取 指定玩家 指定遊戲 指定玩法 的 賺水率
	 * @param $iUid		:
	 * @param $iGame	:遊戲名稱
	 * @param $iType	:玩法
	 *
	 * @return floot
	 */
	public function GetRefunded($iUid, $iGame, $iType)
	{
		
		// 撈取資料
		$_name = self::TurnAcctPlayType($iType);
		$_sql = 'select '.$_name.' from user_acct where uid=? and type=? and attributes=1';
		$this->m_Read->exec($_sql, $iUid, $iGame);
		$_data = $this->m_Read->fetch();

		//
		$_cost = $_data[$_name]; 
		return $_cost;
	}
	
	/**
	 * 各項指定 取得 
	 * @param $iGame : 遊戲
	 * @param $iUid
	 * @param $iAttributes : 屬性 0: 佔成; 1: 退水; 2: 單筆上限; 3: 單號上限 ; 4: 丟公司 5:單邊
	 * @param $iType : 玩法
	 */
	public function GetAcctDesignation($iGame, $iUid, $iAttributes, $iType)
	{
		$_playtype = self::TurnAcctPlayType($iType);
		
		// 撈取 設定資訊
		$_sql = "select ".$_playtype."
				 from user_acct
				 where uid=? and type=? and attributes=?";
		$this->m_Read->exec($_sql, $iUid, $iGame, $iAttributes);
		$_data = $this->m_Read->fetch();
		
		//
		return $_data[$_playtype];
	}
	
	/**
	 * 撈取 完整的 指定帳號 所有參數資料
	 * @param $iUid
	 * @param $iType : 分類;  0: 六合彩 (lottery); 1: 大樂透 (lotto); 2: 539 (five) 
	 * 
	 * @return array
	 * [
	 * 		0 佔成 : [
	 * 			all_car:全車
	 * 			sp：特碼
	 * 			tw：台號
	 * 			tawisan：特尾三
	 * 			star_2：二星
	 * 			star_3：三星
	 * 			star_4：四星
	 * 			pong_2：天碰二
	 * 			pong_3：天碰三
	 * 		],
	 * 		1 退水..., 2單筆上限..., 3單號上限..., 4丟公司, 5單邊
	 * ]
	 */
	public function GetAcct($iUid, $iType)
	{
		$_return = array();
		$_return[0] = $this->GetAcctSub($iUid, $iType, 0);
		$_return[1] = $this->GetAcctSub($iUid, $iType, 1);
		$_return[2] = $this->GetAcctSub($iUid, $iType, 2);
		$_return[3] = $this->GetAcctSub($iUid, $iType, 3);
		$_return[4] = $this->GetAcctSub($iUid, $iType, 4);
		$_return[5] = $this->GetAcctSub($iUid, $iType, 5);
		//
		return $_return;
	}
	
	/**
	 * 撈取 指定的資料
	 * @param $iUid
	 * @param $iType : 分類;  0: 六合彩 (lottery); 1: 大樂透 (lotto); 2: 539 (five)
	 * @param $iAttributes : 屬性 0: 佔成; 1: 退水; 2: 單筆上限; 3: 單號上限 ; 4: 丟公司 5: 單邊
	 *
	 * @return array
	 * [
	 * 		all_car:全車
	 * 		sp：特碼
	 * 		tw：台號
	 * 		tawisan：特尾三
	 * 		star_2：二星
	 * 		star_3：三星
	 * 		star_4：四星
	 * 		pong_2：天碰二
	 * 		pong_3：天碰三
	 * 	]
	 */
	public function GetAcctSub($iUid, $iType, $iAttributes)
	{		
		// 撈取 設定資訊
		$_sql = "select all_car, sp, tw, tawisan, star_2, star_3, star_4, pong_2, pong_3
				 from user_acct
				 where uid=?  and type=? and attributes=?";
		$this->m_Read->exec($_sql, $iUid, $iType, $iAttributes);
		$_data = $this->m_Read->fetch();
		//
		return $_data;
	}
		
	// ===========================================================
	/**
	 * 創建 user_acct 數值
	 */
	private function CreateUserAcctIndex($iUid)
	{
		$_sql = "insert into user_acct (uid, type, attributes) values (?, ?, ?)";
		//
		// user_acct :: 0: 六合彩 (lottery); 1: 大樂透 (lotto); 2: 539 (five)
		for($i = 0; $i<3; ++$i)
		{
			// 0: 佔成; 1: 退水; 2: 單筆上限; 3: 單號上限; 4: 丟公司 5: 單邊
			for($j = 0; $j<6; ++$j)
			{
				$_ret = $this->m_Writer->exec($_sql, $iUid, $i, $j);
				if (!$_ret)
				{
					return false;
				}
			}
		}
		//
		return true;
	}
	
	/**
	 * 創建/修改  user_acct 數值
	 */
	private function UPDateUserAcctVal($iUid, $iType, $iAttributes, $iParam)
	{		
		$_sql = 'update user_acct 
				 set 
					all_car=?,
					sp=?,
					tw=?,
					tawisan=?,
					star_2=?,
					star_3=?,
					star_4=?,
					pong_2=?,
					pong_3=?
				 where uid=? and type=? and attributes=?';
		
		$_ret = $this->m_Writer->exec($_sql, 
				$iParam['all_car'], $iParam['sp'], $iParam['tw'],
				$iParam['tawisan'], $iParam['star_2'], $iParam['star_3'], $iParam['star_4'],
				$iParam['pong_2'], $iParam['pong_3'], 
				$iUid, $iType, $iAttributes
				);
		if (!$_ret)
		{
			$this->_error = false;
			return false;
		}
	}
	
	// ===========================================================
	//

	
	
	
}