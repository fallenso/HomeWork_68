<?php
/**
 * 報表
 */
class TReport extends TIProtocol  {
	
	private $m_MenuFunction = 'GrandTotal';	// 子頁面功能 : 預設 總累計表頁面
	private $m_param; // 頁面所需的參數資料
	
	private $m_Billing = null;
	private $m_ReportPlay = null;
	
	// ==========================================================================
	// 首頁
	function __construct()
	{
		parent::__construct();
		$this->m_Billing = new TBilling();
		$this->m_ReportPlay = new TReportPlay();
	}
	
	function __destruct()
	{
		$this->m_Billing = null;
		$this->m_ReportPlay = null;
	}
	
	//
	public function Main($iData) 
	{
		$this->m_data = $iData;
		
		//
		$_function = $this->m_MenuFunction = (isset($_POST['MenuFunction']))?$_POST['MenuFunction']:$this->m_MenuFunction;
		$this->$_function();
		//
		$this->ShowBasic();
	}
	
	// ==========================================================================
	// 基本頁面
	/**
	 * 基本頁面
	 */
	private function ShowBasic()
	{
		// 設定要帶的參數
		$this->m_param['competence'] = $this->m_data->competence;
		$this->m_param['token'] = TSecurity::encrypt(json_encode($this->m_data));
		$this->m_param['page'] = "TReport";
		$this->m_param['Menu'] = $this->m_MenuFunction;
		$this->m_param['SubPagePath'] = TViewBase::GetPagePath('TReport/'.$this->m_MenuFunction);
		
		//
		TViewBase::Main('TReport/TReport', $this->m_param);
	}
	
	// ==========================================================================
	// 各子頁面
	
	// 控盤表
	private function TConsole()
	{
		// 遊戲選擇
		$_game = self::SelectGameControl();
	
		// 撈取指定遊戲的 期數列表
		$_TBet = new TBet();
		$_id = $_TBet->GetNowTitleID($_game);
		$_TBet = null;
	
		// 玩法選擇 :: 預設 特瑪 :
		// 1. 特碼 2: 全車 3: 二星 4: 三星 5: 四星 6: 台號 7: 天碰二 8: 天碰三 9: 特尾三
		$_type = (isset($_POST['select_type']))?$_POST['select_type']:1;
		$this->m_param['select_type'] = $_type;
	
		/*
		 * 撈取指定的資料 並 統計計算
		 */
		$_data = $this->m_Billing->GetBetDataByTypeAndTitleId($_type, $_id);
	
		// 統計號碼與金額
		$_outData = array();
		if ($_type == PLAY_ST2 || $_type == PLAY_ST3 || $_type == PLAY_ST4 || $_type == PLAY_PN2 || $_type == PLAY_PN3)
		{
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
		}
		else
		{
			foreach ($_data as $val)
			{
				$_key = intval($val['num']);
				if (isset($_outData[$_key]))
				{
					$_outData[$_key] += $val['bet'];
				}
				else
				{
					$_outData[$_key] = $val['bet'];
				}
			}
		}
	
		arsort($_outData);
		//
		$this->m_param['outData'] = $_outData;
	}
	
	// 總累計表
	private function GrandTotal()
	{
		
		// 接收參數
		$_uid = $this->m_data->uid;
		if (isset($_POST['account']) && $_POST['account'] != '')
		{
			// 找人 並取得 帳號清單
			$_TUser = new TUser();
			$_user = $_TUser->GetUidByAccount($_POST['account']);
			$_uid = $_user['uid'];
			$_TUser = null;
		}
		
		$_time = self::SelectTime();
		$_st = $_time['stime'];
		$_et = $_time['etime'];
		$_ssti = date('Y-m-d', $_st);
		$_seti = date('Y-m-d', $_et);
		$this->m_param['stime'] = $_ssti;
		$this->m_param['etime'] = $_seti;
				
		$_isLottery = (isset($_POST['lottery']))?true:false;
		$_isLotto = (isset($_POST['lotto']))?true:false;
		$_isFive = (isset($_POST['five']))?true:false;
		$this->m_param['isLottery'] = $_isLottery;
		$this->m_param['isLotto'] = $_isLotto;
		$this->m_param['isFive'] = $_isFive;
		
		$_isType = [];
		for($i = 1; $i<10; ++$i)
		{
			$_key = 'play_'.$i;
			if (isset($_POST[$i]))
			{
				array_push($_isType, $i);
				$this->m_param[$_key] = true;
			}else $this->m_param[$_key] = false;
		}
		$_isByplay = (isset($_POST['byplay']))?true:false;
				
		// 		
		$_param = [
		
				'uid'=>$_uid,
				'stime'=>$_st,
				'etime'=>$_et,
				'lottery'=>$_isLottery,
				'lotto'=>$_isLotto,
				'five'=>$_isFive,
				'type'=>$_isType,
				'byplay'=>$_isByplay
		];
				
		// 其他動作
		if (isset($_POST['active']) && $_POST['active'] != '' )
		{
						
			// 權限判斷 :: 如果 為下注者底層 則 進入 明細顯示; 否:顯示進階列表
			$_uid = $_POST['active_id'];
			$_param['uid'] = $_uid;
			
			$_act = $_POST['active'];
			$this->$_act($_param);
		}
		else 
		{
			
			//
			$list = $this->m_ReportPlay->GrandTotal($_param);
			
			$this->m_param['outDate'] = $list;
			$this->m_param['isByplay'] = $_isByplay;
		}		
		
		//
		$this->m_param['Competence'] = $this->m_data->competence;
	}

	// 大盤表 :: 統計各號碼的下注金額
	private function Market()
	{		
		// 遊戲選擇	
		$_game = self::SelectGameControl();
		
		// 撈取指定遊戲的 期數列表
		$_list = $this->m_Billing->GetBetDataByGame($_game);
		$this->m_param['list'] = $_list;
		
		// 旗號選擇
		$_id = self::SelectIDControl($_list[0]['id']);
		
		// 玩法選擇 :: 預設 特瑪 :
		// 1. 特碼 2: 全車 3: 二星 4: 三星 5: 四星 6: 台號 7: 天碰二 8: 天碰三 9: 特尾三
		$_type = (isset($_POST['select_type']))?$_POST['select_type']:1;
		$this->m_param['select_type'] = $_type;
		
		/*
		 * 撈取指定的資料 並 統計計算
		 */
		$_data = $this->m_Billing->GetBetDataByTypeAndTitleId($_type, $_id);
		
		/*
		 * 過濾 登入者可看的 :: 
		 */
		// 撈取 登入者 的 相關帳號
		$_TUser = new TUser();
		$_userlist = $_TUser->FindSubAccAll($this->m_data->uid);
		$_userlist = explode(",", $_userlist);
		$_TUser = null;
		
		// 過濾 登入者可看的
		$_userdata = [];
		foreach ($_data as $val)
		{
			if (in_array($val['uid'], $_userlist))
			{
				array_push($_userdata, $val);
			}
		}
		
		// 統計號碼與金額
		$_outData = array();
		foreach ($_userdata as $val)
		{
			$_key = intval($val['num']);
			if (isset($_outData[$_key]))
			{
				$_outData[$_key] += $val['bet'];
			}
			else 
			{
				$_outData[$_key] = $val['bet'];
			}
		}
		
		//
		$this->m_param['outData'] = $_outData;
	}
		
	// 星碰分析表
	private function StarBonb()
	{
		$_game = self::SelectGameControl();
		$_time = self::SelectTime();
		$_st = $_time['stime'];
		$_et = $_time['etime'];
		$_ssti = date('Y-m-d', $_st);
		$_seti = date('Y-m-d', $_et);
		$this->m_param['stime'] = $_ssti;
		$this->m_param['etime'] = $_seti;
		
		// 撈取 指定時間區段內的 2 /3 /4星
		$_st2_betlist = $this->m_Billing->GetBeyByTime($_game, PLAY_ST2, $_st, $_et);
		$_st3_betlist = $this->m_Billing->GetBeyByTime($_game, PLAY_ST3, $_st, $_et);
		$_st4_betlist = $this->m_Billing->GetBeyByTime($_game, PLAY_ST4, $_st, $_et);
							
		/*
		 * 過濾 登入者可看的 ::
		 */
		// 撈取 登入者 的 相關帳號
		$_TUser = new TUser();
		$_userlist = $_TUser->FindSubAccAll($this->m_data->uid);
		$_userlist = explode(",", $_userlist);
		$_TUser = null;
		
		// 過濾 登入者可看的
		$_userdata_st2 = [];
		foreach ($_st2_betlist as $val)
		{
			if (in_array($val['uid'], $_userlist))
			{
				array_push($_userdata_st2, $val);
			}
		}
		
		$_userdata_st3 = [];   
		foreach ($_st3_betlist as $val)
		{
			if (in_array($val['uid'], $_userlist))
			{
				array_push($_userdata_st3, $val);
			}
		}
		
		$_userdata_st4 = [];
		foreach ($_st4_betlist as $val)
		{
			if (in_array($val['uid'], $_userlist))
			{
				array_push($_userdata_st4, $val);
			}
		}
		
		// 統計數值  分組計算
		$_st2 = $this->m_ReportPlay->StarBonb(PLAY_ST2, $_userdata_st2);
		$_st3 = $this->m_ReportPlay->StarBonb(PLAY_ST3, $_userdata_st3);
		$_st4 = $this->m_ReportPlay->StarBonb(PLAY_ST4, $_userdata_st4);
				
		$this->m_param['st2'] = $_st2;
		$this->m_param['st3'] = $_st3;
		$this->m_param['st4'] = $_st4;
	}
	
	// 下注清單
	private function TBet()
	{
		/*
		 * 撈取 所有期數 列表
		 */
		$_list = $this->m_Billing->GetTitleIdList();
		$this->m_param['list'] = $_list;
		
		$_TUser = new TUser();
		$_userlist = $_TUser->FindSub($this->m_data->uid);
		$_TUser = null;
		$this->m_param['userlist'] = $_userlist;
		
		//
		$id = (isset($_POST['select_title_id']))?$_POST['select_title_id']:$_list[0];	
		
		// 選擇撈取的人
		$userselect = (isset($_POST['select_user']) && $_POST['select_user'] != 0)?$_POST['select_user']:$this->m_data->uid;
		
		
		// 顯示
		self::TBet_GetList($id, $userselect);
	}
	
	// ===========================================
	
	/**
	 * 下注明細用 撈單
	 * @param $iTitleID
	 * @param $iUser
	 */
	private function TBet_GetList($iTitleID, $iUser)
	{
		/*
		 * 設定目前指定的user
		 */
		$this->m_param['nowUser'] = $iUser;
		
		/*
		 * 撈取 指定期數資料
		 */
		$_data = $this->m_Billing->GetBetDataByTitleId($iTitleID);
		$this->m_param['selectid'] = $iTitleID;
		
		/*
		 * 撈取 登入者 的 相關帳號
		 */
		$_TUser = new TUser();
		$_userlist = $_TUser->FindSubAccAll($iUser);
		$_TUser = null;
		
		/*
		 * 過濾 使用該登入者 可看到的單
		 */
		$_TBillingFather = new TBillingFather();
		
		$_userlist = explode(",", $_userlist);
		$_betdata = [];
		$_printdata = [];
		foreach ($_data as $val)
		{
			$_tmp = [];
			if (in_array($val['uid'], $_userlist))
			{
				// 過濾的單
				$_tmp['data'] = $val;
		
				// 撈取該單的層級資料 :: 判斷層級 使用 登入者的層級
				$_competence = $_TBillingFather->GetCompetence($val['id'], $this->m_data->uid);
				$_tmp['competence'] = $_competence;
				
				//
				array_push($_betdata, $_tmp);
				array_push($_printdata, $val);
			}
		}
		$_data = null;
		
		/*
		 * 玩法過濾
		 */
		$this->m_param['playSelectAll'] = true; // 是否選擇全度玩法
		// 玩法撈取
		$_isType = [];
		for($i = 1; $i<10; ++$i)
		{
			$_key = 'play_'.$i;
			if (isset($_POST[$i]))
			{
				array_push($_isType, $i);
				$this->m_param[$_key] = true;
				$this->m_param['playSelectAll'] = false;
			}else $this->m_param[$_key] = false;
		}
		
		// 如果不是全選
		if ($this->m_param['playSelectAll'] == false)
		{
						
			$_tmp = [];
			$_tmpprint = [];
			foreach ($_betdata as $val)
			{
				// 確認是否有該玩法
				if (in_array($val['data']['play'], $_isType) == true)
				{
					array_push($_tmp, $val);
					array_push($_tmpprint, $val['data']);
				}
			}
			$this->m_param['data'] = $_tmp;
			$_printdata = $_tmpprint; // 玩法過濾
		}
		else
		{
			$this->m_param['data'] = $_betdata;
		}
		
		// 總計
		$_bet = 0;
		$_prize_sum = 0;
		$_refunded = 0;
		$_win = 0;
		
		foreach ($_betdata as $val)
		{
			$_bet += $val['data']['bet'];
			$_prize_sum += $val['data']['prize_sum'];
			$_refunded += $val['data']['refunded'];
			$_win += $val['data']['win'];
		}
		$this->m_param['bet'] = $_bet;
		$this->m_param['prize_sum'] = $_prize_sum;
		$this->m_param['refunded'] = $_refunded;
		$this->m_param['win'] = $_win;
		
		// 列印頁資訊
		//
		$_gameName = '';
		if ($_printdata[0]['type'] == 0) $_gameName = '六合彩';
		else if ($_printdata[0]['type'] == 1) $_gameName = '大樂透';
		else if ($_printdata[0]['type'] == 2) $_gameName = '539';
		
		$this->m_param['PrintTime'] = date('Y/m/d H:i:s', time());
		$this->m_param['Printtitle'] = $_printdata[0]['title_id'];
		$this->m_param['PrintGame'] = $_gameName;
		
		$_TUser = new TUser();
		$_userlist = $_TUser->GetUserName($iUser);
		$_TUser = null;
		$this->m_param['PrintUser'] = $_userlist['name'];
		
		//
		$_data = TPrint::PSmall($_printdata);
		$this->m_param['Printlist'] = json_encode($_data);
	}
	
	/**
	 * 查看玩法明細
	 * @param $iTitleID
	 * @param $iUid
	 */
	private function PlayDetails($iTitleID, $iUid)
	{
		$this->m_MenuFunction = 'PlayDetails';
		
		// 基本資料定義
		$_sql = "select gametype, award_time from bet where id=?";
		$this->m_Read->exec($_sql, $iTitleID);
		$_data = $this->m_Read->fetch();
		
		if ($_data['gametype'] == Lottery)
		{
			$_game = '六合彩';
		}
		else if ($_data['gametype'] == Lottery)
		{
			$_game = '大樂透';
		}
		else if ($_data['gametype'] == Lottery)
		{
			$_game = '539';
		}
		else 
		{
			$_game = '遊戲有錯';
		}
		
		// 取得玩家資料
		$_TUser = new TUser();
		$_base = $_TUser->GetUserName($iUid);
		$_TUser = null;
		
		$this->m_param['id'] = $iTitleID;
		$this->m_param['game'] = $_game;
		$this->m_param['time'] = date('Y-m-d H:i:s', $_data['award_time']);
		#$this->m_param['account'] = $_base['account'];
		$this->m_param['name'] = $_base['name'];
		
		// 撈取資料
		$_list = $this->m_ReportPlay->GetDataByPlay($iTitleID, $iUid);
		$this->m_param['outDate'] = $_list;
		
		// 計算總計 :: 統計 下注金額/退水/中獎/小計
		$_sums = ['bet'=>0, 'refunded'=>0, 'prize_sum'=>0, 'win'=>0, 'cost'=>0, 'tax'=>0, 'rall'=>0];
		foreach($_list as $val)
		{
				
			$_sums['bet'] += $val['bet'];
			$_sums['refunded'] += $val['refunded'];
			$_sums['prize_sum'] += $val['prize_sum'];
			$_sums['win'] += $val['win'];
			$_sums['cost'] += $val['cost'];
			$_sums['tax'] += $val['tax'];
			$_sums['rall'] += $val['rall'];
		}
		$this->m_param['sums'] = $_sums;
	}
		
	/**
	 * 總累計表 ::查看 下注明細
	 * @param $iParam : 資料清單
	 */
	private function SingleBetList($iParam)
	{		
		$this->m_MenuFunction = 'SingleBetList';
		
		// 取得所有下注單資料
		$_list = $this->m_ReportPlay->Get_GrandTotal_Tiile($iParam);
		
		$_tmplist = [];
		foreach($_list as $val)
		{
			foreach($val["betlist"] as $val2)
			{
				foreach($val2 as $val3)
				{
					foreach($val3 as $val4)
					{
						array_push($_tmplist, $val4);
					}
				}
			}
		}
		
		//
		$this->m_param['outDate'] = $_tmplist;
	}
		
	/**
	 * 總累計表 ::查看 對帳
	 */
	private function SinglePlayList($iParam)
	{
		$this->m_MenuFunction = 'SinglePlayList';
		
		// 取得對障者名稱
		$_TUser = new TUser();
		$_user = $_TUser->GetUserName($iParam['uid']);
		$_TUser = null;
		$this->m_param['user'] = $_user['account'].'('.$_user['name'].')';
		
		// 取得所有下注單資料
		$_list = $this->m_ReportPlay->Get_GrandTotal_Tiile($iParam);
		
		$_tmplist = [];
		foreach($_list as $val)
		{
			foreach($val["betlist"] as $val2)
			{
				foreach($val2 as $val3)
				{
					foreach($val3 as $val4)
					{
						array_push($_tmplist, $val4);
					}
				}
			}
		}
		
		// 進行玩法分類
		$_data = [];
		for($i=0; $i<9; ++$i)
		{
			$tmp = [
				'fews'=>0,	// 隻數
				'bet'=>0,	// 下注
				'prize_sum'=>0, // 中獎
			];
			
			$_data[$i+1] = $tmp;
		}
		
		foreach ($_tmplist as $val)
		{
			$_data[$val['play']]['fews'] += $val['fews'];
			$_data[$val['play']]['bet'] += $val['bet'];
			$_data[$val['play']]['prize_sum'] += $val['prize_sum'];
		}
		
		//
		$this->m_param['outDate'] = $_data;
	}
	
	// ===========================================
	
	// 遊戲類別 選擇控制
	private function SelectGameControl()
	{
		// 遊戲選擇
		$_select = (isset($_POST['select_game']))?$_POST['select_game']:Lottery;
		$this->m_param['select_game'] = $_select;
		return $_select;
	}
	
	// 期別類別 選擇控制
	private function SelectIDControl($iDefault)
	{
		// 遊戲選擇
		$_select = (isset($_POST['select_id']) && $_POST['select_id'] != '')?$_POST['select_id']:$iDefault;		
		$this->m_param['select_id'] = $_select;
		return $_select;
	}
	
	// 時間 類別 選擇控制 
	private function SelectTime()
	{ 
		$_stt = (isset($_POST['stime']))?$_POST['stime']:time();
		$_ett = (isset($_POST['etime']))?$_POST['etime']:time();
		
		$_s = date('y-m-d 0:0:0', strtotime($_stt));
		$_e = date('y-m-d 23:59:59', strtotime($_ett));
		$_st = (isset($_POST['stime']) && $_POST['stime'] != '')?strtotime($_s):strtotime("-1 day");	
		$_et = (isset($_POST['etime']) && $_POST['etime'] != '')?strtotime($_e):strtotime(date ("Y-m-d 23:59:59"));
		//
		return array('stime'=>$_st, 'etime'=>$_et);
	}
	
	
}