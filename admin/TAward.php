<?php
/**
 * 開獎設定
 */
class TAward extends TIProtocol  {
	
	private $m_MenuFunction = 'LotteryPage';	// 子頁面功能 : 預設 六合彩頁面
	private $m_param; // 頁面所需的參數資料
	
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
	
		$_act = (isset($_POST['active']))?$_POST['active']:'';
		if ($_act != '')
		{
			$this->$_act();
		}
		else 
		{
			//
			$this->SetSubPage();
			$this->ShowBasic();
		}
	}
	
	// ==========================================================================
	// 頁面控制
	
	/**
	 * 取得子頁面 & 設定
	 */
	private function SetSubPage()
	{
		$this->m_MenuFunction = (isset($_POST['MenuFunction']))?$_POST['MenuFunction']:'LotteryPage';

		// 
		switch($this->m_MenuFunction)
		{
			case 'LotteryPage': // 六合彩
				$this->GetList(Lottery);
				break;
			case 'LottoPage': // 大樂透
				$this->GetList(Lotto);
				break;
			case 'FivePage': // 539
				$this->GetList(Five);
				break;
			case 'BallotDate': // 攪珠日期
				$this->BallotDate();
				break;
			default:
				$this->GetList(Lottery);
				break;
		}
	}
	
	/**
	 * 取得獎項列表
	 * @param $iType : 取得哪個遊戲的列表; 
	 */
	private function GetList($iType = Lottery)
	{
		// 撈取資料
		$_TAwardControl = new TAwardControl();
		$_ret = $_TAwardControl->GetALL($iType);
		$_TAwardControl = null;
		
		//
		if ( $iType == Five)
		{
			$_ret = TAwardControl::FiveModles($_ret);
		}
		else if ( $iType == Lottery || $iType == Lotto )
		{
			$_ret = TAwardControl::LotteryModles($_ret, $iType);
		}
		
		//
		$this->m_param['list'] = $_ret;
	}
	
	/**
	 * 基本頁面
	 */
	private function ShowBasic()
	{		
		// 權限設定
		$_competence = ($this->m_data->competence >= 6)?true:false;
	
		// 設定要帶的參數
		$this->m_param['token'] = TSecurity::encrypt(json_encode($this->m_data));
		$this->m_param['page'] = "TAward";
		$this->m_param['competence'] = $_competence;	
		$this->m_param['Menu'] = $this->m_MenuFunction;
		$this->m_param['SubPagePath'] = TViewBase::GetPagePath('TAward/'.$this->m_MenuFunction);
		
		//
		TViewBase::Main('TAward/TAward', $this->m_param);
	}
	
	/**
	 * 攪珠日期 取得
	 */
	private function BallotDate()
	{
		#$_html = file_get_html('http://special.hkjc.com/root2/marksix/info/ch/mark6/fixtures.asp');
		#$_table = $_html->find('form[id=calendar]');
		$this->m_param['data'] = 'http://special.hkjc.com/root2/marksix/info/ch/mark6/fixtures.asp';
	}
	
	
	// ==========================================================================
	//
	/**
	 * 手動開獎
	 */
	private function SetPage()
	{
		$_id = $_POST['id'];
		
		if (isset($_POST['complete']))
		{
			$_ar = array();
			$_ar['num_1'] = (isset($_POST['num_1']))?$_POST['num_1']:'';
			$_ar['num_2'] = (isset($_POST['num_2']))?$_POST['num_2']:'';
			$_ar['num_3'] = (isset($_POST['num_3']))?$_POST['num_3']:'';
			$_ar['num_4'] = (isset($_POST['num_4']))?$_POST['num_4']:'';
			$_ar['num_5'] = (isset($_POST['num_5']))?$_POST['num_5']:'';
			$_ar['num_6'] = (isset($_POST['num_6']))?$_POST['num_6']:'';
			$_ar['num_sp'] = (isset($_POST['num_sp']))?$_POST['num_sp']:'';
			$_ar['infor'] = '手動設定 ['.date('y-m-d H:i:s', time()).'] 設定者 :'.$this->m_data->name;
			
			//
			$_TAwardControl = new TAwardControl();
			$_ret = $_TAwardControl->UPdateData($_id, $_ar);
			$_TAwardControl = null;
						
			//
			switch($_POST['type'])
			{
				case 0:
					$this->m_MenuFunction = 'LotteryPage';
					break;
				case 1:
					$this->m_MenuFunction = 'LottoPage';
					break;
				case 2:
					$this->m_MenuFunction = 'FivePage';
					break;
				default:
					$this->m_MenuFunction = 'LotteryPage';
					break;
			}
			
			$this->GetList($_POST['type']);
			$this->ShowBasic();
		}
		else 
		{
			// 撈取資料
			$_TAwardControl = new TAwardControl();
			$_ret = $_TAwardControl->FindIndex($_id);
			$_TAwardControl = null;
			
			// 顯示頁面
			$this->m_param['token'] = TSecurity::encrypt(json_encode($this->m_data));
			$this->m_param['page'] = "TAward";
			$this->m_param['id'] = $_id;
			$this->m_param['data'] = $_ret;
			
			//
			TViewBase::Main('TAward/TAward_set', $this->m_param);
		}
	}
	
	/**
	 * 開始結帳
	 */
	private function CheckOutPage()
	{
		$_id = $_POST['id'];
		echo "<script>alert('點下確定後，開始進行結帳。 結帳過程，請勿關閉網頁視窗。結帳完成，會進行通知。');</script>";
		
		//
		$_TBilling = new TBilling();
		$_ret = $_TBilling->BetCheckOut($_id);
		$_TBilling = null;
		
		//
		$_msg = ($_ret)?$_id.'期  結帳完成':'結帳發生錯誤!';
		echo "<script>alert('".$_msg."');</script>";
		
		//
		$this->SetSubPage();
		$this->ShowBasic();
	}
	
	// ==========================================================================
	// 資料處理
	
	
	
}