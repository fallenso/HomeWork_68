<?php
/*
 * 報表
 */
class TReportPlay extends TIProtocol {
	
	private $m_TBet = null;
	private $m_TUser = null;
	private $m_TBilling = null;
	private $m_TBillingFather = null;
	
	function __construct()
	{
		parent::__construct();
		$this->m_TBet = new TBet();
		$this->m_TUser = new TUser();
		$this->m_TBilling = new TBilling();
		$this->m_TBillingFather = new TBillingFather();
	}
	
	function __destruct()
	{
		$this->m_TBet = null;
		$this->m_TUser = null;
		$this->m_TBilling = null;
		$this->m_TBillingFather = null;
	}
		
	// =========================================
	// 總累計表
	
	/**
	 * 總累計表
	 * 撈取指定的單 (指定帳號底下的單 去拆出 各層級的異動值)
	 * 
	 */
	public function GrandTotal($iParam)
	{
		// 取得所有的下注單資料
		$_list = self::Get_GrandTotal_Tiile($iParam);
		
		// 依玩法統計  或 帳號
		if ($iParam['byplay'] == true)
		{
			// 依玩法統計 :: 1. 依玩法 分類單號
			$_tplay = array();
			for($i = 1; $i<10; ++$i)
			{
				$_tplay[$i] = 0;
				$_tmp = array();
				foreach ($_list as $val)
				{
					// 取出對應玩法 的單
					foreach ($val['betlist'] as $tit)
					{
						foreach($tit as $billing)
						{
							foreach($billing as $bet)
							{
								if ($bet['play'] == $i)	// 玩法確認
								{
									array_push($_tmp, $bet);
								}
							}
						}
					}
				}
				//
				$_tplay[$i] = $_tmp;
				$_tmp = null;
			}
				
			//
			$_list = null; // 分類完 釋放 $_list資源
				
			// 2. 統計資料 :: 總量 中獎 輸贏
			$_betSum = 0; // 後面計算比重用
			foreach ($_tplay as $key=>$val)
			{
				foreach ($val as $billing)
				{
					$_betSum += $billing['bet'];
				}
			}
				
			// 建立資料表
			$_return = array();
			foreach ($_tplay as $key=>$val) // 分玩法
			{
				$_return[$key] = array();
			
				// 統計
				$_bet = 0;	// 下注總量
				$_prize_sum = 0;	// 中獎總金額
				$_refunded = 0;	// 退水總和
				$_win = 0;	// 輸贏總金額
				
				$_level_1 = ['tax'=>0, 'cost'=>0, 'getrefunded'=>0]; // 代理小計
				$_level_2 = ['tax'=>0, 'cost'=>0, 'getrefunded'=>0]; // 總代理小計
				$_level_3 = ['tax'=>0, 'cost'=>0, 'getrefunded'=>0]; // 股東小計
				$_level_4 = ['tax'=>0, 'cost'=>0, 'getrefunded'=>0]; // 大股東小計
				$_level_5 = ['tax'=>0, 'cost'=>0, 'getrefunded'=>0]; // 總監小計
			
				foreach ($val as $tbet)	// 各表單撈出
				{
					
					$_bet += $tbet['bet'];
					$_prize_sum += $tbet['prize_sum'];
					$_refunded += $tbet['refunded'];
					$_win += $tbet['win'];
					
					// 統計每層 金額
					$_le1 = $this->m_TBillingFather->GetGrandTotal($tbet['id'], 1);
					$_level_1['tax'] += $_le1['tax'];
					$_level_1['cost'] += $_le1['cost'];
					$_level_1['getrefunded'] += $_le1['getrefunded'];
						
					$_le2 = $this->m_TBillingFather->GetGrandTotal($tbet['id'], 2);
					$_level_2['tax'] += $_le2['tax'];
					$_level_2['cost'] += $_le2['cost'];
					$_level_2['getrefunded'] += $_le2['getrefunded'];
						
					$_le3 = $this->m_TBillingFather->GetGrandTotal($tbet['id'], 3);
					$_level_3['tax'] += $_le3['tax'];
					$_level_3['cost'] += $_le3['cost'];
					$_level_3['getrefunded'] += $_le3['getrefunded'];
						
					$_le4 = $this->m_TBillingFather->GetGrandTotal($tbet['id'], 4);
					$_level_4['tax'] += $_le4['tax'];
					$_level_4['cost'] += $_le4['cost'];
					$_level_4['getrefunded'] += $_le4['getrefunded'];
						
					$_le5 = $this->m_TBillingFather->GetGrandTotal($tbet['id'], 5);
					$_level_5['tax'] += $_le5['tax'];
					$_level_5['cost'] += $_le5['cost'];
					$_level_5['getrefunded'] += $_le5['getrefunded'];
				}
			
				//
				$tmp = [
						'bet'=>$_bet,
						'prize_sum'=>$_prize_sum,
						'refunded'=>$_refunded,
						'win'=>$_win,
				
						'1'=>$_level_1,
						'2'=>$_level_2,
						'3'=>$_level_3,
						'4'=>$_level_4,
						'5'=>$_level_5,
						'proportion'=>(round($_bet/$_betSum, 4)*100).'%'
				];
				
				//
				array_push($_return[$key], $tmp);
				$tmp = null;
			}
			$_tplay = null;
				
			//
			return $_return;
		}
		else
		{
			// 依帳號統計
			$_betSum = 0; // 後面計算比重用
			foreach ($_list as $val)
			{
				foreach ($val['betlist'] as $tit)	// 取出對應玩法 的單
				{
					foreach($tit as $billing)
					{
						foreach($billing as $bet)
						{
							$_betSum += $bet['bet'];
						}
					}
				}
			}
				
			// 統計資料
			$_return =array();
			foreach ($_list as $val)
			{
				$_tmp = array();
			
				// 統計
				$_bet = 0;	// 下注總量
				$_prize_sum = 0;	// 中獎總金額
				$_refunded = 0;	// 退水總和
				$_win = 0;	// 輸贏總金額
				
				$_level_1 = ['tax'=>0, 'cost'=>0, 'getrefunded'=>0]; // 代理小計
				$_level_2 = ['tax'=>0, 'cost'=>0, 'getrefunded'=>0]; // 總代理小計
				$_level_3 = ['tax'=>0, 'cost'=>0, 'getrefunded'=>0]; // 股東小計
				$_level_4 = ['tax'=>0, 'cost'=>0, 'getrefunded'=>0]; // 大股東小計
				$_level_5 = ['tax'=>0, 'cost'=>0, 'getrefunded'=>0]; // 總監小計
			
				foreach ($val['betlist'] as $tit)	// 取出對應玩法 的單
				{
					foreach($tit as $billing)
					{
						
						//
						foreach($billing as $tbet)
						{
							
							$_bet += $tbet['bet'];
							$_prize_sum += $tbet['prize_sum'];
							$_refunded += $tbet['refunded'];
							$_win += $tbet['win'];

							// 統計每層 金額
							$_le1 = $this->m_TBillingFather->GetGrandTotal($tbet['id'], 1);
							$_level_1['tax'] += $_le1['tax'];
							$_level_1['cost'] += $_le1['cost'];
							$_level_1['getrefunded'] += $_le1['getrefunded'];
							
							$_le2 = $this->m_TBillingFather->GetGrandTotal($tbet['id'], 2);
							$_level_2['tax'] += $_le2['tax'];
							$_level_2['cost'] += $_le2['cost'];
							$_level_2['getrefunded'] += $_le2['getrefunded'];
							
							$_le3 = $this->m_TBillingFather->GetGrandTotal($tbet['id'], 3);
							$_level_3['tax'] += $_le3['tax'];
							$_level_3['cost'] += $_le3['cost'];
							$_level_3['getrefunded'] += $_le3['getrefunded'];
							
							$_le4 = $this->m_TBillingFather->GetGrandTotal($tbet['id'], 4);
							$_level_4['tax'] += $_le4['tax'];
							$_level_4['cost'] += $_le4['cost'];
							$_level_4['getrefunded'] += $_le4['getrefunded'];
							
							$_le5 = $this->m_TBillingFather->GetGrandTotal($tbet['id'], 5);
							$_level_5['tax'] += $_le5['tax'];
							$_level_5['cost'] += $_le5['cost'];
							$_level_5['getrefunded'] += $_le5['getrefunded'];
						}
					}
				}
			
				// 計算比重
				$_proportion = ($_bet == 0 || $_betSum == 0)?'0%':(round($_bet/$_betSum, 4)*100).'%';
			
				// 建立基本資料
				$tmp = [
						'uid'=>$val['uid'],
						'account'=>$val['account'],
						'name'=>$val['name'],
						'competence'=>$val['competence'],
						'bet'=>$_bet,
						'prize_sum'=>$_prize_sum,
						'refunded'=>$_refunded,
						'win'=>$_win,
						
						'1'=>$_level_1,
						'2'=>$_level_2,
						'3'=>$_level_3,
						'4'=>$_level_4,
						'5'=>$_level_5,
						
						'proportion'=>$_proportion
				];
				array_push($_return, $tmp);
				$tmp = null;
			}
			$_list = null;
				
			//
			return $_return;
		}
	}
		
	// 取得所有下注單資料
	public function Get_GrandTotal_Tiile($iParam)
	{
		// 求出要計算那些期數
		$_tidAr = array();
		if ($iParam['lottery'] == true)
		{
			$_lottery = $this->m_TBet->GetBetListByTimeInterval(Lottery, $iParam['stime'], $iParam['etime']);
			foreach ($_lottery as $val)
			{
				array_push($_tidAr, $val['id']);
			}
		}
		else if ($iParam['lotto'] == true)
		{
			$_lotto = $this->m_TBet->GetBetListByTimeInterval(Lotto, $iParam['stime'], $iParam['etime']);
			foreach ($_lotto as $val)
			{
				array_push($_tidAr, $val['id']);
			}
		}
		else if ($iParam['five'] == true)
		{
			$_five = $this->m_TBet->GetBetListByTimeInterval(Five, $iParam['stime'], $iParam['etime']);
			foreach ($_five as $val)
			{
				array_push($_tidAr, $val['id']);
			}
		}
		$_TBet = NULL;
			
		// 取得目標子帳號清單 :: 加入權限判斷
		$_Competence = $this->m_TUser->GetUserCompetence($iParam['uid']);
		$_acclist = array();
		if ($_Competence == 0)
		{
			
			$_user = $this->m_TUser->GetUserName($iParam['uid']);
			$_tmp = [
				'uid'=>$iParam['uid'],
				'account'=>$_user['account'],
				'name'=>$_user['name'],
				'competence'=>$_Competence,
			];
			//
			array_push($_acclist, $_tmp);
		}
		else 
		{
			$_acclist = $this->m_TUser->FindSub($iParam['uid']);
		}
				
		// 撈取指定 期數的 下注單
		$_list = array();
		foreach ($_acclist as $user)
		{
			// 建立基本格式
			$_tmp = array();
	
			// 建立基本資料
			$_tmp['uid'] = $user['uid'];
			$_tmp['account'] = $user['account'];
			$_tmp['name'] = $user['name'];
			$_tmp['competence'] = $user['competence'];
	
			// 取得 相觀的所有單號
			$_betList = array();
			foreach($_tidAr as $val)
			{
				$_billing_list = self::GetBilling($user['uid'], $val, $iParam['type']);
				array_push($_betList, $_billing_list);
			}
			$_tmp['betlist'] = $_betList;
	
			//
			array_push($_list, $_tmp);
			$_tmp = null;
		}
		
		//
		return $_list;
	}
	
	// ===================================================
	// private
	
	/**
	 * 取得子帳號所有相關的下注單
	 * @param $iUid
	 * @param $tid: 期數
	 * @param $iType [array] : 玩法
	 */
	private function GetBilling($iUid, $tid, $iType)
	{
		// 取得直系子帳號
		$_list = $this->m_TUser->FindSubAccAll($iUid);
		$_list = explode(",", $_list);
			
		// 撈出這些帳號 的下注單
		$_betList = array();
		foreach($_list as $val)
		{
			$_bet = $this->m_TBilling->GetBetByComplexPlay($tid, $val, $iType);
			array_push($_betList, $_bet);
		}
				
		//
		return $_betList;
	}
	
	// ===================================================
	/**
	 * StarBonb 星碰分析表
	 * @param $iType
	 * @param $iData
	 * 
	 * @return array
	 */
	public function StarBonb($iType, $iData)
	{
		$_return = array();
		
		/*
		 * 號碼分類
		 */ 
		// 取得所有的組合
		$_tmpgroups = [];
		foreach ($iData as $val)
		{
			if (in_array($val['num'], $_tmpgroups) == false)
			{
				array_push($_tmpgroups, $val['num']);
			}
		}
		
		// 歸納
		$_group = [];
		foreach ($_tmpgroups as $val)
		{
			foreach ($iData as $val2)
			{
				if ($val == $val2['num'])
				{					
					// 存入判斷
					if (isset($_group[$val]) == false)
					{
						$_group[$val] = [];
					}
					array_push($_group[$val], $val2);
				}
			}
		}
		
		// 分組
		$_gtmp = array();
		foreach ($_group as $key=>$val)
		{
			if (count($val) > 0)
			{
				// 統計帳單數值
				$_tmp = ['bet'=>0, 'refunded'=>0, 'prize_sum'=>0, 'win'=>0];
				$_sums = self::SumsBet($val);
				$_tmp['bet'] += $_sums['bet'];	// 下注總量
				$_tmp['refunded'] += $_sums['refunded'];	// 退水
				$_tmp['prize_sum'] += $_sums['prize_sum'];	// 中獎
				$_tmp['win'] += $_sums['win'];	// 輸贏多少
				//
				$_gtmp[$key] = $_tmp;
			}
		}
		//
		array_push($_return, $_gtmp);
		
		/*
		 * 組數分類
		 */
		// 先取得有哪些期數
		$_titles = [];
		foreach ($iData as $val)
		{
			if (in_array($val['title_id'], $_titles) == false)
			{
				array_push($_titles, $val['title_id']);
			}
		}
		
		// 取得所有的組數設定
		$_TBetPrice = new TBetPrice();
		$_tmpgroups = [];
		foreach ($_titles as $val)
		{
			$_setdata = $_TBetPrice->GetStarGroups($val, $iType);
			foreach ($_setdata as $key2=>$val2)
			{
				// 去除散單
				$_comparison = strpos($key2, 'bet');
				if (strlen($_comparison) == 0)
				{
					// 檢查 是否已有
					if (in_array($key2, $_tmpgroups) == false)
					{
						array_push($_tmpgroups, $key2);
					}
				}
			}
		}
		$_TBetPrice = null;
				
		// 統計組數 並給予對應的單
		$_TPlayAward = new TPlayAward();
		$_tmp = array();
		foreach($iData as $val)
		{
			
			$_num = $_TPlayAward->GetCombNums($val['type'], $val['num'], $val['play'], $val['sprule']);
						
			$_key = $this->CatchGroups($_num, $_tmpgroups);
			
			if (isset($_tmp[$_key]) == false)
			{
				$_tmp[$_key] = [];
			}
			
			//
			array_push($_tmp[$_key], $val);
		}
		$_TPlayAward = null;
				
		// 分組
		$_tmp_return = array();
		foreach ($_tmp as $key=>$val)
		{
			// 統計帳單數值
			$_tmp = ['bet'=>0, 'refunded'=>0, 'prize_sum'=>0, 'win'=>0];
			$_sums = self::SumsBet($val);
			$_tmp['bet'] += $_sums['bet'];	// 下注總量
			$_tmp['refunded'] += $_sums['refunded'];	// 退水
			$_tmp['prize_sum'] += $_sums['prize_sum'];	// 中獎
			$_tmp['win'] += $_sums['win'];	// 輸贏多少
			//
			$_tmp_return[$key] = $_tmp;
		}		
		//
		array_push($_return, $_tmp_return);
		
		//
		return $_return;
	}
	
	/**
	 * 星碰 分析表用 :: 看該數值 適用哪個組數  都沒有 回傳散單
	 * @param $iNum
	 * @param $iGroup
	 */
	private function CatchGroups($iNum, $iGroup)
	{
		
		$_grs = '散單';
		foreach ($iGroup as $val)
		{
			$_grs = ($iNum >= $val)?$val:$_grs;
		}
		
		return $_grs;
	}
				
	/**
	 * 追查指定帳號  依玩法 統計資料
	 * @param $iTitleID
	 * @param $iUid
	 */
	public function GetDataByPlay($iTitleID, $iUid)
	{
		
		// 取得目標子帳號清單
		$_list = $this->m_TUser->FindSubAccAll($iUid);
		
		// 帳號轉陣列
		$_list = explode(",", $_list);
		
		// 撈出這些帳號 的下注單 : 統計方式指定
		/*
		 * 遊戲玩法定義
		 * 1.特碼 (SP) 2:全車 (CAR) 3:二星(ST2) 4:三星(ST3) 5:四星(ST4)
		 * 6:台號 (TW) 7:天碰二(PN2) 8:天碰三(PN3) 9:特尾三 (TWS)
		 */
		$_return = array();
		for($i = 1; $i<10; ++$i)
		{
			$_tmp = array();
			$_tmp['bet'] = 0;
			$_tmp['refunded'] = 0;
			$_tmp['prize_sum'] = 0;
			$_tmp['win'] = 0;
			
			foreach ($_list as $val)
			{
				$_data = $this->m_TBilling->GetBetByPlay($iTitleID, $val, $i);
				$_tmp['bet'] += $_data['bets'];
				$_tmp['refunded'] += $_data['refundeds'];
				$_tmp['prize_sum'] += $_data['prize_sums'];
				$_tmp['win'] += $_data['wins'];
				$_tmp['cost'] += $_data['costs'];
				$_tmp['tax'] += $_data['taxs'];
				$_tmp['rall'] += $_data['ralls'];
			}
			//
			array_push($_return, $_tmp);
		}
		
		//
		return $_return;
	}
		
	// =========================================
	//

	/**
	 * 統計撈出的下注單 金額
	 * @param $iBetList : 下注單 群
	 * @param $iCompetence : 最後要看的人的權限
	 */ 
	private function SumsBet($iBetList, $iCompetence = '')
	{
		$_num = ['bet'=>0,'refunded'=>0,'prize_sum'=>0,'win'=>0,'cost'=>0,'tax'=>0,'rall'=>0];
		
		foreach($iBetList as $val)
		{
			$_num['bet'] += $val['bet'];
			$_num['refunded'] += $val['refunded'];
			$_num['prize_sum'] += $val['prize_sum'];
			$_num['win'] += $val['win'];
			
			if ($iCompetence != '')
			{
				$_num['cost'] += $this->m_TBillingFather->GetCost($val['id'], $iCompetence);
				$_num['tax'] += $this->m_TBillingFather->GetTax($val['id'], $iCompetence);
				$_num['rall'] += $this->m_TBillingFather->GetRAll($val['id'], $iCompetence);
			}
		}
		
		//
		return $_num;
	}
		
	/**
	 * 建立資訊清單
	 * @param $iReportType : 報表類型  1.累積表 2.明細表 3.期數表 4.對帳表 5.下注表  6.玩法明細表
	 * 
	 * @return array
	 */ 
	private function CreateTable($iReportType)
	{
		
		$_tmp = array();
		switch ($iReportType)
		{
			case 1: // 累積表
				// 總量 中獎 輸贏 客戶 代理 總代理 股東 大股東 總監 貢獻額 貢獻度 調盤退水 調退實佔
				$_tmp =
				[
					'uid'=>'','account'=>'','name'=>'','bet'=>0, 'prize_sum'=>0,'win'=>0, 'rall'=>0,
					'competence'=>'','0'=>'','1'=>'','2'=>'','3'=>'','4'=>'','5'=>''
				];
				return $_tmp;
				break;
			case 2: // 明細表
				$_tmp =
				[
					'uid'=>'','account'=>'','name'=>'','bet'=>0,'refunded'=>0,
					'prize_sum'=>0,'win'=>0,'cost'=>0,'tax'=>0,'rall'=>0,
				];
				return $_tmp;
			case 3: // 期數表
				$_tmp =
				[
					'titleid'=>'','bet'=>0,'refunded'=>0,
					'prize_sum'=>0,'win'=>0,'cost'=>0,'tax'=>0,'rall'=>0,
				];
				return $_tmp;
			case 4: // 對帳表
				break;
			case 5: // 下注表
				break;
			case 6: // 玩法明細表
				$_tmp =
				[
						'PLAY_SP'=>'','PLAY_CAR'=>0,'PLAY_ST2'=>0,'PLAY_ST3'=>0,
						'PLAY_ST4'=>0,'PLAY_TW'=>0,'PLAY_PN2'=>0,'PLAY_PN3'=>0,'PLAY_TWS'=>0,
				];
				return $_tmp;
		}
	}
	
	
	
}