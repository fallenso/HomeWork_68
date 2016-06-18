<?php
/**
 * 遊戲設定
 * 
 * 要做的事
 * 1. ui上  數字 / 排型 轉換成 按鈕
 * 2. 修改 顯示的資料轉換
 * 3. 新建/修改 要存的資料轉換
 */
class TGameSet extends TIProtocol  {
	
	private $m_MenuFunction = 'GamblingSet';	// 子頁面功能 : 預設 期數設定頁面
	private $m_PageParam; 	// 傳給頁面的參數
	private $m_Game;
	
	// ==========================================================================
	// 首頁
	function __construct()
	{
		parent::__construct();
	}
	
	//
	public function Main($iData) 
	{
		$this->m_data = $iData;
			
		//
		$this->m_Game = (isset($_POST['sltshow']))?$_POST['sltshow']:'0';
		$this->m_PageParam['sltshow'] = $this->m_Game;
		
		// 子頁面 項目
		$_fun = $this->m_MenuFunction = (isset($_POST['MenuFunction']))?$_POST['MenuFunction']:'GamblingSet';
		$this->$_fun();
		
		// 顯示頁面
		$this->ShowBasic();
	}
	
	// ==========================================================================
	// 子頁面
		
	// 期數設定
	private function GamblingSet()
	{		
		if (isset($_POST['active']))
		{
			$_fun = $_POST['active'];
			$this->$_fun();
		}
		else $this->ShowList();
	}
	
	// 遊戲基本設定
	private function GameSet()
	{
		$this->m_PageParam['PagePath'] = TViewBase::GetPagePath('TGameSet/'.$this->m_MenuFunction);
		$_TGameBaseSet = new TGameBaseSet();
		//
		if (isset($_POST['update']))
		{
			$_retCar = $_TGameBaseSet->UPDateAboutCar($_POST['car_limit_down'], $_POST['car_limit_top'], 
					$_POST['car_price_down'], $_POST['car_price_top']);
			
			$_retTime = $_TGameBaseSet->UPDateDelTime($_POST['del_time']);
			
			if (!$_retCar)
			{
				echo '<script>alert("全車設定 更新失敗！");</script>';
			}
			else if (!$_retTime)
			{
				echo '<script>alert("可刪除時間 更新失敗！");</script>';
			}
			else 
			{
				echo '<script>alert("更新成功！");</script>';
			}
		}
		
		//
		$_cardata = $_TGameBaseSet->ReadAboutCar();
		$this->m_PageParam['car'] = $_cardata;
		
		// ReadDelTime
		$_timedata = $_TGameBaseSet->ReadDelTime();
		$this->m_PageParam['del_time'] = $_timedata;
		
		//
		$_TGameBaseSet = null;
	}
	
	// 刪單功能
	private function DelBilling()
	{
		$this->m_PageParam['PagePath'] = TViewBase::GetPagePath('TGameSet/'.$this->m_MenuFunction);
		
		$_TBilling = new TBilling();
		$this->m_PageParam['playSelectAll'] = true; // 是否選擇全度玩法
		/*
		 * 
		 */
		$_active = (isset($_POST['active']))?$_POST['active']:'';
		// 動作
		if ($_active == 'delete')	// 直接刪單
		{
			$_billing_id = (isset($_POST['billing_id']))?$_POST['billing_id']:'';
			$_msg = '';
			if ($_billing_id == '')
			{
				$_msg = "未輸入單號,請先輸入單號 或選擇要刪的單";
			}
			else 
			{
				$_ret = $_TBilling->StrongDelBet($_billing_id);
				$_msg = ($_ret == true)?'刪除成功':'刪除失敗';
			}
			//
			echo '<script>alert("'.$_msg.'");</script>';
		}
		else if ($_active == 'find') // 撈取資料
		{
			// 玩法撈取
			$_isType = [];
			for($i = 1; $i<10; ++$i)
			{
				$_key = 'play_'.$i;
				if (isset($_POST[$i]))
				{
					array_push($_isType, $i);
					$this->m_PageParam[$_key] = true;
					$this->m_PageParam['playSelectAll'] = false;
				}else $this->m_PageParam[$_key] = false;
			}
			
		}
		
		/*
		 * 撈取 所有期數 列表
		 */
		$_list = $_TBilling->GetTitleIdList();
		$this->m_PageParam['list'] = $_list;
		
		$titleID = (isset($_POST['select_title_id']))?$_POST['select_title_id']:$_list[0];
		$this->m_PageParam['selectid'] = $titleID;
		
		/*
		 * 撈取 指定期數資料
		 */
		$_data = $_TBilling->GetBetDataByTitleId($titleID);
		$_TBilling = null;
				
		// 如果不是全選
		if ($this->m_PageParam['playSelectAll'] == false)
		{
			$_tmp = [];
			foreach ($_data as $val)
			{
				// 確認是否有該玩法
				if (in_array($val['play'], $_isType) == true)
				{
					array_push($_tmp, $val);
				}
			}
			$this->m_PageParam['data'] = $_tmp;
		}
		else 
		{
			$this->m_PageParam['data'] = $_data;
		}
	}
	
	// 期數預設值設定
	private function AutoCreateSet()
	{
		$this->m_PageParam['PagePath'] = TViewBase::GetPagePath('TGameSet/'.$this->m_MenuFunction);
		
		//
		$_TBetDefault = new TBetDefault();
		if (isset($_POST['update']))
		{
			$_ret = $_TBetDefault->ToUPDate($_POST);
			//
			$_msg = (!$_ret)?'更新失敗!':'更新成功!';
			echo '<script>alert("'.$_msg.'");</script>';
		}
		//
		$_data = $_TBetDefault->ToReadAll();
		
		$this->m_PageParam['data'] = $_data;
	}
	
	// 注金設定 :: 六合彩每注金額設定
	private function LotteryBetSet()
	{
		$this->m_PageParam['PagePath'] = TViewBase::GetPagePath('TGameSet/'.$this->m_MenuFunction);
		$_TBaseStake = new TBaseStake();
		
		if (isset($_POST['update']))
		{
			$_ret = $_TBaseStake->UPDateStake(Lottery, $_POST);
			$_msg = ($_ret)?'更新完成':'更新失敗';
			echo '<script>alert("'.$_msg.'");</script>';
		}
		
		//
		$_data = $_TBaseStake->ReadGameStake(Lottery);
		$_TBaseStake= null;
		
		$this->m_PageParam['data'] = $_data;
	}
	
	// 注金設定 :: 大樂透每注金額設定
	private function LottoBetSet()
	{
		$this->m_PageParam['PagePath'] = TViewBase::GetPagePath('TGameSet/'.$this->m_MenuFunction);
		$_TBaseStake = new TBaseStake();
		
		if (isset($_POST['update']))
		{
			$_ret = $_TBaseStake->UPDateStake(Lotto, $_POST);
			$_msg = ($_ret)?'更新完成':'更新失敗';
			echo '<script>alert("'.$_msg.'");</script>';
		}
		
		//
		$_data = $_TBaseStake->ReadGameStake(Lotto);
		$_TBaseStake= null;
		
		$this->m_PageParam['data'] = $_data;
	}
	
	// 注金設定 :: 539每注金額設定
	private function FiveBetSet()
	{
		$this->m_PageParam['PagePath'] = TViewBase::GetPagePath('TGameSet/'.$this->m_MenuFunction);
		$_TBaseStake = new TBaseStake();
		
		if (isset($_POST['update']))
		{
			$_ret = $_TBaseStake->UPDateStake(Five, $_POST);
			$_msg = ($_ret)?'更新完成':'更新失敗';
			echo '<script>alert("'.$_msg.'");</script>';
		}
		
		//
		$_data = $_TBaseStake->ReadGameStake(Five);
		$_TBaseStake= null;
		
		$this->m_PageParam['data'] = $_data;
	}
	
	/**
	 * 最低本金設定
	 */
	private function CostLimit()
	{
		$this->m_PageParam['PagePath'] = TViewBase::GetPagePath('TGameSet/'.$this->m_MenuFunction);
		
		if (isset($_POST['update']))
		{
			$_ret = TCostlimit::GetInstance()->UPData($_POST);
			$_msg = ($_ret)?'更新完成':'更新失敗';
			echo '<script>alert("'.$_msg.'");</script>';
		}
		
		// 讀取資料
		$_data = TCostlimit::GetInstance()->GetData();
		$this->m_PageParam['data'] = $_data;
	}
	
	// ==========================================================================
	// 基本功能
	/**
	 * 基本頁面
	 */
	private function ShowBasic()
	{
		// 功能顯示(可用) 的權限控制
		$_TGod = TGod::GetInstance()->GetGod();		
		$this->m_PageParam["competence"] = $this->m_data->competence;	// 
		$this->m_PageParam["default"] = $_TGod->default;	// 建期預設設定功能
		$this->m_PageParam["create"] = $_TGod->create;				// 建立
		$this->m_PageParam["del"] = $_TGod->del;					// 刪除
		$this->m_PageParam["SetPage"] = $_TGod->SetPage;			// 設定
		$this->m_PageParam["PricePage"] = $_TGod->PricePage;		// 本金
		$this->m_PageParam["PrsPage"] = $_TGod->PrsPage;			// 調漲
				
		// 設定要帶的參數
		$this->m_PageParam['token'] = TSecurity::encrypt(json_encode($this->m_data));
		$this->m_PageParam['page'] = "TGameSet";
		$this->m_PageParam['menu'] = $this->m_MenuFunction;
		//
		TViewBase::Main('TGameSet/TGameSet', $this->m_PageParam);
	}
	
	// ==========================================================================
	// 期數 設定頁面控制
	/**
	 * 顯示 期數列表
	 */
	private function ShowList()
	{
		
		$_game = (isset($_POST['sltshow']))?$_POST['sltshow']:'0';
		
		// 撈取資料
		$_TBet = new TBet();
		$_ret = $_TBet->GetAll($_game);		
		$_ar = $_TBet->TurnShowTime($_ret);	// 資料轉換
		$_TBet = null;
		
		// 設定要帶的參數
		$this->m_PageParam['PagePath'] = TViewBase::GetPagePath('TGameSet/'.$this->m_MenuFunction);
		$this->m_PageParam['list'] = $_ar;
		$this->m_PageParam['sltshow'] = $_game;
	}
	
	/**
	 * find page
	 */
	private function FindPage()
	{
		$_id = trim($_POST['id']);
		
		if ($_id != null)
		{
			// 撈取資料
			$_TBet = new TBet();
			$_ret = $_TBet->FindIndex($_id);
			$_ar = $_TBet->TurnShowTime($_ret);	// 資料轉換
			$_TBet = null;
			
			// 設定要帶的參數
			$this->m_PageParam['PagePath'] = TViewBase::GetPagePath('TGameSet/'.$this->m_MenuFunction);
			$this->m_PageParam['list'] = $_ar;
		}
		else
		{
			$this->m_PageParam['PagePath'] = TViewBase::GetPagePath('TGameSet/'.$this->m_MenuFunction);
			$this->m_PageParam['list'] = array();
		}
	}
	
	/**
	 * Create Page
	 */
	private function CreatePage()
	{
		
		if (isset($_POST['complete']))
		{
			//
			if (isset($_POST['setrule']) && $_POST['setrule'] == "copy")
			{
				// 複製
				$_copyid = ($_POST['copy_id'] != '')?$_POST['copy_id']:$_POST['select_id'];
				
				// 撈取資料
				$_TBet = new TBet();
				$_ret = $_TBet->FindIndex($_copyid);
				$_ret = $_ret[0];
											
				$_ret['gametype'] = $_POST['gametype'];
				$_ret['start_time'] = strtotime($_POST['start_time']);
				$_ret['end_time'] = strtotime($_POST['end_time']);
				$_ret['award_time'] = strtotime($_POST['award_time']);
								
				
				// 換掉id
				$_ret['id'] = $_POST['id'];
				
				$_ret = $_TBet->CreateNew($_ret);
				$_TBet = null;
			}
			else 
			{
				$_data = $this->TurnData();
				
				$_TBet = new TBet();
				$_ret = $_TBet->CreateNew($_data);
				$_TBet = null;
			}
			
			//
			$_msg = (!$_ret)?'建立失敗!':'建立成功!';
			echo '<script>alert("'.$_msg.'");</script>';
			
			// 顯示頁面
			$this->ShowList();
		}
		else 
		{
			// 撈取 其他相關 期數資料 做完使用者可選複製對象用
			$_TBet = new TBet();
			$_ret = $_TBet->GetAll();
			$_TBet = null;
			
			//
			$_list = array();
			foreach ($_ret as $val)
			{
				array_push($_list, $val['id']);
			}
						
			// 設定要帶的參數
			$this->m_PageParam['token'] = TSecurity::encrypt(json_encode($this->m_data));
			$this->m_PageParam['page'] = "TGameSet";
			$this->m_PageParam['active'] = 'CreatePage';
			$this->m_PageParam['list'] = $_list;
			
			TViewBase::Main('TGameSet/GamblingSet_set', $this->m_PageParam);
		}
	}
	
	/**
	 * Set Page
	 */
	private function SetPage()
	{
		$_id = $_POST['id'];
		//
		if (isset($_POST['complete']))
		{
			$_data = $this->TurnData();
			
			$_TBet = new TBet();
			$_ret = $_TBet->UPdateData($_id, $_data);
			$_TBet = null;
			
			//
			$_msg = (!$_ret)?'更新失敗!':'更新成功!';
			echo '<script>alert("'.$_msg.'");</script>';
			//
			// 撈取資料
			$this->ShowList();
		}
		else
		{			
			// 撈取資料
			$_TBet = new TBet();
			$_ret = $_TBet->FindIndex($_id);
			$_ret = $_ret[0];
			
			//
			$_ret['start_time'] = date('Y/m/d H:i:s', $_ret['start_time']);
			$_ret['end_time'] = date('Y/m/d H:i:s', $_ret['end_time']);
			$_ret['award_time'] = date('Y/m/d H:i:s', $_ret['award_time']);
			
			// 台號 資料拆解
			$_tmp = TBet::PlayBet_DBToData($_ret['bet_tw']);
			foreach($_tmp as $key=>$val)
			{
				$_ret[$key] = $val;
			}
			
			// 特尾三 資料拆解
			$_tmp = TBet::PlayBet_DBToData($_ret['bet_tws']);
			foreach($_tmp as $key=>$val)
			{
				$_ret[$key] = $val;
			}
						
			// 這邊資料要轉換一下 給 封牌數字 / 熱/賠 等顯示用
						
			// 設定要帶的參數
			$this->m_PageParam['token'] = TSecurity::encrypt(json_encode($this->m_data));
			$this->m_PageParam['page'] = "TGameSet";
			$this->m_PageParam['active'] = 'SetPage';
			$this->m_PageParam['data'] = $_ret;
			$this->m_PageParam['id'] = $_id;
			
			TViewBase::Main('TGameSet/GamblingSet_set', $this->m_PageParam);
		}
	}
	
	/**
	 * Price Page 本金價格設定頁面
	 */
	private function PricePage()
	{
		
		$_titid = $_POST['id'];
		
		if (isset($_POST['complete']))
		{
			// 資料來源
			if ($_POST['settype'] == 'new')
			{
				$_TBetPrice = new TBetPrice();
				$_ret = $_TBetPrice->UPDateTable($_titid, $_POST);
				$_TBetPrice = null;
			}
			else 
			{
				// 取得要複製的目標旗號
				$_copyid = ($_POST['copy_id'] != '')?$_POST['copy_id']:$_POST['select_id'];
				
				$_TBetPrice = new TBetPrice();
				$_ret = $_TBetPrice->CopyByOtherID($_titid, $_copyid);
				$_TBetPrice = null;
			}
			
			//
			$_msg = ($_ret)?'更新完成':'更新失敗';
			echo '<script>alert("'.$_msg.'");</script>';
			
			// 回主頁
			$this->ShowList();
		}
		else 
		{
			/*
			 * 
			 */
			// 撈取 其他相關 期數資料 做完使用者可選複製對象用
			$_TBet = new TBet();
			$_ret = $_TBet->GetAll();
			$_TBet = null;
				
			//
			$_list = array();
			foreach ($_ret as $val)
			{
				array_push($_list, $val['id']);
			}
			
			/*
			 * 
			 */
			// 取得遊戲基本資料
			$_bet = new TBet();
			$_basedata = $_bet->FindIndex($_titid);
			$_basedata = $_basedata[0];
			$_bet = null;
			
			$_TBetPrice = new TBetPrice();
			$_data = $_TBetPrice->ReadTableAll($_titid, $_basedata['gametype']);
			
			/*
			 * 
			 */
			// 取得 2/3/4  星 散連柱碰 資料
			$_st2 = $_TBetPrice->ReadTableByPlay($_titid, PLAY_ST2, true);
			$_st3 = $_TBetPrice->ReadTableByPlay($_titid, PLAY_ST3, true);
			$_st4 = $_TBetPrice->ReadTableByPlay($_titid, PLAY_ST4, true);
			$_TBetPrice = null;
						
			// 基本資訊
			$_gamename = '';
			if ($_basedata['gametype'] == 0)
			{
				$_gamename = '六合彩';
			}
			else if ($_basedata['gametype'] == 1)
			{
				$_gamename = '大樂透';
			}
			else if ($_basedata['gametype'] == 2)
			{
				$_gamename = '539';
			}
			
			//
			$this->m_PageParam['token'] = TSecurity::encrypt(json_encode($this->m_data));
			$this->m_PageParam['page'] = "TGameSet";
			$this->m_PageParam['active'] = 'PricePage';
			$this->m_PageParam['game'] = $_basedata['gametype'];
			$this->m_PageParam['titleid'] = $_titid;
			$this->m_PageParam['title'] = $_gamename.' 第'.$_titid;
			$this->m_PageParam['base'] = $_basedata;
			$this->m_PageParam['data'] = $_data;
			$this->m_PageParam['menu'] = $this->m_MenuFunction;
			$this->m_PageParam['st2'] = $_st2;
			$this->m_PageParam['st3'] = $_st3;
			$this->m_PageParam['st4'] = $_st4;
			$this->m_PageParam['list'] = $_list;
						
			TViewBase::Main('TGameSet/PricePage', $this->m_PageParam);
		}
	}
	
	/**
	 * 價格調漲 設定
	 */
	private function PrsPage()
	{
		$_titid = $_POST['id'];
		
		// --------------------
		if (isset($_POST['complete']))
		{			
			$_data = [];
			foreach($_POST as $key=>$val)
			{
				$_ret = strpos($key, '_type');
				if (strlen($_ret) != 0)
				{
					$_ret = explode("_", $key);
					// 
					$_obj = [];
					$_obj['type'] = $_POST[$_ret[0].'_type'];					
					$_obj['num'] = $_POST[$_ret[0].'_num'];
					$_obj['limit'] = $_POST[$_ret[0].'_limit'];
					$_obj['case'] = $_POST[$_ret[0].'_case'];
					
					array_push($_data, $_obj);
				}				
			}
			
			$_TPrs = new TPrs();
			$_ret = $_TPrs->ToSave($_titid, $_data);
			$_TPrs = null;
			//
			//
			$_msg = ($_ret)?'更新完成':'更新失敗';
			echo '<script>alert("'.$_msg.'");</script>';
			
			// 回主頁
			$this->ShowList();
		}
		else 
		{
			$_TPrs = new TPrs();
			$_ret = $_TPrs->ToRead($_titid);
			$_TPrs = null;
			
			//
			$this->m_PageParam['token'] = TSecurity::encrypt(json_encode($this->m_data));
			$this->m_PageParam['page'] = "TGameSet";
			$this->m_PageParam['active'] = 'PrsPage';
			$this->m_PageParam['titleid'] = $_titid;
			$this->m_PageParam['title'] = '第'.$_titid.'期 調漲設定';
			$this->m_PageParam['menu'] = $this->m_MenuFunction;
			$this->m_PageParam['list'] = $_ret;
			$this->m_PageParam['competence'] = $this->m_data->competence;
							
			TViewBase::Main('TGameSet/PrsPage', $this->m_PageParam);
		}
	}
	
	/**
	 * 組合固定獎金 設定
	 */
	private function GroupPage()
	{
	$_titid = $_POST['id'];
		
		// --------------------
		if (isset($_POST['complete']))
		{
			
			$_data = [];
			foreach($_POST as $key=>$val)
			{
				$_ret = strpos($key, '_type');
				if (strlen($_ret) != 0)
				{
					$_ret = explode("_", $key);
					// 
					$_obj = [];
					$_obj['type'] = $_POST[$_ret[0].'_type'];					
					$_obj['num'] = $_POST[$_ret[0].'_num'];
					$_obj['bonbs'] = $_POST[$_ret[0].'_bonbs'];
					$_obj['bonus'] = $_POST[$_ret[0].'_bonus'];
					
					array_push($_data, $_obj);
				}				
			}
			
			$_TBetGP = new TBetGP();
			$_ret = $_TBetGP->ToSave($_titid, $_data);
			$_TBetGP = null;
			//
			//
			$_msg = ($_ret)?'更新完成':'更新失敗';
			echo '<script>alert("'.$_msg.'");</script>';
			
			// 回主頁
			$this->ShowList();
		}
		else
		{
			$_TBetGP = new TBetGP();
			$_ret = $_TBetGP->ToRead($_titid);
			$_TBetGP = null;
			
			//
			$this->m_PageParam['token'] = TSecurity::encrypt(json_encode($this->m_data));
			$this->m_PageParam['page'] = "TGameSet";
			$this->m_PageParam['active'] = 'GroupPage';
			$this->m_PageParam['titleid'] = $_titid;
			$this->m_PageParam['title'] = '第'.$_titid.'期 調漲設定';
			$this->m_PageParam['menu'] = $this->m_MenuFunction;
			$this->m_PageParam['list'] = $_ret;
			$this->m_PageParam['competence'] = $this->m_data->competence;
			
			TViewBase::Main('TGameSet/GroupPage', $this->m_PageParam);
		}
	}
	
	/**
	 * DelPage
	 */
	private function DelPage()
	{
		$_id = $_POST['id'];
		
		$_TBet = new TBet();
		$_ret = $_TBet->DelBet($_id);
		$_TBet = null;
		
		//
		$_msg = ($_ret)?'刪除成功。':'刪除失敗';
		echo '<script>alert("'.$_msg.'");</script>';
		
		// 顯示頁面
		$this->ShowList();
	}
	
	/**
	 * 要建立/更新 的 輸入資料轉換
	 *
	 * @return array : (資料結構同sql資料結構)
	 */
	private function TurnData()
	{
		// 台號賠率 資料重組
		$_tw = TBet::TW_DataToDB($_POST);
		
		// 特尾三賠率 資料重組
		$_tws = TBet::TWS_DataToDB($_POST);
				
		// 編成陣列		
		$_ar = array(
	
				'gametype'=>(isset($_POST['gametype']))?$_POST['gametype']:'',
				'start_time'=>(isset($_POST['start_time']))?$_POST['start_time']:'',
				'end_time'=>(isset($_POST['end_time']))?$_POST['end_time']:'',
				'award_time'=>(isset($_POST['award_time']))?$_POST['award_time']:'',
								
				'bet_sp'=>(isset($_POST['bet_sp']))?$_POST['bet_sp']:'',
				'bet_car'=>(isset($_POST['bet_car']))?$_POST['bet_car']:'',
				'bet_st2'=>(isset($_POST['bet_st2']))?$_POST['bet_st2']:'',
				'bet_st3'=>(isset($_POST['bet_st3']))?$_POST['bet_st3']:'',
				'bet_st4'=>(isset($_POST['bet_st4']))?$_POST['bet_st4']:'',
				'bet_tw'=>$_tw,
				'bet_pn2'=>(isset($_POST['bet_pn2']))?$_POST['bet_pn2']:'',
				'bet_pn3'=>(isset($_POST['bet_pn3']))?$_POST['bet_pn3']:'',
				'bet_tws'=>$_tws,	
		);
		//
		return $_ar;
	}
	
	// ===============================================================
	// 功能
	
	
}