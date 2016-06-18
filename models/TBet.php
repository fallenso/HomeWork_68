<?php
/**
 * 期數資料
 *
 */
class TBet  extends TIProtocol {
	
	function __construct()
	{
		parent::__construct();
	}
	
	// =========================================
	
	
	/**
	 * 取得指定旗號 / 玩法 / 號碼 的賠率
	 * @param $iTitleID
	 * @param $iPlay :玩法
	 * @param $iNum : 號碼
	 */
	public function GetBet($iTitleID, $iPlay, $iNum)
	{
		// 取得賠率
		$_sql = "select * from bet where id=?";
		$this->m_Read->exec($_sql, $iTitleID);
		$_bet = $this->m_Read->fetch();
		
		// 依照玩法取值
		switch($iPlay)
		{
			case PLAY_SP:
				return $_bet['bet_sp'];
			case PLAY_CAR:
				return $_bet['bet_car'];
			case PLAY_ST2:
				return $_bet['bet_st2'];
			case PLAY_ST3:
				return $_bet['bet_st3'];
			case PLAY_ST4:
				return $_bet['bet_st4'];
			case PLAY_TW:
				// 拆解 資料
				$_js = json_decode($_bet['bet_tw']);
				$_key = 'bet_tw_'.$iNum;
				return $_js->$_key;
			case PLAY_PN2:
				return $_bet['bet_pn2'];
			case PLAY_PN3:
				return $_bet['bet_pn3'];
			case PLAY_TWS:
				// 計算線數
				$_nar = str_split($iNum);
				$_n1 = $_nar[0].$_nar[1];
				$_n2 = $_nar[1].$_nar[2];
				
				$_line1 = self::PlayLineCard($_n1);
				$_line2 = self::PlayLineCard($_n2);
				$_line = $_line1 + $_line2;
				
				// 拆解 資料
				$_js = json_decode($_bet['bet_tws']);
				$_key = 'bet_tws_'.$_line;
				
				//
				return $_js->$_key;
		}
	}
		
	/**
	 * 取德 時間段內 的 期數列表
	 * @param $iGame
	 * @param $iSt : 開始時間
	 * @param $iEt : 結束時間 
	 */
	public function GetBetListByTimeInterval($iGame, $iSt, $iEt)
	{
		$_sql = "select id, start_time, end_time, award_time from bet where gametype=? and award_time >=? and award_time <?";
		$this->m_Read->exec($_sql, $iGame, $iSt, $iEt);
		$_ret = $this->m_Read->fetchAll();
		return $_ret;
	}
	
	/**
	 * 建立新資料
	 * @param $iParam : 資料來源 (資料結構同sql資料結構)
	 * 
	 * @return bool
	 */
	public function CreateNew($iParam)
	{		
		// id / year_cycle setting
		$_iddata = $this->CreateID($iParam['gametype']);
		
		// ------------------------------------------------------------		
		// create bet
		$_sql = 'insert into bet (id, time, year_cycle) values (?, ?, ?)';
		$_ret = $this->m_Writer->exec($_sql, $_iddata['id'], time(), $_iddata['year']);
		if (!$_ret)
		{
			TLogs::log("TBet-CreateNew", "insert into bet is fail. id=".$_iddata['id']);
			return false;
		}
		
		// create TAward
		$_TAwardControl = new TAwardControl();
		$_ret = $_TAwardControl->CreateNew($_iddata['id'], $iParam['gametype'], $iParam['award_time']);
		$_TAwardControl = null;
		if (!$_ret)
		{
			TLogs::log("TBet-CreateNew", "insert into TAward is fail.");
			return false;
		}
		
		// 建立 基本價格資料 bet_price
		$_TBetPrice = new TBetPrice();
		$_ret = $_TBetPrice->CreateTable($_iddata['id'], $iParam['gametype']);
		$_TBetPrice = null;
		if (!$_ret)
		{
			TLogs::log("TBet-CreateNew", "insert into BetPrice is fail.");
			return false;
		}		
		
		// create TPrs
		$_TPrs = new TPrs();
		$_TPrs->CreateTable($_iddata['id']);
		$_TPrs = null;
		
		// create betgroups
		$_TBetGP = new TBetGP();
		$_TBetGP->CreateTable($_iddata['id']);
		$_TBetGP = null;
		
		// copy Ov<
		//TLogs::log("TBet-CreateNew", "to run UPdateData.");
		$_ret = $this->UPdateData($_iddata['id'], $iParam);
		return $_ret;
	}
	
	/**
	 * 刪除
	 * @param $id
	 * 
	 * @return bool
	 */
	public function DelBet($id)
	{
		// ------------------------------------------------------------
		// del bet
		$_sql = 'delete from bet where id=?';
		$_ret = $this->m_Writer->exec($_sql, $id);
		if (!$_ret)
		{
			return false;
		}
		
		// ------------------------------------------------------------
		// del TAward
		$_TAwardControl = new TAwardControl();
		$_ret = $_TAwardControl->Del($id);
		$_TAwardControl = null;
		if (!$_ret)
		{
			return false;
		}
		
		// ------------------------------------------------------------
		// del billing & billingfather
		$_TBilling = new TBilling();
		$_data = $_TBilling->GetBetDataByTitleId($id);
		
		foreach ($_data as $val)
		{
			$_TBilling->ToughDel($val['id']);
		}
		$_TBilling = null;
		
		// -------------------------------------------------------------
		// del bet_price
		$_TBetPrice = new TBetPrice();
		$_ret = $_TBetPrice->Del($id);
		$_TBetPrice = null;
		
		// -------------------------------------------------------------
		// del TPrs
		$_TPrs = new TPrs();
		$_TPrs->Del($id);
		$_TPrs = null;
		
		// -------------------------------------------------------------
		// del TBetGropus
		$_TBetGP = new TBetGP();
		$_TBetGP->Del($id);
		$_TBetGP = null;
		
		//
		return true;
	}	
	
	/**
	 * 更新資料
	 * @param $iId : 旗號
	 * @param $iParam : 資料來源 (資料結構同sql資料結構)
	 * 
	 * @return bool
	 */
	public function UPdateData($iId, $iParam)
	{
		// -------------------------------------------------------
		// 時間轉換
		$_start_time = strtotime($iParam["start_time"]);
		$_end_time = strtotime($iParam["end_time"]);
		$_award_time = strtotime($iParam["award_time"]);
		
		// -------------------------------------------------------
		// 更新 award 表
		
		// 更新開獎表的遊戲種類
		$_TAwardControl = new TAwardControl();
		$_ret = $_TAwardControl->UPdateGame($iId, $iParam["gametype"]);
		if (!$_ret) {
			return false;
		}
				
		// 更新開獎表的開獎時間
		$_ret = $_TAwardControl->UPDateTime($iId, $_award_time);
		$_TAwardControl = null;
		if (!$_ret) {
			return false;
		}
			
		// -------------------------------------------------------
		// 更新 bet表
		// 1:全車 2:特碼 3:正特碼雙面 4:台號 5:特尾三 6:二星 7:三星 8:四星 9:天碰二 10:天碰三
		$_sql = "update bet
				 set gametype=?, start_time=?,end_time=?,award_time=?,
				 bet_sp=?,bet_car=?,bet_st2=?,bet_st3=?,bet_st4=?,bet_tw=?,bet_pn2=?,bet_pn3=?,bet_tws=?
				 where id=?";
				
		$_ret = $this->m_Writer->exec($_sql, 
				$iParam["gametype"], $_start_time, $_end_time, $_award_time,
				$iParam["bet_sp"],$iParam["bet_car"],$iParam["bet_st2"],$iParam["bet_st3"],$iParam["bet_st4"],$iParam["bet_tw"],$iParam["bet_pn2"],$iParam["bet_pn3"],$iParam["bet_tws"],
				$iId
		);
		if (!$_ret) {
		
			$_msg = "UPdateData! :".$this->m_Writer->errorCode()."<br>\n";
			TLogs::log("TBet-UPdateData", $_msg);
			return false;
		}
		return true;
	}
	
	/**
	 * 取得每期資料 (簡)
	 * @param $iGame
	 * 每期期號/分類/設定開始 下注時間/設定停止下注時間/設定 開獎時間
	 */
	public function GetAll($iGame = '')
	{		
		if ($iGame == '')
		{
			$_sql = "select id, gametype, start_time, end_time, award_time from bet order by award_time DESC";
			$this->m_Read->exec($_sql);
		}
		else 
		{
			$_sql = "select id, gametype, start_time, end_time, award_time from bet where gametype=? order by award_time DESC";
			$this->m_Read->exec($_sql, $iGame);
		}
		
		//
		$_ret = $this->m_Read->fetchAll();
		return $_ret;
	}
	
	/**
	 * 取得指定的 期數資料
	 * @param $iId : 指定的id
	 */
	public function FindIndex($iId)
	{
		$_sql = "select * from bet where id=?";
		$this->m_Read->exec($_sql, $iId);
		$_ret = $this->m_Read->fetchAll();
		return $_ret;
	}
	
	/**
	 * 資料轉換 :: 主要是把 分類/設定開始 下注時間/設定停止下注時間/設定 開獎時間\
	 * 轉成人看得懂的 (用在列表表單上)
	 */
	public function TurnShowTime($iRet)
	{
		$_weeklist = array('日', '一', '二', '三', '四', '五', '六');
		$_ar = array();
		foreach($iRet as $val)
		{
			$_tmp = array();
			//
			$_tmp['id'] = $val['id'];
			//
			if ($val['gametype'] == 0) $_tmp['gametype'] = '六合彩';
			else if ($val['gametype'] == 1) $_tmp['gametype'] = '大樂透';
			else if ($val['gametype'] == 2) $_tmp['gametype'] = '539';
			else $_tmp['gametype'] = '沒有設定';
			//
			
			$_tmp['start_time'] = date('Y/m/d H:i:s', $val['start_time']);
			$_tmp['end_time'] = date('Y/m/d H:i:s', $val['end_time']);
			$_tmp['award_time'] = date('Y/m/d H:i:s', $val['award_time']);
			$_tmp['week'] = $_weeklist[date('w', $val['award_time'])];
			//
			array_push($_ar, $_tmp);
		}
		//
		return $_ar;
	}
	
	/**
	 * 取得當前可下注旗號
	 * @param $iGame : 遊戲代碼
	 *
	 * @return id
	 */
	public function GetNowTitleID($iGame)
	{	
		$_now = strtotime(date('Y-m-d H:i:s', time()));
		$_sql = "select id from bet where gametype=? and start_time<=? and end_time>?";
	
		$this->m_Read->exec($_sql, $iGame, $_now, $_now);
		$_ret = $this->m_Read->fetch();
		return $_ret['id'];
	}
	
	/**
	 * 資料轉換 針對 台號賠率
	 * @param $iData
	 *
	 * @return string
	 */
	public static function TW_DataToDB($iData)
	{
		//
		$_ar = array();
		foreach ($iData as $key=>$val)
		{
			$_ret = strpos($key, 'bet_tw_');
			if ($_ret !== false)
			{
				$_ar[$key] = $val;
			}
		}
	
		$_js = json_encode($_ar);
		return $_js;
	}
	
	/**
	 * 資料轉換 針對 賠率
	 * @param $iStr
	 *
	 * @return array
	 */
	public static function PlayBet_DBToData($iStr)
	{
		return json_decode($iStr, true);
	}
	
	/**
	 * 資料轉換 針對 特尾三賠率
	 * @param $iData
	 *
	 * @return string
	 */
	public static function TWS_DataToDB($iData)
	{
		//
		$_ar = array();
		foreach ($iData as $key=>$val)
		{
			$_ret = strpos($key, 'bet_tws_');
			if ($_ret !== false)
			{
				$_ar[$key] = $val;
			}
		}
	
		$_js = json_encode($_ar);
		return $_js;
	}
		
	// ============================================================
	
	/**
	 * 取得當前年代 的 指定遊戲項目  有幾期
	 * @param $iGameType : 遊戲代碼
	 * @param $iCycle : 取民國第幾年的周期 ex: 105 | 106 | 201
	 */ 
	private function GetNumsByCycle($iGameType, $iCycle)
	{
		// 取最後一個的id
		$_sql = 'select id from bet where gametype=? and year_cycle=? order by id DESC';
		$this->m_Read->exec($_sql, $iGameType, $iCycle);
		$_ret = $this->m_Read->fetch();
		
		return (is_array($_ret) == true)?$_ret['id']:'';
	}
	
	/**
	 * 建立 id / year_cycle  (注意自動建立那邊也有喔)
	 * @param $iGametype : 遊戲代碼
	 * 
	 * @return arrat(id, cycle)
	 */
	private function CreateID($iGametype)
	{
		/**
		 * 每期id 設定規則
		 * 1. 開頭英文代碼 :六合彩－s, 大樂透－b, 539-f
		 * 2. 後3碼數字 :中華民國年  如:s105(民國105年六和彩), b106(民國106年大樂透)...
		 * 3. 後數字3碼 : 每期流水序號
		 */
		// 取得目前為民國幾年
		$_nowRepublic = TTime::GetNowRepublicByYear();
		
		// 取得目前遊戲代碼  還有目前為第幾期
		$_gameCode = '';
		switch ($iGametype)
		{
			case Lottery:
				$_gameCode = 's';
				break;
			case Lotto:
				$_gameCode= 'b';
				break;
			case Five:
				$_gameCode = 'f';
				break;
		
			default:
				$_gameCode = 'non';
				break;
		}
		
		// 取得目前為第幾期
		$_lastid = $this->GetNumsByCycle($iGametype, $_nowRepublic);
		if ($_lastid != '')
		{
			$_deStr = $_gameCode.$_nowRepublic;
			$_tstr = str_replace($_deStr, '', $_lastid);
			$_gameCycle = $_tstr;
			++$_gameCycle;
			
		}else $_gameCycle = 1;
		
		// 新的本期旗號
		$_newid = $_gameCode.$_nowRepublic.sprintf("%03d", $_gameCycle);
		//
		return array('id'=>$_newid, 'year'=>$_nowRepublic);
	}
	
	/**
	 * 計算線牌
	 * @param $iNum
	 *
	 * @param int : line
	 */
	private function PlayLineCard($iNum)
	{
		if ($iNum == 1 || $iNum == 12 || $iNum == 23 || $iNum == 34 || $iNum == 45 || $iNum == 56 || $iNum == 67 || $iNum == 78 || $iNum == 89 || $iNum == 90)		return 1;
		else if ($iNum == 2|| $iNum == 13 || $iNum == 24 || $iNum == 35 || $iNum == 46 || $iNum == 57 || $iNum == 68 || $iNum == 79 || $iNum == 80 || $iNum == 91)  return 2;
		else if ($iNum == 3|| $iNum == 14 || $iNum == 25 || $iNum == 36 || $iNum == 47 || $iNum == 58 || $iNum == 69 || $iNum == 70 || $iNum == 81 || $iNum == 92)	return 3;
		else if ($iNum == 4|| $iNum == 15 || $iNum == 26 || $iNum == 37 || $iNum == 48 || $iNum == 59 || $iNum == 60 || $iNum == 71 || $iNum == 82 || $iNum == 93)	return 4;
		else if ($iNum == 5|| $iNum == 16 || $iNum == 27 || $iNum == 38 || $iNum == 49 || $iNum == 50 || $iNum == 61 || $iNum == 72 || $iNum == 83 || $iNum == 94)	return 5;
		else if ($iNum == 6|| $iNum == 17 || $iNum == 28 || $iNum == 39 || $iNum == 40 || $iNum == 51 || $iNum == 62 || $iNum == 73 || $iNum == 84 || $iNum == 95)	return 6;
		else if ($iNum == 7|| $iNum == 18 || $iNum == 29 || $iNum == 30 || $iNum == 41 || $iNum == 52 || $iNum == 63 || $iNum == 74 || $iNum == 85 || $iNum == 96)	return 7;
		else if ($iNum == 8|| $iNum == 19 || $iNum == 20 || $iNum == 31 || $iNum == 42 || $iNum == 53 || $iNum == 64 || $iNum == 75 || $iNum == 86 || $iNum == 97)	return 8;
		else if ($iNum == 9|| $iNum == 10 || $iNum == 21 || $iNum == 32 || $iNum == 43 || $iNum == 54 || $iNum == 65 || $iNum == 76 || $iNum == 87 || $iNum == 98)	return 9;
		else if ($iNum == 0|| $iNum == 11 || $iNum == 22 || $iNum == 33 || $iNum == 44 || $iNum == 55 || $iNum == 66 || $iNum == 77 || $iNum == 88 || $iNum == 99)	return 10;
	}
}

