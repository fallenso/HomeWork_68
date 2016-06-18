<?php
/**
 * 獎項相關處理 / 運算
 */
class TAwardControl extends TIProtocol {
	
	function __construct()
	{
		parent::__construct();
	}
	
	// ==================================================
	/**
	 * 取得 未開獎的 期數資料
	 *
	 */
	public function GetNoOutAward()
	{
		$_sql = "select id from award where checkout is null";
		$this->m_Read->exec($_sql);
		$_ret = $this->m_Read->fetchAll();
	
		$_output = [];
		foreach ($_ret as $val)
		{
			array_push($_output, $val['id']);
		}
		//
		return $_output;
	}
	
	/**
	 * 取得已開獎的期數資料
	 */
	public function GetAwardedList()
	{
		$_sql = "select * from award where checkout=1 order by time DESC";
		$this->m_Read->exec($_sql);
		$_ret = $this->m_Read->fetchAll();
		return $_ret;
	}
		
	/**
	 * 取得指定開獎表資料
	 * @param $iId : 指定的id
	 */
	public function FindIndex($iId)
	{
		$_sql = "select * from award where id=?";
		$this->m_Read->exec($_sql, $iId);
		$_ret = $this->m_Read->fetch();
		return $_ret;
	}
	
	/**
	 * 取得全部開獎表資料
	 * @param $iType : 取得哪個遊戲的列表; 
	 */
	public function GetALL($iType)
	{
		$_sql = 'select * from award where type=? order by time DESC';
		$this->m_Read->exec($_sql, $iType);
		$_ret = $this->m_Read->fetchAll();
		return $_ret;
	}
	
	/**
	 * 更新 開獎表
	 * @param $iId : 期號
	 * @param $iParam : array
	 * [
	 * 	'num_1':1號球,
	 * 	'num_2':2號球,
	 * 	'num_3':3號球,
	 * 	'num_4':4號球,
	 * 	'num_5':5號球,
	 * 	'num_6':6號球,
	 *  'num_sp':特瑪,
	 * 	'infor':要記錄的資訊,
	 * ]
	 * 
	 * @return bool
	 */
	public function UPdateData($iId, $iParam)
	{
		
		if ($iParam['num_sp'] != '')
		{
			$_sql = "update award 
					 set 
						num_1=?,
						num_2=?,
						num_3=?,
						num_4=?,
						num_5=?,
						num_6=?,
						num_sp=?,
						infor=?,
						checked=?
					 where id=?";
			$_ret = $this->m_Writer->exec($_sql, $iParam['num_1'], $iParam['num_2'],
					 $iParam['num_3'], $iParam['num_4'], $iParam['num_5'],
					 $iParam['num_6'], $iParam['num_sp'], $iParam['infor'], "setting", $iId);
		}
		else 
		{
			
			$_sql = "update award
					 set
						num_1=?,
						num_2=?,
						num_3=?,
						num_4=?,
						num_5=?,
						infor=?,
						checked=?
					 where id=?";
			$_ret = $this->m_Writer->exec($_sql, $iParam['num_1'], $iParam['num_2'],
					$iParam['num_3'], $iParam['num_4'], $iParam['num_5'],
					$iParam['infor'], "setting", $iId);
		}
		
		if (!$_ret)
		{
			TLogs::log("TAward-UPdateData", "update into award is fail.".$this->m_Writer->errorCode());
			return false;
		}
		return true;
	}
	
	/**
	 * 建立 開獎表
	 * @param $iId : 期號
	 * @param $iType : 分類
	 * @param $iTime : 開獎時間
	 * 
	 * @return bool
	 */
	public function CreateNew($iId, $iType, $iTime)
	{
				
		$_sql = 'insert into award (id, type, time) values (?, ?, ?)';
		$_ret = $this->m_Writer->exec($_sql, $iId, $iType, $iTime);
		if (!$_ret)
		{
			TLogs::log("TAward-CreateNew", "insert into award is fail.");
			return false;
		}
		return true;
	}
	
	/**
	 * 刪除
	 * @param $iId
	 * 
	 * @return bool
	 */
	public function Del($iId)
	{
		$_sql = 'delete from award where id=?';
		$_ret = $this->m_Writer->exec($_sql, $iId);
		if (!$_ret)
		{
			return false;
		}
		//
		return true;
	}
	
	
	/**
	 * 更新 遊戲類別
	 * @param $iId : 期號
	 * @param $iType : 分類
	 * 
	 * @return bool
	 */
	public function UPdateGame($iId, $iType)
	{
		$_sql = "update award set type=? where id=?";
		$_ret = $this->m_Writer->exec($_sql, $iType, $iId);
		if (!$_ret)
		{
			TLogs::log("TAward-UPdateGame", "update award is fail.");
			return false;
		}
		return true;
	}
	
	/**
	 * 更新 開獎時間
	 */
	public function UPDateTime($iId, $iTime)
	{
		$_sql = "update award set time=? where id=?";
		$_ret = $this->m_Writer->exec($_sql, $iTime, $iId);
		if (!$_ret)
		{
			TLogs::log("TAward-UPDateTime", "update award is fail.");
			return false;
		}
		return true;
	}
	
	/**
	 * 更新 結帳完成資訊
	 * @param $iId : 指定旗號
	 */
	public function UPDateCheckOut($iId)
	{
		$_sql = "update award set checkout=true where id=?";
		$_ret = $this->m_Writer->exec($_sql, $iId);
		if (!$_ret)
		{
			TLogs::log("TAward-UPDateCheckOut", "update award is fail.");
			return false;
		}
		return true;
	}
	
	
	// ==================================================
	/**
	 * 處理 539 的顯示資料
	 * @param $iRet : award 的 539 資料
	 */ 
	public static function FiveModles($iRet)
	{
		//
		$_weeklist = array('日', '一', '二', '三', '四', '五', '六');
	
		//	
		$_ar = array();
		foreach($iRet as $val)
		{
			$_time = date('y-m-d H:i:s', $val['time']);
			$_weekday  = $_weeklist[date('w', $val['time'])];
			
			$_tar = array();
			$_tar['id'] = $val['id'];
			$_tar['time'] = $_time;
			$_tar['week'] = $_weekday;
			$_tar['num_1'] = $val['num_1'];
			$_tar['num_2'] = $val['num_2'];
			$_tar['num_3'] = $val['num_3'];
			$_tar['num_4'] = $val['num_4'];
			$_tar['num_5'] = $val['num_5'];
			$_tar['pos_1'] = TAwardControl::PosTurn(Five, $val['num_1']);
			$_tar['pos_2'] = TAwardControl::PosTurn(Five, $val['num_2']);
			$_tar['pos_3'] = TAwardControl::PosTurn(Five, $val['num_3']);
			$_tar['pos_4'] = TAwardControl::PosTurn(Five, $val['num_4']);
			$_tar['pos_5'] = TAwardControl::PosTurn(Five, $val['num_5']);
			
			$_tar['checkout'] = $val['checkout'];
			
			//
			array_push($_ar, $_tar);
		}
		return $_ar;
	}
	
	/**
	 * 處理 單一筆 539 的顯示資料
	 * @param $iRet : 單一筆 award 的 539 資料
	 */
	public static function FiveSingle($iRet)
	{
		//
		$_weeklist = array('日', '一', '二', '三', '四', '五', '六');
		
		$_time = date('y-m-d H:i:s', $iRet['time']);
		$_weekday  = $_weeklist[date('w', $iRet['time'])];
			
		$_tar = array();
		$_tar['id'] = $iRet['id'];
		$_tar['time'] = $_time;
		$_tar['week'] = $_weekday;
		$_tar['num_1'] = $iRet['num_1'];
		$_tar['num_2'] = $iRet['num_2'];
		$_tar['num_3'] = $iRet['num_3'];
		$_tar['num_4'] = $iRet['num_4'];
		$_tar['num_5'] = $iRet['num_5'];
		$_tar['pos_1'] = TAwardControl::PosTurn(Five, $iRet['num_1']);
		$_tar['pos_2'] = TAwardControl::PosTurn(Five, $iRet['num_2']);
		$_tar['pos_3'] = TAwardControl::PosTurn(Five, $iRet['num_3']);
		$_tar['pos_4'] = TAwardControl::PosTurn(Five, $iRet['num_4']);
		$_tar['pos_5'] = TAwardControl::PosTurn(Five, $iRet['num_5']);
			
		$_tar['checkout'] = $iRet['checkout'];
		
		//
		return $_tar;
	}
	
	/**
	 * 處理 六和彩/大樂透  的顯示資料
	 * @param $iRet : award 的  資料
	 * @param $iType : 哪個遊戲; 
	 */ 
	public static function LotteryModles($iRet, $iType)
	{
		//
		$_weeklist = array('日', '一', '二', '三', '四', '五', '六');
		
		$_ar = array();
		foreach($iRet as $val)
		{
			$_tar = array();
			//
			$_time = date('y-m-d H:i:s', $val['time']);
			$_weekday  = $_weeklist[date('w', $val['time'])];
			
			$_tar['id'] = $val['id'];
			$_tar['time'] = $_time;
			$_tar['week'] = $_weekday;
			$_tar['num_1'] = $val['num_1'];
			$_tar['num_2'] = $val['num_2'];
			$_tar['num_3'] = $val['num_3'];
			$_tar['num_4'] = $val['num_4'];
			$_tar['num_5'] = $val['num_5'];
			$_tar['num_6'] = $val['num_6'];
			$_tar['num_sp'] = $val['num_sp'];
			$_tar['pos_1'] = TAwardControl::PosTurn($iType, $val['num_1']);
			$_tar['pos_2'] = TAwardControl::PosTurn($iType, $val['num_2']);
			$_tar['pos_3'] = TAwardControl::PosTurn($iType, $val['num_3']);
			$_tar['pos_4'] = TAwardControl::PosTurn($iType, $val['num_4']);
			$_tar['pos_5'] = TAwardControl::PosTurn($iType, $val['num_5']);
			$_tar['pos_6'] = TAwardControl::PosTurn($iType, $val['num_6']);
			$_tar['pos_sp'] = TAwardControl::PosTurn($iType, $val['num_sp']);
			
			// 計算台號 / 特尾三
			$_t_ar = TAwardControl::TwTurn($_tar['num_1'], $_tar['num_2'], $_tar['num_3'], $_tar['num_4'], $_tar['num_5'], $_tar['num_6']);
			$_tar['t_1'] = $_t_ar['t1'];
			$_tar['t_2'] = $_t_ar['t2'];
			$_tar['t_3'] = $_t_ar['t3'];
			$_tar['t_4'] = $_t_ar['t4'];
			$_tar['t_5'] = $_t_ar['t5'];
			$_tar['tw3'] = $_t_ar['tw3'];
			
			$_tar['checkout'] = $val['checkout'];
			
			//
			array_push($_ar, $_tar);
		}
		//
		return $_ar;
	}
	
	/**
	 * 處理 單一筆 六和彩/大樂透  的顯示資料
	 * @param $val : 單一筆 award 的 539 資料
	 */
	public static function LotterySingle($val, $iType)
	{
		//
		$_weeklist = array('日', '一', '二', '三', '四', '五', '六');
		
		$_tar = array();
		//
		$_time = date('y-m-d H:i:s', $val['time']);
		$_weekday  = $_weeklist[date('w', $val['time'])];
			
		$_tar['id'] = $val['id'];
		$_tar['time'] = $_time;
		$_tar['week'] = $_weekday;
		$_tar['num_1'] = $val['num_1'];
		$_tar['num_2'] = $val['num_2'];
		$_tar['num_3'] = $val['num_3'];
		$_tar['num_4'] = $val['num_4'];
		$_tar['num_5'] = $val['num_5'];
		$_tar['num_6'] = $val['num_6'];
		$_tar['num_sp'] = $val['num_sp'];
		$_tar['pos_1'] = TAwardControl::PosTurn($iType, $val['num_1']);
		$_tar['pos_2'] = TAwardControl::PosTurn($iType, $val['num_2']);
		$_tar['pos_3'] = TAwardControl::PosTurn($iType, $val['num_3']);
		$_tar['pos_4'] = TAwardControl::PosTurn($iType, $val['num_4']);
		$_tar['pos_5'] = TAwardControl::PosTurn($iType, $val['num_5']);
		$_tar['pos_6'] = TAwardControl::PosTurn($iType, $val['num_6']);
		$_tar['pos_sp'] = TAwardControl::PosTurn($iType, $val['num_sp']);
			
		// 計算台號 / 特尾三
		$_t_ar = TAwardControl::TwTurn($_tar['num_1'], $_tar['num_2'], $_tar['num_3'], $_tar['num_4'], $_tar['num_5'], $_tar['num_6']);
		$_tar['t_1'] = $_t_ar['t1'];
		$_tar['t_2'] = $_t_ar['t2'];
		$_tar['t_3'] = $_t_ar['t3'];
		$_tar['t_4'] = $_t_ar['t4'];
		$_tar['t_5'] = $_t_ar['t5'];
		$_tar['tw3'] = $_t_ar['tw3'];
			
		$_tar['checkout'] = $val['checkout'];
				
		return $_tar;
	}
	
	
	/**
	 * 雙單/大小 轉換
	 * @param $iType : game type
	 * @param $iVal
	 */ 
	private static function PosTurn($iType, $iVal)
	{
		$_watershed = 0;
		if ($iType == Five) $_watershed = 20;
		else if ($iType == Lottery) $_watershed = 24; 
		else if ($iType == Lotto) $_watershed = 25;
		
		// 
		$_str = '';
		if ($iVal%2 == 0)
		{
			$_str = '雙';
		}
		else
		{
			$_str = '單';
		}	
		
		// 
		if ($iVal < $_watershed)
		{
			$_str .= '小';
		}
		else
		{
			$_str .= '大';
		}	
		
		//
		return $_str;
	}
	
	/**
	 * 計算台號 / 特尾三
	 */
	private static function TwTurn($iN1, $iN2, $iN3, $iN4, $iN5, $iN6 )
	{
		// 排列
		$_sort = array($iN1, $iN2, $iN3, $iN4, $iN5, $iN6);
		sort($_sort);
		
		// 計算台號
		$_t1 = ($_sort[0]%10).($_sort[1]%10);
		$_t2 = ($_sort[1]%10).($_sort[2]%10);
		$_t3 = ($_sort[2]%10).($_sort[3]%10);
		$_t4 = ($_sort[3]%10).($_sort[4]%10);
		$_t5 = ($_sort[4]%10).($_sort[5]%10);
		// 計算特尾三
		$_tw3 = ($_t2%10).($_t3%10).($_t4%10);
		
		$_ar = array(
				't1'=>$_t1,
				't2'=>$_t2,
				't3'=>$_t3,
				't4'=>$_t4,
				't5'=>$_t5,
				'tw3'=>$_tw3,
		);
		//
		return $_ar;
	}
	
}