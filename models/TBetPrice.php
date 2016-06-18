<?php
/**
 * 各期 的基本本金
 */
class TBetPrice extends TIProtocol {
		
	function __construct()
	{
		parent::__construct();
	}
	
	// ==========================================
	
	/**
	 * 取得 目前帳號 及其父帳號積累的 本金值
	 * @param $iGame
	 * @param $iUid
	 * @param $iPlay
	 *
	 * @return array
	 */
	public function GetAllPrice($iGame, $iUid, $iPlay)
	{
		$_TUser = new TUser();
		$_list = $_TUser->GetFatherList($iUid);
		
		//
		$userlist = explode(",", $_list);	
		$_costsum = 0;
		foreach ($userlist as $val)
		{
			if ($val != '') // 還要去掉自己的 才是基準本金
			{
				$_cost = $_TUser->GetAcctDesignation($iGame, $val, 1, $iPlay);
				$_costsum += $_cost;
			}
		}
		
		$_TUser = null;
		return $_costsum;
	}
	
	
	/**
	 * 取得該玩家 正確的本金金額 (給前台 顯示正確金額用的 key都需轉化為數值)
	 * @param $iGame
	 * @param $iUid
	 * @param $iTitle_ID
	 * @param $iPlay
	 *
	 * @return array
	 */
	public function GetUserPrice($iGame, $iUid, $iTitle_ID, $iPlay)
	{
		/*
		 * 取得 該期 的基本資料 & 資料轉化
		 */
		$_data = self::ReadTableByPlay($iTitle_ID, $iPlay);
		
		$_tmp = [];
		foreach ($_data as $key=>$val)
		{
			$_ta = explode("_", $key);
			$tk = $_ta[count($_ta) -1];
			$_tmp[$tk] = $val;
		}
		$_data = $_tmp;
		
		/*
		 * 計算針對該玩家 正確的 本金應為多少
		 * 會員 真實本金 = 退水 * 上面給的本金
		 */
		$_tmp = [];
		foreach ($_data as $key=>$val)
		{
			// 2/3/4 星特別處理
			if ($iPlay == PLAY_ST2 || $iPlay == PLAY_ST3 || $iPlay == PLAY_ST4)
			{
				if ($key != 0)
				{
					$_ta = explode(",", $val);
					
					$_tmp[$key][0] = $_ta[0] + self::PlayPrice($iGame, $iUid, $iPlay);
					$_tmp[$key][1] = $_ta[1] + self::PlayPrice($iGame, $iUid, $iPlay);
				}
				else 
				{
					$_tmp[$key] = $val + self::PlayPrice($iGame, $iUid, $iPlay);
				}
			}
			else 
			{
				$_tmp[$key] = $val + self::PlayPrice($iGame, $iUid, $iPlay);
			}
		}
		$_data = $_tmp;		
		
		//
		return $_data;
	}
	
	// ==========================================.
	/**
	 * 取得指定期數 / 玩法 的 組數 (2/3/4 星限定)
	 * @param $iTitleID
	 * @param $iPlay
	 */
	public function GetStarGroups($iTitleID, $iPlay)
	{
		$_sql = 'select context from bet_price where title_id=? and playtype=?';
		$this->m_Read->exec($_sql, $iTitleID, $iPlay);
		$_data = $this->m_Read->fetch();
		
		return json_decode($_data['context'], true);
	}
	
	/**
	 * 建立新資料
	 * @param $iTitleID
	 * @param $iGameType
	 * 
	 * @return bool
	 */
	public function CreateTable($iTitleID, $iGameType)
	{		
		// 加入 交易 模式
		$this->m_Writer->beginTransaction();
		
		// 建立資料
		for($i =1; $i<10; ++$i)
		{
			$_sql = 'insert into bet_price (title_id, playtype) values (?, ?)';
			$_ret = $this->m_Writer->exec($_sql, $iTitleID, $i);
			if (!$_ret)
			{
				echo 'err ='.$this->m_Writer->errorCode()."<br>\n";
				$this->m_Writer->rollBack();
				return false;
			}
		}
				
		/*
		 * 讀取預設本金資料 然後寫入更新
		 */ 
		$_TBaseStake = new TBaseStake();
		$_ar = $_TBaseStake->ReadGameStake($iGameType);
		$_TBaseStake = null;
		
		if ($iGameType == 2)
		{
			$_carAr = $this->DePostCAR($_ar);
			$_st2Ar = $this->DePostST2($_ar);
			$_st3Ar = $this->DePostST3($_ar);
			$_st4Ar = $this->DePostST4($_ar);
			
			$this->WriteToDB($iTitleID, 2, $_carAr);
			$this->WriteToDB($iTitleID, 3, $_st2Ar);
			$this->WriteToDB($iTitleID, 4, $_st3Ar);
			$this->WriteToDB($iTitleID, 5, $_st4Ar);
		}
		else 
		{
			$_spAr = $this->DePostSP($_ar);
			$_carAr = $this->DePostCAR($_ar);
			$_st2Ar = $this->DePostST2($_ar);
			$_st3Ar = $this->DePostST3($_ar);
			$_st4Ar = $this->DePostST4($_ar);
			$_twAr = $this->DePostTW($_ar);
			
			$_pn2 = json_encode(array('bet_7'=>$_ar['bet_7']));
			$_pn3 = json_encode(array('bet_8'=>$_ar['bet_8']));
			$_tws = json_encode(array('bet_9'=>$_ar['bet_9']));
		
			$this->WriteToDB($iTitleID, 1, $_spAr);
			$this->WriteToDB($iTitleID, 2, $_carAr);
			$this->WriteToDB($iTitleID, 3, $_st2Ar);
			$this->WriteToDB($iTitleID, 4, $_st3Ar);
			$this->WriteToDB($iTitleID, 5, $_st4Ar);
			$this->WriteToDB($iTitleID, 6, $_twAr);
			$this->WriteToDB($iTitleID, 7, $_pn2);
			$this->WriteToDB($iTitleID, 8, $_pn3);
			$this->WriteToDB($iTitleID, 9, $_tws);
		}
		
		// 交易結束
		$this->m_Writer->commit();
		return true;
	}
	
	/**
	 * 移除資料
	 * @param $iTitleID
	 * 
	 * @return bool
	 */
	public function Del($iTitleID)
	{
		$_sql = 'delete from bet_price where title_id=?';
		$_ret = $this->m_Writer->exec($_sql, $iTitleID);
		if (!$_ret)
		{
			return false;
		}
		//
		return true;
	}
	
	/**
	 * 更新資料
	 * @param $iTitleID
	 * @param $iData
	 * 
	 * @return bool
	 */
	public function UPDateTable($iTitleID, $iData)
	{
		$_spAr = $this->DePostSP($iData);
		$_carAr = $this->DePostCAR($iData);
		$_st2Ar = $this->DePostST2($iData);
		$_st3Ar = $this->DePostST3($iData);
		$_st4Ar = $this->DePostST4($iData);
		$_twAr = $this->DePostTW($iData);
		
		$_pn2 = json_encode(array('bet_7'=>$iData['bet_7']));
		$_pn3 = json_encode(array('bet_8'=>$iData['bet_8']));
		$_tws = json_encode(array('bet_9'=>$iData['bet_9']));
		
		// 儲存 ---
		// 開始交易
		$ret = $this->m_Writer->beginTransaction();
		if (false == $ret) {
		
			$_msg = "beginTransaction fail! error code: ".$this->m_Writer->errorCode()."<br>\n";
			TLogs::log("TBetPrice-UPDateTable", $_msg);
				
			$this->m_Writer->rollBack();
			return false;
		}
		
		$this->WriteToDB($iTitleID, 1, $_spAr);
		$this->WriteToDB($iTitleID, 2, $_carAr);
		$this->WriteToDB($iTitleID, 3, $_st2Ar);
		$this->WriteToDB($iTitleID, 4, $_st3Ar);
		$this->WriteToDB($iTitleID, 5, $_st4Ar);
		$this->WriteToDB($iTitleID, 6, $_twAr);
		$this->WriteToDB($iTitleID, 7, $_pn2);
		$this->WriteToDB($iTitleID, 8, $_pn3);
		$this->WriteToDB($iTitleID, 9, $_tws);
		
		// 交易完成
		$this->m_Writer->commit();
		
		return true;
	}
	
	/**
	 * 複製其他期數資料
	 * @param $iMainID : 要被更新的
	 * @param $iSourceID : 來源
	 */
	public function CopyByOtherID($iMainID, $iSourceID)
	{
		/*
		 * 取出目標資料
		 */
		$_sql = 'select playtype, context from bet_price where title_id=?';
		$this->m_Read->exec($_sql, $iSourceID);
		$_data = $this->m_Read->fetchAll();
				
		/*
		 * 寫入到目標id
		 */
		// 儲存 ---
		// 開始交易
		$ret = $this->m_Writer->beginTransaction();
		if (false == $ret) {
		
			$_msg = "beginTransaction fail! error code: ".$this->m_Writer->errorCode()."<br>\n";
			TLogs::log("TBetPrice-UPDateTable", $_msg);
		
			$this->m_Writer->rollBack();
			return false;
		}
		
		foreach ($_data as $val)
		{
			$this->WriteToDB($iMainID, $val['playtype'], $val['context']);
		}
		
		// 交易完成
		$this->m_Writer->commit();
		$_data = null;
		return true;
	}
	
	
	/**
	 * 讀取資料 指定期數的 所有資料
	 * @param $iTitleID
	 * @param $iGame
	 */
	public function ReadTableAll($iTitleID, $iGame)
	{
		// 先讀看看 有沒有資料
		$_sql = 'select context from bet_price where title_id=?';
		$this->m_Read->exec($_sql, $iTitleID);
		$_data = $this->m_Read->fetchAll();		
		
		//
		$_ar = array();
		if (!$_data)	// 如果沒有讀到 則撈取預設資料
		{
			$_TBaseStake = new TBaseStake();
			$_ar = $_TBaseStake->ReadGameStake($iGame);
			$_TBaseStake = null;
		}
		else 
		{
			// 資料整理
			foreach($_data as $val)
			{
				if ($val['context'] != null)
				{
					$_tmp = json_decode($val['context'], true);
					$_ar += $_tmp;
				}
			}
		}
		
		//
		return $_ar;
	}
	
	/**
	 * 讀取指定期數 和玩法 的資料
	 * @param $iTitleID
	 * @param $iType
	 */
	public function ReadTableByPlay($iTitleID, $iType, $IsOnlyGetBombInFOR = false)
	{
		// 先讀看看 有沒有資料
		$_sql = 'select context from bet_price where title_id=? and playtype=?';
		$this->m_Read->exec($_sql, $iTitleID, $iType);
		$_ret = $this->m_Read->fetch();
				
		// 
		if (!$_ret)	// 如果沒有讀到 則撈取預設資料
		{
			$_TBaseStake = new TBaseStake();
			$_ret = $_TBaseStake->ReadDataByPlay();
			$_TBaseStake = null;
		}else $_ret = json_decode($_ret['context'], true);
		
		// 是否僅取得 連住碰資料
		if ($IsOnlyGetBombInFOR == true)
		{
			$_tmp = array();
			foreach($_ret as $key=>$val)
			{
				$_ret = strpos($key, 'bet');
				if ($_ret !== false)
				{
					
				}
				else 
				{
					$_tmp[$key] = $val;
				}
			}
			//
			$_ret = $_tmp;
			$_tmp = null;
		}
		
		//
		return $_ret;
	}
	
	// ============================================
	// 資料分析
	
	/**
	 * 本金計算
	 * @param $iGame
	 * @param $iUid
	 * @param $iPlay
	 * @param $iMoney : 本金金額
	 */
	private function PlayPrice($iGame, $iUid, $iPlay)
	{
		/*
		 * 撈取 玩家的位階 及父帳號
		 */
		$_TUser = new TUser();
		$_Competence = $_TUser->GetUserCompetence($iUid);
		$_Father = $_TUser->GetFatherUid($iUid);
		$_Water = $_TUser->GetAcctDesignation($iGame, $iUid, 1, $iPlay);
		$_TUser = null;
				
		// 水差 0.20% 的算法
		// 判斷是否為上組
		if ($_Competence == 6 || $_Competence == 5)
		{
			// 取得基本本金
			return 0;
		}
		else
		{
			$_money = self::PlayPrice($iGame, $_Father, $iPlay);
			$_ret = $_money + $_Water;
			return $_ret;
		}
	}
	
	/**
	 * 拆解post 特碼 資料  並返回陣列
	 * @param $iData : 數據資料
	 *
	 * 1.特碼 (SP) 2:全車 (CAR) 3:二星(ST2) 4:三星(ST3) 5:四星(ST4)
	 * 6:台號 (TW) 7:天碰二(PN2) 8:天碰三(PN3) 9:特尾三 (TWS)
	 */
	private function DePostSP($iData)
	{
		// 特瑪部分有49個號碼
		$_ar = array();
		//
		foreach($iData as $key=>$val)
		{
			for($i=0; $i<49; ++$i)
			{
				if ($key == 'bet_1_'.($i+1))
				{
					$_ar[$key] = $val;
				}
			}
		}
			
		//
		return json_encode($_ar);
	}
	
	/**
	 * 拆解post 全車 資料  並返回陣列
	 * @param $iData : 數據資料
	 *
	 * 1.特碼 (SP) 2:全車 (CAR) 3:二星(ST2) 4:三星(ST3) 5:四星(ST4)
	 * 6:台號 (TW) 7:天碰二(PN2) 8:天碰三(PN3) 9:特尾三 (TWS)
	 */
	private function DePostCAR($iData)
	{
		// 全車部分有49個號碼
		$_ar = array();
		//
		foreach($iData as $key=>$val)
		{
			for($i=0; $i<49; ++$i)
			{
				if ($key == 'bet_2_'.($i+1))
				{
					$_ar[$key] = $val;
				}
			}
		}
			
		//
		return json_encode($_ar);
	}
	
	/**
	 * 拆解post 台號 資料  並返回陣列
	 * @param $iData : 數據資料
	 *
	 * 1.特碼 (SP) 2:全車 (CAR) 3:二星(ST2) 4:三星(ST3) 5:四星(ST4)
	 * 6:台號 (TW) 7:天碰二(PN2) 8:天碰三(PN3) 9:特尾三 (TWS)
	 */
	private function DePostTW($iData)
	{
		// 全車部分有49個號碼
		$_ar = array();
		//
		foreach($iData as $key=>$val)
		{
			for($i=0; $i<100; ++$i)
			{
				if ($key == 'bet_6_'.$i)
				{
					$_ar[$key] = $val;
				}
			}
		}
			
		//
		return json_encode($_ar);
	}
	
	/**
	 * 拆解post 2星 資料  並返回陣列
	 * @param $iData : 數據資料
	 *
	 * 1.特碼 (SP) 2:全車 (CAR) 3:二星(ST2) 4:三星(ST3) 5:四星(ST4)
	 * 6:台號 (TW) 7:天碰二(PN2) 8:天碰三(PN3) 9:特尾三 (TWS)
	 */
	private function DePostST2($iData)
	{
		// 2星部分 - 散單 bet_3_1 / 連碰 bet_3_2 / 柱碰 bet_3_3
		$_ar = array();
		
		// 散單
		$_ar['bet_3_1_0'] =  (isset($iData['bet_3_1_0']))?$iData['bet_3_1_0']:$iData['bet_3_1'];
		
		// 連柱碰
		foreach($iData as $key=>$val)
		{
			$_ret = strpos($key, 'bet_3_2_');
			if ($_ret !== false)
			{
				$_tk = explode("bet_3_2_", $key);
				
				// 取得最後的數值 
				$_ar[$_tk[1]] = $val;
			}
		}
					
		//
		return json_encode($_ar);
	}
	
	/**
	 * 拆解post 3星 資料  並返回陣列
	 * @param $iData : 數據資料
	 *
	 * 1.特碼 (SP) 2:全車 (CAR) 3:二星(ST2) 4:三星(ST3) 5:四星(ST4)
	 * 6:台號 (TW) 7:天碰二(PN2) 8:天碰三(PN3) 9:特尾三 (TWS)
	 */
	private function DePostST3($iData)
	{
		// 3星部分 - 散單bet_4_1 / 連碰 bet_4_2 / 柱碰 bet_4_3
		$_ar = array();
		
		// 散單
		$_ar['bet_4_1_0'] =  (isset($iData['bet_4_1_0']))?$iData['bet_4_1_0']:$iData['bet_4_1'];
		
		// 連柱碰
		foreach($iData as $key=>$val)
		{
			$_ret = strpos($key, 'bet_4_2_');
			if ($_ret !== false)
			{
				$_tk = explode("bet_4_2_", $key);
				
				// 取得最後的數值 
				$_ar[$_tk[1]] = $val;
			}
		}
					
		//
		return json_encode($_ar);
	}
	
	/**
	 * 拆解post 4星 資料  並返回陣列
	 * @param $iData : 數據資料
	 *
	 * 1.特碼 (SP) 2:全車 (CAR) 3:二星(ST2) 4:三星(ST3) 5:四星(ST4)
	 * 6:台號 (TW) 7:天碰二(PN2) 8:天碰三(PN3) 9:特尾三 (TWS)
	 */
	private function DePostST4($iData)
	{
		// 4星部分 - 散單bet_5_1 / 連碰 bet_5_2 / 柱碰 bet_5_3
		$_ar = array();
		
		// 散單
		$_ar['bet_5_1_0'] =  (isset($iData['bet_5_1_0']))?$iData['bet_5_1_0']:$iData['bet_5_1'];
		
		// 連柱碰
		foreach($iData as $key=>$val)
		{
			$_ret = strpos($key, 'bet_5_2_');
			if ($_ret !== false)
			{
				$_tk = explode("bet_5_2_", $key);
				
				// 取得最後的數值 
				$_ar[$_tk[1]] = $val;
			}
		}
					
		//
		return json_encode($_ar);
	}
	
	// ============================================
	// 資料庫相關

	/**
	 * 寫入資料
	 * @param $iTitle : 旗號
	 * @param $iPlay : 玩法類型
	 * @param $iData : 數據資料
	 */
	private function WriteToDB($iTitle, $iPlay, $iData)
	{
		$_sql = "update bet_price set context=? where title_id=? and playtype=?";
		$_ret = $this->m_Writer->exec($_sql, $iData, $iTitle, $iPlay);
		if (false == $_ret) {
	
			$_msg = "write fail! :".$this->m_Writer->errorCode()."<br>\n";
			TLogs::log("TBetPrice-WriteToDB", $_msg);
			$this->m_Writer->rollBack();
			return false;
		}
	}

}