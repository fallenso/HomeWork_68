<?php
/**
 * 漲調
 */
class TPrs extends TIProtocol {

	function __construct()
	{
		parent::__construct();
	}

	// ======================================
	/**
	 * 撈取封牌資料
	 * @param $iTitleID
	 * @param $iPlay
	 */
	public function GetCloseCard($iTitleID, $iPlay)
	{
		// 取得 調整資料
		$_pris = self::GetPrices($iTitleID, $iPlay);
		
		$_list = [];
		foreach($_pris as $val)
		{
			if ($val['case'] == 0)
			{
				array_push($_list, $val['num']);
			}
		}
		//
		return $_list;
	}
	
	/**
	 * 撈取調整過後的本金
	 * @param $iTitleID
	 * @param $iPlay
	 * @param $iGame
	 * @param $iUid
	 * @param $iNum : 號碼
	 */
	public function GetBetPrices($iTitleID, $iPlay, $iGame, $iUid, $iNum, $iSP)
	{
		$_price = 0; // 原始本金
		$_getprs = 0; // 取得調漲多少
		
		// 取得原始本金
		$_TBetPrice = new TBetPrice();
		$_ori = $_TBetPrice->GetUserPrice($iGame, $iUid, $iTitleID, $iPlay);
		$_TBetPrice = null;
		
		// 取得 調整資料
		$_pris = self::GetPrices($iTitleID, $iPlay);
		
		// 玩法判斷
		if ($iPlay == PLAY_ST2 || $iPlay == PLAY_ST3 || $iPlay == PLAY_ST4 )
		{
			// 計算總共下幾支
			$_TPlayAward = new TPlayAward();
			$_fews = $_TPlayAward->GetCombNums($iGame, $iNum, $iPlay, $iSP);
			$_TPlayAward = null;
			
			// 判斷 在哪個組數內
			$_group = [];
			foreach($_ori as $key=>$val)
			{
				array_push($_group, $key);
			}
			
			sort($_group);
			$_index = 0;
			foreach ($_group as $val)
			{
				if ($_fews == $val)
				{
					$_index = $val;
				}
				else
				{
					if ($_fews > $val)
					{
						$_index = $val;
					}
				}
			}
			
			// 判斷是否為散單
			if ($_index == 0)
			{
				// 取得原始價格
				$_price = $_ori[$_index]; // 先設定為散單價格
				
				// 有無 調漲號碼
				// 計算漲幅
				$_cobnum = explode('x', $iNum);
				foreach ($_cobnum as $comval)
				{
					$_listnum = explode(',', $comval);
					foreach ($_listnum as $ln)
					{
						foreach ($_pris as $val)
						{
							if ($ln == $val['num'])
							{
								$_getprs = ($_getprs > $val['case'])?$_getprs:$val['case'];
							}
						}
					}
				}
			}
			else 
			{
				// 判斷為連碰 還是柱碰
				$_bettype = 0;
				$_comparison = strpos($iNum, 'x');
				if (strlen($_comparison) == 0)
				{
					$_bettype = 0;
				}else $_bettype = 1;
				
				// 取得原始價格
				$_price = $_ori[$_index][$_bettype]; // 先設定價格
			}
		}
		else if ($iPlay == PLAY_PN2 || $iPlay == PLAY_PN3)
		{
			
			if ($iPlay == PLAY_PN2)
			{
				$_price = $_ori[7];
			}
			else if ($iPlay == PLAY_PN3)
			{
				$_price = $_ori[8];
			}
			
			// 計算漲幅
			$_cobnum = explode('x', $iNum);
			$_listnum = explode(',', $_cobnum[0]);
			foreach ($_listnum as $ln)
			{
				foreach ($_pris as $val)
				{
					if ($ln == $val['num'])
					{
						$_getprs = ($_getprs > $val['case'])?$_getprs:$val['case'];
					}
				}
			}		
		}
		else if ($iPlay == PLAY_TWS)
		{
			$_price = $_ori[9];
			foreach ($_pris as $val)
			{
				if ($iNum == $val['num'])
				{
					$_getprs = ($_getprs > $val['case'])?$_getprs:$val['case'];
				}
			}
		}
		else 
		{
			// 取得本金
			foreach ($_ori as $key=>$val)
			{
				if ($key == intval($iNum))
				{
					$_price = $val;
				}
			}
			
			if (count($_pris) == 0)
			{
				return $_price;
			}
			
			// 計算漲幅
			foreach ($_pris as $val)
			{
				if ($iNum == $val['num'])
				{
					$_getprs = ($_getprs > $val['case'])?$_getprs:$val['case'];
				}
			}
		}
		
		//
		$_price += $_getprs;
		return $_price;
	}
	
	/**
	 * 原始調整資料撈取
	 * @param $iTitleID
	 * @param $iPlay
	 * 
	 * @return data
	 */
	public function GetPrices($iTitleID, $iPlay)
	{
		// 撈出該期下注資料
		$_TBilling = new TBilling();
		$_titledata = $_TBilling->GetBetDataByTypeAndTitleId($iPlay, $iTitleID);
		$_TBilling = null;
		
		// 撈取調漲資料
		$_data = self::ToRead($iTitleID);
		if ($_data == null)
		{
			$_data = [];
		}
		// 過濾 ::檢查該玩法 是否有該號碼在,然後查目前下注額 是否已到
		$_list = [];
		foreach ($_data as $val)
		{
			if ($iPlay == $val['type'])
			{
				// 查詢該玩法的當前下注額度			
				$_betSums = 0;
				foreach ($_titledata as $tit)
				{
					// 號碼比對
					$_ret = strpos($tit['num'], $val['num']);
					if (strlen($_ret) != 0)
					{
						$_betSums += intval($tit['bet']);
					}
				}
				
				//　查目前下注額 是否超過
				if ($val['limit'] <= $_betSums)
				{
					array_push($_list, $val);
				}
			}
		}
		
		// 過濾：　最高限制額
		$_limit = [];
		foreach ($_list as $val)
		{
			if (isset($_limit[$val['num']]))
			{
				if ($_limit[$val['num']]['limit'] < $val['limit'])
				{
					$_limit[$val['num']] = $val;
				}
			}
			else 
			{
				$_limit[$val['num']] = $val;
			}
		}
		//
		return $_limit;		
	}	
		
	/**
	 * 讀取資料
	 * @param $iTitleID
	 */
	public function ToRead($iTitleID)
	{
		$_sql = 'select context from bet_prs where title_id=?';
		$this->m_Read->exec($_sql, $iTitleID);
		$_ret = $this->m_Read->fetch();
		
		$_dedata = json_decode($_ret['context'], true);
		return $_dedata;
	}
	
	/**
	 * 儲存資料
	 * @param $iTitleID
	 * @param $data [array]
	 * 
	 * @return bool
	 */
	public function ToSave($iTitleID, $data)
	{
		$_jsdata = json_encode($data);
		//
		$_sql = 'update bet_prs set context=? where title_id=?';
		$_ret = $this->m_Writer->exec($_sql, $_jsdata, $iTitleID);
		if (false == $_ret) {
		
			$_msg = "write fail! :".$this->m_Writer->errorCode()."<br>\n";
			TLogs::log("TPrs-ToSave", $_msg);
			$this->m_Writer->rollBack();
			return false;
		}
		//
		return true;
	}
	
	/**
	 * 預建資料
	 * @param $iTitleID
	 */
	public function CreateTable($iTitleID)
	{
		$_sql = 'insert into bet_prs (title_id) values (?)';
		$this->m_Writer->exec($_sql, $iTitleID);
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
		$_sql = 'delete from bet_prs where title_id=?';
		$_ret = $this->m_Writer->exec($_sql, $iTitleID);
		if (!$_ret)
		{
			return false;
		}
		//
		return true;
	}
}