<?php
/**
 * TBilling 相關
 */
class TBilling extends TIProtocol
{
	private $m_PlayAward = null;	// 中獎計算 物件
		
	function __construct()
	{
		parent::__construct();
		$this->m_PlayAward = new TPlayAward();
	}
	
	function __destruct()
	{
		$this->m_PlayAward = null;
	}
	
	// =========================================
	// 報表相關
	
	/**
	 * 取得 有下注的期數列表
	 */
	public function GetTitleIdList()
	{
		
		$_sql = 'select DISTINCT(id) from bet';
		$this->m_Read->exec($_sql);
		$_data = $this->m_Read->fetchAll();
		
		// 
		$_return = array();
		foreach ($_data as $val)
		{
			array_push($_return, $val['id']);
		}
		//
		return $_return;
	}
	
	/**
	 * 取得 指定遊戲 的下注列表 
	 * @param $iGame
	 */
	public function GetBetDataByGame($iGame)
	{
		$_sql = 'select DISTINCT(id), award_time from bet where gametype=?';
		$this->m_Read->exec($_sql, $iGame);
		$_data = $this->m_Read->fetchAll();
		
		//
		$_return = array();
		foreach ($_data as $val)
		{
			$_tmp = array();
			#array_push($_return, $val['id'].' ['.date('Y/m/d', $val['award_time']).']');
			$_tmp['id'] = $val['id'];
			$_tmp['time'] = ' ['.date('Y/m/d', $val['award_time']).']';
			array_push($_return, $_tmp);
		}
		//
		return $_return;
	}
	
	/**
	 * 取得 指定時間內的 指定玩家 的 所有下注資料 (前台報表用)
	 * 
	 * @param $iUid 
	 * @param $iSt
	 * @param $iEt
	 * @param $iGame : default null
	 */
	public function GetBetByUserAndTime($iUid, $iSt, $iEt, $iGame = "null")
	{
		if ($iGame == "null")
		{
			$_sql = "select * from billing where uid=? and time>=? and time<? and checkout=0";
			$this->m_Read->exec($_sql, $iUid, $iSt, $iEt);
		}
		else 
		{
			$_sql = "select * from billing where uid=? and time>=? and time<? and type=? and checkout=0";
			$this->m_Read->exec($_sql, $iUid, $iSt, $iEt, $iGame);
		}
		//
		$_data = $this->m_Read->fetchAll();

		//
		$_return = array();
		foreach ($_data as $val)
		{
			// 時間 中文化
			$val['time'] = ' ['.date('Y/m/d H:i:s', $val['time']).']';
			
			// 調盤資訊
			$_tmp = json_decode($val['save_price'], true);
			$_tmpstr = '= 調漲:<br/>';
			foreach ($_tmp as $tpval)
			{
				$_tmpstr .= '[ 號碼 '.$tpval['num'].':漲'.$tpval['case'].' ]<br/>';
			}
			$val['save_price'] = $_tmpstr;
			
			// 固定賠 資訊
			$_tmp = json_decode($val['save_groups'], true);
			$_tmpstr = '= 固定賠:<br/>';
			foreach ($_tmp as $tpval)
			{
				$_tmpstr .= '[ 號碼 '.$tpval['num'].', '.$tpval['bonbs'].'碰以下，中'.$tpval['bonus'].' ]<br/>';
			}
			$val['save_groups'] = $_tmpstr;
			
			//
			array_push($_return, $val);
		}
		//
		return $_return;
	}
	
	// =========================================
	
	/**
	 * 統計指定期數 玩法 的總下注金額
	 * @param $iTitleID : 指定期數
	 * @param $iType : 指定玩法
	 * 
	 * @return bet
	 */
	public function GetAllBetByPlay($iTitleID, $iType)
	{
		
		$_sql = 'select sum(bet) as sums from billing where title_id=? and play=?';
		$this->m_Read->exec($_sql, $iTitleID, $iType);
		$_data = $this->m_Read->fetchAll();
		
		if ($_data == false)
		{
			return 0;
		}
		//
		if (isset($_data['sums']) == true)
		{
			return $_data['sums'];
		}
		else 
		{
			return 0;
		}
	}
	
	/**
	 * 取得 指定玩法 / 期數  的 下注列表 
	 * @param $iType : 指定玩法
	 * @param $iID : 指定期數
	 */
	public function GetBetDataByTypeAndTitleId($iType, $iID)
	{
		$_sql = 'select * from billing where play=? and title_id=?';
		$this->m_Read->exec($_sql, $iType, $iID);
		$_data = $this->m_Read->fetchAll();
		
		//
		return $_data;
	}
	
	/**
	 * 取得指定期數的下注資料
	 * @param $iTitleID : 指定期數
	 */
	public function GetBetDataByTitleId($iTitleID)
	{
		$_sql = 'select * from billing where title_id=?';
		$this->m_Read->exec($_sql, $iTitleID);
		$_data = $this->m_Read->fetchAll();
		
		$_turn = array();
		foreach ($_data as $val)
		{
			$_tmp = array();
			foreach($val as $key=>$val2)
			{
				$_tmp[$key] = ($key == 'time')?date('Y/m/d H:i:s', $val2):$val2;
			}
			array_push($_turn, $_tmp);
		}
		//
		return $_turn;
	}
	
	/**
	 * 取得指定期數 與 玩家 的下注資料
	 * @param $iTitleID : 指定期數
	 * @param $iUid : 指定使用者的id
	 */
	public function GetBetByUser($iTitleID, $iUid)
	{
		$_sql = 'select * from billing where title_id=? and uid=?';
		$this->m_Read->exec($_sql, $iTitleID, $iUid);
		$_data = $this->m_Read->fetchAll();
		
		//
		return $_data;
	}
	
	/**
	 * TReportAward 專用 : 統計 每期資料
	 */
	public function GetTReportAward($iTitleID, $iUid)
	{
		$_sql = 'select sum(bet) as bets, sum(refunded) as refundeds, sum(prize_sum) as prizes, sum(win) as wins
				 from billing where title_id=? and uid=? and is_del=0 and checkout!=0';
		$this->m_Read->exec($_sql, $iTitleID, $iUid);
		$_data = $this->m_Read->fetchAll();
	
		//
		return $_data;
	}
	
	/**
	 * 指定  玩家/期數/玩法  的下注單資料
	 * @param $iTitleID : 指定期數
	 * @param $iUid : 指定使用者的id
	 * @param $iType : 玩法
	 */
	public function GetSingleByPlay($iTitleID, $iUid, $iType = '')
	{
		if ($iType == '')
		{
			$_sql = "select * from billing where title_id=? and uid=?";
			$this->m_Read->exec($_sql, $iTitleID, $iUid);
		}
		else 
		{
			$_sql = "select * from billing where title_id=? and uid=? and play=?";
			$this->m_Read->exec($_sql, $iTitleID, $iUid, $iType);
		}
		
		$_data = $this->m_Read->fetchAll();
		
		$_turn = array();
		foreach ($_data as $val)
		{
			$_tmp = array();
			foreach($val as $key=>$val2)
			{
				$_tmp[$key] = ($key == 'time')?date('Y/m/d H:i:s', $val2):$val2;
			}
			array_push($_turn, $_tmp);
		}
		//
		return $_turn;
	}
	
	/**
	 * 複合指定玩法 的下注單資料
	 * @param $iTitleID : 指定期數
	 * @param $iUid : 指定使用者的id
	 * @param $iTypeAr : 玩法
	 */
	public function GetBetByComplexPlay($iTitleID, $iUid, $iTypeAr)
	{
		$_sql = "select * from billing where title_id=? and uid=?";
		
		if (count($iTypeAr) >0)
		{
			$_sql .= ' and (';
		}
			
		$_str = '';
		foreach ($iTypeAr as $val)
		{
			$_str .= ($_str == '')?' play='.$val:' or play='.$val;
		}
		
		if (count($iTypeAr) >0)
		{
			$_sql .= $_str;
			$_sql .= ' )';
		}
		
		
		$this->m_Read->exec($_sql, $iTitleID, $iUid);
		$_data = $this->m_Read->fetchAll();
		
		// 時間轉換
		$_turn = array();
		foreach ($_data as $val)
		{
			$_tmp = array();
			foreach($val as $key=>$val2)
			{
				$_tmp[$key] = ($key == 'time')?date('Y/m/d H:i:s', $val2):$val2;
			}
			array_push($_turn, $_tmp);
		}
		//
		
		return $_turn;
	}
	
	
	/**
	 * 指定  玩家/期數/玩法  統計資料
	 * @param $iTitleID : 指定期數
	 * @param $iUid : 指定使用者的id
	 * @param $iType : 玩法
	 */
	public function GetBetByPlay($iTitleID, $iUid, $iType)
	{
		$_sql = "select 
					sum(bet) as bets, 
					sum(refunded) as refundeds, 
					sum(prize_sum) as prize_sums, 
					sum(win) as wins, 
					sum(cost) as costs, 
					sum(tax) as taxs, 
					sum(rall) as ralls
				 from billing where title_id=? and uid=? and play=?";
		$this->m_Read->exec($_sql, $iTitleID, $iUid, $iType);
		$_data = $this->m_Read->fetch();
		
		//
		return $_data;
	}
	
	/**
	 * 指定 時間區段  / 遊戲 / 玩法 取得下注單
	 * @param $iGame
	 * @param $iType
	 * @param $iSTime
	 * @param $iETime 
	 */
	public function GetBeyByTime($iGame, $iType, $iSTime, $iETime)
	{
		$_sql = "select * from billing where type=? and play=? and time>=? and time<?";
		$this->m_Read->exec($_sql, $iGame, $iType, $iSTime, $iETime);
		$_data = $this->m_Read->fetchAll();
		
		return $_data;
	}
	
	/**
	 * 取得指定 單號的資料
	 * @param $iID
	 */
	public function GetBetByID($iID)
	{
		$_sql = "select * from billing where id=?";
		$this->m_Read->exec($_sql, $iID);
		$_data = $this->m_Read->fetch();
		return 	$_data;	
	}
	
	// =========================================
	// 下注相關
	
	/**
	 * 進行下注
	 * @param $iData : array
	 * [
			uid : 流水號 / 玩家ID
			name: 玩家暱稱
			title_id: 下注期號  - 該期期號
			time:下注時間 - 時間戳
			game:分類 - 0: 六合彩 (lottery); 1: 大樂透 (lotto); 2: 539 (five)
			type:玩法 - 1. 特碼 (單號) 2: 全車 (單號碰全) 3: 二星 4: 三星 5: 四星 6: 台號 (單號) 7: 天碰二  8: 天碰三 9: 特尾三 (單號)
			num:號碼 - num_1,num_2 x num_1,num_2,…
			bet:下注金額
			price :當前本金
			multiple: 當前賠率
			spplay : 特殊玩法名稱
	 * ]
	 * 
	 * @return bool
	 */
	public function ToBet($iData)
	{
		/*
		 * 計算 是否有特殊玩法
		 */
		$_spplay = '';
		if (isset($iData['spplay']))
		{
			$_spplay = $iData['spplay'];
		}
		
		/*
		 * 下注金額
		 */
		$_betmoney = $iData['bet'];
		
		// ------------------------------------------------------------------
		// 計算總共下幾支
		// 玩法取得 反射成執行方法名稱 ::
		// 1.特碼 2:全車 3:二星4:三星5:四星6:台號7:天碰二8:天碰三9:特尾三
		$_num = $this->m_PlayAward->GetCombNums($iData['game'], $iData['num'], $iData['type'], $_spplay);
		
		/*
		 * 檢查
		 * 1. 是否還可下注 (超過下注時間)
		 * 2. 檢查單筆上限
		 * 3. 檢查單號上限
		 */ 
		
		$_tret = $this->CheckBetTime($iData['title_id']);
		if (!$_tret)
		{
			$imsg = TError::GetError("300004");
			echo '<script>alert("'.$imsg.'");</script>';
			return false;
		}
		
		// 234星 / 天碰23  特殊處理
		$_singcheck = $_betmoney;
		if ($iData['type'] == PLAY_ST2 || $iData['type'] == PLAY_ST3 || $iData['type'] == PLAY_ST4 
			|| $iData['type'] == PLAY_PN2 || $iData['type'] == PLAY_PN3 )
		{
			$_singcheck = $_betmoney / $_num;
		}
		
		$_cSing = $this->CheckSingOver($iData['uid'], $iData['game'], $iData['type'], $_singcheck);
		if (!$_cSing)
		{
			if ($iData['type'] == PLAY_ST2 || $iData['type'] == PLAY_ST3 || $iData['type'] == PLAY_ST4
					|| $iData['type'] == PLAY_PN2 || $iData['type'] == PLAY_PN3 )
			{
				$imsg = TError::GetError("300008");
			}
			else 
			{
				$imsg = TError::GetError("300001");
			}
			
			echo '<script>alert("'.$imsg.'");</script>';
			return false;
		}
		
		// 單組限額 :: 
		if ($iData['type'] == PLAY_PN2 || $iData['type'] == PLAY_PN3 )
		{
			
		}
		else if ($iData['type'] == PLAY_ST2 || $iData['type'] == PLAY_ST3 || $iData['type'] == PLAY_ST4)
		{
			$_singcheck = $_betmoney / $_num;	// 單號價格
			
			//
			$_TCheckNumOver = new TCheckNumOver();
			$_cNum = $_TCheckNumOver->Main($iData['uid'], $iData['game'], $iData['type'], $iData['title_id'], $iData['num'], $_singcheck, $_spplay);
			$_TCheckNumOver = null;
			if (!$_cNum)
			{
				return false;
			}
		}
		else 
		{
			//
			$_TCheckNumOver = new TCheckNumOver();
			$_cNum = $_TCheckNumOver->Main($iData['uid'], $iData['game'], $iData['type'], $iData['title_id'], $iData['num'], $_singcheck, $_spplay);
			$_TCheckNumOver = null;
			if (!$_cNum)
			{
				return false;
			}
		}
		
		/*
		 * 額度增減 :: 信用制 增加數值; 付款制 扣減額度
		 */
		$_ret = $this->CreditLimit($iData['uid'], $_betmoney);
		if (!$_ret)
		{
			$imsg = TError::GetError("300003");
			echo '<script>alert("'.$imsg.'");</script>';
			return false;
		}
				
		/*
		 * 計算退水 
		 */
		// 下注總金額 - 退水值
		$_RecessionMon = $_betmoney * (100 - $iData['price']) / 100;
		
		// ------------------------------------------------------------------
		//		
		/*
		 * 開始 寫入下注資料 
		 */ 
		$_sql = 'insert into billing 
					(
						uid,
						name,
						title_id,
						time,
						type,
						play,
						num,
						bet,
						fews,
						refunded, 
						price, 
						multiple,
						sprule
					) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';
		$_ret = $this->m_Writer->exec($_sql, $iData['uid'], $iData['name'], $iData['title_id'], $iData['time'], 
				$iData['game'], $iData['type'], $iData['num'], $_betmoney, $_num, $_RecessionMon, $iData['price'], $iData['multiple'], $_spplay);
		if (!$_ret)
		{
			TLogs::log("TBilling-ToBet", "insert bet is fail.".$this->m_Writer->errorCode());
			return false;
		}
		
		// ------------------------------------------------------------------
		// 寫入當時調盤資訊
		$_billingID = $this->m_Writer->lastInsertId();
		
		//
		$_TPrs = new TPrs();
		$_save_pr = $_TPrs->GetPrices($iData['title_id'], $iData['type']);
		$_TPrs = null;
		$_js_pr = json_encode($_save_pr);
		
		$_TBetGP = new TBetGP();
		$_save_Quota = $_TBetGP->GetQuota($iData['title_id'], $iData['type']);
		$_TBetGP = null;
		$_js_qu = json_encode($_save_Quota);
		
		$_sql = "update billing set save_price=?, save_groups=? where id=?";
		$_ret = $this->m_Writer->exec($_sql, $_js_pr, $_js_qu, $_billingID);
		if (!$_ret)
		{
			TLogs::log("TBilling-ToBet", "update save_price/save_groups is fail.".$this->m_Writer->errorCode());
			return false;
		}
		
		//
		return true;
	}
	
	/**
	 * 銷單
	 * @param $betid : 要銷的單號
	 * 
	 * @return ierror
	 */
	public function DelBet($betid)
	{
		// 檢查
		// 是否超過最後下注時間
		// 是否超過 該單下注15分後 超過不能銷單
		// 額度 要加回去
		
		// 取出該張資料
		$_sql = "select uid, title_id, time, bet from billing where id=?";
		$this->m_Read->exec($_sql, $betid);
		$_data = $this->m_Read->fetch();
		
		// 取出該期的最後下注時間
		$_TBet = new TBet();
		$_title_data = $_TBet->FindIndex($_data['title_id']);
		$_title_data = $_title_data[0];
		$_TBet = null;
		
		// 是否超過最後下注時間
		$_now = time();
		if ($_title_data['end_time'] <= $_now)
		{
			return "300004";
		}
		
		// 是否超過 該單下注15分後 超過不能銷單
		$_TGameBaseSet = new TGameBaseSet();
		$_del_time = $_TGameBaseSet->ReadDelTime();
		$_del_timeSec = $_del_time * 60;
		$_TGameBaseSet = null;
		
		$_time = $_now - $_data['time'];
		if ($_time >= $_del_timeSec)
		{
			return "300005";
		}
		
		// 加回額度
		$_ret = self::CreditLimit($_data['uid'], $_data['bet'], false);
		if (!$_ret)
		{
			return "300006";
		}
		
		// 刪單
		$_sql = 'delete from billing where id=?';
		$_ret = $this->m_Writer->exec($_sql, $betid);
		if (!$_ret)
		{
			return '300007';
		}
		
		// 清除father
		$_TBillingFather = new TBillingFather();
		$_TBillingFather->Del($betid);
		$_TBillingFather = null;
		
		
		return '000000';
	}
	
	/**
	 * 後台強刪單
	 */
	public function StrongDelBet($betid)
	{
		// 檢查
		// 額度 要加回去
		
		// 取出該張資料
		$_sql = "select uid, title_id, time, bet from billing where id=?";
		$this->m_Read->exec($_sql, $betid);
		$_data = $this->m_Read->fetch();
				
		// 加回額度
		$_ret = self::CreditLimit($_data['uid'], $_data['bet'], false);
		if (!$_ret)
		{
			return false;
		}
		
		// 刪單
		$_sql = 'delete from billing where id=?';
		$_ret = $this->m_Writer->exec($_sql, $betid);
		if (!$_ret)
		{
			return false;
		}
		
		// 清除father
		$_TBillingFather = new TBillingFather();
		$_TBillingFather->Del($betid);
		$_TBillingFather = null;
		
		//
		return true;
	}
	
	
	/**
	 * 強硬刪除
	 * @param $betid : 要銷的單號
	 */
	public function ToughDel($betid)
	{
		// 刪單
		$_sql = 'delete from billing where id=?';
		$_ret = $this->m_Writer->exec($_sql, $betid);
		if (!$_ret)
		{
			return false;
		}
		
		// 清除father
		$_TBillingFather = new TBillingFather();
		$_TBillingFather->Del($betid);
		$_TBillingFather = null;
		
		//
		return true;
	}
		
	/**
	 * 中獎計算 :: 結算
	 * @param $iTitleID : 期號
	 * 
	 * @return bool
	 */
	public function BetCheckOut($iTitleID)
	{
		// 開始寫入的交易模式
		$ret = $this->m_Writer->beginTransaction();
		if (false == $ret) {
		
			$_msg = "beginTransaction fail! error code: ".$this->m_Writer->errorCode()."<br>\n";
			TLogs::log("TBilling-BetCheckOut", $_msg);
		
			$this->m_Writer->rollBack();
			return false;
		}
		
		/*
		 * 撈取 該期的 號碼相關資訊
		 */
		$_TAwardControl = new TAwardControl();
		$_data = $_TAwardControl->FindIndex($iTitleID);
				
		// 取得整理過後的資料
		if ($_data['type'] == Lottery)
		{
			$_awardd = TAwardControl::LotterySingle($_data, $_data['type']);
		}else if ($_data['type'] == Lotto)
		{
			$_awardd = TAwardControl::LotterySingle($_data, $_data['type']);
		}else if ($_data['type'] == Five)
		{
			$_awardd = TAwardControl::FiveSingle($_data);
		}
		
		/*
		 * 撈取所有的下注資料 進行計算
		 */
		$_betList = $this->GetBetDataByTitleId($iTitleID);
		foreach($_betList as $val)
		{
			$_ret = $this->CheckAward($val, $_awardd);
			if (!$_ret)
			{
				$this->m_Writer->rollBack();
				return false;
			}
		}
		
		/*
		 * 計算完成 更新 award 該期資料 顯示帳務計算完成
		 */
		$_TAwardControl->UPDateCheckOut($iTitleID);
		$_TAwardControl = null;
		
		// 交易完成
		$this->m_Writer->commit();
		
		//
		return true;
	}
	
	
	/**
	 * 檢查是否超過單筆上限 - 單筆下注的金額上限 (234星/天碰 是看幾隻)
	 * @param $iUid
	 * @param $iGame
	 * @param $iType [const]
	 * @param $iMoney
	 * 
	 * @return bool
	 */
	public function CheckSingOver($iUid, $iGame, $iType, $iMoney)
	{
		
		// 取得玩法名稱 (代碼 轉換成 sql 內的欄位名稱)
		$_acct_play_name = TUser::TurnAcctPlayType($iType);
		if ($_acct_play_name == null)
		{
			TLogs::log('TBilling-CheckSingOver', 'not find play type. type='.$iType);
			return false;
		}
		
		// 撈取sql 資料
		$_sql = 'select '.$_acct_play_name.' from user_acct where uid=? and type=? and attributes=2';
		$_ret = $this->m_Read->exec($_sql, $iUid, $iGame);
		if (false == $_ret) {
				
			$_msg = "message :".$this->m_Read->errorCode()."<br>\n";
			TLogs::log("TBilling-CheckSingOver", $_msg);
			return false;
		}
		$_money = $this->m_Read->fetch();
		
		if ($_money[$_acct_play_name] == 0) return true;
		return ($iMoney > $_money[$_acct_play_name])?false:true;
	}
	
	
	/**
	 * 檢查是否超過單號上限 - 單個號碼可收的最大金額.
	 * @param $iUid
	 * @param $iGame
	 * @param $iType [const]
	 * @param $iId: 旗號
	 * @param $Nums : 號碼組合 - num_1,num_2 x num_1,num_2,…
	 * @param $iMoney
	 * @param $ispplay : 特殊玩法
	 * 
	 * @return bool
	 */
	public function CheckNumOver($iUid, $iGame, $iType, $iId, $Nums, $iMoney, $ispplay)
	{
		// 取得玩法名稱 (代碼 轉換成 sql 內的欄位名稱)
		$_acct_play_name = TUser::TurnAcctPlayType($iType);
		if ($_acct_play_name == null)
		{
			TLogs::log('TBilling-CheckNumOver', 'not find play type. type='.$iType);
			return false;
		}
		
		// 撈取sql 資料
		$_sql = 'select '.$_acct_play_name.' from user_acct where uid=? and type=? and attributes=3';
		$_ret = $this->m_Read->exec($_sql, $iUid, $iGame);
		if (false == $_ret) {
		
			$_msg = "[user_acct] message :".$this->m_Read->errorCode()."<br>\n";
			TLogs::log("TBilling-CheckNumOver", $_msg);
			return false;
		}
		$_setMon = $this->m_Read->fetch();
		if ($_setMon[$_acct_play_name] == 0) return true;
		
		/*
		 * 遊戲算法分類
		 */
		if ($iType == PLAY_ST2 || $iType == PLAY_ST3 || $iType == PLAY_ST4 )
		{
			$_outData = array();
			
			// 找出目前有下的單
			$_data = self::GetSingleByPlay($iId, $iUid, $iType);
			
			// 拆出下注單組合
			foreach ($_data as $val)
			{
				$_unitBet = $val['bet'] / $val['fews']; // 計算每碰單價 sprule
				$_tmp = TPlayAward::GetSort($val['play'], $val['sprule'], $val['num']); // 取得號碼組合
				foreach ($_tmp as $grs)
				{
					if (isset($_outData[$grs]))
					{
						$_outData[$grs] += $_unitBet;
					}
					else
					{
						$_outData[$grs] = $_unitBet;
					}
				}
			}
			
			// 拆出 玩家當前下單組合
			$_tmp = TPlayAward::GetSort($iType, $ispplay, $Nums);
			
			// 求出該號碼組合 的 下注金額
			foreach ($_outData as $key=>$val)
			{
				foreach($_tmp as $tn)
				{
					if ($key == $tn)
					{
						// 判斷是否超過
						$_tmo = $iMoney + $val;
						// 單次收的號碼 最高可收上限
						if ($_tmo > $_setMon[$_acct_play_name])
						{
							
							$imsg = $key.'超過單組限額';
							echo '<script>alert("'.$imsg.'");</script>';
							return false;
						}
					}
				}
			}
		}
		else 
		{
			// 計算目前下注的總金額
			$_sql = 'select sum(bet) as bets from billing where uid=? and title_id=? and num=? and play=?';
			$_ret = $this->m_Read->exec($_sql, $iUid, $iId, $Nums, $iType);
			if (false == $_ret) {
			
				$_msg = "[billing] message :".$this->m_Read->errorCode()."<br>\n";
				TLogs::log("TBilling-CheckNumOver", $_msg);
				return false;
			}
			$_oldMoney = $this->m_Read->fetch();
			$_titMon = $_oldMoney['bets'] + $iMoney;
			
			// 單次收的號碼 最高可收上限
			if ($_titMon > $_setMon[$_acct_play_name])
			{
				
				$imsg = $key.'超過單號上限';
				echo '<script>alert("'.$imsg.'");</script>';
				return false;
			}
		}
		//
		return true;
	}
	
	/**
	 * 下注/銷單 增減額度
	 * @param $iUid
	 * @param $iMoney
	 * @param $iIsBet : 下注: true; 銷單:false
	 * 
	 * @return bool
	 */
	public function CreditLimit($iUid, $iMoney, $iIsBet = true)
	{
		/*
		 * 撈取 該玩家額度資料
		 */ 
		$_sql = 'select pay_type, credit_limit, spend_credit from user where uid=?';
		$_ret = $this->m_Read->exec($_sql, $iUid);
		if (false == $_ret) {
		
			$_msg = "[user] message :".$this->m_Read->errorCode()."<br>\n";
			TLogs::log("TBilling-CreditLimit", $_msg);
			return false;
		}
		$_userData = $this->m_Read->fetch();
		
		// 
		/*
		 * 計算出要更新的數值 
		 * 1. 信用制 :: // 下注狀態-  :: 0315 變更為不會增加
		 * 2. 信用制 :: // 銷單狀態- :: 0315 變更為不會扣減
		 * 3. 付款制 :: // 下注狀態-
		 * 4. 付款制 :: // 銷單狀態-
		 */
		$_num = 0;
		if ($_userData['pay_type'] == 1 && $iIsBet == true) $_num = 0; //$_userData['credit_limit'] + $iMoney;
		else if ($_userData['pay_type'] == 1 && $iIsBet == false) $_num = 0; //$_userData['credit_limit'] - $iMoney;
		else if ($_userData['pay_type'] == 2 && $iIsBet == true) $_num = $_userData['spend_credit'] + $iMoney;
		else if ($_userData['pay_type'] == 2 && $iIsBet == false) $_num = $_userData['spend_credit'] - $iMoney;
		else return false;
		
		// 檢查 ::　付款制 :: 下注狀態-扣減數值　－ 數值是否會小於0
		if ($_userData['pay_type'] == 2 && $iIsBet == true && $_num > $_userData['credit_limit'])
		{
			return false;
		}
		
		/*
		 * 更新額度值
		 */ 
		$_sql = "update user set spend_credit=? where uid=?";
		$_ret = $this->m_Writer->exec($_sql, $_num, $iUid);
		if (false == $_ret) {
		
			$_msg = "write fail! :".$this->m_Writer->errorCode()."<br>\n";
			TLogs::log("TBilling-CreditLimit", $_msg);
			return false;
		}
		
		//
		return true;
	}
	
	
	/**
	 * 檢查 當前時間 是否還可以進行下注
	 * @param $iBetID : 旗號
	 * 
	 * @reutnr bool
	 */
	public function CheckBetTime($iBetID)
	{
		/*
		 * 取得 資料設定的時間
		 */
		$_sql = "select end_time from bet where id=?";
		$this->m_Read->exec($_sql, $iBetID);
		$_dataTime = $this->m_Read->fetch();
		
		/*
		 * 取得當前時間
		 */
		$_now = time();
		
		/*
		 * 比對
		 */
		return ($_dataTime['end_time'] > $_now)?true:false;
	}
	
	/**
	 * 計算各階層 要負擔的 成本 / 小計 / 實際金額
	 * @param $iUid
	 * @param $iGame
	 * @param $iType
	 * @param $iBet
	 * @param $iWin
	 *
	 * - 佔成 (實際輸贏 * 自己的佔成) = 自己要賠多少 或是贏多少
	 * - 小計 - 上繳金額 實際輸贏*(1-自己佔成)
	 * - 實際總量 - 下注總金額 * (1-自己佔成)
	 */
	public function PlayCost($iUid, $iGame, $iType, $iBet, $iWin)
	{
		// 3. 佔成 (實際輸贏 * 自己的佔成) = 自己要賠多少 或是贏多少
		$_TUser = new TUser();
		$_cost = $_TUser->GetCost($iUid, $iGame, $iType) / 100;
		$_selfCost = $iWin * $_cost;
	
		// 4. 小計 - 上繳金額 實際輸贏*(1-自己佔成)
		$_tax = $iWin * (1-$_cost);
	
		// 5. 實際總量 - 下注總金額 * (1-自己佔成)
		$_rAll = $iBet * (1-$_cost);
	
		$_return =
		[
				'cost'=>$_selfCost,
				'tax'=>$_tax,
				'rall'=>$_rAll
		];
		//
		return $_return;
	}
	
	// =======================================================
	//
	
	/**
	 * 確認是否有中獎 中多少 及更新 資料庫
	 * @param $iBet :　下注單 [billing 全格式]
	 * @param $iAward : 中獎獎號 整理過的資料
	 * 
	 * @return bool
	 * 
	 * 	prize_sum : 0:無中獎
		win : 實際輸贏多少 - 下注金額 - 中獎金額 - 退水 [10000-7200-2620 = 180]
		cost : 佔成分配 :: 自己賠多少 賺多少 [實際輸贏 * 自己的佔成] 180 *0.5 = 90
		tax : 上繳金額 實際輸贏*(1-自己佔成) 180* (1-0.5) = 90
	 * 
	 */
	private function CheckAward($iBet, $iAward)
	{		
		/*
		 * 結帳前檢查
		 */
		/*
		// 確認是否已結過帳
		$_sql = 'select checkout from billing where id=?';
		$this->m_Read->exec($_sql, $iBet['id']);
		$_check = $this->m_Read->fetch();
		if ($_check['checkout'] == true)
		{
			return true;
		}
		*/
		// 清除father
		$_TBillingFather = new TBillingFather();
		$_TBillingFather->Del($iBet['id']);
		$_TBillingFather = null;
		
		/*
		 * 開始結帳
		 */	
		// 玩法取得 反射成執行方法名稱 ::
		// 1.特碼 2:全車 3:二星4:三星5:四星6:台號7:天碰二8:天碰三9:特尾三
		$_playCode = $iBet['play'];
		$_fun = TUser::TurnAcctPlayType($_playCode);
		
		// 執行該玩法　計算 :: 計算中幾隻
		$_ggn = '';	// 中獎號碼資料 (字串)
		$_ret = 0;	// 中幾隻
		if ($_playCode == PLAY_ST2 || $_playCode == PLAY_ST3 || $_playCode == PLAY_ST4
				|| $_playCode == PLAY_PN2 || $_playCode == PLAY_PN3)
		{
			// 判斷是否有特殊玩法
			if ($iBet['sprule'] == 'IsStarStraightPong')	// 連柱碰玩法
			{
				$_group = $this->m_PlayAward->Star3StraightPong($iBet, $iAward);
				$_groupNum = $_group['group'];
				$_ret = $_group['fews'];
			}
			else	// 一般玩法 
			{	
				$_group = $this->m_PlayAward->$_fun($iBet, $iAward);
				$_groupNum = $_group['group'];
				$_ret = $_group['fews'];
			}		
						
			if ($_ret > 0)
			{
				if ($_playCode == PLAY_PN2 || $_playCode == PLAY_PN3)
				{
					$_ggn .= $iAward['num_sp'];
				}
				
				foreach ($_groupNum as $i)
				{
					if (!$_ggn == '')	$_ggn .= 'x';
					//
					foreach ($i as $j)
					{
						$_ggn .= ($_ggn == '')?$j:','.$j;
					}
				}
			}
		}
		else 
		{
			$_ret = $this->m_PlayAward->$_fun($iBet, $iAward);
			if ($_ret > 0)
			{
				$_ggn = $iBet['num'];
			}
		}
		
		// 更新 中獎號碼
		$_sql = "update billing	set	award_num=?	where id=?";
		$this->m_Writer->exec($_sql, $_ggn, $iBet['id']);
		
		// 計算前 先檢查 有無固定賠
		// 計算賭金
		$_winMoney = 0;
		
		$_betgp_param = [
				
			'title_id'=>$iBet['title_id'],
			'play'=>$iBet['play'],
			'nums_str'=>$_ggn,
			'sbet'=>$iBet['bet'] / $iBet['fews'],
			'multiple'=>$iBet['multiple'],
			'fews'=>$iBet['fews'],
			'win_fews'=>$_ret,
		];
		
		$_TBetGP = new TBetGP();
		$_winMoney = $_TBetGP->GetBonus($_betgp_param);
		$_TBetGP = null;
				
		// 2. 實際輸贏 下注總額 - 退水 - 中獎金額
		$_getWin = $iBet['bet'] - $iBet['refunded'] - $_winMoney;
		
		// 3. 佔成 :: 客戶無佔成
		$_selfCost = 0;
		
		// 4. 上繳實際總量/小計 - 應該上繳/拿 的金額　:: 實際輸贏 
		$_rAll = $_tax = $_getWin;
				
		// 更新下注表單
		$_sql = "update billing 
				 set 
				 	prize_sum=?,
					checkout=?,
					win=?,
					cost=?,
					tax=?,
					rall=?
				 where id=?";
		$_ret = $this->m_Writer->exec($_sql, $_winMoney, true, $_getWin, $_selfCost, $_tax, $_rAll, $iBet['id']);
		if (false == $_ret) {
				
			$_msg = "update is fail! :".$this->m_Writer->errorCode()."<br>\n";
			TLogs::log("TBilling-CheckAward", $_msg);
			return false;
		}
		
		// 儲存 billing_father		
		// 計算父階層 的 佔成 / 小計 / 實際金額 並記錄
		$_TUser = new TUser();
		$_father = $_TUser->GetFatherUid($iBet['uid']);
		$_TUser = null;
				
		// 上繳的coco 計算		
		self::PlayFather($iBet['id'], $iBet['type'], $iBet['play'], $_father, $iBet['uid'], $iBet['bet'], $_getWin, $_tax);
				
		//
		return true;
	}
	
	/**
	 * 計算父階層 關於這單的 佔成 / 小計 / 實際金額 並記錄
	 * @param $billing_id : 下注單單號
	 * @param $iGame	:遊戲名稱
	 * @param $iType	:玩法
	 * @param $ifather
	 * @param $iUid : 子帳號
	 * @param $iBet : 下注金額
	 * @param $iWin : 客戶實際輸贏金額
	 * @param $itax : 下階上交的錢
	 * @param $icost : 下階的佔成數 :: 客戶無佔成 預設0
	 */
	private function PlayFather($billing_id, $iGame, $iType, $ifather, $iUid, $iBet, $iWin, $itax, $icost = 0)
	{
	
		if ($ifather != '' && $ifather != 0)
		{
			$_TUser = new TUser();
			$_cost = $_TUser->GetCost($ifather, $iGame, $iType) / 100;
			$_nextF = $_TUser->GetFatherUid($ifather);
			$_competence = $_TUser->GetUserCompetence($ifather);
			
			// 2. 算出自己賺到的水錢 :: 總下注額 * 水差(開給下一層的)
			$_refunded = $_TUser->GetRefunded($iUid, $iGame, $iType) / 100;
			$_getrefunded = $iBet * $_refunded;
				
			// 3. 算出自己的佔成金額 :: (上繳金額 - 自己賺到的水錢)  * ( 自己的佔成數 - 分給下階的佔成數)
			$_decost = $_cost - $icost;
			$_selfCost = ( $itax - $_getrefunded) * $_decost; // $iWin * $_decost; // ( $iWin - $_getrefunded) * $_decost;
			
			// 4. 應該上繳的金額　:: 下階上繳的錢 - 自己的佔成 - 自己賺到的水錢
			$_tax = $itax - $_selfCost - $_getrefunded;
				
			// 5. 自己的實際總量 :: 自己賺到佔成 + 自己賺到的水錢
			$_rall = $_selfCost + $_getrefunded;
				
			// 寫入資料
			$_sql = 'insert into billing_father
					(fuid, cuid, billing_id, cost, tax, rall, getrefunded, competence) values (?, ?, ?, ?, ?, ?, ?, ?)';
			$this->m_Writer->exec($_sql, $ifather, $iUid, $billing_id, $_selfCost, $_tax, $_rall, $_getrefunded, $_competence);
	
			//判斷是否要計算總監部分
			if ($_competence == 4)
			{
				// 2. 算出自己賺到的水錢 :: 總下注額 * 水差
				$_refunded = $_TUser->GetRefunded($ifather, $iGame, $iType) / 100;
				$_getrefunded = $iBet * $_refunded;
				
				// 3. 算出自己的佔成 :: (上繳金額- 自己賺到的水錢) * 自己的佔成數
				// 確認總監有無佔成
				$_cost_5 = $_TUser->GetCost($_nextF, $iGame, $iType) / 100;
				if ($_cost_5 == '' || $_cost_5 == 0)
				{
					$_selfCost =  ( $_tax - $_getrefunded) * (1 - $_cost);
				}
				else 
				{
					$_selfCost =  ( $_tax - $_getrefunded) * ($_cost_5 - $_cost);
				}
				
	
				// 4. 應該上繳的金額　:: 實際輸贏 - 自己的佔成 - 自己賺到的水錢
				$_tax = $_tax - $_selfCost - $_getrefunded;
					
				// 寫入資料
				$_sql = 'insert into billing_father
					(fuid, cuid, billing_id, cost, tax, rall, getrefunded, competence) values (?, ?, ?, ?, ?, ?, ?, ?)';
				$this->m_Writer->exec($_sql, 10, $_nextF, $billing_id, $_selfCost, $_tax, $_rall, $_getrefunded, 5);
			}
	
			// 繼續找父親
			if ($_competence < 4)
			{
				self::PlayFather($billing_id, $iGame, $iType, $_nextF, $ifather, $iBet, $iWin, $_tax, $_cost);
			}
		}
	}
	
	
}