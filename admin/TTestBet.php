<?php
/**
 * 下注測試用
 */
class TTestBet extends TIProtocol {
	
	private $m_param; // 頁面所需的參數資料
	
	function __construct()
	{
		parent::__construct();
	}
	
	// =========================================
	//
	public function Main($iData)
	{
		$this->m_data = $iData;
		
		//
		if (isset($_POST['active']))
		{
			$_fun = $_POST['active'];
			self::$_fun();
		}
		
		// 撈取指定遊戲的 期數列表
		$_TBilling = new TBilling();
		$_list = $_TBilling->GetTitleIdList();
		$_TBilling = null;
		array_unshift($_list, '選一個');
		
		$this->m_param['list'] = $_list;
		
		//
		self::ShowBasic();
	}
	
	// =========================================
	/**
	 * 基本頁面
	 */
	private function ShowBasic()
	{
		// 設定要帶的參數		
		$this->m_param['token'] = TSecurity::encrypt(json_encode($this->m_data));
		$this->m_param['page'] = "TTestBet";
		TViewBase::Main('TTestBet', $this->m_param);
	}
	
	//
	private function ToBet()
	{
		/*
		 * uid : 流水號 / 玩家ID
			name: 玩家暱稱
			title_id: 下注期號  - 該期期號
			time:下注時間 - 時間戳
			game:分類 - 0: 六合彩 (lottery); 1: 大樂透 (lotto); 2: 539 (five)
			type:玩法 - 1. 特碼 (單號) 2: 全車 (單號碰全) 3: 二星 4: 三星 5: 四星 6: 台號 (單號) 7: 天碰二  8: 天碰三 9: 特尾三 (單號)
			num:號碼 - num_1,num_2 x num_1,num_2,…
			bet:下注金額
		 */
		
		$_uid = $this->m_data->uid;
		$_name = $this->m_data->name;
		$_title_id = $_POST['select_id'];
		$_time = time();
		
		// 遊戲類別 從旗號做判斷
		$_game = 0;
		$_code = substr($_title_id, 0, 1);		
		if ($_code == 's') $_game = 0;
		else if ($_code == 'b') $_game = 1;
		else $_game = 2;
			
		$_type = $_POST['type'];
		$_bet = $_POST['bet'];
		$_money = $_POST['money'];
		
		$_ar = 
		[
			'uid'=>$_uid,
			'name'=>$_name,
			'title_id'=>$_title_id,
			'time'=>$_time,
			'game'=>$_game,
			'type'=>$_type,
			'num'=>$_bet,
			'bet'=>$_money,
		];
				
		// 開始下注
		$_TBilling = new TBilling();
		$_ret = $_TBilling->ToBet($_ar);
		$_TBilling= null;
		echo '<script>alert("'.$_ret.'");</script>';
	}
	
}