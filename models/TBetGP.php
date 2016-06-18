<?php
/**
 * 設定固定組合 只賠固定金額
 */
class TBetGP  extends TIProtocol {
	
	// ==============================================
	// 首頁
	function __construct()
	{
		parent::__construct();
	}
	
	// ==============================================
	
	/**
	 * 計算\賭金
	 * @param $iParam [
	 * 		'title_id'=>期號,
			'play'=>玩法,
			'nums_str'=>中獎號碼,
			'sbet'=>單碰(隻)價格,
			'multiple'=>下注時的賠率,
			'fews'=>隻數,
			'win_fews'-> 中獎隻數,
		]
	 */
	public function GetBonus($iParam)
	{
		$_return_money = 0;
		$_title_id = $iParam['title_id'];
		$_play = $iParam['play'];
		$_nums = $iParam['nums_str'];
		$_sbet = $iParam['sbet'];
		$_multiple = $iParam['multiple'];
		$_fews = $iParam['fews'];
		$_win_fews = $iParam['win_fews'];
		
		// -----------------------------------------------------------
		// 如果沒有中獎隻數
		if ($_win_fews == 0)
		{
			return 0;
		}
		
		// -----------------------------------------------------------
		// 撈取資料
		$_sql = 'select context from bet_groups where title_id=?';
		$this->m_Read->exec($_sql, $_title_id);
		$_ret = $this->m_Read->fetch();
		$_data = json_decode($_ret['context'], true);
		
		// 過濾玩法
		$_getdata = [];
		foreach ($_data as $val)
		{
			if ($val['type'] == $_play)
			{
				array_push($_getdata, $val);
			}
		}
		
		// -----------------------------------------------------------
		// 如果沒有設定固定賠
		if (count($_getdata) == 0)
		{
			// 是否為全車特製 : 全車要轉換
			$_return_money = $_sbet * $_multiple * $_win_fews;
			return $_return_money;
		}
		
		// -----------------------------------------------------------
		// 比對 / 檢查限制(碰數) / 檢查號碼
		$_oldnums = explode(",", $_nums);
		foreach ($_getdata as $val)
		{
			// 檢查碰數
			if ($val['bonbs'] <= $_fews)
			{
				// 檢查號碼
				$_hasNum = true;
				$_tmp_nums = explode(",", $val['num']);
				foreach($_tmp_nums as $tval)
				{
					if(in_array($tval, $_oldnums) == false)
					{
						$_hasNum = false;
					}
				}
				
				// 如果號碼都存在
				if ($_hasNum == true)
				{
					$_return_money += $val['bonus'];
					--$_win_fews;
				}
			}
		}
		
		//		
		$_return_money = $_sbet * $_multiple * $_win_fews;
		//
		return $_return_money;
	}
	
	
	// ==============================================
	/**
	 * 取得玩法限制的條盤
	 * @param $iTitleID
	 * @param $iPlay
	 * 
	 */
	public function GetQuota($iTitleID, $iPlay)
	{
		// 撈取資料
		$_sql = 'select context from bet_groups where title_id=?';
		$this->m_Read->exec($_sql, $iTitleID);
		$_ret = $this->m_Read->fetch();
		$_data = json_decode($_ret['context'], true);
		
		// 過濾玩法
		$_getdata = [];
		foreach ($_data as $val)
		{
			if ($val['type'] == $iPlay)
			{
				array_push($_getdata, $val);
			}
		}
		
		//
		return $_getdata;
	}
	
	// ==============================================
	
	/**
	 * 讀取資料
	 * @param $iTitleID
	 */
	public function ToRead($iTitleID)
	{
		$_sql = 'select context from bet_groups where title_id=?';
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
		//
		$_jsdata = json_encode($data);
		
		//
		$_sql = 'update bet_groups set context=? where title_id=?';
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
		$_sql = 'insert into bet_groups (title_id) values (?)';
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
		$_sql = 'delete from bet_groups where title_id=?';
		$_ret = $this->m_Writer->exec($_sql, $iTitleID);
		if (!$_ret)
		{
			return false;
		}
		//
		return true;
	}
	
}