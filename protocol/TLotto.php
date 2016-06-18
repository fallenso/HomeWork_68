<?php
/**
 * 大樂透 頁面
 */
class TLotto  extends TIProtocol {
	
	private $m_MenuFunction = 'CarPage';	// 子頁面功能 : 預設 全車頁面
	private $m_param; // 頁面所需的參數資料
	private $m_Prices; // 漲價資訊
	
	private $m_BetPrice = null;	// 每注本金相關處理
	private $m_Bet = null;	
	private $m_Billing = null;
	
	private $m_titleID = '';
	private $m_Game = Lotto;
	
	function __construct()
	{
		parent::__construct();
		$this->m_BetPrice = new TBetPrice();
		$this->m_Bet = new TBet();
		$this->m_Billing = new TBilling();
	}
	
	function __destruct()
	{
		$this->m_BetPrice = null;
		$this->m_Bet = null;
		$this->m_Billing = null;
		$this->m_titleID = null;
		$this->m_Game = null;
		$this->m_Prices = null;
	}
	
	// =========================================
	//
	public function Main($iData)
	{
		$this->m_data = $iData;
		
		// 判斷是否已到可下注時間
		if (!self::IsOpen())
		{
			$this->m_MenuFunction = 'LivePage';
		}
				
		// 取得 目前可下注的期數
		$this->m_titleID = $this->m_Bet->GetNowTitleID($this->m_Game);
		$this->m_param['titleID'] = ($this->m_titleID == '')?'目前無可下注期':$this->m_titleID;
				
		// 玩法執行
		$_fun = $this->m_MenuFunction = (isset($_POST['MenuPage']))?$_POST['MenuPage']:$this->m_MenuFunction;
		self::$_fun();
		
		//
		self::ShowBasic();
	}
		
	private function IsOpen()
	{
		$_st = strtotime(date ("Y-m-d 0:0:0", time()));
		$_et = strtotime(date ("Y-m-d 23:59:59", time()));
	
		$_setting = $this->m_Bet->GetBetListByTimeInterval($this->m_Game, $_st, $_et);
		if (count($_setting) == 0)	return false;
	
		$_now = time();
		if ($_setting[0]['start_time'] <= $_now && $_setting[0]['end_time'] > $_now)	return true;
		return false;
	}
	
	// ==========================================================================
	// 子頁
	
	// 特瑪
	private function SpPage()
	{
		/*
		 * 玩家相關
		 */
		// 取得 當前玩家資料
		self::ShowPlayInfor($this->m_Game, PLAY_SP);
		
		/*
		 * 取得設定資料
		 */
		$_data = $this->m_Bet->FindIndex($this->m_titleID);
		$_data = $_data[0];
				
		/*
		 * 讀取倍率資料
		 */
		$_bet = $this->m_Bet->PlayBet_DBToData($_data['bet_sp']);
		$this->m_param['bet'] = $_bet;
		
		/*
		 * 頁面相關
		 */
		// 取得 本金金額設定
		$_cost = $this->m_BetPrice->GetUserPrice($this->m_Game, $this->m_data->uid, $this->m_titleID, PLAY_SP);		
		$this->m_param['cost'] = $_cost;
				
		/*
		 * 送出判斷
		 */
		if(isset($_POST['SendBet']))
		{
			// 取出對那些號碼 下注 下多少
			$_betlist = array();
			for($i=0; $i<49; ++$i)
			{
				if (!empty($_POST[$i]) )
				{
					$_betlist[$i] = $_POST[$i];
				}
			}
			
			// 檢查有沒有封牌的數值
			$_ret = self::CheckClose($_betlist, PLAY_SP);
			if (!$_ret)
			{
				echo '<script>alert("有已被封牌的號碼，請重新更新頁面再下注。");</script>';
				return;
			}
			
			//
			$_isf = false;	// 是否有下注失敗
			foreach ($_betlist as $key=>$val)
			{
				$_ret = self::ToBet(PLAY_SP, $key, $val);
				if (!$_ret)
				{
					$_isfalse = true;
					echo '<script>alert("特碼 '.$key.' 下注失敗");</script>';
				}
			}
			
			if ($_isf == false)
			{
				echo '<script>alert("特碼  下注成功");</script>';
			}
			else 
			{
				echo '<script>alert("特碼  下注結束");</script>';
			}
		}
		
		// 取得漲價資訊
		self::GetPrices(PLAY_SP);
	}
	
	// 全車
	private function CarPage()
	{
		/*
		 * 玩家相關
		 */
		// 取得 當前玩家資料
		self::ShowPlayInfor($this->m_Game, PLAY_CAR);
		
		/*
		 * 取得設定資料
		 */
		$_data = $this->m_Bet->FindIndex($this->m_titleID);
		$_data = $_data[0];
				
		/*
		 * 讀取倍率資料
		 */
		$_bet = $this->m_Bet->PlayBet_DBToData($_data['bet_car']);
		$this->m_param['bet'] = $_bet;
		
		/*
		 * 頁面相關
		 */
		// 取得 本金金額設定
		$_cost = $this->m_BetPrice->GetUserPrice($this->m_Game, $this->m_data->uid, $this->m_titleID, PLAY_CAR);
		$this->m_param['cost'] = $_cost;
				
		/*
		 * 送出判斷
		 */
		if(isset($_POST['SendBet']))
		{
			// 取出對那些號碼 下注 下多少
			$_betlist = array();
			for($i=0; $i<49; ++$i)
			{
				if (!empty($_POST[$i]) )
				{
					$_betlist[$i] = $_POST[$i];
				}
			}
			
			// 檢查有沒有封牌的數值
			$_ret = self::CheckClose($_betlist, PLAY_CAR);
			if (!$_ret)
			{
				echo '<script>alert("有已被封牌的號碼，請重新更新頁面再下注。");</script>';
				return;
			}
			
			//
			$_isf = false;	// 是否有下注失敗
			foreach ($_betlist as $key=>$val)
			{
				$_ret = self::ToBet(PLAY_CAR, $key, $val);
				if (!$_ret)
				{
					$_isfalse = true;
					echo '<script>alert("全車 '.$key.' 下注失敗");</script>';
				}
			}
				
			//
			if ($_isf == false)
			{
				echo '<script>alert("全車  下注成功");</script>';
			}
			else
			{
				echo '<script>alert("全車  下注結束");</script>';
			}
		}
		
		// 取得漲價資訊
		self::GetPrices(PLAY_CAR);
	}
	
	// 二三四星
	private function StarPage()
	{
		/*
		 * 玩家相關
		 * :: 因為2/3/4 星 單碰/連碰/柱碰 都放在一起
		 */
		// 取得 當前玩家資料
		self::ShowPlayInfor($this->m_Game, PLAY_ST2);
		
		/*
		 * 取得 單碰(單筆)上限  / 單組上限
		 */
		// 取得 玩家單筆上限
		$_TUser = new TUser();
		$_limit = $_TUser->GetAcctDesignation($this->m_Game, $this->m_data->uid, 2, PLAY_ST2);
		$this->m_param['limit_st2'] = $_limit;
		
		$_limit = $_TUser->GetAcctDesignation($this->m_Game, $this->m_data->uid, 2, PLAY_ST3);
		$this->m_param['limit_st3'] = $_limit;
		
		$_limit = $_TUser->GetAcctDesignation($this->m_Game, $this->m_data->uid, 2, PLAY_ST4);
		$this->m_param['limit_st4'] = $_limit;
		
		$_limit = $_TUser->GetAcctDesignation($this->m_Game, $this->m_data->uid, 3, PLAY_ST2);
		$this->m_param['group_limit_st2'] = $_limit;
		
		$_limit = $_TUser->GetAcctDesignation($this->m_Game, $this->m_data->uid, 3, PLAY_ST3);
		$this->m_param['group_limit_st3'] = $_limit;
		
		$_limit = $_TUser->GetAcctDesignation($this->m_Game, $this->m_data->uid, 3, PLAY_ST4);
		$this->m_param['group_limit_st4'] = $_limit;
		
		$_TUser = null;
		
		/*
		 * 取得設定資料
		 */
		$_data = $this->m_Bet->FindIndex($this->m_titleID);
		$_data = $_data[0];
				
		/*
		 * 讀取倍率資料
		 */
		$_bet = $this->m_Bet->PlayBet_DBToData($_data['bet_st2']);
		$this->m_param['bet_st2'] = $_bet;
		
		$_bet = $this->m_Bet->PlayBet_DBToData($_data['bet_st3']);
		$this->m_param['bet_st3'] = $_bet;
		
		$_bet = $this->m_Bet->PlayBet_DBToData($_data['bet_st4']);
		$this->m_param['bet_st4'] = $_bet;
		
		/*
		 * 取得 本金金額設定
		 */
		$_cost = $this->m_BetPrice->GetUserPrice($this->m_Game, $this->m_data->uid, $this->m_titleID, PLAY_ST2);
		$this->m_param['cost_st2'] = $_cost;
		
		
		$_cost = $this->m_BetPrice->GetUserPrice($this->m_Game, $this->m_data->uid, $this->m_titleID, PLAY_ST3);
		$this->m_param['cost_st3'] = $_cost;
		
		
		$_cost = $this->m_BetPrice->GetUserPrice($this->m_Game, $this->m_data->uid, $this->m_titleID, PLAY_ST4);
		$this->m_param['cost_st4'] = $_cost;
		
		/*
		 * 送出判斷
		 */
		if(isset($_POST['SendBet']))
		{
			// 下注模式
			// 連碰 & 注碰 & 簡單模式
			
			// 取得下注號碼
			$_num = $_POST['send_nums'];
		
			// 取得各下注金額
			$_st2bet = $_POST['send_money_st2'];
			$_st3bet = $_POST['send_money_st3'];
			$_st4bet = $_POST['send_money_st4'];
			

			// 檢查有沒有封牌的數值
			$_ret = self::CheckClose($_num, PLAY_ST2);
			if (!$_ret)
			{
				echo '<script>alert("有已被封牌的號碼，請重新更新頁面再下注。");</script>';
				return;
			}
		
			//
			$_msg = '';
			if ($_st2bet != '' && $_st2bet != 0)
			{
				$_ret = self::ToBet(PLAY_ST2, $_num, $_st2bet);
				if (!$_ret)
				{
					$_msg .= '二星下注失敗'.'\n';
				}
				else
				{
					$_msg .= '二星下注成功'.'\n';
				}
			}
		
			if ($_st3bet != '' && $_st3bet != 0)
			{
				// 判斷 是否為特殊玩法 :: 連柱碰
				if (isset($_POST['IsStarStraightPong']) && $_POST['IsStarStraightPong'] == true)
				{
					
					$_ret = self::ToBet(PLAY_ST3, $_num, $_st3bet, 'IsStarStraightPong');
					if (!$_ret)
					{
						$_msg .= '三星-連柱碰 下注失敗'.'\n';
					}
					else
					{
						$_msg .= '三星-連柱碰 下注成功'.'\n';
					}
				}
				else 
				{
					$_ret = self::ToBet(PLAY_ST3, $_num, $_st3bet);
					if (!$_ret)
					{
						$_msg .= '三星 下注失敗'.'\n';
					}
					else
					{
						$_msg .= '三星 下注成功'.'\n';
					}
				}
			}
		
			if ($_st4bet != '' && $_st4bet != 0)
			{
				$_ret = self::ToBet(PLAY_ST4, $_num, $_st4bet);
				if (!$_ret)
				{
					$_msg .= '四星 下注失敗'.'\n';
				}
				else
				{
					$_msg .= '四星 下注成功'.'\n';
				}
			}
			
			//
			echo '<script>alert("'.$_msg.'");</script>';
		}
		
		// 取得漲價資訊
		self::GetPrices(PLAY_ST2);
	}
	
	// 台號
	private function TwPage()
	{
		/*
		 * 玩家相關
		 */
		// 取得 當前玩家資料
		self::ShowPlayInfor($this->m_Game, PLAY_TW);
		
		
		$_data = $this->m_Bet->FindIndex($this->m_titleID);
		$_data = $_data[0];
				
		/*
		 * 讀取倍率資料
		 */
		$_bet = $this->m_Bet->PlayBet_DBToData($_data['bet_tw']);
		$this->m_param['bet'] = $_bet;
		
		// 取得 本金金額設定
		$_cost = $this->m_BetPrice->GetUserPrice($this->m_Game, $this->m_data->uid, $this->m_titleID, PLAY_TW);
		$this->m_param['cost'] = $_cost;
		
		/*
		 * 送出判斷
		 */
		if(isset($_POST['SendBet']))
		{
			// 取出對那些號碼 下注 下多少
			$_betlist = array();
			for($i=0; $i<99; ++$i)
			{
				if (!empty($_POST[$i]) )
				{
					$_betlist[$i] = $_POST[$i];
				}
			}
				
			// 檢查有沒有封牌的數值
			$_ret = self::CheckClose($_betlist, PLAY_TW);
			if (!$_ret)
			{
				echo '<script>alert("有已被封牌的號碼，請重新更新頁面再下注。");</script>';
				return;
			}
			
			//
			foreach ($_betlist as $key=>$val)
			{
				self::ToBet(PLAY_TW, $key, $val);
			}
				
			//
			echo '<script>alert("下注結束");</script>';	
		}
		
		// 取得漲價資訊
		self::GetPrices(PLAY_TW);
	}
	
	// 天碰
	private function PongPage()
	{
		/*
		 * 玩家相關
		 */
		// 取得 當前玩家資料
		self::ShowPlayInfor($this->m_Game, PLAY_PN2);
		
		// 取得 玩家單筆上限
		$_TUser = new TUser();
		$_limit = $_TUser->GetAcctDesignation($this->m_Game, $this->m_data->uid, 2, PLAY_PN2);
		$this->m_param['limit_pn2'] = $_limit;
		
		$_limit = $_TUser->GetAcctDesignation($this->m_Game, $this->m_data->uid, 2, PLAY_PN3);
		$this->m_param['limit_pn3'] = $_limit;
		
		$_TUser = null;
		
		/*
		 * 取得設定資料
		 */
		$_data = $this->m_Bet->FindIndex($this->m_titleID);
		$_data = $_data[0];
				
		/*
		 * 讀取倍率資料
		 */
		$_bet = $this->m_Bet->PlayBet_DBToData($_data['bet_sp']);
		$this->m_param['bet'] = $_bet;
		
		/*
		 * 讀取 本金
		 */
		$_cost = $this->m_BetPrice->GetUserPrice($this->m_Game, $this->m_data->uid, $this->m_titleID, PLAY_PN2);
		$this->m_param['cost_pn2'] = $_cost;
		
		$_cost = $this->m_BetPrice->GetUserPrice($this->m_Game, $this->m_data->uid, $this->m_titleID, PLAY_PN3);
		$this->m_param['cost_pn3'] = $_cost;
						
		/*
		 * 送出判斷
		 */
		if(isset($_POST['SendBet']))
		{
			
			$_num = $_POST['send_nums'];
			$_p2 = $_POST['send_money_pn2'];
			$_p3 = $_POST['send_money_pn3'];
								
			// 檢查有沒有封牌的數值
			$_ret = self::CheckClose($_num, PLAY_PN2);
			if (!$_ret)
			{
				echo '<script>alert("有已被封牌的號碼，請重新更新頁面再下注。");</script>';
				return;
			}
								
			// 進下下注判斷
			$_msg = '';
			if ($_p2 != '' && $_p2 != 0)
			{					
				$_ret = self::ToBet(PLAY_PN2, $_num, $_p2);
				if (!$_ret)
				{
					$_msg .= '天碰二 下注失敗'.'\n';
				}
				else
				{
					$_msg .= '天碰二 下注成功'.'\n';
				}
			}
				
			if ($_p3 != '' && $_p3 != 0)
			{
				$_ret = self::ToBet(PLAY_PN3, $_num, $_p3);
				if (!$_ret)
				{
					$_msg .= '天碰三 下注失敗'.'\n';
				}
				else
				{
					$_msg .= '天碰三 下注成功'.'\n';
				}
			}
			
			//
			echo '<script>alert("'.$_msg.'");</script>';
		}
		
		// 取得漲價資訊
		self::GetPrices(PLAY_PN2);
	}
	
	// 特尾三
	private function TWSPage()
	{
		/*
		 * 玩家相關
		 */
		// 取得 當前玩家資料
		self::ShowPlayInfor($this->m_Game, PLAY_TWS);
		
		$_data = $this->m_Bet->FindIndex($this->m_titleID);
		$_data = $_data[0];
				
		/*
		 * 讀取倍率資料
		 */
		$_bet = $this->m_Bet->PlayBet_DBToData($_data['bet_tws']);
		$this->m_param['bet'] = $_bet;
		
		/*
		 * 取得 本金金額設定
		 */
		$_cost = $this->m_BetPrice->GetUserPrice($this->m_Game, $this->m_data->uid, $this->m_titleID, PLAY_TWS);
		$this->m_param['cost'] = $_cost;
				
		/*
		 * 送出判斷
		 */
		if(isset($_POST['SendBet']))
		{
			// 取出對那些號碼 下注 下多少
			$_betlist = array();
			for($i=0; $i<999; ++$i)
			{
				$_key = str_pad($i, 3, '0', STR_PAD_LEFT);
				if (!empty($_POST[$_key]) )
				{
					$_betlist[$_key] = $_POST[$_key];
				}
			}
			
			// 檢查有沒有封牌的數值
			$_ret = self::CheckClose($_betlist, PLAY_TWS);
			if (!$_ret)
			{
				echo '<script>alert("有已被封牌的號碼，請重新更新頁面再下注。");</script>';
				return;
			}
			
			//
			foreach ($_betlist as $key=>$val)
			{
				self::ToBet(PLAY_TWS, $key, $val);
			}
			
			//
			echo '<script>alert("下注結束");</script>';
		}
		
		// 取得漲價資訊
		self::GetPrices(PLAY_TWS);
	}
	
	// 現場直播
	private function LivePage()
	{
		$this->m_param['IsClose'] = 'true';
	}
	
	// 未開盤
	private function ClosePage()
	{
		$this->m_param['IsClose'] = 'true';
	}
		
	// ==========================================================================
	// 頁面控制
	
	/**
	 * 漲價
	 * @param $iPlay
	 */
	private function GetPrices($iPlay)
	{
		/*
		 * 調整漲幅 撈
		 */
		$_TPrs = new TPrs();
		$_prs = $_TPrs->GetPrices($this->m_titleID, $iPlay);
		$_TPrs = null;
		$this->m_Prices = $_prs;
		$this->m_param['prs'] = $_prs;
				
		// 取出有那些被封牌 - 為 星/天碰特製
		$_closestr = '';
		$_prsstr = '';
		$_groupClose = '';
		foreach ($_prs as $val)
		{		
			// 封牌的
			if ($val['case'] == 0)
			{
				// 判斷是否為組合封牌
				$_group = explode(",", $val['num']);
				if (count($_group) > 1)
				{
					$_groupClose .= ($_closestr=='')?$val['num']:'x'.$val['num'];
				}
				else 
				{
					$_closestr .= ($_closestr=='')?$val['num']:','.$val['num'];
				}
			}
			else 
			{
				// 漲價的  給js用
				$_prsstr .= ($_prsstr=='')?$val['num'].'-'.$val['case']:','.$val['num'].'-'.$val['case'];
			}
		}
		$this->m_param['close'] = $_closestr;
		$this->m_param['groupClose'] = $_groupClose;
		$this->m_param['prs_js'] = $_prsstr;
	}
	
	/**
	 * 基本頁面
	 */
	private function ShowBasic()
	{					
		// 更新額度
		self::UPDataCredit();
		
		// 設定要帶的參數
		$this->m_param['token'] = TSecurity::encrypt(json_encode($this->m_data));
		$this->m_param['page'] = "TLotto";
		$this->m_param['MenuPage'] = $this->m_MenuFunction;
		$this->m_param['SubPagePath'] = TViewBase::GetPagePath('TLotto/'.$this->m_MenuFunction);
		
		TViewBase::Main('TLotto/TLotto', $this->m_param);
	}
	
	/**
	 * 下注
	 * @param $iType : 玩法
	 * @param $iNum : 下注號碼
	 * @param $iBet : 下注金額
	 * @param $iIsSP : array : 紀錄特殊規格用
	 * 		{
	 * 			IsStarStraightPong : 是否為三星的連柱碰
	 * 		}
	 */
	private function ToBet($iType, $iNum, $iBet, $iSP = NULL)
	{

		// 檢查是否超過單邊上限
		$_TUser = new TUser();
		$_check = $_TUser->CheckUnilateralLimit($this->m_data->uid, $this->m_titleID, $this->m_Game, $iType, $iBet);
		$_TUser = null;
		
		if ($_check == false)
		{
			echo '<script>alert("超過該線的可收上限！");</script>';
			return;
		}
		
		// 只有會員可下注
		if ($this->m_data->competence != 0)
		{
			echo '<script>alert("只有會員可以下注");</script>';
			return;
		}
		
		// 取得當前 本金多少
		$_TPrs = new TPrs();
		$_pris = $_TPrs->GetBetPrices($this->m_titleID, $iType, $this->m_Game, $this->m_data->uid, $iNum, $iSP);
		$_TPrs = null;
				
		// 取得當前 賠率多少
		$_multiple = $this->m_Bet->GetBet($this->m_titleID, $iType, $iNum);
		
		/*
		 * uid : 流水號 / 玩家ID
		 name: 玩家暱稱
		 title_id: 下注期號  - 該期期號
		 time:下注時間 - 時間戳
		 game:分類 - 0: 六合彩 (lottery); 1: 大樂透 (lotto); 2: 539 (five)
		 type:玩法 - 1. 特碼 (單號) 2: 全車 (單號碰全) 3: 二星 4: 三星 5: 四星 6: 台號 (單號) 7: 天碰二  8: 天碰三 9: 特尾三 (單號)
		 num:號碼 - num_1,num_2 x num_1,num_2,…
		 bet:下注金額
		 price: 當前本金
		 multiple: 當前賠率
		 */	
		// 號碼拆解
		$_coblist = explode("x", $iNum);
		$_02dnum = '';
		foreach ($_coblist as $cobval)
		{
			$_02dnum .= ($_02dnum == '')?'':'x';
			$_numlist = explode(",", $cobval);
			
			sort($_numlist);	// 排序
			
			foreach ($_numlist as $val)
			{
				if ($val != '')
				{
					$_02dnum .= ($_02dnum == '')?'':',';
					$_02dnum .= sprintf("%02d", intval($val));
				}
			}
		}
				
		// 
		$_ar =
		[
			'uid'=>$this->m_data->uid,
			'name'=>$this->m_data->name,
			'title_id'=>$this->m_titleID,
			'time'=>time(),
			'game'=>$this->m_Game,
			'type'=>$iType,
			'num'=>$_02dnum,
			'bet'=>$iBet,
			'price'=>$_pris, 
			'multiple'=>$_multiple,
			'spplay'=> $iSP,
		];
			
		// 開始下注
		$_ret = $this->m_Billing->ToBet($_ar);		
		return $_ret;
	}
	
	/**
	 * 顯示下注玩家資料
	 * @param $iGame : 遊戲類別
	 * @param $iType : 玩法
	 */
	private function ShowPlayInfor($iGame, $iType)
	{
		/*
		 * 玩家相關
		 */
		// 取得 當前玩家資料
		$this->m_param['account'] = $this->m_data->account;
		$this->m_param['name'] = $this->m_data->name;
		$this->m_param['pay_type'] = $this->m_data->pay_type;
								
		// 取得 玩家單筆上限
		$_TUser = new TUser();
		$_limit = $_TUser->GetAcctDesignation($iGame, $this->m_data->uid, 2, $iType);
		$_TUser = null;
		$this->m_param['limit'] = $_limit;
	}
	
	/**
	 * 判斷送出的下注內容是否有封牌的內容
	 * @parma $iBet
	 * @param $iPlay : 玩法
	 */
	private function CheckClose($iBet, $iPlay)
	{
		// 撈取 封牌資料
		$_TPrs = new TPrs();
		$_close = $_TPrs->GetCloseCard($this->m_titleID, $iPlay);
		$_TPrs = null;
		
		if(count($_close) == 0)	return true;
		
		// 天碰特殊處理 只封特瑪
		if ($iPlay == PLAY_PN2 || $iPlay == PLAY_PN3 )
		{
			// 檢查 有無封牌的
			$_listNums = explode("x", $iBet);
			$_list = explode(",", $_listNums[0]);
					
			// 判斷 是否進行 封牌判斷
			// 判斷單封牌
			foreach ($_list as $val)
			{
				if (in_array($val, $_close))
				{
					return;
				}
			}
			
			// 判斷組合封牌
			foreach ($_close as $val)
			{
				$_closelist = explode(",", $val);
				$_csums = 0;
				foreach ($_closelist as $clo)
				{
					foreach($_listNums as $li)
					{
						$_lilist = explode(",", $li);
						if (in_array($clo, $_lilist) == true)
						{
							++$_csums;
						}
					}
				}
			
				if ($_csums == count($_closelist))
				{
					return false;
				}
			}
		}
		else if ($iPlay == PLAY_ST2 || $iPlay == PLAY_ST3 || $iPlay == PLAY_ST4 )
		{
			
			// 判斷 是否進行 封牌判斷
			// 判斷單封牌
			$_list = explode(",", $iBet);
			foreach ($_list as $val)
			{
				$_tn = sprintf("%02d", $val);
				if (in_array($_tn, $_close) == true)
				{
					return false;
				}
			}
			
			// 判斷組合封牌
			foreach ($_close as $val)
			{
				$_closelist = explode(",", $val);
				$_csums = 0;
				foreach ($_closelist as $clo)
				{
					if (in_array($clo, $_list) == true)
					{
						++$_csums;
					}
				}
				
				if ($_csums == count($_closelist))
				{
					return false;
				}
			}			
		}
		else 
		{			
			foreach ($iBet as $key=>$val)
			{
				if (in_array($key, $_close) == true)
				{
					return false;
				}
			}
		}
		//
		return true;
	}
	
	/**
	 * 額度更新
	 */
	private function UPDataCredit()
	{
		$_TUser = new TUser();
		$_AllCreditLimit = $_TUser->GetCreditLimit($this->m_data->uid);
		$_TUser = null;
		
		// 取得玩家 目前下注金額
		$_betList = $this->m_Billing->GetBetByUser($this->m_titleID, $this->m_data->uid);
		$_spendBet = 0;
		foreach($_betList as $val)
		{
			$_spendBet += $val['bet'];
		}
		$this->m_param['spendBet'] = $_spendBet;
		
		// 依額度制度 來取得 額度數值
		if ($this->m_data->pay_type == 1)
		{
			// 信用制 :: 取得玩家目前 已下注金額
			$credit_all = $_AllCreditLimit['credit_limit'];
			$credit_limit = $_AllCreditLimit['credit_limit'] - $_spendBet;
		}
		else
		{
			// 付費制
			$credit_all = $_AllCreditLimit['credit_limit'];
			$credit_limit = $_AllCreditLimit['credit_limit'] - $_AllCreditLimit['spend_credit'];
		}
		$this->m_param['credit_all'] = $credit_all;
		$this->m_param['credit_limit'] = $credit_limit;
	}
	
}