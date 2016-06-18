<?php
/**
 * 檢查是否超過單號上限 - 單個號碼可收的最大金額.
 */
class TCheckNumOver extends TIProtocol
{
	function __construct()
	{
		parent::__construct();
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
	function Main($iUid, $iGame, $iType, $iId, $Nums, $iMoney, $ispplay)
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
			foreach($_tmp as $key)
			{
				if (isset($_outData[$key]) == true)
				{
					// 判斷是否超過 :: 單次收的號碼 最高可收上限
					if (($iMoney + $_outData[$key]) > $_setMon[$_acct_play_name])
					{
						$_playname = '二星';
						if ($iType == PLAY_ST2)
						{
							$_playname = '二星';
						}
						
						if ( $iType == PLAY_ST3 )
						{
							$_playname = '三星';
						}
						
						if ( $iType == PLAY_ST4 )
						{
							$_playname = '四星';
						}
						
						$imsg = $_playname.' '.$key.'超過單組限額';
						echo '<script>alert("'.$imsg.'");</script>';
						return false;
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
	 * 指定  玩家/期數/玩法  的下注單資料
	 * @param $iTitleID : 指定期數
	 * @param $iUid : 指定使用者的id
	 * @param $iType : 玩法
	 */
	private function GetSingleByPlay($iTitleID, $iUid, $iType = '')
	{
		$_sql = "select * from billing where title_id=? and uid=? and play=?";
		$this->m_Read->exec($_sql, $iTitleID, $iUid, $iType);
		$_data = $this->m_Read->fetchAll();
	
		//
		return $_data;
	}
}