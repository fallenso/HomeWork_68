<?php
/**
 * 各遊戲 各玩法 每注本金金額設定
 */
class TBaseStake extends TIProtocol {
		
	function __construct()
	{
		parent::__construct();
	}
	
	// ==================================================
	/**
	 * 更新 每注金額
	 * @param $iGame : 遊戲類型
	 * @param $iData : 數據資料
	 * 1.特碼 (SP) 2:全車 (CAR) 3:二星(ST2) 4:三星(ST3) 5:四星(ST4) 
 	 * 6:台號 (TW) 7:天碰二(PN2) 8:天碰三(PN3) 9:特尾三 (TWS)
	 */
	public function UPDateStake($iGame, $iData)
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
			TLogs::log("TBaseStake-UPDateStake", $_msg);
			
			$this->m_Writer->rollBack();
			return false;
		}
		
		$this->WriteToDB($iGame, 1, $_spAr);
		$this->WriteToDB($iGame, 2, $_carAr);
		$this->WriteToDB($iGame, 3, $_st2Ar);
		$this->WriteToDB($iGame, 4, $_st3Ar);
		$this->WriteToDB($iGame, 5, $_st4Ar);
		$this->WriteToDB($iGame, 6, $_twAr);
		$this->WriteToDB($iGame, 7, $_pn2);
		$this->WriteToDB($iGame, 8, $_pn3);
		$this->WriteToDB($iGame, 9, $_tws);
		
		// 交易完成
		$this->m_Writer->commit();
		
		return true;
	}
	
	/**
	 * 讀取指定遊戲的 每注金額資料
	 * @param $iGame : 遊戲類型
	 * @return array
	 * 
	 * 1.特碼 (SP) 2:全車 (CAR) 3:二星(ST2) 4:三星(ST3) 5:四星(ST4) 
 	 * 6:台號 (TW) 7:天碰二(PN2) 8:天碰三(PN3) 9:特尾三 (TWS)
	 */
	public function ReadGameStake($iGame)
	{
		$_sql = 'select context from base_stake where gametype=?';
		$this->m_Read->exec($_sql, $iGame);
		$_data = $this->m_Read->fetchAll();
		
		// 資料整理
		$_ar = array();
		foreach($_data as $val)
		{
			if ($val['context'] != null)
			{
				$_tmp = json_decode($val['context'], true);
				$_ar += $_tmp;
			}
		}
		//
		return $_ar;
	}
		
	/**
	 * 讀取指定遊戲 / 玩法的預設資料
	 * @param $iGame
	 * @param $iType
	 */
	public function ReadDataByPlay($iGame, $iType)
	{
		$_sql = 'select context from base_stake where gametype=? and playtype=?';
		$this->m_Read->exec($_sql, $iGame, $iType);
		$_data = $this->m_Read->fetch();
		
		$_data = json_decode($_data['context'], true);
		//
		return $_data;
	}
	
	// ============================================
	// 資料分析
	
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
		$_ar["bet_3_1_0"] = $iData['bet_3_1_0'];
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
		$_ar["bet_4_1_0"] = $iData['bet_4_1_0'];
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
		$_ar["bet_5_1_0"] = $iData['bet_5_1_0'];
		return json_encode($_ar);
	}
	
	// ============================================
	// 資料庫相關
	
	/**
	 * 寫入資料
	 * @param $iGame : 遊戲類型
	 * @param $iPlay : 玩法類型
	 * @param $iData : 數據資料
	 */
	private function WriteToDB($iGame, $iPlay, $iData)
	{
		$_sql = "update base_stake set context=? where gametype=? and playtype=?";
		$_ret = $this->m_Writer->exec($_sql, $iData, $iGame, $iPlay);
		if (false == $_ret) {
		
			$_msg = "write fail! :".$this->m_Writer->errorCode()."<br>\n";
			TLogs::log("TBaseStake-UPDateStake", $_msg);
			$this->m_Writer->rollBack();
			return false;
		}
	}
	
}